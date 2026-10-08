<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\LowStockNotification;
use App\Services\ReportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

/** Daily: tell the staff which products reached the low-stock threshold. */
class SendLowStockReport implements ShouldQueue
{
    use Queueable;

    public function handle(ReportService $reports): void
    {
        $threshold = $reports->lowStockThreshold();

        $items = $reports->lowStockQuery()->with('variants')->get()->flatMap(function ($product) use ($threshold) {
            if ($product->has_variants) {
                return $product->variants->where('is_active', true)->where('stock', '<=', $threshold)
                    ->map(fn ($v) => ['name' => $product->name_ar.' ('.$v->label.')', 'sku' => $v->sku, 'stock' => (int) $v->stock]);
            }

            return [['name' => $product->name_ar, 'sku' => $product->sku, 'stock' => (int) $product->stock]];
        })->values()->all();

        if (! $items) {
            return;
        }

        $staff = User::query()->whereHas('role', fn ($q) => $q->whereIn('slug', ['super_admin', 'admin', 'manager']))
            ->where('is_active', true)->get();

        Notification::send($staff, new LowStockNotification($items));

        if ($email = config('services.store.admin_email')) {
            Notification::route('mail', $email)->notify(new LowStockNotification($items));
        }
    }
}
