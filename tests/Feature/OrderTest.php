<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderStatusChanged;
use App\Exceptions\OrderException;
use App\Jobs\CancelUnpaidOrders;
use App\Listeners\SendOrderStatusNotification;
use App\Mail\OrderMail;
use App\Mail\PasswordResetMail;
use App\Mail\WelcomeMail;
use App\Models\Order;
use App\Models\Product;
use App\Notifications\NewOrderAdminNotification;
use App\Notifications\OrderStatusNotification;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrderTest extends TestCase
{
    private function order($user = null, array $product = ['stock' => 10]): Order
    {
        $user ??= $this->customer();
        $carts = app(CartService::class);
        $cart = $carts->resolve(true, $user);
        $carts->add($cart, $this->product($product)->id, null, 2);

        return app(OrderService::class)->createFromCart($cart, $this->checkoutData($this->shipping()), $user);
    }

    public function test_valid_transitions_are_recorded_in_history_with_actor_and_note(): void
    {
        $order = $this->order();
        $admin = $this->staff('admin');
        $service = app(OrderService::class);

        $service->changeStatus($order, OrderStatus::Confirmed, $admin, 'تم التواصل مع العميل');
        $service->changeStatus($order, OrderStatus::Processing, $admin);
        $service->changeStatus($order, OrderStatus::Shipped, $admin);
        $service->changeStatus($order, OrderStatus::Delivered, $admin);

        $this->assertSame(OrderStatus::Delivered, $order->fresh()->status);
        $history = $order->histories()->reorder('id')->get();
        $this->assertCount(5, $history);
        $this->assertNull($history[0]->from_status);
        $this->assertSame(OrderStatus::Pending, $history[1]->from_status);
        $this->assertSame(OrderStatus::Confirmed, $history[1]->to_status);
        $this->assertSame($admin->id, $history[1]->user_id);
        $this->assertSame('تم التواصل مع العميل', $history[1]->note);
    }

    public function test_invalid_transitions_are_rejected(): void
    {
        $order = $this->order();
        $service = app(OrderService::class);

        foreach ([OrderStatus::Delivered, OrderStatus::Shipped, OrderStatus::Refunded] as $status) {
            try {
                $service->changeStatus($order, $status);
                $this->fail("Pending -> {$status->value} must be rejected");
            } catch (OrderException) {
                $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
            }
        }

        $service->changeStatus($order, OrderStatus::Cancelled);
        $this->expectException(OrderException::class);
        $service->changeStatus($order, OrderStatus::Confirmed);
    }

    public function test_cancelling_returns_stock_sales_count_and_coupon_usage(): void
    {
        $order = $this->order(null, ['stock' => 10]);
        $product = Product::first();
        $this->assertSame(8, $product->stock);
        $this->assertSame(2, $product->sales_count);

        app(OrderService::class)->changeStatus($order, OrderStatus::Cancelled);

        $this->assertSame(10, $product->fresh()->stock);
        $this->assertSame(0, $product->fresh()->sales_count);
    }

    public function test_variant_stock_is_restored_on_cancel(): void
    {
        $user = $this->customer();
        $product = $this->variantProduct([5]);
        $variant = $product->variants->first();
        $carts = app(CartService::class);
        $cart = $carts->resolve(true, $user);
        $carts->add($cart, $product->id, $variant->id, 3);

        $order = app(OrderService::class)->createFromCart($cart, $this->checkoutData($this->shipping()), $user);
        $this->assertSame(2, $variant->fresh()->stock);
        $this->assertSame($variant->id, $order->items->first()->variant_id);
        $this->assertSame('M', $order->items->first()->variant_label);

        app(OrderService::class)->changeStatus($order, OrderStatus::Cancelled);
        $this->assertSame(5, $variant->fresh()->stock);
    }

    public function test_delivering_a_cash_on_delivery_order_marks_it_paid(): void
    {
        $order = $this->order();
        $service = app(OrderService::class);

        foreach ([OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered] as $status) {
            $service->changeStatus($order, $status);
        }

        $this->assertSame(PaymentStatus::Paid, $order->fresh()->payment_status);
        $this->assertSame(PaymentStatus::Paid, $order->payments()->first()->status);
        $this->assertNotNull($order->payments()->first()->paid_at);
    }

    public function test_customer_can_cancel_own_pending_order_only(): void
    {
        $user = $this->customer();
        $order = $this->order($user);

        $this->actingAs($user)->post(route('account.orders.cancel', $order->order_number))->assertSessionHas('success');
        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);

        $shipped = $this->order($user);
        app(OrderService::class)->changeStatus($shipped, OrderStatus::Confirmed);
        app(OrderService::class)->changeStatus($shipped, OrderStatus::Shipped);

        $this->post(route('account.orders.cancel', $shipped->order_number))->assertForbidden();
        $this->assertSame(OrderStatus::Shipped, $shipped->fresh()->status);
    }

    public function test_customers_cannot_view_or_cancel_other_customers_orders(): void
    {
        $order = $this->order();
        $intruder = $this->customer();

        $this->actingAs($intruder)->get(route('account.orders.show', $order->order_number))->assertForbidden();
        $this->post(route('account.orders.cancel', $order->order_number))->assertForbidden();
        $this->get(route('account.orders.invoice', $order->order_number))->assertForbidden();
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }

    public function test_owner_sees_order_details_and_order_list(): void
    {
        $user = $this->customer();
        $order = $this->order($user);

        $this->actingAs($user)->get('/account/orders')->assertOk()->assertSee($order->order_number);
        $this->get(route('account.orders.show', $order->order_number))->assertOk()->assertSee('إلغاء الطلب')->assertSee('سجل الحالة');
    }

    public function test_guest_can_track_order_with_matching_email_only(): void
    {
        $order = $this->order();

        $this->post('/track-order', ['order_number' => $order->order_number, 'email' => 'buyer@example.com'])
            ->assertOk()->assertSee($order->order_number)->assertSee('قيد الانتظار');

        $this->post('/track-order', ['order_number' => $order->order_number, 'email' => 'someone@else.com'])
            ->assertSessionHasErrors('order_number');
    }

    public function test_status_notifications_are_sent_to_customer(): void
    {
        $user = $this->customer();
        $order = $this->order($user);
        Notification::fake();

        $order->refresh();
        app(OrderService::class)->changeStatus($order, OrderStatus::Confirmed);
        (new SendOrderStatusNotification)->handle(new OrderStatusChanged($order->fresh(), OrderStatus::Pending, OrderStatus::Confirmed));

        Notification::assertSentTo($user, OrderStatusNotification::class, fn ($n) => $n->type === 'confirmed');
    }

    public function test_order_placed_notifies_customer_and_admins_and_creates_invoice(): void
    {
        $admin = $this->staff('admin');
        $user = $this->customer();
        Notification::fake();

        $order = $this->order($user);

        Notification::assertSentTo($user, OrderStatusNotification::class, fn ($n) => $n->type === 'created');
        Notification::assertSentTo($admin, NewOrderAdminNotification::class);
        $this->assertDatabaseHas('invoices', ['order_id' => $order->id]);
    }

    public function test_guest_order_notification_goes_to_the_checkout_email(): void
    {
        Notification::fake();
        $user = $this->customer();
        $order = $this->order($user);
        $order->update(['user_id' => null]);

        $order->fresh()->customerNotifiable()->notify(new OrderStatusNotification($order, 'shipped'));

        Notification::assertSentOnDemand(OrderStatusNotification::class, fn ($n, $channels, $notifiable) => $notifiable->routes['mail'] === ['buyer@example.com' => 'عميل اختبار']);
    }

    public function test_every_order_mail_template_renders(): void
    {
        $order = $this->order();
        $order->update(['tracking_number' => 'T123', 'tracking_url' => 'https://track.example/T123']);

        foreach (array_keys(OrderMail::TYPES) as $type) {
            $html = (new OrderMail($order, $type))->render();
            $this->assertStringContainsString($order->order_number, $html, $type);
            $this->assertStringContainsString('dir="rtl"', $html);
        }

        $this->assertStringContainsString('T123', (new OrderMail($order, 'shipped'))->render());
        $this->assertStringContainsString('أهلاً', (new WelcomeMail($order->user))->render());
        $this->assertStringContainsString('http://x/reset', (new PasswordResetMail($order->user, 'http://x/reset'))->render());
    }

    public function test_unpaid_online_orders_are_auto_cancelled_by_the_scheduled_job(): void
    {
        $stale = $this->order(null, ['stock' => 10]);
        $stale->forceFill(['payment_method' => 'stripe', 'created_at' => now()->subDays(2)])->save();
        $fresh = $this->order();
        $fresh->update(['payment_method' => 'stripe']);
        $cod = $this->order();
        $cod->forceFill(['created_at' => now()->subDays(3)])->save();

        Event::fake([OrderStatusChanged::class]);
        (new CancelUnpaidOrders)->handle(app(OrderService::class));

        $this->assertSame(OrderStatus::Cancelled, $stale->fresh()->status);
        $this->assertSame(OrderStatus::Pending, $fresh->fresh()->status);
        $this->assertSame(OrderStatus::Pending, $cod->fresh()->status);
    }
}
