<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** One mailable for every order e-mail; the template lives in resources/views/emails/order/{type}.blade.php */
class OrderMail extends Mailable
{
    use SerializesModels;

    public const TYPES = [
        'created' => 'تم استلام طلبك رقم :number',
        'confirmed' => 'تم تأكيد طلبك رقم :number',
        'processing' => 'طلبك رقم :number قيد التجهيز',
        'shipped' => 'تم شحن طلبك رقم :number',
        'delivered' => 'تم تسليم طلبك رقم :number',
        'cancelled' => 'تم إلغاء طلبك رقم :number',
        'refunded' => 'تم استرجاع طلبك رقم :number',
    ];

    public function __construct(public Order $order, public string $type) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: str_replace(':number', $this->order->order_number, self::TYPES[$this->type]));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.order.'.$this->type, with: ['order' => $this->order->loadMissing('items')]);
    }
}
