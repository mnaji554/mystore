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

    <div x-data="{ activeTab: 'available' }" class="space-y-4">
        <nav class="card flex gap-2 overflow-x-auto p-2" role="tablist" aria-label="حالات الطلبات">
            @foreach([
                'available' => ['متاحة للاستلام', $availableOrders->total()],
                'awaiting' => ['بانتظار تجهيز المتجر', $awaitingOrders->count()],
                'ready' => ['جاهزة للاستلام من المتجر', $readyOrders->count()],
                'delivery' => ['قيد التوصيل', $inDeliveryOrders->count()],
                'completed' => ['تم التسليم', $completedOrders->count()],
            ] as $tab => [$label, $count])
                <button type="button" @click="activeTab = '{{ $tab }}'" :aria-selected="activeTab === '{{ $tab }}'" :class="activeTab === '{{ $tab }}' ? 'border-brand-600 bg-brand-50 text-brand-700 dark:bg-brand-950/30 dark:text-brand-300' : 'border-transparent text-slate-500 hover:bg-slate-50 dark:text-slate-400 dark:hover:bg-slate-800'" class="flex shrink-0 items-center gap-2 rounded-lg border px-4 py-3 text-sm font-bold transition" role="tab">
                    <span>{{ $label }}</span>
                    <span class="rounded-full bg-white px-2 py-0.5 text-xs dark:bg-slate-900">{{ $count }}</span>
                </button>
            @endforeach
        </nav>

        <section x-show="activeTab === 'available'" x-cloak class="space-y-3" aria-label="الطلبات المتاحة للاستلام">
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
                @include('livewire.courier.order-details', ['order' => $order])
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

        @foreach([
            'awaiting' => ['بانتظار تجهيز المتجر', $awaitingOrders, 'لا توجد طلبات بانتظار التجهيز.'],
            'ready' => ['جاهزة للاستلام من المتجر', $readyOrders, 'لا توجد طلبات جاهزة للاستلام حالياً.'],
            'delivery' => ['قيد التوصيل', $inDeliveryOrders, 'لا توجد طلبات قيد التوصيل حالياً.'],
        ] as $tab => [$heading, $orders, $emptyText])
            <section x-show="activeTab === '{{ $tab }}'" x-cloak class="space-y-3" role="tabpanel">
                <h2 class="text-lg font-extrabold">{{ $heading }} ({{ $orders->count() }})</h2>
                @forelse($orders as $order)
                    @include('livewire.courier.active-order-card', ['order' => $order])
                @empty
                    <x-empty-state icon="truck" :title="$emptyText" text="ستظهر الطلبات هنا عند انتقالها إلى هذه المرحلة." />
                @endforelse
            </section>
        @endforeach

        <section x-show="activeTab === 'completed'" x-cloak class="space-y-3" role="tabpanel" aria-label="الطلبات المسلّمة">
            <h2 class="text-lg font-extrabold">تم تسليمها مؤخراً</h2>
            <div class="card divide-y divide-slate-100 dark:divide-slate-800">
                @foreach($completedOrders as $order)
                    <div wire:key="done-{{ $order->id }}" class="flex flex-wrap items-center justify-between gap-3 p-4 text-sm">
                        <span class="font-bold" dir="ltr">{{ $order->order_number }}</span>
                        <span>{{ $order->customer_name }}</span>
                        <x-status-badge :status="$order->status" />
                        <span class="text-slate-500">تم التسليم: {{ $order->delivered_at?->format('Y/m/d H:i') ?? '—' }}</span>
                        @include('livewire.courier.order-details', ['order' => $order])
                        @if($order->delivery_photos)
                            <div class="flex w-full flex-wrap gap-3">
                                @foreach($order->delivery_photos as $index => $photo)
                                    <a wire:key="proof-{{ $order->id }}-{{ $index }}" href="{{ route('courier.orders.proof', ['order' => $order->id, 'index' => $index, 'variant' => 'image']) }}" target="_blank" rel="noopener" class="block">
                                        <img src="{{ route('courier.orders.proof', ['order' => $order->id, 'index' => $index, 'variant' => 'thumb']) }}" alt="صورة إثبات التسليم للطلب {{ $order->order_number }}" loading="lazy" class="h-20 w-20 rounded-lg border border-slate-200 object-cover dark:border-slate-700">
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            @if($completedOrders->isEmpty())
                <x-empty-state icon="truck" title="لا توجد طلبات تم تسليمها بعد" text="ستظهر هنا الطلبات بعد تأكيد تسليمها." />
            @endif
        </section>
    </div>
</div>
