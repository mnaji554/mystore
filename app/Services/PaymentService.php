<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentException;
use App\Models\Order;
use App\Models\Payment;
use App\Support\PaymentResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /** @return Collection<string, PaymentGateway> */
    public function gateways(): Collection
    {
        return collect(config('payments.gateways', []))
            ->map(fn (string $class) => app($class));
    }

    /** @return Collection<string, PaymentGateway> */
    public function available(): Collection
    {
        return $this->gateways()->filter(fn (PaymentGateway $g) => $g->isAvailable());
    }

    public function gateway(string $key): PaymentGateway
    {
        $gateway = $this->available()->get($key);

        if (! $gateway) {
            throw new PaymentException('طريقة الدفع المختارة غير متاحة.');
        }

        return $gateway;
    }

    public function createPayment(Order $order, string $gatewayKey): Payment
    {
        return $order->payments()->create([
            'gateway' => $gatewayKey,
            'status' => PaymentStatus::Pending,
            'amount' => $order->grand_total,
            'currency' => $order->currency,
        ]);
    }

    /** Start the payment for a freshly created order. */
    public function initiate(Order $order): PaymentResult
    {
        $payment = $order->payment ?? $this->createPayment($order, $order->payment_method);
        $result = $this->gateway($payment->gateway)->initiate($order, $payment);

        if ($result->reference) {
            $payment->update(['reference' => $result->reference]);
        }

        $payment->transactions()->create([
            'type' => 'charge',
            'status' => $result->status,
            'amount' => $payment->amount,
            'provider_reference' => $result->reference,
            'payload' => ['redirect' => $result->requiresRedirect()],
        ]);

        if ($result->status === 'paid') {
            $this->markPaid($payment, $result->reference);
        }

        return $result;
    }

    /** Idempotent: safe to call from the return URL and the webhook. */
    public function markPaid(Payment $payment, ?string $providerReference = null, array $payload = []): void
    {
        $confirmed = DB::transaction(function () use ($payment, $providerReference, $payload) {
            $payment = Payment::query()->lockForUpdate()->find($payment->id);

            if ($payment->status === PaymentStatus::Paid) {
                return false;
            }

            $payment->update(['status' => PaymentStatus::Paid, 'paid_at' => now()]);
            $payment->order()->update(['payment_status' => PaymentStatus::Paid]);
            $payment->transactions()->create([
                'type' => 'charge',
                'status' => 'paid',
                'amount' => $payment->amount,
                'provider_reference' => $providerReference ?? $payment->reference,
                'payload' => $payload ?: null,
            ]);

            return true;
        });

        if ($confirmed) {
            $order = $payment->order()->first();

            if ($order->status === OrderStatus::Pending && $payment->gateway !== 'cod') {
                app(OrderService::class)->changeStatus($order, OrderStatus::Confirmed, null, 'تم تأكيد الدفع تلقائياً');
            }
        }
    }

    public function markFailed(Payment $payment, string $reason = ''): void
    {
        if ($payment->status === PaymentStatus::Paid) {
            return;
        }

        $payment->update(['status' => PaymentStatus::Failed]);
        $payment->order()->update(['payment_status' => PaymentStatus::Failed]);
        $payment->transactions()->create([
            'type' => 'charge', 'status' => 'failed', 'amount' => $payment->amount,
            'payload' => $reason ? ['reason' => $reason] : null,
        ]);
    }

    /** Refund every paid payment of the order. Returns true when nothing is left to refund manually. */
    public function refundOrder(Order $order): bool
    {
        $allAutomatic = true;

        foreach ($order->payments()->where('status', PaymentStatus::Paid)->get() as $payment) {
            $automatic = $this->gateways()->get($payment->gateway)?->refund($payment) ?? false;
            $allAutomatic = $allAutomatic && $automatic;

            $payment->update(['status' => PaymentStatus::Refunded]);
            $payment->transactions()->create([
                'type' => 'refund',
                'status' => $automatic ? 'refunded' : 'manual',
                'amount' => $payment->amount,
            ]);
        }

        if ($order->payment_status === PaymentStatus::Paid) {
            $order->update(['payment_status' => PaymentStatus::Refunded]);
        }

        return $allAutomatic;
    }
}
