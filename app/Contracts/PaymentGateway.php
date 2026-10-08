<?php

namespace App\Contracts;

use App\Models\Order;
use App\Models\Payment;
use App\Support\PaymentResult;

interface PaymentGateway
{
    /** Unique key stored in orders.payment_method / payments.gateway. */
    public function key(): string;

    public function label(): string;

    public function description(): string;

    /** Whether the gateway is configured and may be offered at checkout. */
    public function isAvailable(): bool;

    /** Start a payment. Card data never touches this application. */
    public function initiate(Order $order, Payment $payment): PaymentResult;

    /** Refund a paid payment through the provider. Return false when it cannot be automated. */
    public function refund(Payment $payment): bool;
}
