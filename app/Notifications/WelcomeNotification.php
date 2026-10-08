<?php

namespace App\Notifications;

use App\Mail\WelcomeMail;

class WelcomeNotification extends StoreNotification
{
    public function toMail(object $notifiable): WelcomeMail
    {
        return (new WelcomeMail($notifiable))->to($this->mailAddress($notifiable));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'مرحباً بك',
            'message' => 'تم إنشاء حسابك بنجاح في '.setting('store_name').'.',
            'url' => route('home'),
        ];
    }
}
