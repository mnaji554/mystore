<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.orders.index') }}" class="btn-ghost btn-sm"><x-icon name="chevron-right" class="h-4 w-4" /> الطلبات</a>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.orders.invoice', $order) }}" target="_blank" class="btn-outline btn-sm"><x-icon name="printer" class="h-4 w-4" /> طباعة الفاتورة</a>
            <a href="{{ route('admin.orders.invoice.pdf', $order) }}" class="btn-outline btn-sm"><x-icon name="download" class="h-4 w-4" /> تحميل PDF</a>
            @can('update', $order)<button type="button" wire:click="resendNotification" wire:loading.attr="disabled" class="btn-outline btn-sm"><x-icon name="mail" class="h-4 w-4" /> إرسال إشعار للعميل</button>@endcan
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            <div class="card flex flex-wrap items-center justify-between gap-3 p-5">
                <div><p class="text-xl font-extrabold" dir="ltr">{{ $order->order_number }}</p><p class="text-sm text-slate-500">{{ $order->placed_at?->format('Y/m/d H:i') }}</p></div>
                <div class="flex items-center gap-2"><x-status-badge :status="$order->status" class="!px-3 !py-1 !text-sm" /><x-status-badge :status="$order->payment_status" /></div>
            </div>

            <div class="card overflow-x-auto">
                <table class="w-full">
                    <thead class="border-b border-slate-100 dark:border-slate-800"><tr><th class="table-th">المنتج</th><th class="table-th">SKU</th><th class="table-th">السعر</th><th class="table-th">الكمية</th><th class="table-th">الإجمالي</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($order->items as $item)
                            <tr wire:key="it-{{ $item->id }}">
                                <td class="table-td font-bold">{{ $item->name }} @if($item->variant_label)<span class="text-xs font-normal text-slate-500">({{ $item->variant_label }})</span>@endif</td>
                                <td class="table-td" dir="ltr">{{ $item->sku }}</td>
                                <td class="table-td">{{ money($item->sale_price) }}</td>
                                <td class="table-td">{{ $item->quantity }}</td>
                                <td class="table-td font-bold">{{ money($item->line_total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="card p-5">
                <h2 class="mb-3 font-extrabold">سجل الحالات</h2>
                <ol class="space-y-3 border-s-2 border-slate-200 ps-4 text-sm dark:border-slate-700">
                    @foreach($order->histories as $h)
                        <li wire:key="h-{{ $h->id }}" class="relative"><span class="absolute -start-[1.4rem] top-1.5 h-2.5 w-2.5 rounded-full bg-brand-600"></span>
                            @if($h->from_status)<span class="text-slate-500">{{ $h->from_status->label() }} ←</span> @endif<b>{{ $h->to_status->label() }}</b>
                            <span class="text-xs text-slate-500">· {{ $h->created_at->format('Y/m/d H:i') }} · {{ $h->user?->name ?? 'النظام / العميل' }}</span>
                            @if($h->note)<p class="text-slate-500">{{ $h->note }}</p>@endif
                        </li>
                    @endforeach
                </ol>
            </div>

            <div class="card p-5">
                <h2 class="mb-3 font-extrabold">المدفوعات</h2>
                @forelse($order->payments as $payment)
                    <div wire:key="pay-{{ $payment->id }}" class="mb-3 rounded-xl bg-slate-50 p-3 text-sm dark:bg-slate-800/50">
                        <div class="flex flex-wrap items-center justify-between gap-2"><b>{{ $payment->gateway }}</b><x-status-badge :status="$payment->status" /><span>{{ money($payment->amount) }}</span></div>
                        @if($payment->reference)<p class="mt-1 text-xs text-slate-500" dir="ltr">{{ $payment->reference }}</p>@endif
                        @foreach($payment->transactions as $tx)<p class="mt-1 text-xs text-slate-500">{{ $tx->created_at->format('Y/m/d H:i') }} · {{ $tx->type }} · {{ $tx->status }}</p>@endforeach
                    </div>
                @empty
                    <p class="text-sm text-slate-500">لا توجد مدفوعات.</p>
                @endforelse
            </div>
        </div>

        <div class="space-y-5">
            @can('update', $order)
                <div class="card space-y-3 p-5">
                    <h2 class="font-extrabold">تغيير الحالة</h2>
                    @if(count($transitions))
                        <form wire:submit="changeStatus" class="space-y-3">
                            <select wire:model="newStatus" class="input" aria-label="الحالة الجديدة">
                                <option value="">— اختر —</option>
                                @foreach($transitions as $t)<option value="{{ $t->value }}">{{ $t->label() }}</option>@endforeach
                            </select>
                            @error('newStatus')<p class="text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                            <textarea wire:model="note" rows="2" class="input" placeholder="ملاحظة (اختياري)"></textarea>
                            <button class="btn-primary w-full" wire:loading.attr="disabled" wire:target="changeStatus">تحديث الحالة</button>
                        </form>
                    @else
                        <p class="text-sm text-slate-500">لا توجد انتقالات متاحة من هذه الحالة.</p>
                    @endif
                </div>

                <form wire:submit="saveTracking" class="card space-y-3 p-5">
                    <h2 class="font-extrabold">التتبع</h2>
                    <x-field label="رقم التتبع" model="trackingNumber" dir="ltr" />
                    <x-field label="رابط التتبع" model="trackingUrl" dir="ltr" placeholder="https://" hint="يُولَّد تلقائياً من شركة الشحن إن وُجد قالب." />
                    <button class="btn-secondary w-full">حفظ</button>
                </form>
            @endcan

            <div class="card space-y-2 p-5 text-sm">
                <h2 class="mb-1 font-extrabold">العميل</h2>
                @if($order->user)<a href="{{ route('admin.customers.show', $order->user) }}" class="font-bold text-brand-600">{{ $order->customer_name }}</a>@else<p class="font-bold">{{ $order->customer_name }} <span class="badge badge-slate">زائر</span></p>@endif
                <p class="text-slate-500" dir="ltr" style="text-align:right">{{ $order->customer_email }}</p>
                <p class="text-slate-500" dir="ltr" style="text-align:right">{{ $order->customer_phone }}</p>
                <hr class="border-slate-100 dark:border-slate-800">
                <p class="font-bold">عنوان الشحن</p>
                <p class="text-slate-500">{{ $order->shipping_address['full_name'] ?? '' }}<br>{{ $order->address_line }}</p>
                <p class="text-slate-500">الشحن: {{ $order->shipping_method_name }}</p>
                @if($order->customer_note)<p class="rounded-lg bg-amber-50 p-2 text-xs dark:bg-amber-500/10">ملاحظة العميل: {{ $order->customer_note }}</p>@endif
            </div>

            <div class="card space-y-2 p-5 text-sm">
                <h2 class="mb-1 font-extrabold">المبالغ</h2>
                <div class="flex justify-between"><span class="text-slate-500">المجموع الفرعي</span><span>{{ money($order->subtotal) }}</span></div>
                @if($order->discount_total > 0)<div class="flex justify-between text-emerald-600"><span>الخصم</span><span>-{{ money($order->discount_total) }}</span></div>@endif
                @if($order->coupon_discount > 0)<div class="flex justify-between text-emerald-600"><span>الكوبون ({{ $order->coupon_code }})</span><span>-{{ money($order->coupon_discount) }}</span></div>@endif
                <div class="flex justify-between"><span class="text-slate-500">الشحن</span><span>{{ money($order->shipping_cost) }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">الضريبة</span><span>{{ money($order->tax_total) }}</span></div>
                <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-extrabold dark:border-slate-800"><span>الإجمالي</span><span>{{ money($order->grand_total) }}</span></div>
            </div>
        </div>
    </div>
</div>
