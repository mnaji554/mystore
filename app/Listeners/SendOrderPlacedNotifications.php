<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Models\User;
use App\Notifications\NewOrderAdminNotification;
use App\Notifications\OrderStatusNotification;
use App\Services\InvoiceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendOrderPlacedNotifications implements ShouldQueue
{
    public function __construct(private readonly InvoiceService $invoices) {}

    public function handle(OrderPlaced $event): void
    {
        $order = $event->order->load(['items', 'user']);

        $this->invoices->ensureInvoice($order);

        $order->customerNotifiable()->notify(new OrderStatusNotification($order, 'created'));

        $staff = User::query()->whereHas('role', fn ($q) => $q->whereIn('slug', ['super_admin', 'admin']))
            ->where('is_active', true)->get();

        Notification::send($staff, new NewOrderAdminNotification($order));

        if ($email = config('services.store.admin_email')) {
            Notification::route('mail', $email)->notify(new NewOrderAdminNotification($order));
        }
    }
}
