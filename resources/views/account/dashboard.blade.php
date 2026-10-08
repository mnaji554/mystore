<x-account-layout title="لوحة الحساب" :heading="'مرحباً، '.$user->name">
    <div class="grid gap-4 sm:grid-cols-3">
        <a href="{{ route('account.orders') }}" class="card p-5 transition hover:shadow-md"><p class="text-3xl font-extrabold text-brand-600">{{ $ordersCount }}</p><p class="text-sm text-slate-500">طلباتي</p></a>
        <a href="{{ route('account.addresses') }}" class="card p-5 transition hover:shadow-md"><p class="text-3xl font-extrabold text-brand-600">{{ $addressesCount }}</p><p class="text-sm text-slate-500">عناويني</p></a>
        <a href="{{ route('account.reviews') }}" class="card p-5 transition hover:shadow-md"><p class="text-3xl font-extrabold text-brand-600">{{ $reviewsCount }}</p><p class="text-sm text-slate-500">تقييماتي</p></a>
    </div>

    <div class="card mt-6 overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 p-4 dark:border-slate-800">
            <h2 class="font-extrabold">آخر الطلبات</h2>
            <a href="{{ route('account.orders') }}" class="text-sm font-bold text-brand-600">عرض الكل</a>
        </div>
        @forelse($recentOrders as $order)
            <a href="{{ route('account.orders.show', $order->order_number) }}" class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 p-4 text-sm last:border-0 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800/50">
                <span class="font-extrabold" dir="ltr">{{ $order->order_number }}</span>
                <span class="text-slate-500">{{ $order->created_at->format('Y/m/d') }}</span>
                <x-status-badge :status="$order->status" />
                <span class="font-bold">{{ money($order->grand_total) }}</span>
            </a>
        @empty
            <p class="p-8 text-center text-sm text-slate-500">لا توجد طلبات بعد.</p>
        @endforelse
    </div>
</x-account-layout>
