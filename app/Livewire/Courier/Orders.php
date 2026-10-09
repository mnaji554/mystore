<?php

namespace App\Livewire\Courier;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Role;
use App\Services\OrderService;
use Livewire\Component;

class Orders extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole(Role::COURIER), 403);
    }

    public function advance(int $orderId, OrderService $orders): void
    {
        $order = Order::query()->where('courier_id', auth()->id())->findOrFail($orderId);

        $nextStatus = match ($order->status) {
            OrderStatus::Processing => OrderStatus::Shipped,
            OrderStatus::Shipped => OrderStatus::Delivered,
            default => null,
        };

        abort_unless($nextStatus, 403, 'لا يمكن تحديث حالة هذا الطلب.');

        $orders->changeStatus($order, $nextStatus, auth()->user(), 'تم تحديث الحالة بواسطة مندوب التوصيل');
        $this->dispatch('notify', message: 'تم تحديث حالة الطلب.', type: 'success');
    }

    public function render()
    {
        $activeOrders = Order::query()->where('courier_id', auth()->id())
            ->whereIn('status', [OrderStatus::Processing, OrderStatus::Shipped])
            ->orderBy('placed_at')
            ->get(['id', 'order_number', 'customer_name', 'customer_phone', 'shipping_address', 'tracking_number', 'shipping_cost', 'grand_total', 'status', 'placed_at']);
        $completedOrders = Order::query()->where('courier_id', auth()->id())
            ->where('status', OrderStatus::Delivered)
            ->latest('placed_at')->limit(20)
            ->get(['id', 'order_number', 'customer_name', 'status', 'placed_at']);

        return view('livewire.courier.orders', compact('activeOrders', 'completedOrders'))
            ->layout('components.layouts.app', ['title' => 'طلبات التوصيل', 'noindex' => true]);
    }
}
