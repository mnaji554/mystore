<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Exceptions\PaymentException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Setting;
use App\Support\PaymentResult;
use Stripe\Event;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'stripe';
    }

    public function label(): string
    {
        return 'بطاقة ائتمانية (Stripe)';
    }

    public function description(): string
    {
        return 'ادفع بأمان عبر Stripe. لا نقوم بتخزين بيانات بطاقتك.';
    }

    public function isAvailable(): bool
    {
        return $this->isConfigured()
            && filter_var(Setting::get('payment_stripe_enabled', '1'), FILTER_VALIDATE_BOOLEAN);
    }

    public function client(): StripeClient
    {
        if (! $this->isConfigured()) {
            throw new PaymentException('بوابة الدفع غير مفعّلة.');
        }

        return new StripeClient(config('services.stripe.secret'));
    }

    private function isConfigured(): bool
    {
        return filled(config('services.stripe.secret')) && filled(config('services.stripe.key'));
    }

    public function initiate(Order $order, Payment $payment): PaymentResult
    {
        try {
            $session = $this->client()->checkout->sessions->create([
                'mode' => 'payment',
                'customer_email' => $order->customer_email,
                'client_reference_id' => $order->order_number,
                'line_items' => [[
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => strtolower($order->currency),
                        'unit_amount' => (int) round(((float) $order->grand_total) * 100),
                        'product_data' => ['name' => 'طلب رقم '.$order->order_number],
                    ],
                ]],
                'metadata' => ['order_id' => (string) $order->id, 'payment_id' => (string) $payment->id],
                // the placeholder must not be URL-encoded
                'success_url' => route('checkout.stripe.return', $order->order_number).'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('checkout.payment-cancelled', $order->order_number),
            ]);
        } catch (\Throwable $e) {
            report($e);
            throw new PaymentException('تعذر بدء عملية الدفع، يرجى المحاولة لاحقاً.');
        }

        return new PaymentResult('pending', $session->url, $session->id);
    }

    /** Returns [paid, payment_intent_id] for a checkout session after verifying it belongs to the order. */
    public function verifySession(string $sessionId, Order $order): array
    {
        try {
            $session = $this->client()->checkout->sessions->retrieve($sessionId);
        } catch (\Throwable $e) {
            report($e);

            return [false, null];
        }

        if ((string) ($session->metadata['order_id'] ?? '') !== (string) $order->id) {
            return [false, null];
        }

        return [$session->payment_status === 'paid', $session->payment_intent ?? $session->id];
    }

    public function constructEvent(string $payload, ?string $signature): Event
    {
        return Webhook::constructEvent($payload, (string) $signature, (string) config('services.stripe.webhook_secret'));
    }

    public function refund(Payment $payment): bool
    {
        $transaction = $payment->transactions()->where('type', 'charge')->where('status', 'paid')->latest('id')->first();

        if (! $transaction?->provider_reference || ! str_starts_with($transaction->provider_reference, 'pi_')) {
            return false;
        }

        try {
            $this->client()->refunds->create(['payment_intent' => $transaction->provider_reference]);

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }
}
