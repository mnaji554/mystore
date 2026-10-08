<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\CartService;
use Illuminate\Auth\Events\Login;

class HandleUserLogin
{
    public function __construct(private readonly CartService $carts) {}

    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $event->user->forceFill(['last_login_at' => now()])->saveQuietly();

        $this->carts->mergeGuestCart($event->user, session('cart_token'));
    }
}
