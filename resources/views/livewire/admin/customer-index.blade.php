<div class="space-y-4">
    <div class="card flex flex-wrap items-end gap-3 p-4">
        <div class="min-w-48 flex-1"><label class="label" for="s">بحث</label><input id="s" type="search" wire:model.live.debounce.400ms="search" class="input" placeholder="الاسم أو البريد أو الجوال"></div>
        <div><label class="label" for="r">الدور</label>
            <select id="r" wire:model.live="role" class="input !w-auto"><option value="">الكل</option>@foreach($roles as $r)<option value="{{ $r->slug }}">{{ $r->name }}</option>@endforeach</select></div>
        <div><label class="label" for="st">الحالة</label>
            <select id="st" wire:model.live="state" class="input !w-auto"><option value="">الكل</option><option value="active">مفعّل</option><option value="disabled">معطّل</option></select></div>
    </div>

    <div class="card overflow-x-auto">
        <table class="w-full">
            <thead class="border-b border-slate-100 dark:border-slate-800">
                <tr><th class="table-th">العميل</th><th class="table-th">الدور</th><th class="table-th">الطلبات</th><th class="table-th">إجمالي المشتريات</th><th class="table-th">آخر نشاط</th><th class="table-th">الحالة</th><th class="table-th"></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($users as $user)
                    <tr wire:key="u-{{ $user->id }}">
                        <td class="table-td"><a href="{{ route('admin.customers.show', $user) }}" class="font-bold hover:text-brand-600">{{ $user->name }}</a><br><span class="text-xs text-slate-500" dir="ltr">{{ $user->email }}</span></td>
                        <td class="table-td"><span class="badge badge-slate">{{ $user->role?->name ?? '—' }}</span></td>
                        <td class="table-td">{{ $user->orders_count }}</td>
                        <td class="table-td font-bold">{{ money($user->total_spent ?? 0) }}</td>
                        <td class="table-td whitespace-nowrap text-xs text-slate-500">{{ ($user->last_login_at ?? ($user->last_order_at ? \Illuminate\Support\Carbon::parse($user->last_order_at) : $user->created_at))->diffForHumans() }}</td>
                        <td class="table-td"><span class="badge {{ $user->is_active ? 'badge-emerald' : 'badge-rose' }}">{{ $user->is_active ? 'مفعّل' : 'معطّل' }}</span></td>
                        <td class="table-td text-end">
                            <div class="flex justify-end gap-1">
                                <a href="{{ route('admin.customers.show', $user) }}" class="btn-ghost btn-sm !p-2" aria-label="عرض"><x-icon name="eye" class="h-4 w-4" /></a>
                                @can('update', $user)
                                    @if($user->id !== auth()->id())<button type="button" wire:click="toggleActive({{ $user->id }})" wire:confirm="{{ $user->is_active ? 'تعطيل هذا الحساب؟' : 'تفعيل هذا الحساب؟' }}" class="btn-ghost btn-sm">{{ $user->is_active ? 'تعطيل' : 'تفعيل' }}</button>@endif
                                @endcan
                                @can('delete', $user)<button type="button" wire:click="delete({{ $user->id }})" wire:confirm="حذف الحساب نهائياً؟" class="btn-ghost btn-sm !p-2 text-rose-600" aria-label="حذف"><x-icon name="trash" class="h-4 w-4" /></button>@endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-0"><x-empty-state icon="users" title="لا يوجد عملاء مطابقون" class="!border-0 !shadow-none" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $users->links() }}
</div>
