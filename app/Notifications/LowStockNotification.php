<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class LowStockNotification extends StoreNotification
{
    /** @param list<array{name:string,sku:string,stock:int}> $items */
    public function __construct(public array $items) {}

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('تنبيه: منتجات منخفضة المخزون')
            ->line('المنتجات التالية وصلت لحد المخزون المنخفض:');

        foreach (array_slice($this->items, 0, 25) as $item) {
            $mail->line("• {$item['name']} ({$item['sku']}) — المتبقي: {$item['stock']}");
        }

        return $mail->action('إدارة المنتجات', route('admin.products.index'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'منتجات منخفضة المخزون',
            'message' => count($this->items).' منتج بحاجة لإعادة التوريد',
            'url' => route('admin.products.index'),
        ];
    }
}
