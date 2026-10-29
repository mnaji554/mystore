<?php

namespace App\Livewire\Courier;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Role;
use App\Services\ImageService;
use App\Services\OrderService;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Throwable;

class Orders extends Component
{
    use WithFileUploads, WithPagination;

    public ?string $courierLatitude = null;

    public ?string $courierLongitude = null;

    public ?int $deliveryOrderId = null;

    /** @var list<TemporaryUploadedFile> */
    public array $deliveryPhotos = [];

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
        abort_unless(auth()->user()?->hasRole(Role::COURIER), 403);

        $order = Order::query()->where('courier_id', auth()->id())->findOrFail($orderId);

        abort_unless($order->status === OrderStatus::Processing, 403, 'لا يمكن تحديث حالة هذا الطلب.');

        $orders->changeStatus($order, OrderStatus::Shipped, auth()->user(), 'تم استلام الطلب من المتجر بواسطة المندوب');
        $this->dispatch('notify', message: 'تم تحديث حالة الطلب.', type: 'success');
    }

    public function prepareDelivery(int $orderId): void
    {
        abort_unless(auth()->user()?->hasRole(Role::COURIER), 403);

        $order = Order::query()->where('courier_id', auth()->id())->findOrFail($orderId);
        abort_unless($order->status === OrderStatus::Shipped, 403, 'لا يمكن تأكيد تسليم هذا الطلب.');

        $this->deliveryOrderId = $order->id;
        $this->deliveryPhotos = [];
        $this->resetValidation();
    }

    public function cancelDelivery(): void
    {
        $this->reset('deliveryOrderId', 'deliveryPhotos');
        $this->resetValidation();
    }

    public function completeDelivery(OrderService $orders, ImageService $images): void
    {
        abort_unless(auth()->user()?->hasRole(Role::COURIER), 403);
        abort_unless($this->deliveryOrderId, 403, 'اختر الطلب المراد تأكيد تسليمه.');

        $order = Order::query()
            ->where('courier_id', auth()->id())
            ->findOrFail($this->deliveryOrderId);
        abort_unless($order->status === OrderStatus::Shipped, 403, 'لا يمكن تأكيد تسليم هذا الطلب.');

        $this->validate([
            'deliveryPhotos' => ['array', 'max:3'],
            'deliveryPhotos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'deliveryPhotos.max' => 'يمكن إرفاق 3 صور كحد أقصى.',
            'deliveryPhotos.*.image' => 'يجب أن يكون الملف صورة صالحة.',
            'deliveryPhotos.*.mimes' => 'الصيغ المدعومة هي JPG وPNG وWEBP.',
            'deliveryPhotos.*.max' => 'يجب ألا يتجاوز حجم الصورة 5 ميجابايت.',
        ]);

        $storedPhotos = [];

        try {
            foreach ($this->deliveryPhotos as $photo) {
                $storedPhotos[] = $images->store(
                    $photo,
                    "delivery-proofs/{$order->id}",
                    disk: 'private_documents',
                );
            }

            $orders->changeStatus(
                $order,
                OrderStatus::Delivered,
                auth()->user(),
                'تم تأكيد التسليم بواسطة مندوب التوصيل',
                $storedPhotos,
            );
        } catch (Throwable $exception) {
            if ($order->status !== OrderStatus::Delivered || $order->delivery_photos !== $storedPhotos) {
                Storage::disk('private_documents')->delete(
                    collect($storedPhotos)->flatMap(fn (array $photo) => [$photo['path'], $photo['thumb_path']])->all(),
                );
            }

            throw $exception;
        }

        $this->cancelDelivery();
        $this->dispatch('notify', message: 'تم تأكيد تسليم الطلب.', type: 'success');
    }

    public function render()
    {
        $activeStatuses = $this->activeStatuses();
        $columns = [
            'id', 'order_number', 'customer_name', 'customer_phone', 'shipping_address',
            'delivery_latitude', 'delivery_longitude', 'tracking_number', 'shipping_method_name',
            'payment_method', 'payment_status', 'subtotal', 'discount_total', 'tax_total',
            'customer_note', 'shipping_cost', 'grand_total', 'status', 'placed_at',
            'delivered_at', 'delivery_photos',
        ];
        $items = 'items:id,order_id,name,sku,variant_label,quantity,line_total';
        $activeOrders = Order::query()->with($items)->where('courier_id', auth()->id())
            ->whereIn('status', $activeStatuses)
            ->orderBy('placed_at')
            ->orderBy('id')
            ->get($columns);
        $availableOrders = Order::query()->with($items)
            ->whereNull('courier_id')
            ->whereIn('status', $activeStatuses)
            ->orderBy('placed_at')
            ->orderBy('id')
            ->paginate(20, $columns);
        $availableOrders->getCollection()->each(function (Order $order): void {
            $order->distance_km = $this->distanceInKilometers($order);
        });
        $activeOrders->each(function (Order $order): void {
            $order->distance_km = $this->distanceInKilometers($order);
        });
        $awaitingOrders = $activeOrders->filter(fn (Order $order): bool => in_array(
            $order->status,
            [OrderStatus::Pending, OrderStatus::Confirmed],
            true,
        ))->values();
        $readyOrders = $activeOrders->filter(fn (Order $order): bool => $order->status === OrderStatus::Processing)->values();
        $inDeliveryOrders = $activeOrders->filter(fn (Order $order): bool => $order->status === OrderStatus::Shipped)->values();
        $completedOrders = Order::query()->with($items)->where('courier_id', auth()->id())
            ->where('status', OrderStatus::Delivered)
            ->latest('placed_at')->limit(20)
            ->get($columns);

        return view('livewire.courier.orders', compact(
            'activeOrders',
            'availableOrders',
            'awaitingOrders',
            'readyOrders',
            'inDeliveryOrders',
            'completedOrders',
        ))
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
