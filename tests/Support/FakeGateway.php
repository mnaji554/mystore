<?php

namespace Tests\Support;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Models\Payment;
use App\Support\PaymentResult;

class FakeGateway implements PaymentGateway
{
    public static bool $refunded = false;

    public function key(): string
    {
        return 'fake';
    }

    public function label(): string
    {
        return 'Fake gateway';
    }

    public function description(): string
    {
        return 'testing';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function initiate(Order $order, Payment $payment): PaymentResult
    {
        return new PaymentResult('pending', 'https://pay.example.test/checkout/'.$order->order_number, 'ref_'.$order->id);
    }

    public function refund(Payment $payment): bool
    {
        return self::$refunded = true;
    }
}
