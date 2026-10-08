<div class="space-y-4">
    <div class="card flex flex-wrap items-end gap-3 p-4">
        <div class="min-w-48 flex-1"><label class="label" for="s">بحث (رقم الطلب / اسم العميل / بريد / جوال)</label><input id="s" type="search" wire:model.live.debounce.400ms="search" class="input" placeholder="ابحث…"></div>
        <div><label class="label" for="st">الحالة</label>
            <select id="st" wire:model.live="status" class="input !w-auto"><option value="">الكل</option>@foreach(\App\Enums\OrderStatus::cases() as $s)<option value="{{ $s->value }}">{{ $s->label() }}</option>@endforeach</select></div>
        <div><label class="label" for="pm">الدفع</label>
            <select id="pm" wire:model.live="payment" class="input !w-auto"><option value="">الكل</option>@foreach(\App\Enums\PaymentStatus::cases() as $s)<option value="{{ $s->value }}">{{ $s->label() }}</option>@endforeach</select></div>
        <div><label class="label" for="f">من تاريخ</label><input id="f" type="date" wire:model.live="from" class="input"></div>
        <div><label class="label" for="t">إلى تاريخ</label><input id="t" type="date" wire:model.live="to" class="input"></div>
        <button type="button" wire:click="resetFilters" class="btn-ghost">مسح</button>
    </div>

    <div class="card overflow-x-auto">
        <table class="w-full">
            <thead class="border-b border-slate-100 dark:border-slate-800">
                <tr><th class="table-th">رقم الطلب</th><th class="table-th">العميل</th><th class="table-th">التاريخ</th><th class="table-th">الحالة</th><th class="table-th">الدفع</th><th class="table-th">الإجمالي</th><th class="table-th"></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800" wire:loading.class="opacity-50">
                @forelse($orders as $order)
                    <tr wire:key="o-{{ $order->id }}">
                        <td class="table-td font-extrabold" dir="ltr"><a href="{{ route('admin.orders.show', $order) }}" class="hover:text-brand-600">{{ $order->order_number }}</a></td>
                        <td class="table-td">{{ $order->customer_name }}<br><span class="text-xs text-slate-500" dir="ltr">{{ $order->customer_email }}</span></td>
                        <td class="table-td whitespace-nowrap">{{ $order->created_at->format('Y/m/d H:i') }}</td>
                        <td class="table-td"><x-status-badge :status="$order->status" /></td>
                        <td class="table-td"><x-status-badge :status="$order->payment_status" /></td>
                        <td class="table-td font-bold">{{ money($order->grand_total) }}</td>
                        <td class="table-td text-end"><a href="{{ route('admin.orders.show', $order) }}" class="btn-outline btn-sm">عرض</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-0"><x-empty-state icon="orders" title="لا توجد طلبات مطابقة" class="!border-0 !shadow-none" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $orders->links() }}
</div>
