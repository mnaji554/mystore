<?php

namespace Tests\Feature;

use App\Exceptions\CartException;
use App\Livewire\CartPage;
use App\Livewire\ProductListing;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;
use Tests\TestCase;

class CartTest extends TestCase
{
    private function cartFor($user = null)
    {
        return app(CartService::class)->resolve(true, $user);
    }

    public function test_add_increments_quantity_and_respects_stock(): void
    {
        $product = $this->product(['stock' => 3]);
        $carts = app(CartService::class);
        $cart = $this->cartFor($this->customer());

        $carts->add($cart, $product->id, null, 2);
        $carts->add($cart, $product->id, null, 1);
        $this->assertSame(3, $cart->items()->first()->quantity);

        $this->expectException(CartException::class);
        $carts->add($cart, $product->id, null, 1);
    }

    public function test_cannot_add_unavailable_products(): void
    {
        $carts = app(CartService::class);
        $cart = $this->cartFor($this->customer());

        foreach ([$this->product(['status' => 'draft']), $this->product(['stock' => 0])] as $product) {
            try {
                $carts->add($cart, $product->id);
                $this->fail('Expected CartException');
            } catch (CartException) {
                $this->assertTrue(true);
            }
        }

        $this->assertSame(0, $cart->items()->count());
    }

    public function test_variant_products_require_a_valid_variant_with_its_own_stock(): void
    {
        $product = $this->variantProduct([2, 5]);
        $carts = app(CartService::class);
        $cart = $this->cartFor($this->customer());
        [$small, $large] = $product->variants;

        try {
            $carts->add($cart, $product->id);
            $this->fail('variant is required');
        } catch (CartException) {
        }

        $carts->add($cart, $product->id, $small->id, 2);

        try {
            $carts->add($cart, $product->id, $small->id, 1);
            $this->fail('variant stock exceeded');
        } catch (CartException) {
        }

        $carts->add($cart, $product->id, $large->id, 3);
        $this->assertSame(2, $cart->items()->count());
    }

    public function test_totals_are_calculated_on_the_server(): void
    {
        $this->product(['price' => 100, 'sale_price' => 80, 'stock' => 10]);
        $p = Product::first();
        $carts = app(CartService::class);
        $cart = $this->cartFor($this->customer());
        $carts->add($cart, $p->id, null, 2);

        $summary = $carts->summary($cart);

        $this->assertSame(200.0, $summary->subtotal);
        $this->assertSame(40.0, $summary->discount);
        $this->assertSame(160.0, $summary->netSubtotal());
        $this->assertSame(24.0, $summary->tax); // 15%
        $this->assertSame(184.0, $summary->total);

        // price changes in the database are reflected immediately (nothing is trusted from the client)
        $p->update(['sale_price' => 50]);
        $this->assertSame(100.0, $carts->summary($cart->fresh())->netSubtotal());
    }

    public function test_shipping_is_added_and_free_shipping_threshold_applies(): void
    {
        $p = $this->product(['price' => 100]);
        $method = $this->shipping(['price' => 30, 'free_shipping_threshold' => 150]);
        $carts = app(CartService::class);
        $cart = $this->cartFor($this->customer());

        $carts->add($cart, $p->id, null, 1);
        $this->assertSame(30.0, $carts->summary($cart, $method)->shipping);
        $this->assertSame(145.0, $carts->summary($cart, $method)->total); // 100 + 30 + 15 tax

        $carts->add($cart, $p->id, null, 1);
        $this->assertSame(0.0, $carts->summary($cart->fresh(), $method)->shipping);
    }

    public function test_guest_cart_is_merged_into_user_cart_on_login(): void
    {
        $p1 = $this->product(['stock' => 10]);
        $p2 = $this->product(['stock' => 10]);
        $user = $this->customer();

        $carts = app(CartService::class);
        $userCart = $this->cartFor($user);
        $carts->add($userCart, $p1->id, null, 2);

        // guest adds items through the Livewire storefront (session based cart)
        Livewire::test(ProductListing::class, ['mode' => 'all'])
            ->call('addToCart', $p1->id)->call('addToCart', $p2->id);

        $this->assertDatabaseCount('carts', 2);
        $token = session('cart_token');
        $this->assertNotNull($token);

        $this->post('/login', ['email' => $user->email, 'password' => 'Password123']);

        $this->assertDatabaseCount('carts', 1);
        $items = $userCart->fresh()->items->pluck('quantity', 'product_id');
        $this->assertSame(3, $items[$p1->id]);
        $this->assertSame(1, $items[$p2->id]);
    }

    public function test_merge_never_exceeds_stock(): void
    {
        $product = $this->product(['stock' => 3]);
        $user = $this->customer();
        $carts = app(CartService::class);
        $carts->add($this->cartFor($user), $product->id, null, 2);

        $guest = Cart::create(['token' => 'guest-token']);
        $guest->items()->create(['product_id' => $product->id, 'quantity' => 3]);

        $carts->mergeGuestCart($user, 'guest-token');

        $this->assertSame(3, $user->cart->items()->first()->quantity);
    }

    public function test_cart_page_lets_guests_change_quantities_and_remove_items(): void
    {
        $product = $this->product(['stock' => 5, 'name_ar' => 'منتج السلة']);

        Livewire::test(ProductListing::class, ['mode' => 'all'])->call('addToCart', $product->id);

        $item = CartItem::first();
        Livewire::test(CartPage::class)->assertSee('منتج السلة')
            ->call('updateQuantity', $item->id, 4)
            ->call('updateQuantity', $item->id, 9)
            ->call('remove', $item->id)->assertSee('سلتك فارغة');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_users_cannot_modify_items_of_other_carts(): void
    {
        $product = $this->product();
        $carts = app(CartService::class);
        $mine = $this->cartFor($this->customer());
        $theirs = $this->cartFor($this->customer());
        $item = $carts->add($theirs, $product->id);

        $this->expectException(ModelNotFoundException::class);
        $carts->updateQuantity($mine, $item->id, 5);
    }

    public function test_coupon_is_applied_to_cart_summary(): void
    {
        $product = $this->product(['price' => 200]);
        Coupon::factory()->create(['code' => 'TEN', 'value' => 10]);
        $carts = app(CartService::class);
        $cart = $this->cartFor($this->customer());
        $carts->add($cart, $product->id);

        Livewire::actingAs($cart->user)->test(CartPage::class)
            ->set('couponCode', 'ten')->call('applyCoupon')->assertSee('TEN');

        $summary = $carts->summary($cart->fresh());
        $this->assertSame(20.0, $summary->couponDiscount);
        $this->assertSame(207.0, $summary->total); // (200 - 20) * 1.15

        Livewire::actingAs($cart->user)->test(CartPage::class)
            ->set('couponCode', 'NOPE')->call('applyCoupon')->assertHasErrors('couponCode');
    }
}
