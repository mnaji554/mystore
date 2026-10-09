<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Setting;
use App\Support\PaymentResult;

class CashOnDeliveryGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'cod';
    }

    public function label(): string
    {
        return 'الدفع عند الاستلام';
    }

    public function description(): string
    {
        return 'ادفع نقداً عند استلام طلبك.';
    }

    public function isAvailable(): bool
    {
        return filter_var(Setting::get('payment_cod_enabled', '1'), FILTER_VALIDATE_BOOLEAN);
    }

    public function initiate(Order $order, Payment $payment): PaymentResult
    {
        return new PaymentResult('pending', message: 'سيتم تحصيل المبلغ عند التسليم.');
    }

    public function refund(Payment $payment): bool
    {
        return true; // cash is returned manually
    }
}
