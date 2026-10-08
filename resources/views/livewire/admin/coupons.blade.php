<div class="space-y-5">
    <div class="flex justify-end">@can('create', App\Models\Coupon::class)<button type="button" wire:click="create" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> كوبون جديد</button>@endcan</div>

    @if($showForm)
        <form wire:submit="save" class="card animate-fade-in space-y-4 p-5">
            <h2 class="font-extrabold">{{ $editingId ? 'تعديل الكوبون' : 'كوبون جديد' }}</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-field label="الكود *" model="form.code" dir="ltr" class="input uppercase" />
                <div>
                    <label class="label" for="ctype">النوع</label>
                    <select id="ctype" wire:model.live="form.type" class="input"><option value="percentage">نسبة مئوية %</option><option value="fixed">مبلغ ثابت</option></select>
                </div>
                <x-field label="{{ $form['type'] === 'percentage' ? 'قيمة الخصم (%) *' : 'قيمة الخصم *' }}" model="form.value" type="number" step="0.01" dir="ltr" />
                <x-field label="الحد الأدنى للطلب" model="form.min_order_amount" type="number" step="0.01" dir="ltr" />
                <x-field label="أقصى خصم" model="form.max_discount" type="number" step="0.01" dir="ltr" />
                <x-field label="عدد الاستخدامات الكلي" model="form.usage_limit" type="number" min="1" dir="ltr" />
                <x-field label="الاستخدامات لكل مستخدم" model="form.usage_limit_per_user" type="number" min="1" dir="ltr" />
                <x-field label="الوصف" model="form.description" />
                <x-field label="تاريخ البداية" model="form.starts_at" type="datetime-local" dir="ltr" />
                <x-field label="تاريخ الانتهاء" model="form.expires_at" type="datetime-local" dir="ltr" />
                <div class="flex items-end gap-5 pb-2 sm:col-span-2">
                    <label class="flex items-center gap-2 text-sm font-bold"><input type="checkbox" wire:model="form.is_active" class="rounded text-brand-600"> مفعّل</label>
                    <label class="flex items-center gap-2 text-sm font-bold"><input type="checkbox" wire:model="form.is_public" class="rounded text-brand-600"> يظهر في صفحة الكوبونات</label>
                </div>
            </div>

            <div class="grid gap-5 lg:grid-cols-2">
                <div>
                    <p class="label">منتجات محددة (اتركه فارغاً لكل المنتجات)</p>
                    <div class="mb-2 flex flex-wrap gap-2">
                        @foreach($selectedProducts as $p)<span wire:key="sp-{{ $p->id }}" class="badge badge-indigo gap-1">{{ $p->name_ar }}<button type="button" wire:click="removeProduct({{ $p->id }})" aria-label="إزالة">✕</button></span>@endforeach
                    </div>
                    <input type="search" wire:model.live.debounce.300ms="productSearch" class="input" placeholder="ابحث لإضافة منتج…">
                    @if($results->isNotEmpty())
                        <ul class="card mt-1 divide-y divide-slate-100 text-sm dark:divide-slate-800">
                            @foreach($results as $r)<li wire:key="sr-{{ $r->id }}"><button type="button" wire:click="addProduct({{ $r->id }})" class="w-full px-3 py-2 text-start hover:bg-slate-50 dark:hover:bg-slate-800">{{ $r->name_ar }} <span class="text-xs text-slate-500" dir="ltr">{{ $r->sku }}</span></button></li>@endforeach
                        </ul>
                    @endif
                </div>
                <div>
                    <p class="label">تصنيفات محددة</p>
                    <div class="max-h-44 space-y-1 overflow-y-auto rounded-xl border border-slate-200 p-3 dark:border-slate-700">
                        @foreach($categoryOptions as $opt)
                            <label wire:key="cc-{{ $opt['id'] }}" class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="categoryIds" value="{{ $opt['id'] }}" class="rounded text-brand-600"> {{ $opt['name'] }}</label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="flex gap-2"><button class="btn-primary">حفظ</button><button type="button" wire:click="resetForm" class="btn-outline">إلغاء</button></div>
        </form>
    @endif

    <div class="card overflow-x-auto">
        <table class="w-full">
            <thead class="border-b border-slate-100 dark:border-slate-800"><tr><th class="table-th">الكود</th><th class="table-th">الخصم</th><th class="table-th">الاستخدام</th><th class="table-th">الصلاحية</th><th class="table-th">النطاق</th><th class="table-th">الحالة</th><th class="table-th"></th></tr></thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($coupons as $coupon)
                    <tr wire:key="cp-{{ $coupon->id }}">
                        <td class="table-td font-extrabold" dir="ltr">{{ $coupon->code }}</td>
                        <td class="table-td">{{ $coupon->label }}@if($coupon->max_discount)<span class="block text-xs text-slate-500">حتى {{ money($coupon->max_discount) }}</span>@endif</td>
                        <td class="table-td">{{ $coupon->used_count }}{{ $coupon->usage_limit ? ' / '.$coupon->usage_limit : '' }}</td>
                        <td class="table-td text-xs">{{ $coupon->starts_at?->format('Y/m/d') ?? '—' }} → {{ $coupon->expires_at?->format('Y/m/d') ?? '∞' }}</td>
                        <td class="table-td text-xs">{{ ($coupon->products_count + $coupon->categories_count) ? 'مخصص' : 'عام' }}</td>
                        <td class="table-td"><button type="button" wire:click="toggle({{ $coupon->id }})" class="badge {{ $coupon->is_active ? 'badge-emerald' : 'badge-slate' }}">{{ $coupon->is_active ? 'مفعّل' : 'متوقف' }}</button></td>
                        <td class="table-td text-end">
                            <button type="button" wire:click="edit({{ $coupon->id }})" class="btn-ghost btn-sm !p-2" aria-label="تعديل"><x-icon name="edit" class="h-4 w-4" /></button>
                            @can('delete', $coupon)<button type="button" wire:click="delete({{ $coupon->id }})" wire:confirm="حذف الكوبون؟" class="btn-ghost btn-sm !p-2 text-rose-600" aria-label="حذف"><x-icon name="trash" class="h-4 w-4" /></button>@endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-0"><x-empty-state icon="ticket" title="لا توجد كوبونات" class="!border-0 !shadow-none" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $coupons->links() }}
</div>
