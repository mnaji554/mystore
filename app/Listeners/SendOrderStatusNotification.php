<?php

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Notifications\OrderStatusNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrderStatusNotification implements ShouldQueue
{
    public function handle(OrderStatusChanged $event): void
    {
        if ($event->to === OrderStatus::Pending) {
            return;
        }

        $order = $event->order->load('items');
        $order->customerNotifiable()->notify(new OrderStatusNotification($order, $event->to->value));
    }
}
