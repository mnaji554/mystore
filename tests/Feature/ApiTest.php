<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Address;
use App\Models\Coupon;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiTest extends TestCase
{
    public function test_register_login_and_logout_with_tokens(): void
    {
        $register = $this->postJson('/api/v1/auth/register', [
            'name' => 'مستخدم API', 'email' => 'api@example.com', 'password' => 'Secret123', 'password_confirmation' => 'Secret123',
        ])->assertCreated()->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token']);

        $token = $register->json('token');
        $this->getJson('/api/v1/profile', ['Authorization' => "Bearer {$token}"])->assertOk()->assertJsonPath('data.email', 'api@example.com');

        $login = $this->postJson('/api/v1/auth/login', ['email' => 'api@example.com', 'password' => 'Secret123'])->assertOk();
        $this->assertNotEmpty($login->json('token'));

        $this->postJson('/api/v1/auth/login', ['email' => 'api@example.com', 'password' => 'wrong'])->assertStatus(422);

        $this->withToken($login->json('token'))->postJson('/api/v1/auth/logout')->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_disabled_accounts_cannot_get_a_token_or_use_an_existing_one(): void
    {
        $user = $this->customer();
        $token = $user->createToken('t')->plainTextToken;
        $user->forceFill(['is_active' => false])->save();

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'Password123'])->assertStatus(422);
        $this->withToken($token)->getJson('/api/v1/profile')->assertForbidden();
    }

    public function test_protected_endpoints_require_authentication(): void
    {
        foreach (['profile', 'cart', 'wishlist', 'orders'] as $endpoint) {
            $this->getJson("/api/v1/{$endpoint}")->assertUnauthorized();
        }
    }

    public function test_public_catalog_endpoints(): void
    {
        $category = $this->category(['slug' => 'phones']);
        $product = $this->product(['category_id' => $category->id, 'name_ar' => 'هاتف الاختبار', 'price' => 100, 'sale_price' => 80]);
        $this->product(['status' => 'draft', 'name_ar' => 'مسودة']);

        $this->getJson('/api/v1/products')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'هاتف الاختبار')->assertJsonPath('data.0.final_price', 80)->assertJsonPath('data.0.discount_percent', 20)
            ->assertJsonStructure(['data', 'links', 'meta']);

        $this->getJson('/api/v1/products?q='.urlencode('هاتف').'&category=phones&sort=price_asc')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/products?q='.urlencode('غير موجود'))->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/products?per_page=500')->assertStatus(422);
        $this->getJson('/api/v1/products/'.$product->slug)->assertOk()->assertJsonPath('data.sku', $product->sku)->assertJsonStructure(['data' => ['images', 'variants', 'description']]);
        $this->getJson('/api/v1/products/missing')->assertNotFound();

        $this->getJson('/api/v1/categories')->assertOk()->assertJsonPath('data.0.slug', 'phones');
        $this->getJson('/api/v1/categories/phones')->assertOk();
        $this->getJson('/api/v1/shipping-methods')->assertOk();
        $this->getJson('/api/v1/payment-methods')->assertOk()->assertJsonPath('data.0.key', 'cod');
    }

    public function test_cart_endpoints(): void
    {
        $user = $this->customer();
        Sanctum::actingAs($user);
        $product = $this->product(['price' => 100, 'stock' => 5]);
        Coupon::factory()->create(['code' => 'API10', 'value' => 10]);

        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 2])->assertOk()
            ->assertJsonPath('data.items_count', 2)->assertJsonPath('data.subtotal', 200);

        $itemId = $user->cart->items()->first()->id;
        $this->patchJson("/api/v1/cart/items/{$itemId}", ['quantity' => 9])->assertStatus(422); // stock
        $this->patchJson("/api/v1/cart/items/{$itemId}", ['quantity' => 3])->assertOk()->assertJsonPath('data.items_count', 3);

        $this->postJson('/api/v1/cart/coupon', ['code' => 'API10'])->assertOk()->assertJsonPath('data.coupon_discount', 30);
        $this->postJson('/api/v1/cart/coupon', ['code' => 'NOPE'])->assertStatus(422);
        $this->deleteJson('/api/v1/cart/coupon')->assertOk()->assertJsonPath('data.coupon_discount', 0);

        $this->deleteJson("/api/v1/cart/items/{$itemId}")->assertOk()->assertJsonPath('data.items_count', 0);

        // other users' cart items are unreachable
        $other = $this->customer();
        $otherItem = app(CartService::class)->add(app(CartService::class)->resolve(true, $other), $product->id);
        $this->deleteJson("/api/v1/cart/items/{$otherItem->id}");
        $this->assertDatabaseHas('cart_items', ['id' => $otherItem->id]);
        $this->patchJson("/api/v1/cart/items/{$otherItem->id}", ['quantity' => 1])->assertNotFound();
    }

    public function test_wishlist_endpoints(): void
    {
        $user = $this->customer();
        Sanctum::actingAs($user);
        $product = $this->product();

        $this->postJson("/api/v1/wishlist/{$product->id}")->assertCreated();
        $this->postJson("/api/v1/wishlist/{$product->id}")->assertCreated(); // idempotent
        $this->getJson('/api/v1/wishlist')->assertOk()->assertJsonCount(1, 'data');
        $this->deleteJson("/api/v1/wishlist/{$product->id}")->assertOk();
        $this->getJson('/api/v1/wishlist')->assertJsonCount(0, 'data');
    }

    public function test_checkout_and_order_endpoints(): void
    {
        $user = $this->customer();
        Sanctum::actingAs($user);
        $product = $this->product(['price' => 100, 'stock' => 5]);
        $method = $this->shipping(['price' => 20]);
        $address = Address::create(['user_id' => $user->id, 'full_name' => 'أنا', 'phone' => '0501234567', 'city' => 'الرياض', 'street' => 'شارع 1']);

        $this->postJson('/api/v1/orders', ['address_id' => $address->id, 'shipping_method_id' => $method->id, 'payment_method' => 'cod'])->assertStatus(422);

        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 2]);

        $this->postJson('/api/v1/orders', ['shipping_method_id' => $method->id, 'payment_method' => 'cod'])->assertStatus(422); // no address

        $response = $this->postJson('/api/v1/orders', ['address_id' => $address->id, 'shipping_method_id' => $method->id, 'payment_method' => 'cod', 'note' => 'api'])
            ->assertCreated()->assertJsonPath('data.status', 'pending')->assertJsonPath('data.grand_total', 250)->assertJsonCount(1, 'data.items');
        $number = $response->json('data.order_number');

        $this->assertSame(3, $product->fresh()->stock);
        $this->getJson('/api/v1/orders')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/v1/orders/{$number}")->assertOk()->assertJsonPath('data.order_number', $number)->assertJsonStructure(['data' => ['history']]);

        // other customers cannot see or cancel it
        Sanctum::actingAs($this->customer());
        $this->getJson("/api/v1/orders/{$number}")->assertForbidden();
        $this->postJson("/api/v1/orders/{$number}/cancel")->assertForbidden();

        Sanctum::actingAs($user);
        $this->postJson("/api/v1/orders/{$number}/cancel")->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->assertSame(5, $product->fresh()->stock);
        $this->postJson("/api/v1/orders/{$number}/cancel")->assertForbidden();
    }

    public function test_api_checkout_rejects_foreign_address_and_unknown_payment(): void
    {
        $user = $this->customer();
        $foreign = Address::create(['user_id' => $this->customer()->id, 'full_name' => 'غيري', 'phone' => '0501234567', 'city' => 'جدة', 'street' => 'x']);
        Sanctum::actingAs($user);
        $method = $this->shipping();
        $this->postJson('/api/v1/cart/items', ['product_id' => $this->product()->id]);

        $this->postJson('/api/v1/orders', ['address_id' => $foreign->id, 'shipping_method_id' => $method->id, 'payment_method' => 'cod'])->assertStatus(422);

        $inline = ['full_name' => 'أنا', 'phone' => '0501234567', 'city' => 'الرياض', 'street' => 'شارع'];
        $this->postJson('/api/v1/orders', ['address' => $inline, 'shipping_method_id' => $method->id, 'payment_method' => 'bitcoin'])->assertStatus(422);
        $this->postJson('/api/v1/orders', ['address' => $inline, 'shipping_method_id' => $method->id, 'payment_method' => 'cod'])->assertCreated();
    }

    public function test_profile_endpoints(): void
    {
        $user = $this->customer();
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/profile', ['name' => 'جديد', 'email' => 'new@example.com', 'phone' => '0501111111'])->assertOk()->assertJsonPath('data.name', 'جديد');
        $this->putJson('/api/v1/profile', ['name' => '', 'email' => 'bad'])->assertStatus(422);
        $this->putJson('/api/v1/profile/password', ['current_password' => 'Password123', 'password' => 'Brand123', 'password_confirmation' => 'Brand123'])->assertOk();
        $this->putJson('/api/v1/profile/password', ['current_password' => 'nope', 'password' => 'Brand123', 'password_confirmation' => 'Brand123'])->assertStatus(422);
    }

    public function test_review_endpoints_require_a_delivered_purchase(): void
    {
        Storage::fake('public');
        $user = $this->customer();
        $product = $this->product();
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/products/{$product->slug}/reviews", ['rating' => 5])->assertStatus(422);

        $carts = app(CartService::class);
        $cart = $carts->resolve(true, $user);
        $carts->add($cart, $product->id);
        $orders = app(OrderService::class);
        $order = $orders->createFromCart($cart, $this->checkoutData($this->shipping()), $user);
        foreach ([OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered] as $status) {
            $orders->changeStatus($order->fresh(), $status);
        }

        $this->postJson("/api/v1/products/{$product->slug}/reviews", ['rating' => 6])->assertStatus(422);
        $created = $this->postJson("/api/v1/products/{$product->slug}/reviews", ['rating' => 5, 'title' => 'رائع', 'photos' => [UploadedFile::fake()->image('p.jpg')]])
            ->assertCreated()->assertJsonPath('data.status', 'pending');
        $id = $created->json('data.id');

        $this->getJson("/api/v1/products/{$product->slug}/reviews")->assertOk()->assertJsonCount(0, 'data'); // not approved yet
        $this->putJson("/api/v1/reviews/{$id}", ['rating' => 4])->assertOk()->assertJsonPath('data.rating', 4);

        Sanctum::actingAs($this->customer());
        $this->putJson("/api/v1/reviews/{$id}", ['rating' => 1])->assertForbidden();
        $this->deleteJson("/api/v1/reviews/{$id}")->assertForbidden();

        Sanctum::actingAs($user);
        $this->deleteJson("/api/v1/reviews/{$id}")->assertOk();
    }

    public function test_api_rate_limits_authentication(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/auth/login', ['email' => 'x@example.com', 'password' => 'bad']);
        }

        $this->postJson('/api/v1/auth/login', ['email' => 'x@example.com', 'password' => 'bad'])->assertStatus(429);
    }
}
