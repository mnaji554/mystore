<div class="container-x space-y-6 py-8">
    <div>
        <h1 class="text-2xl font-extrabold">طلبات التوصيل</h1>
        <p class="mt-1 text-sm text-slate-500">اختر الطلبات المتاحة للتوصيل، وتابع المسافة المباشرة إلى موقع التسليم.</p>
    </div>

    <div x-data="courierLocationPicker()"
         @keydown.escape.window="if (mapOpen) closeMap()"
         class="card space-y-4 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="font-bold">موقعك </p>
                <p class="text-sm text-slate-500">استخدم موقع جهازك أو حدّد موقعك يدويًا على الخريطة لعرض المسافات واستلام الطلبات.</p>
                <p x-show="locationMessage" x-text="locationMessage" role="status" class="mt-1 text-sm text-slate-600 dark:text-slate-300"></p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" @click="startGeolocation()" class="btn-outline">تحديث موقعي من الجهاز</button>
                <button type="button" @click="openMap()" class="btn-primary">اختيار الموقع على الخريطة</button>
            </div>
        </div>

        <template x-teleport="body">
            <div x-show="mapOpen" x-cloak x-transition.opacity
                 @click.self="closeMap()"
                 class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/70 p-3 backdrop-blur-sm sm:p-6"
                 role="dialog" aria-modal="true" aria-labelledby="courier-map-title">
                <section class="card relative flex max-h-[92vh] w-full max-w-5xl flex-col gap-4 overflow-y-auto p-4 shadow-2xl sm:p-6">
                    <header class="flex items-start justify-between gap-4">
                        <div>
                            <h2 id="courier-map-title" class="text-lg font-extrabold">تحديد موقعك على الخريطة</h2>
                            <p class="mt-1 text-sm text-slate-500">اضغط على الخريطة أو حرّك العلامة لتحديد موقعك. لا يتطلب هذا الخيار إذن GPS. الخريطة من OpenStreetMap.</p>
                        </div>
                        <button type="button" @click="closeMap()" class="btn-ghost !p-2" aria-label="إغلاق النافذة">
                            <x-icon name="close" />
                        </button>
                    </header>

                    <div x-ref="map" wire:ignore dir="ltr" class="h-[55vh] min-h-80 max-h-[34rem] rounded-xl bg-slate-100 dark:bg-slate-800"></div>
                    <p x-show="latitude !== null" class="text-sm font-semibold text-slate-600 dark:text-slate-300" dir="ltr">
                        <span x-text="latitude?.toFixed(6)"></span>,
                        <span x-text="longitude?.toFixed(6)"></span>
                    </p>
                    <p x-show="locationMessage" x-text="locationMessage" role="status" class="text-sm font-bold text-rose-600"></p>
                    <footer class="flex justify-end gap-2">
                        <button type="button" @click="closeMap()" class="btn-ghost">إلغاء</button>
                        <button type="button" @click="confirmLocation()" :disabled="latitude === null" class="btn-primary">
                            تأكيد موقعي
                        </button>
                    </footer>
                </section>
            </div>
        </template>

    </div>

    <section class="space-y-3">
        <h2 class="text-lg font-extrabold">طلبات متاحة للاستلام ({{ $availableOrders->total() }})</h2>
        @forelse($availableOrders as $order)
            <article wire:key="available-{{ $order->id }}" class="card space-y-4 p-5">
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
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3 text-sm dark:border-slate-800">
                    <span>رسوم التوصيل: <b>{{ money($order->shipping_cost) }}</b></span>
                    <span>الإجمالي: <b>{{ money($order->grand_total) }}</b></span>
                    <button type="button" wire:click="claim({{ $order->id }})" wire:loading.attr="disabled" wire:target="claim" @disabled($courierLatitude === null || $courierLongitude === null) class="btn-primary">
                        استلام الطلب
                    </button>
                </div>
            </article>
        @empty
            <x-empty-state icon="truck" title="لا توجد طلبات متاحة حالياً" text="ستظهر هنا الطلبات النشطة غير المسندة إلى مندوب." />
        @endforelse
        @if($availableOrders->hasPages())
            <div>{{ $availableOrders->links() }}</div>
        @endif
    </section>

    <section class="space-y-3">
        <h2 class="text-lg font-extrabold">طلباتي النشطة ({{ $activeOrders->count() }})</h2>
        @forelse($activeOrders as $order)
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
                    @if($order->tracking_number)<p class="mt-1 text-slate-500">رقم التتبع: {{ $order->tracking_number }}</p>@endif
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3 text-sm dark:border-slate-800">
                    <span>رسوم التوصيل: <b>{{ money($order->shipping_cost) }}</b></span>
                    <span>الإجمالي: <b>{{ money($order->grand_total) }}</b></span>
                    @if(in_array($order->status, [\App\Enums\OrderStatus::Processing, \App\Enums\OrderStatus::Shipped], true))
                        <button type="button" wire:click="advance({{ $order->id }})" wire:loading.attr="disabled" wire:target="advance" class="btn-primary">
                            {{ $order->status === \App\Enums\OrderStatus::Processing ? 'تأكيد استلام الشحنة' : 'تأكيد التسليم' }}
                        </button>
                    @else
                        <span class="text-slate-500">بانتظار تجهيز الطلب من المتجر</span>
                    @endif
                </div>
            </article>
        @empty
            <x-empty-state icon="truck" title="لا توجد طلبات مسندة إليك" text="استلم طلباً من القائمة أعلاه ليظهر هنا." />
        @endforelse
    </section>

    @if($completedOrders->isNotEmpty())
        <section class="space-y-3">
            <h2 class="text-lg font-extrabold">تم تسليمها مؤخراً</h2>
            <div class="card divide-y divide-slate-100 dark:divide-slate-800">
                @foreach($completedOrders as $order)
                    <div wire:key="done-{{ $order->id }}" class="flex flex-wrap items-center justify-between gap-3 p-4 text-sm">
                        <span class="font-bold" dir="ltr">{{ $order->order_number }}</span>
                        <span>{{ $order->customer_name }}</span>
                        <x-status-badge :status="$order->status" />
                        <span class="text-slate-500">{{ $order->placed_at?->format('Y/m/d') }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
