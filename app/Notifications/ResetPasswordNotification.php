<?php

namespace App\Notifications;

use App\Mail\PasswordResetMail;
use App\Models\User;

class ResetPasswordNotification extends StoreNotification
{
    public function __construct(public string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): PasswordResetMail
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new PasswordResetMail($notifiable, $url))->to($notifiable->email);
    }
}
