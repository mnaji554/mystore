<?php

namespace App\Notifications;

use App\Enums\OrderStatus;
use App\Mail\OrderMail;
use App\Models\Order;

/** Customer notification for order creation and every status change (confirmed ... refunded). */
class OrderStatusNotification extends StoreNotification
{
    public function __construct(public Order $order, public string $type) {}

    public static function forStatus(Order $order, OrderStatus $status): ?self
    {
        return match ($status) {
            OrderStatus::Pending => new self($order, 'created'),
            default => new self($order, $status->value),
        };
    }

    public function toMail(object $notifiable): OrderMail
    {
        return (new OrderMail($this->order, $this->type))->to($this->mailAddress($notifiable));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => str_replace(':number', $this->order->order_number, OrderMail::TYPES[$this->type]),
            'message' => 'إجمالي الطلب: '.money($this->order->grand_total),
            'url' => route('account.orders.show', $this->order->order_number),
            'order_id' => $this->order->id,
        ];
    }
}
