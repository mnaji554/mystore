<?php

namespace App\Jobs;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\StoreException;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Hourly: cancel online-payment orders that were never paid, returning their stock. */
class CancelUnpaidOrders implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $afterHours = 24) {}

    public function handle(OrderService $orders): void
    {
        Order::query()
            ->where('status', OrderStatus::Pending)
            ->where('payment_method', '!=', 'cod')
            ->whereIn('payment_status', [PaymentStatus::Pending, PaymentStatus::Failed])
            ->where('created_at', '<', now()->subHours($this->afterHours))
            ->each(function (Order $order) use ($orders) {
                try {
                    $orders->changeStatus($order, OrderStatus::Cancelled, null, 'إلغاء تلقائي: لم يكتمل الدفع');
                } catch (StoreException $e) {
                    report($e);
                }
            });
    }
}
