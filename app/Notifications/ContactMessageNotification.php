<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use Illuminate\Notifications\Messages\MailMessage;

class ContactMessageNotification extends StoreNotification
{
    public function __construct(public ContactMessage $message) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('رسالة تواصل جديدة: '.$this->message->subject)
            ->replyTo($this->message->email, $this->message->name)
            ->line("من: {$this->message->name} <{$this->message->email}>")
            ->line($this->message->message);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'رسالة جديدة من '.$this->message->name,
            'message' => $this->message->subject,
            'url' => route('admin.messages'),
        ];
    }
}
