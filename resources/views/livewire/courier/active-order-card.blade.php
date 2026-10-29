<article wire:key="delivery-{{ $order->id }}" class="card space-y-4 p-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="font-extrabold" dir="ltr">{{ $order->order_number }}</p>
            <p class="text-sm text-slate-500">{{ $order->customer_name }} · <span dir="ltr">{{ $order->customer_phone }}</span></p>
        </div>
        <x-status-badge :status="$order->status" />
    </div>
    <div class="text-sm">
        <p class="font-bold">عنوان التوصيل</p>
        <p class="text-slate-600 dark:text-slate-300">{{ $order->address_line }}</p>
        @if($order->distance_km !== null)
            <p class="mt-2 font-bold text-brand-700 dark:text-brand-300">المسافة المباشرة: {{ number_format($order->distance_km, 1) }} كم</p>
        @elseif($courierLatitude === null || $courierLongitude === null)
            <p class="mt-2 text-slate-500">حدّث موقعك لعرض المسافة.</p>
        @else
            <p class="mt-2 text-slate-500">المسافة غير متاحة لهذا الطلب لعدم توفر إحداثيات التسليم.</p>
        @endif
        @if($order->tracking_number)
            <p class="mt-1 text-slate-500">رقم التتبع: {{ $order->tracking_number }}</p>
        @endif
    </div>
    @include('livewire.courier.order-details', ['order' => $order])
    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3 text-sm dark:border-slate-800">
        <span>رسوم التوصيل: <b>{{ money($order->shipping_cost) }}</b></span>
        <span>الإجمالي: <b>{{ money($order->grand_total) }}</b></span>
        @if($order->status === \App\Enums\OrderStatus::Processing)
            <button type="button" wire:click="advance({{ $order->id }})" wire:loading.attr="disabled" wire:target="advance" class="btn-primary">
                تأكيد استلام الطلب من المتجر
            </button>
        @elseif($order->status === \App\Enums\OrderStatus::Shipped)
            <button type="button" wire:click="prepareDelivery({{ $order->id }})" wire:loading.attr="disabled" wire:target="prepareDelivery" class="btn-primary">
                تأكيد التسليم
            </button>
        @else
            <span class="text-slate-500">بانتظار تجهيز الطلب من المتجر</span>
        @endif
    </div>
    @if($deliveryOrderId === $order->id)
        <div class="space-y-3 rounded-xl border border-brand-200 bg-brand-50/60 p-4 dark:border-brand-900 dark:bg-brand-950/20">
            <div>
                <label for="delivery-photos-{{ $order->id }}" class="font-bold">صور إثبات التسليم (اختياري، حتى 3 صور)</label>
                <input id="delivery-photos-{{ $order->id }}" type="file" wire:model="deliveryPhotos" multiple accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full text-sm file:me-3 file:rounded-lg file:border-0 file:bg-brand-100 file:px-3 file:py-2 file:font-bold file:text-brand-700">
                @error('deliveryPhotos')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                @error('deliveryPhotos.*')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" wire:click="completeDelivery" wire:loading.attr="disabled" wire:target="completeDelivery,deliveryPhotos" class="btn-primary">
                    تأكيد إتمام التسليم
                </button>
                <button type="button" wire:click="cancelDelivery" wire:loading.attr="disabled" class="btn-ghost">إلغاء</button>
            </div>
        </div>
    @endif
</article>
