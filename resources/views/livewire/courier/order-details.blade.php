<details class="rounded-xl border border-slate-200 dark:border-slate-800">
    <summary class="cursor-pointer px-4 py-3 text-sm font-bold">تفاصيل الطلب والمنتجات ({{ $order->items->count() }})</summary>
    <div class="space-y-3 border-t border-slate-100 px-4 py-3 text-sm dark:border-slate-800">
        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            @foreach($order->items as $item)
                <div wire:key="order-item-{{ $item->id }}" class="flex flex-wrap items-center justify-between gap-2 py-2">
                    <span>{{ $item->name }}{{ $item->variant_label ? ' - '.$item->variant_label : '' }} × {{ $item->quantity }}</span>
                    <span class="font-semibold">{{ money($item->line_total) }}</span>
                </div>
            @endforeach
        </div>
        <div class="grid gap-2 text-slate-600 dark:text-slate-300 sm:grid-cols-2">
            <p>طريقة الدفع: <span class="font-semibold">{{ $order->payment_method }}</span></p>
            <p>حالة الدفع: <span class="font-semibold">{{ $order->payment_status->value }}</span></p>
            <p>المجموع الفرعي: <span class="font-semibold">{{ money($order->subtotal) }}</span></p>
            <p>الخصم: <span class="font-semibold">{{ money($order->discount_total) }}</span></p>
            <p>الضريبة: <span class="font-semibold">{{ money($order->tax_total) }}</span></p>
            <p>طريقة الشحن: <span class="font-semibold">{{ $order->shipping_method_name ?: '—' }}</span></p>
        </div>
        @if($order->customer_note)
            <p class="rounded-lg bg-amber-50 p-3 text-amber-900 dark:bg-amber-950/30 dark:text-amber-200">ملاحظة العميل: {{ $order->customer_note }}</p>
        @endif
    </div>
</details>
