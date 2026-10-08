<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderPlaced;
use App\Exceptions\OrderException;
use App\Exceptions\PaymentException;
use App\Livewire\Checkout;
use App\Livewire\ProductListing;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    private function fillCart($user, array $items): Cart
    {
        $carts = app(CartService::class);
        $cart = $carts->resolve(true, $user);

        foreach ($items as [$product, $qty]) {
            $carts->add($cart, $product->id, null, $qty);
        }

        return $cart;
    }

    private function place($cart, $data, $user = null): Order
    {
        return app(OrderService::class)->createFromCart($cart, $data, $user);
    }

    public function test_order_is_created_with_server_calculated_totals_and_stock_is_decremented(): void
    {
        Event::fake([OrderPlaced::class]);
        $user = $this->customer();
        $product = $this->product(['price' => 100, 'sale_price' => 80, 'stock' => 10, 'cost_price' => 50]);
        $method = $this->shipping(['price' => 20]);
        $coupon = Coupon::factory()->fixed(10)->create(['code' => 'MINUS10']);
        $cart = $this->fillCart($user, [[$product, 2]]);
        $cart->update(['coupon_code' => 'MINUS10']);

        $order = $this->place($cart, $this->checkoutData($method), $user);

        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertStringStartsWith('ORD-', $order->order_number);
        $this->assertEquals(200.00, $order->subtotal);
        $this->assertEquals(40.00, $order->discount_total);
        $this->assertEquals(10.00, $order->coupon_discount);
        $this->assertEquals(20.00, $order->shipping_cost);
        $this->assertEquals(22.50, $order->tax_total); // 15% of (200 - 40 - 10)
        $this->assertEquals(192.50, $order->grand_total); // 150 + 20 + 22.5

        $item = $order->items->first();
        $this->assertSame(2, $item->quantity);
        $this->assertEquals(80.00, $item->sale_price);
        $this->assertEquals(100.00, $item->unit_price);
        $this->assertEquals(50.00, $item->cost_price);

        $this->assertSame(8, $product->fresh()->stock);
        $this->assertSame(2, $product->fresh()->sales_count);
        $this->assertSame(0, $cart->fresh()->items()->count());
        $this->assertNull($cart->fresh()->coupon_code);
        $this->assertDatabaseHas('coupon_usages', ['coupon_id' => $coupon->id, 'order_id' => $order->id, 'discount_amount' => 10]);
        $this->assertSame(1, $coupon->fresh()->used_count);
        $this->assertDatabaseHas('order_status_histories', ['order_id' => $order->id, 'to_status' => 'pending']);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'gateway' => 'cod', 'status' => 'pending']);
        Event::assertDispatched(OrderPlaced::class);
    }

    public function test_checkout_fails_when_stock_is_insufficient_and_nothing_is_changed(): void
    {
        $user = $this->customer();
        $product = $this->product(['stock' => 3]);
        $cart = $this->fillCart($user, [[$product, 3]]);
        $product->update(['stock' => 1]); // someone else bought it meanwhile

        try {
            $this->place($cart, $this->checkoutData($this->shipping()), $user);
            $this->fail('Expected OrderException');
        } catch (OrderException $e) {
            $this->assertStringContainsString('المتاح', $e->getMessage());
        }

        $this->assertSame(1, $product->fresh()->stock);
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(1, $cart->items()->count());
    }

    public function test_empty_cart_inactive_shipping_and_unknown_payment_are_rejected(): void
    {
        $user = $this->customer();
        $cart = app(CartService::class)->resolve(true, $user);
        $method = $this->shipping();

        try {
            $this->place($cart, $this->checkoutData($method), $user);
            $this->fail('empty cart');
        } catch (OrderException) {
        }

        $cart = $this->fillCart($user, [[$this->product(), 1]]);

        try {
            $this->place($cart, $this->checkoutData($this->shipping(['is_active' => false])), $user);
            $this->fail('inactive shipping');
        } catch (OrderException) {
        }

        $this->expectException(PaymentException::class);
        $this->place($cart, $this->checkoutData($method, 'bitcoin'), $user);
    }

    public function test_expired_coupon_blocks_checkout(): void
    {
        $user = $this->customer();
        $cart = $this->fillCart($user, [[$this->product(), 1]]);
        Coupon::factory()->create(['code' => 'OLD', 'expires_at' => now()->subDay()]);
        $cart->update(['coupon_code' => 'OLD']);

        $this->expectException(OrderException::class);
        $this->place($cart, $this->checkoutData($this->shipping()), $user);
    }

    public function test_guest_checkout_through_the_livewire_wizard(): void
    {
        $product = $this->product(['price' => 100, 'stock' => 5]);
        $method = $this->shipping(['price' => 25]);

        Livewire::test(ProductListing::class, ['mode' => 'all'])->call('addToCart', $product->id);

        $component = Livewire::test(Checkout::class)
            ->set('name', 'زائر')->set('email', 'guest@example.com')->set('phone', '0501234567')
            ->call('next')->assertSet('step', 2)
            ->set('address.full_name', 'زائر')->set('address.phone', '0501234567')->set('address.city', 'جدة')->set('address.street', 'شارع التحلية')
            ->call('next')->assertSet('step', 3)
            ->set('shippingMethodId', $method->id)->call('next')->assertSet('step', 4)
            ->call('next')->assertSet('step', 5)
            ->set('paymentMethod', 'cod')->call('next')->assertSet('step', 6)
            ->assertSee('تأكيد الطلب')
            ->call('placeOrder');

        $order = Order::firstOrFail();
        $component->assertRedirect(route('checkout.success', $order->order_number));

        $this->assertNull($order->user_id);
        $this->assertSame('guest@example.com', $order->customer_email);
        $this->assertEquals(140.00, $order->grand_total); // 100 + 25 shipping + 15 tax (tax excludes shipping)
        $this->assertSame('جدة', $order->shipping_address['city']);

        $this->get(route('checkout.success', $order->order_number))->assertOk()->assertSee($order->order_number);
    }

    public function test_wizard_validates_each_step(): void
    {
        $this->product(); // ensure catalog exists
        Livewire::test(ProductListing::class, ['mode' => 'all'])->call('addToCart', Product::first()->id);

        Livewire::test(Checkout::class)
            ->call('next')->assertHasErrors(['name', 'email', 'phone'])->assertSet('step', 1)
            ->set('name', 'س')->set('email', 'not-an-email')->set('phone', 'abc')->call('next')->assertHasErrors(['email', 'phone']);
    }

    public function test_logged_in_customer_can_use_saved_address_and_cannot_use_someone_elses(): void
    {
        $user = $this->customer();
        $other = $this->customer();
        $mine = Address::create([
            'user_id' => $user->id, 'full_name' => 'أنا', 'phone' => '0501111111', 'city' => 'الرياض', 'street' => 'شارع 1', 'is_default' => true,
        ]);
        $theirs = Address::create([
            'user_id' => $other->id, 'full_name' => 'هم', 'phone' => '0502222222', 'city' => 'جدة', 'street' => 'شارع 2',
        ]);
        $method = $this->shipping();
        $this->fillCart($user, [[$this->product(), 1]]);

        $this->actingAs($user);
        $component = Livewire::test(Checkout::class)->set('shippingMethodId', $method->id)->set('paymentMethod', 'cod')->assertSet('addressId', $mine->id);

        Livewire::test(Checkout::class)->set('addressId', $theirs->id)->set('step', 2)->call('next')->assertForbidden();

        $component->call('placeOrder');
        $this->assertSame('الرياض', Order::firstOrFail()->shipping_address['city']);
        $this->assertSame($user->id, Order::first()->user_id);
    }

    public function test_concurrent_oversell_is_prevented_by_stock_checks(): void
    {
        $product = $this->product(['stock' => 1]);
        $method = $this->shipping();
        $a = $this->customer();
        $b = $this->customer();
        $cartA = $this->fillCart($a, [[$product, 1]]);
        $cartB = $this->fillCart($b, [[$product, 1]]);

        $this->place($cartA, $this->checkoutData($method), $a);

        $this->expectException(OrderException::class);
        $this->place($cartB, $this->checkoutData($method), $b);
    }

    public function test_payment_status_defaults_to_pending(): void
    {
        $user = $this->customer();
        $order = $this->place($this->fillCart($user, [[$this->product(), 1]]), $this->checkoutData($this->shipping()), $user);

        $this->assertSame(PaymentStatus::Pending, $order->payment_status);
    }
}
