<x-layouts.app title="تم استلام طلبك" :noindex="true">
    <div class="container-x max-w-3xl py-12">
        <div class="card animate-slide-up p-8 text-center">
            <span class="mx-auto grid h-20 w-20 place-items-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15"><x-icon name="check-circle" class="h-12 w-12" /></span>
            <h1 class="mt-5 text-2xl font-extrabold sm:text-3xl">شكراً لك! تم استلام طلبك</h1>
            <p class="mt-2 text-slate-500">رقم طلبك: <b class="text-slate-900 dark:text-white" dir="ltr">{{ $order->order_number }}</b></p>
            <p class="mt-1 text-sm text-slate-500">أرسلنا تفاصيل الطلب إلى <span dir="ltr">{{ $order->customer_email }}</span></p>

            <div class="mx-auto mt-6 max-w-md space-y-2 rounded-xl bg-slate-50 p-5 text-start text-sm dark:bg-slate-800/50">
                <div class="flex justify-between"><span class="text-slate-500">طريقة الدفع</span><span class="font-bold">{{ app(\App\Services\PaymentService::class)->gateways()->get($order->payment_method)?->label() }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">حالة الدفع</span><x-status-badge :status="$order->payment_status" /></div>
                <div class="flex justify-between"><span class="text-slate-500">الشحن</span><span class="font-bold">{{ $order->shipping_method_name }}</span></div>
                <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-extrabold dark:border-slate-700"><span>الإجمالي</span><span>{{ money($order->grand_total) }}</span></div>
            </div>

            <div class="mt-7 flex flex-wrap justify-center gap-3">
                @auth
                    <a href="{{ route('account.orders.show', $order->order_number) }}" class="btn-primary">عرض الطلب</a>
                @else
                    <a href="{{ route('orders.track') }}" class="btn-primary">تتبع الطلب</a>
                @endauth
                <a href="{{ route('products.index') }}" class="btn-outline">متابعة التسوق</a>
            </div>
        </div>
    </div>
</x-layouts.app>
