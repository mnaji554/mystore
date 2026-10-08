<x-account-layout title="طلباتي">
    @if($orders->isEmpty())
        <x-empty-state icon="orders" title="لا توجد طلبات سابقة" text="عندما تُتم أول عملية شراء ستظهر هنا.">
            <a href="{{ route('products.index') }}" class="btn-primary">ابدأ التسوق</a>
        </x-empty-state>
    @else
        <div class="card overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-slate-100 dark:border-slate-800">
                    <tr><th class="table-th">رقم الطلب</th><th class="table-th">التاريخ</th><th class="table-th">المنتجات</th><th class="table-th">الحالة</th><th class="table-th">الإجمالي</th><th class="table-th"></th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($orders as $order)
                        <tr>
                            <td class="table-td font-extrabold" dir="ltr">{{ $order->order_number }}</td>
                            <td class="table-td">{{ $order->created_at->format('Y/m/d') }}</td>
                            <td class="table-td">{{ $order->items->sum('quantity') }}</td>
                            <td class="table-td"><x-status-badge :status="$order->status" /></td>
                            <td class="table-td font-bold">{{ money($order->grand_total) }}</td>
                            <td class="table-td text-end"><a href="{{ route('account.orders.show', $order->order_number) }}" class="btn-outline btn-sm">التفاصيل</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $orders->links() }}</div>
    @endif
</x-account-layout>
