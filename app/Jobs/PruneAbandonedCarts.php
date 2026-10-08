<?php

namespace App\Jobs;

use App\Models\Cart;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Weekly: remove guest carts that have been untouched for 30 days. */
class PruneAbandonedCarts implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Cart::query()->whereNull('user_id')->where('updated_at', '<', now()->subDays(30))->delete();
    }
}
