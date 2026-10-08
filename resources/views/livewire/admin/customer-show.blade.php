<div class="space-y-5">
    <a href="{{ route('admin.customers.index') }}" class="btn-ghost btn-sm"><x-icon name="chevron-right" class="h-4 w-4" /> العملاء</a>

    <div class="flex items-center gap-3">
        <span class="grid h-12 w-12 place-items-center rounded-full bg-brand-600 text-xl font-extrabold text-white">{{ mb_substr($user->name, 0, 1) }}</span>
        <div><h2 class="text-xl font-extrabold">{{ $user->name }}</h2><p class="text-sm text-slate-500" dir="ltr" style="text-align:right">{{ $user->email }} · عضو منذ {{ $user->created_at->format('Y/m/d') }}</p></div>
    </div>

    @if($user->trashed())<p class="rounded-xl bg-rose-50 p-3 text-sm font-bold text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">هذا الحساب محذوف.</p>@endif

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="card p-4"><p class="text-xs font-bold text-slate-500">عدد الطلبات</p><p class="mt-1 text-2xl font-extrabold">{{ $stats['orders'] }}</p></div>
        <div class="card p-4"><p class="text-xs font-bold text-slate-500">إجمالي المشتريات</p><p class="mt-1 text-2xl font-extrabold">{{ money($stats['spent']) }}</p></div>
        <div class="card p-4"><p class="text-xs font-bold text-slate-500">آخر طلب</p><p class="mt-1 text-lg font-extrabold">{{ $stats['last_order'] ? \Illuminate\Support\Carbon::parse($stats['last_order'])->diffForHumans() : '—' }}</p></div>
        <div class="card p-4"><p class="text-xs font-bold text-slate-500">آخر دخول</p><p class="mt-1 text-lg font-extrabold">{{ $user->last_login_at?->diffForHumans() ?? '—' }}</p></div>
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        <form wire:submit="save" class="card space-y-4 p-5 lg:col-span-1">
            <h2 class="font-extrabold">بيانات الحساب</h2>
            <x-field label="الاسم" model="name" />
            <x-field label="البريد الإلكتروني" model="email" type="email" dir="ltr" />
            <x-field label="الجوال" model="phone" dir="ltr" />
            <x-field label="كلمة مرور جديدة (اختياري)" model="newPassword" type="password" dir="ltr" autocomplete="new-password" />
            @if($canAssignRole)
                <div><label class="label" for="role">الدور</label>
                    <select id="role" wire:model="roleSlug" class="input">@foreach($roles as $r)<option value="{{ $r->slug }}">{{ $r->name }}</option>@endforeach</select></div>
            @else
                <p class="text-sm text-slate-500">الدور: <b>{{ $user->role?->name }}</b></p>
            @endif
            <label class="flex items-center gap-2 text-sm font-bold"><input type="checkbox" wire:model="is_active" class="rounded text-brand-600"> الحساب مفعّل</label>
            @error('is_active')<p class="text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
            @can('update', $user)<button class="btn-primary w-full">حفظ</button>@endcan
        </form>

        <div class="card overflow-x-auto lg:col-span-2">
            <div class="border-b border-slate-100 p-4 font-extrabold dark:border-slate-800">طلبات العميل</div>
            <table class="w-full">
                <thead><tr><th class="table-th">الطلب</th><th class="table-th">التاريخ</th><th class="table-th">الحالة</th><th class="table-th">الإجمالي</th></tr></thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($orders as $order)
                        <tr wire:key="co-{{ $order->id }}">
                            <td class="table-td font-bold" dir="ltr"><a href="{{ route('admin.orders.show', $order) }}" class="hover:text-brand-600">{{ $order->order_number }}</a></td>
                            <td class="table-td">{{ $order->created_at->format('Y/m/d') }}</td>
                            <td class="table-td"><x-status-badge :status="$order->status" /></td>
                            <td class="table-td font-bold">{{ money($order->grand_total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="p-8 text-center text-sm text-slate-500">لا توجد طلبات.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4">{{ $orders->links() }}</div>
        </div>
    </div>
</div>
