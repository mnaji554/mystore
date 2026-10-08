<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Notifications\Notification;

/**
 * Base class: mail always, database for registered users, plus any channels
 * configured in config('store.extra_notification_channels') (SMS, WhatsApp, ...).
 */
abstract class StoreNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        $channels = ['mail'];

        if ($notifiable instanceof User) {
            $channels[] = 'database';
        }

        return array_values(array_unique(array_merge($channels, config('store.extra_notification_channels', []))));
    }

    /** Normalizes the mail route (string, or [email => name] for guests) into something Mailable::to() accepts. */
    protected function mailAddress(object $notifiable): string|Address
    {
        $route = $notifiable->routeNotificationFor('mail', $this);

        if (is_array($route)) {
            $email = array_key_first($route);

            return is_int($email) ? (string) $route[$email] : new Address($email, (string) $route[$email]);
        }

        return (string) $route;
    }
}
