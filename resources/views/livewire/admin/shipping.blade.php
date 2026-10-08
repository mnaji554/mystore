<div class="space-y-5">
    <div class="flex gap-2 border-b border-slate-200 dark:border-slate-800">
        <button type="button" wire:click="$set('tab','methods')" class="-mb-px border-b-2 px-4 py-2 font-extrabold {{ $tab === 'methods' ? 'border-brand-600 text-brand-700 dark:text-brand-300' : 'border-transparent text-slate-500' }}">طرق الشحن</button>
        <button type="button" wire:click="$set('tab','companies')" class="-mb-px border-b-2 px-4 py-2 font-extrabold {{ $tab === 'companies' ? 'border-brand-600 text-brand-700 dark:text-brand-300' : 'border-transparent text-slate-500' }}">شركات الشحن</button>
    </div>

    @if($tab === 'methods')
        <div class="flex justify-end"><button type="button" wire:click="newMethod" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> طريقة شحن</button></div>

        @if($showMethodForm)
            <form wire:submit="saveMethod" class="card animate-fade-in space-y-4 p-5">
                <h2 class="font-extrabold">{{ $methodId ? 'تعديل طريقة الشحن' : 'طريقة شحن جديدة' }}</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <x-field label="الاسم *" model="method.name" />
                    <x-field label="الوصف" model="method.description" />
                    <div><label class="label" for="mc">شركة الشحن</label>
                        <select id="mc" wire:model="method.shipping_company_id" class="input"><option value="">— بدون —</option>@foreach($companies as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
                    <x-field label="السعر *" model="method.price" type="number" step="0.01" min="0" dir="ltr" />
                    <x-field label="حد الشحن المجاني (حد أدنى للطلب)" model="method.free_shipping_threshold" type="number" step="0.01" min="0" dir="ltr" />
                    <x-field label="أقل مدة توصيل (أيام)" model="method.delivery_days_min" type="number" min="0" dir="ltr" />
                    <x-field label="أقصى مدة توصيل (أيام)" model="method.delivery_days_max" type="number" min="0" dir="ltr" />
                    <x-field label="الترتيب" model="method.sort_order" type="number" min="0" dir="ltr" />
                </div>
                <div class="flex flex-wrap gap-5">
                    <label class="flex items-center gap-2 text-sm font-bold"><input type="checkbox" wire:model="method.is_free" class="rounded text-brand-600"> شحن مجاني دائماً</label>
                    <label class="flex items-center gap-2 text-sm font-bold"><input type="checkbox" wire:model="method.is_active" class="rounded text-brand-600"> مفعّلة</label>
                </div>
                <div class="flex gap-2"><button class="btn-primary">حفظ</button><button type="button" wire:click="resetMethod" class="btn-outline">إلغاء</button></div>
            </form>
        @endif

        <div class="card overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-slate-100 dark:border-slate-800"><tr><th class="table-th">الطريقة</th><th class="table-th">الشركة</th><th class="table-th">السعر</th><th class="table-th">الشحن المجاني</th><th class="table-th">المدة</th><th class="table-th">الحالة</th><th class="table-th"></th></tr></thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($methods as $m)
                        <tr wire:key="m-{{ $m->id }}">
                            <td class="table-td font-bold">{{ $m->name }}</td>
                            <td class="table-td">{{ $m->company?->name ?? '—' }}</td>
                            <td class="table-td">{{ $m->is_free ? 'مجاني' : money($m->price) }}</td>
                            <td class="table-td text-xs">{{ $m->free_shipping_threshold ? 'فوق '.money($m->free_shipping_threshold) : '—' }}</td>
                            <td class="table-td">{{ $m->delivery_estimate }}</td>
                            <td class="table-td"><span class="badge {{ $m->is_active ? 'badge-emerald' : 'badge-slate' }}">{{ $m->is_active ? 'مفعّلة' : 'متوقفة' }}</span></td>
                            <td class="table-td text-end">
                                <button type="button" wire:click="editMethod({{ $m->id }})" class="btn-ghost btn-sm !p-2" aria-label="تعديل"><x-icon name="edit" class="h-4 w-4" /></button>
                                @can('delete-records')<button type="button" wire:click="deleteMethod({{ $m->id }})" wire:confirm="حذف طريقة الشحن؟" class="btn-ghost btn-sm !p-2 text-rose-600" aria-label="حذف"><x-icon name="trash" class="h-4 w-4" /></button>@endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-0"><x-empty-state icon="truck" title="لا توجد طرق شحن" text="أضف طريقة شحن واحدة على الأقل ليتمكن العملاء من إتمام الطلبات." class="!border-0 !shadow-none" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <div class="flex justify-end"><button type="button" wire:click="newCompany" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> شركة شحن</button></div>

        @if($showCompanyForm)
            <form wire:submit="saveCompany" class="card animate-fade-in space-y-4 p-5">
                <h2 class="font-extrabold">{{ $companyId ? 'تعديل شركة الشحن' : 'شركة شحن جديدة' }}</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="اسم الشركة *" model="company.name" />
                    <x-field label="قالب رابط التتبع" model="company.tracking_url_template" dir="ltr" placeholder="https://carrier.com/track?no={number}" hint="استخدم {number} مكان رقم التتبع." />
                </div>
                <label class="flex items-center gap-2 text-sm font-bold"><input type="checkbox" wire:model="company.is_active" class="rounded text-brand-600"> مفعّلة</label>
                <div class="flex gap-2"><button class="btn-primary">حفظ</button><button type="button" wire:click="resetCompany" class="btn-outline">إلغاء</button></div>
            </form>
        @endif

        <div class="card overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-slate-100 dark:border-slate-800"><tr><th class="table-th">الشركة</th><th class="table-th">قالب التتبع</th><th class="table-th">الطرق</th><th class="table-th">الحالة</th><th class="table-th"></th></tr></thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($companies as $c)
                        <tr wire:key="c-{{ $c->id }}">
                            <td class="table-td font-bold">{{ $c->name }}</td>
                            <td class="table-td text-xs" dir="ltr">{{ $c->tracking_url_template ?? '—' }}</td>
                            <td class="table-td">{{ $c->methods_count }}</td>
                            <td class="table-td"><span class="badge {{ $c->is_active ? 'badge-emerald' : 'badge-slate' }}">{{ $c->is_active ? 'مفعّلة' : 'متوقفة' }}</span></td>
                            <td class="table-td text-end">
                                <button type="button" wire:click="editCompany({{ $c->id }})" class="btn-ghost btn-sm !p-2" aria-label="تعديل"><x-icon name="edit" class="h-4 w-4" /></button>
                                @can('delete-records')<button type="button" wire:click="deleteCompany({{ $c->id }})" wire:confirm="حذف الشركة؟" class="btn-ghost btn-sm !p-2 text-rose-600" aria-label="حذف"><x-icon name="trash" class="h-4 w-4" /></button>@endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-0"><x-empty-state icon="truck" title="لا توجد شركات شحن" class="!border-0 !shadow-none" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
