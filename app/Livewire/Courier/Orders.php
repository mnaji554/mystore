<?php

namespace App\Livewire\Courier;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Role;
use App\Services\OrderService;
use Livewire\Component;
use Livewire\WithPagination;

class Orders extends Component
{
    use WithPagination;

    public ?string $courierLatitude = null;

    public ?string $courierLongitude = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasRole(Role::COURIER), 403);
    }

    public function setCourierLocation(string|int|float $latitude, string|int|float $longitude): void
    {
        $this->courierLatitude = (string) $latitude;
        $this->courierLongitude = (string) $longitude;
        $this->validate([
            'courierLatitude' => ['required', 'numeric', 'between:-90,90'],
            'courierLongitude' => ['required', 'numeric', 'between:-180,180'],
        ], [
            'courierLatitude.between' => 'تعذر التحقق من موقعك الحالي.',
            'courierLongitude.between' => 'تعذر التحقق من موقعك الحالي.',
        ]);
    }

    public function clearCourierLocation(): void
    {
        $this->courierLatitude = null;
        $this->courierLongitude = null;
        $this->resetValidation('courierLatitude');
        $this->resetValidation('courierLongitude');
    }

    public function claim(int $orderId): void
    {
        abort_unless(auth()->user()?->hasRole(Role::COURIER), 403);

        $this->validate([
            'courierLatitude' => ['required', 'numeric', 'between:-90,90'],
            'courierLongitude' => ['required', 'numeric', 'between:-180,180'],
        ], [
            'courierLatitude.required' => 'حدد موقعك أولاً لاستلام الطلب.',
            'courierLongitude.required' => 'حدد موقعك أولاً لاستلام الطلب.',
        ]);

        $claimed = Order::query()
            ->whereKey($orderId)
            ->whereNull('courier_id')
            ->whereIn('status', $this->activeStatuses())
            ->update(['courier_id' => auth()->id()]);

        if ($claimed !== 1) {
            $this->dispatch('notify', message: 'لم يعد هذا الطلب متاحاً للاستلام.', type: 'error');

            return;
        }

        $this->dispatch('notify', message: 'تم استلام الطلب بنجاح.', type: 'success');
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
        $activeStatuses = $this->activeStatuses();
        $activeOrders = Order::query()->where('courier_id', auth()->id())
            ->whereIn('status', $activeStatuses)
            ->orderBy('placed_at')
            ->orderBy('id')
            ->get(['id', 'order_number', 'customer_name', 'customer_phone', 'shipping_address', 'delivery_latitude', 'delivery_longitude', 'tracking_number', 'shipping_cost', 'grand_total', 'status', 'placed_at']);
        $availableOrders = Order::query()
            ->whereNull('courier_id')
            ->whereIn('status', $activeStatuses)
            ->orderBy('placed_at')
            ->orderBy('id')
            ->paginate(20, ['id', 'order_number', 'customer_name', 'customer_phone', 'shipping_address', 'delivery_latitude', 'delivery_longitude', 'shipping_cost', 'grand_total', 'status', 'placed_at']);
        $availableOrders->getCollection()->each(function (Order $order): void {
            $order->distance_km = $this->distanceInKilometers($order);
        });
        $activeOrders->each(function (Order $order): void {
            $order->distance_km = $this->distanceInKilometers($order);
        });
        $completedOrders = Order::query()->where('courier_id', auth()->id())
            ->where('status', OrderStatus::Delivered)
            ->latest('placed_at')->limit(20)
            ->get(['id', 'order_number', 'customer_name', 'status', 'placed_at']);

        return view('livewire.courier.orders', compact('activeOrders', 'availableOrders', 'completedOrders'))
            ->layout('components.layouts.app', ['title' => 'طلبات التوصيل', 'noindex' => true]);
    }

    /**
     * @return list<OrderStatus>
     */
    private function activeStatuses(): array
    {
        return [
            OrderStatus::Pending,
            OrderStatus::Confirmed,
            OrderStatus::Processing,
            OrderStatus::Shipped,
        ];
    }

    private function distanceInKilometers(Order $order): ?float
    {
        if (
            $this->courierLatitude === null
            || $this->courierLongitude === null
            || $order->delivery_latitude === null
            || $order->delivery_longitude === null
        ) {
            return null;
        }

        $latitudeDelta = deg2rad((float) $order->delivery_latitude - (float) $this->courierLatitude);
        $longitudeDelta = deg2rad((float) $order->delivery_longitude - (float) $this->courierLongitude);
        $latitude = deg2rad((float) $this->courierLatitude);
        $deliveryLatitude = deg2rad((float) $order->delivery_latitude);
        $haversine = sin($latitudeDelta / 2) ** 2
            + cos($latitude) * cos($deliveryLatitude) * sin($longitudeDelta / 2) ** 2;
        $haversine = min(1, $haversine);
        $centralAngle = 2 * atan2(sqrt($haversine), sqrt(1 - $haversine));

        return round(6371 * $centralAngle, 1);
    }
}
