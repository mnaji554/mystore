<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentException;
use App\Models\Order;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\Payments\StripeGateway;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Schema;
use Tests\Support\FakeGateway;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['payments.gateways.fake' => FakeGateway::class]);
        FakeGateway::$refunded = false;
    }

    private function order(string $gateway, $user = null): Order
    {
        $user ??= $this->customer();
        $carts = app(CartService::class);
        $cart = $carts->resolve(true, $user);
        $carts->add($cart, $this->product(['price' => 100])->id);

        return app(OrderService::class)->createFromCart($cart, $this->checkoutData($this->shipping(['price' => 0]), $gateway), $user);
    }

    public function test_cash_on_delivery_is_always_available_and_stripe_only_when_configured(): void
    {
        $payments = app(PaymentService::class);

        $this->assertTrue($payments->available()->has('cod'));
        $this->assertFalse($payments->available()->has('stripe'));

        config(['services.stripe.key' => 'pk_test_x', 'services.stripe.secret' => 'sk_test_x']);
        $this->assertTrue(app(PaymentService::class)->available()->has('stripe'));
    }

    public function test_unavailable_gateway_cannot_be_used_for_an_order(): void
    {
        $this->expectException(PaymentException::class);
        $this->order('stripe');
    }

    public function test_new_gateways_can_be_plugged_in_through_configuration(): void
    {
        $order = $this->order('fake');
        $result = app(PaymentService::class)->initiate($order);

        $this->assertTrue($result->requiresRedirect());
        $this->assertSame('https://pay.example.test/checkout/'.$order->order_number, $result->redirectUrl);
        $this->assertSame('ref_'.$order->id, $order->payment->fresh()->reference);
        $this->assertDatabaseHas('payment_transactions', ['payment_id' => $order->payment->id, 'type' => 'charge', 'status' => 'pending']);
    }

    public function test_mark_paid_is_idempotent_and_confirms_online_orders(): void
    {
        $order = $this->order('fake');
        $service = app(PaymentService::class);
        $payment = $order->payment;

        $service->markPaid($payment, 'pi_1');
        $service->markPaid($payment->fresh(), 'pi_1');

        $order->refresh();
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame(OrderStatus::Confirmed, $order->status);
        $this->assertSame(1, $payment->transactions()->where('status', 'paid')->count());
        $this->assertSame(2, $order->histories()->count()); // created + confirmed, only once
    }

    public function test_failed_payment_is_recorded_and_never_overrides_paid(): void
    {
        $order = $this->order('fake');
        $service = app(PaymentService::class);

        $service->markFailed($order->payment, 'declined');
        $this->assertSame(PaymentStatus::Failed, $order->fresh()->payment_status);

        $service->markPaid($order->payment->fresh(), 'pi_2');
        $service->markFailed($order->payment->fresh(), 'late failure');
        $this->assertSame(PaymentStatus::Paid, $order->fresh()->payment_status);
    }

    public function test_refunding_an_order_refunds_paid_payments(): void
    {
        $order = $this->order('fake');
        $service = app(PaymentService::class);
        $service->markPaid($order->payment, 'pi_3');

        foreach ([OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered, OrderStatus::Refunded] as $status) {
            app(OrderService::class)->changeStatus($order->fresh(), $status);
        }

        $this->assertTrue(FakeGateway::$refunded);
        $this->assertSame(PaymentStatus::Refunded, $order->fresh()->payment_status);
        $this->assertDatabaseHas('payment_transactions', ['type' => 'refund', 'status' => 'refunded']);
    }

    public function test_no_card_data_columns_exist(): void
    {
        foreach (['payments', 'payment_transactions'] as $table) {
            foreach (Schema::getColumnListing($table) as $column) {
                $this->assertDoesNotMatchRegularExpression('/card|cvv|cvc|pan|expiry/i', $column);
            }
        }
    }

    private function webhook(array $object, string $type, ?string $secret = 'whsec_test', ?string $signature = null)
    {
        $payload = json_encode(['id' => 'evt_test', 'object' => 'event', 'type' => $type, 'data' => ['object' => $object]]);
        $timestamp = time();
        $signature ??= "t={$timestamp},v1=".hash_hmac('sha256', "{$timestamp}.{$payload}", (string) $secret);

        return $this->call('POST', '/webhooks/stripe', [], [], [], ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'], $payload);
    }

    private function stripeOrder(): Order
    {
        config(['services.stripe.key' => 'pk_test_x', 'services.stripe.secret' => 'sk_test_x', 'services.stripe.webhook_secret' => 'whsec_test']);

        return $this->order('stripe');
    }

    public function test_stripe_webhook_rejects_invalid_signature_and_unconfigured_secret(): void
    {
        $order = $this->stripeOrder();
        $object = ['id' => 'cs_1', 'object' => 'checkout.session', 'payment_status' => 'paid', 'payment_intent' => 'pi_x', 'metadata' => ['payment_id' => (string) $order->payment->id]];

        $this->webhook($object, 'checkout.session.completed', signature: 't=1,v1=bad')->assertStatus(400);
        $this->assertSame(PaymentStatus::Pending, $order->fresh()->payment_status);

        config(['services.stripe.webhook_secret' => null]);
        $this->webhook($object, 'checkout.session.completed')->assertStatus(503);
    }

    public function test_stripe_webhook_marks_the_order_paid(): void
    {
        $order = $this->stripeOrder();
        $object = ['id' => 'cs_1', 'object' => 'checkout.session', 'payment_status' => 'paid', 'payment_intent' => 'pi_abc', 'metadata' => ['payment_id' => (string) $order->payment->id, 'order_id' => (string) $order->id]];

        $this->webhook($object, 'checkout.session.completed')->assertOk();

        $order->refresh();
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame(OrderStatus::Confirmed, $order->status);
        $this->assertDatabaseHas('payment_transactions', ['provider_reference' => 'pi_abc', 'status' => 'paid']);

        // replayed event changes nothing
        $this->webhook($object, 'checkout.session.completed')->assertOk();
        $this->assertSame(2, $order->histories()->count());
    }

    public function test_stripe_webhook_ignores_unpaid_sessions_and_handles_expiry(): void
    {
        $order = $this->stripeOrder();
        $metadata = ['payment_id' => (string) $order->payment->id];

        $this->webhook(['id' => 'cs_2', 'object' => 'checkout.session', 'payment_status' => 'unpaid', 'metadata' => $metadata], 'checkout.session.completed')->assertOk();
        $this->assertSame(PaymentStatus::Pending, $order->fresh()->payment_status);

        $this->webhook(['id' => 'cs_2', 'object' => 'checkout.session', 'metadata' => $metadata], 'checkout.session.expired')->assertOk();
        $this->assertSame(PaymentStatus::Failed, $order->fresh()->payment_status);
    }

    public function test_stripe_return_url_verifies_the_session_before_marking_paid(): void
    {
        $order = $this->stripeOrder();

        $this->partialMock(StripeGateway::class, fn ($mock) => $mock->shouldReceive('verifySession')->once()->andReturn([true, 'pi_return']));

        $this->withSession(['last_order_number' => $order->order_number])
            ->get(route('checkout.stripe.return', $order->order_number).'?session_id=cs_ok')
            ->assertRedirect(route('checkout.success', $order->order_number));

        $this->assertSame(PaymentStatus::Paid, $order->fresh()->payment_status);
    }

    public function test_stripe_return_with_unverified_session_does_not_mark_paid(): void
    {
        $order = $this->stripeOrder();

        $this->partialMock(StripeGateway::class, fn ($mock) => $mock->shouldReceive('verifySession')->once()->andReturn([false, null]));

        $this->withSession(['last_order_number' => $order->order_number])
            ->get(route('checkout.stripe.return', $order->order_number).'?session_id=cs_fake')
            ->assertRedirect(route('checkout.payment-cancelled', $order->order_number));

        $this->assertSame(PaymentStatus::Pending, $order->fresh()->payment_status);
    }

    public function test_result_pages_are_not_accessible_to_strangers(): void
    {
        $order = $this->order('cod');

        $this->get(route('checkout.success', $order->order_number))->assertNotFound();
        $this->actingAs($this->customer())->get(route('checkout.success', $order->order_number))->assertNotFound();
        $this->actingAs($order->user)->get(route('checkout.success', $order->order_number))->assertOk();
    }
}
