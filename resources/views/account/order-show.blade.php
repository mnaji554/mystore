@php
    use App\Enums\OrderStatus;
    $flow = [OrderStatus::Pending, OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered];
    $currentIndex = array_search($order->status, $flow, true);
@endphp
<x-account-layout :title="'طلب '.$order->order_number" :heading="'تفاصيل الطلب '.$order->order_number">
    <div class="space-y-5">
        <div class="card flex flex-wrap items-center justify-between gap-3 p-5">
            <div class="flex items-center gap-3">
                <x-status-badge :status="$order->status" class="!px-3 !py-1 !text-sm" />
                <x-status-badge :status="$order->payment_status" />
                <span class="text-sm text-slate-500">{{ $order->placed_at?->format('Y/m/d H:i') }}</span>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('account.orders.invoice', $order->order_number) }}" class="btn-outline btn-sm"><x-icon name="download" class="h-4 w-4" /> الفاتورة PDF</a>
                @can('cancel', $order)
                    <form method="POST" action="{{ route('account.orders.cancel', $order->order_number) }}" onsubmit="return confirm('هل أنت متأكد من إلغاء الطلب؟')">
                        @csrf
                        <button class="btn-outline btn-sm !text-rose-600">إلغاء الطلب</button>
                    </form>
                @endcan
            </div>
        </div>

        @if($currentIndex !== false)
            <div class="card p-5">
                <ol class="flex items-center justify-between text-center text-xs font-bold">
                    @foreach($flow as $i => $s)
                        <li class="flex flex-1 flex-col items-center gap-1.5 {{ $i <= $currentIndex ? 'text-brand-700 dark:text-brand-300' : 'text-slate-400' }}">
                            <span class="grid h-9 w-9 place-items-center rounded-full {{ $i <= $currentIndex ? 'bg-brand-600 text-white' : 'bg-slate-200 dark:bg-slate-800' }}">{{ $i < $currentIndex ? '✓' : $i + 1 }}</span>
                            {{ $s->label() }}
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif

        @if($order->tracking_number)
            <div class="card flex flex-wrap items-center justify-between gap-2 p-5 text-sm">
                <span><x-icon name="truck" class="me-1 inline h-5 w-5 text-brand-600" /> رقم التتبع: <b dir="ltr">{{ $order->tracking_number }}</b></span>
                @if($order->tracking_url)<a href="{{ $order->tracking_url }}" target="_blank" rel="noopener noreferrer" class="btn-primary btn-sm">تتبع الشحنة</a>@endif
            </div>
        @endif

        <div class="card overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-slate-100 dark:border-slate-800"><tr><th class="table-th">المنتج</th><th class="table-th">السعر</th><th class="table-th">الكمية</th><th class="table-th">الإجمالي</th></tr></thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($order->items as $item)
                        <tr>
                            <td class="table-td"><div class="font-bold">{{ $item->name }}</div>@if($item->variant_label)<div class="text-xs text-slate-500">{{ $item->variant_label }}</div>@endif</td>
                            <td class="table-td">{{ money($item->sale_price) }}</td>
                            <td class="table-td">{{ $item->quantity }}</td>
                            <td class="table-td font-bold">{{ money($item->line_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="grid gap-5 md:grid-cols-2">
            <div class="card space-y-1 p-5 text-sm">
                <h2 class="mb-2 font-extrabold">عنوان الشحن</h2>
                <p>{{ $order->shipping_address['full_name'] ?? $order->customer_name }}</p>
                <p class="text-slate-500">{{ $order->address_line }}</p>
                <p class="text-slate-500" dir="ltr" style="text-align:right">{{ $order->shipping_address['phone'] ?? $order->customer_phone }}</p>
                <p class="pt-2 text-slate-500">الشحن: {{ $order->shipping_method_name }}</p>
                <p class="text-slate-500">الدفع: {{ app(\App\Services\PaymentService::class)->gateways()->get($order->payment_method)?->label() ?? $order->payment_method }}</p>
            </div>
            <div class="card space-y-2 p-5 text-sm">
                <h2 class="mb-2 font-extrabold">ملخص المبالغ</h2>
                <div class="flex justify-between"><span class="text-slate-500">المجموع الفرعي</span><span>{{ money($order->subtotal) }}</span></div>
                @if($order->discount_total > 0)<div class="flex justify-between text-emerald-600"><span>الخصم</span><span>-{{ money($order->discount_total) }}</span></div>@endif
                @if($order->coupon_discount > 0)<div class="flex justify-between text-emerald-600"><span>الكوبون {{ $order->coupon_code }}</span><span>-{{ money($order->coupon_discount) }}</span></div>@endif
                <div class="flex justify-between"><span class="text-slate-500">الشحن</span><span>{{ money($order->shipping_cost) }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">الضريبة</span><span>{{ money($order->tax_total) }}</span></div>
                <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-extrabold dark:border-slate-800"><span>الإجمالي</span><span>{{ money($order->grand_total) }}</span></div>
            </div>
        </div>

        <div class="card p-5">
            <h2 class="mb-3 font-extrabold">سجل الحالة</h2>
            <ol class="space-y-3 border-s-2 border-slate-200 ps-4 text-sm dark:border-slate-700">
                @foreach($order->histories as $h)
                    <li class="relative"><span class="absolute -start-[1.4rem] top-1.5 h-2.5 w-2.5 rounded-full bg-brand-600"></span>
                        <b>{{ $h->to_status->label() }}</b> <span class="text-xs text-slate-500">· {{ $h->created_at->format('Y/m/d H:i') }}</span>
                        @if($h->note)<p class="text-slate-500">{{ $h->note }}</p>@endif
                    </li>
                @endforeach
            </ol>
        </div>

        @if($order->status === OrderStatus::Delivered)
            <div class="card flex flex-wrap items-center justify-between gap-2 p-5 text-sm">
                <span>شاركنا رأيك في المنتجات التي استلمتها:</span>
                <div class="flex flex-wrap gap-2">
                    @foreach($order->items->filter(fn ($i) => $i->product)->unique('product_id') as $item)
                        <a href="{{ route('products.show', $item->product->slug) }}#reviews" class="btn-outline btn-sm">قيّم «{{ \Illuminate\Support\Str::limit($item->name, 20) }}»</a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-account-layout>
