<div class="container-x space-y-6 py-8">
    <div>
        <h1 class="text-2xl font-extrabold">طلبات التوصيل</h1>
        <p class="mt-1 text-sm text-slate-500">حدّث حالة الطلب عند استلامه ثم عند تسليمه للعميل.</p>
    </div>

    <section class="space-y-3">
        <h2 class="text-lg font-extrabold">الطلبات النشطة ({{ $activeOrders->count() }})</h2>
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
                    @if($order->tracking_number)<p class="mt-1 text-slate-500">رقم التتبع: {{ $order->tracking_number }}</p>@endif
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3 text-sm dark:border-slate-800">
                    <span>رسوم التوصيل: <b>{{ money($order->shipping_cost) }}</b></span>
                    <span>الإجمالي: <b>{{ money($order->grand_total) }}</b></span>
                    <button type="button" wire:click="advance({{ $order->id }})" wire:loading.attr="disabled" class="btn-primary">
                        {{ $order->status === \App\Enums\OrderStatus::Processing ? 'تأكيد استلام الشحنة' : 'تأكيد التسليم' }}
                    </button>
                </div>
            </article>
        @empty
            <x-empty-state icon="truck" title="لا توجد طلبات مسندة إليك" text="ستظهر هنا الطلبات التي يسنِدها المتجر إلى حسابك." />
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
