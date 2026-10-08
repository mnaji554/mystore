<?php

use App\Jobs\CancelUnpaidOrders;
use App\Jobs\PruneAbandonedCarts;
use App\Jobs\SendLowStockReport;
use Illuminate\Support\Facades\Schedule;

/*
| Scheduler — run `php artisan schedule:work` (dev) or a `* * * * * php artisan schedule:run` cron (production).
*/
Schedule::job(new CancelUnpaidOrders)->hourly()->name('cancel-unpaid-orders')->withoutOverlapping();
Schedule::job(new SendLowStockReport)->dailyAt('08:00')->name('low-stock-report');
Schedule::job(new PruneAbandonedCarts)->weekly()->name('prune-abandoned-carts');
Schedule::command('queue:prune-failed --hours=168')->daily();
Schedule::command('model:prune')->daily();
Schedule::command('sanctum:prune-expired --hours=24')->daily();
