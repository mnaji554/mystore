<div class="container-x space-y-6 py-8">
    <div>
        <h1 class="text-2xl font-extrabold">طلبات التوصيل</h1>
        <p class="mt-1 text-sm text-slate-500">اختر الطلبات المتاحة للتوصيل، وتابع المسافة المباشرة إلى موقع التسليم.</p>
    </div>

    <div x-data="{
        locationMessage: '',
        startGeolocation() {
            this.locationMessage = 'جارٍ تحديد موقعك...';
            $wire.clearCourierLocation().then(() => {
                if (!navigator.geolocation) {
                    this.locationMessage = 'متصفحك لا يدعم تحديد الموقع. استخدم متصفحاً حديثاً واتصالاً آمناً.';
                    return;
                }

                navigator.geolocation.getCurrentPosition(async (position) => {
                    try {
                        await $wire.setCourierLocation(position.coords.latitude, position.coords.longitude);
                        this.locationMessage = 'تم تحديث موقعك.';
                    } catch {
                        this.locationMessage = 'تعذر التحقق من الموقع. يرجى المحاولة مرة أخرى.';
                    }
                }, (error) => {
                    this.locationMessage = error.code === 1
                        ? 'يرجى السماح بالوصول إلى الموقع لعرض المسافات واستلام الطلبات.'
                        : 'تعذر تحديد موقعك. تحقق من إعدادات الجهاز وحاول مجدداً.';
                }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
            });
        }
    }" class="card flex flex-wrap items-center justify-between gap-3 p-4">
        <div>
            <p class="font-bold">موقعك الحالي</p>
            <p class="text-sm text-slate-500">يُستخدم موقعك لحساب المسافة ولا يُحفظ في حسابك.</p>
            <p x-show="locationMessage" x-text="locationMessage" class="mt-1 text-sm text-slate-600 dark:text-slate-300"></p>
        </div>
        <button type="button" @click="startGeolocation()" class="btn-outline">تحديث موقعي</button>
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
