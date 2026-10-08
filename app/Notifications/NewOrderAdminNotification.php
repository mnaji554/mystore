<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Messages\MailMessage;

class NewOrderAdminNotification extends StoreNotification
{
    public function __construct(public Order $order) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('طلب جديد '.$this->order->order_number)
            ->greeting('طلب جديد!')
            ->line("العميل: {$this->order->customer_name}")
            ->line('الإجمالي: '.money($this->order->grand_total))
            ->action('عرض الطلب', route('admin.orders.show', $this->order));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'طلب جديد '.$this->order->order_number,
            'message' => "{$this->order->customer_name} - ".money($this->order->grand_total),
            'url' => route('admin.orders.show', $this->order),
            'order_id' => $this->order->id,
        ];
    }
}
