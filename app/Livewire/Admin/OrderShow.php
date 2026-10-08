<?php

namespace App\Livewire\Admin;

use App\Enums\OrderStatus;
use App\Exceptions\StoreException;
use App\Models\Order;
use App\Notifications\OrderStatusNotification;
use App\Services\OrderService;
use App\Services\ShippingService;
use Livewire\Component;

class OrderShow extends Component
{
    public int $orderId;

    public string $newStatus = '';

    public string $note = '';

    public string $trackingNumber = '';

    public string $trackingUrl = '';

    public function mount(Order $order): void
    {
        $this->authorize('view', $order);

        $this->orderId = $order->id;
        $this->trackingNumber = (string) $order->tracking_number;
        $this->trackingUrl = (string) $order->tracking_url;
    }

    private function order(): Order
    {
        return Order::query()->with(['items.product.primaryImage', 'histories.user', 'payments.transactions', 'user', 'shippingMethod.company'])
            ->findOrFail($this->orderId);
    }

    public function changeStatus(OrderService $orders): void
    {
        $order = $this->order();
        $this->authorize('update', $order);

        $this->validate(['newStatus' => ['required'], 'note' => ['nullable', 'string', 'max:500']], ['newStatus.required' => 'اختر الحالة الجديدة.']);

        $status = OrderStatus::tryFrom($this->newStatus);
        if (! $status) {
            $this->addError('newStatus', 'حالة غير صحيحة.');

            return;
        }

        try {
            $orders->changeStatus($order, $status, auth()->user(), $this->note ?: null);
        } catch (StoreException $e) {
            $this->addError('newStatus', $e->getMessage());

            return;
        }

        $this->reset('newStatus', 'note');
        $this->dispatch('notify', message: 'تم تحديث حالة الطلب وإشعار العميل.', type: 'success');
    }

    public function saveTracking(ShippingService $shipping): void
    {
        $order = $this->order();
        $this->authorize('update', $order);

        $this->validate([
            'trackingNumber' => ['nullable', 'string', 'max:120'],
            'trackingUrl' => ['nullable', 'url:http,https', 'max:500'],
        ], ['trackingUrl.url' => 'الرابط غير صحيح.']);

        $shipping->applyTracking($order, $this->trackingNumber ?: null, $this->trackingUrl ?: null);
        $this->trackingUrl = (string) $order->fresh()->tracking_url;
        $this->dispatch('notify', message: 'تم حفظ بيانات التتبع.', type: 'success');
    }

    public function resendNotification(): void
    {
        $order = $this->order();
        $this->authorize('update', $order);

        $type = $order->status === OrderStatus::Pending ? 'created' : $order->status->value;
        $order->customerNotifiable()->notify(new OrderStatusNotification($order, $type));

        $this->dispatch('notify', message: 'تم إرسال الإشعار للعميل.', type: 'success');
    }

    public function render()
    {
        $order = $this->order();

        return view('livewire.admin.order-show', [
            'order' => $order,
            'transitions' => $order->status->allowedTransitions(),
        ])->layout('components.layouts.admin', ['title' => 'طلب '.$order->order_number]);
    }
}
