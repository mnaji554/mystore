<x-layouts.app title="لم يكتمل الدفع" :noindex="true">
    <div class="container-x max-w-2xl py-12">
        <div class="card p-8 text-center">
            <span class="mx-auto grid h-20 w-20 place-items-center rounded-full bg-amber-100 text-amber-600 dark:bg-amber-500/15"><x-icon name="warning" class="h-12 w-12" /></span>
            <h1 class="mt-5 text-2xl font-extrabold">لم تكتمل عملية الدفع</h1>
            <p class="mt-2 text-slate-500">تم حفظ طلبك رقم <b dir="ltr">{{ $order->order_number }}</b> لكن الدفع لم يكتمل. يمكنك إعادة المحاولة.</p>
            <div class="mt-7 flex flex-wrap justify-center gap-3">
                @if($order->payment_method === 'stripe' && $order->payment_status !== \App\Enums\PaymentStatus::Paid)
                    <form method="POST" action="{{ route('checkout.retry', $order->order_number) }}">@csrf<button class="btn-primary">إعادة محاولة الدفع</button></form>
                @endif
                <a href="{{ route('contact') }}" class="btn-outline">تواصل مع الدعم</a>
            </div>
        </div>
    </div>
</x-layouts.app>
