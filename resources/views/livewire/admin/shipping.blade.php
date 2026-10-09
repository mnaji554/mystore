<div class="space-y-5">
    <div class="flex gap-2 border-b border-slate-200 dark:border-slate-800">
        <button type="button" wire:click="$set('tab','methods')" class="-mb-px border-b-2 px-4 py-2 font-extrabold {{ $tab === 'methods' ? 'border-brand-600 text-brand-700 dark:text-brand-300' : 'border-transparent text-slate-500' }}">طرق الشحن</button>
        <button type="button" wire:click="$set('tab','companies')" class="-mb-px border-b-2 px-4 py-2 font-extrabold {{ $tab === 'companies' ? 'border-brand-600 text-brand-700 dark:text-brand-300' : 'border-transparent text-slate-500' }}">شركات الشحن</button>
        <button type="button" wire:click="$set('tab','couriers')" class="-mb-px border-b-2 px-4 py-2 font-extrabold {{ $tab === 'couriers' ? 'border-brand-600 text-brand-700 dark:text-brand-300' : 'border-transparent text-slate-500' }}">المناديب</button>
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
    @elseif($tab === 'companies')
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
    @else
        <div class="flex justify-end"><button type="button" wire:click="newCourier" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> حساب مندوب</button></div>

        @if($showCourierForm)
            <form wire:submit="saveCourier" class="card animate-fade-in space-y-4 p-5">
                <h2 class="font-extrabold">{{ $courierId ? 'تعديل حساب المندوب' : 'حساب مندوب جديد' }}</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="الاسم الكامل *" model="courier.name" />
                    <x-field label="البريد الإلكتروني *" model="courier.email" type="email" dir="ltr" />
                    <x-field label="رقم الجوال" model="courier.phone" type="tel" dir="ltr" />
                    <x-field :label="$courierId ? 'كلمة مرور جديدة (اختياري)' : 'كلمة المرور *'" model="courier.password" type="password" autocomplete="new-password" />
                    <x-field label="تأكيد كلمة المرور" model="courier.password_confirmation" type="password" autocomplete="new-password" />
                </div>
                <section class="space-y-4 border-t border-slate-100 pt-4 dark:border-slate-800">
                    <div>
                        <h3 class="font-extrabold">بيانات التحقق والمركبة</h3>
                        <p class="mt-1 text-xs text-slate-500">أرقام الهوية واللوحة والرخصة تحفظ مشفّرة، والوثائق لا تظهر إلا لموظفي إدارة الشحن.</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <x-field label="رقم الهوية (10 أرقام)" model="courier.national_id" dir="ltr" inputmode="numeric" maxlength="10" />
                        <x-field label="رقم لوحة المركبة" model="courier.vehicle_plate" maxlength="20" />
                        <x-field label="رقم رخصة القيادة" model="courier.driving_license_number" dir="ltr" maxlength="30" />
                    </div>
                    <div class="grid gap-4 lg:grid-cols-3">
                        <div class="space-y-2 rounded-xl border border-slate-200 p-4 dark:border-slate-700">
                            <label class="label" for="national-id-image">صورة الهوية</label>
                            <input id="national-id-image" type="file" wire:model="nationalIdImage" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm file:me-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:font-bold file:text-brand-700">
                            @if($courierDocumentExists['national-id'] ?? false)
                                <div class="flex flex-wrap gap-3 text-xs">
                                    <a class="font-bold text-brand-700 underline dark:text-brand-300" href="{{ route('admin.couriers.documents.download', ['user' => $courierId, 'document' => 'national-id']) }}">تنزيل الصورة الحالية</a>
                                    <button type="button" wire:click="removeCourierDocument('national-id')" wire:confirm="حذف صورة الهوية؟" class="font-bold text-rose-600">حذف</button>
                                </div>
                            @endif
                            @error('nationalIdImage')<p class="text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                            <p class="text-xs text-slate-500">JPG أو PNG أو WEBP، بحد أقصى 5 ميجابايت.</p>
                        </div>
                        <div class="space-y-2 rounded-xl border border-slate-200 p-4 dark:border-slate-700">
                            <label class="label" for="vehicle-registration-image">صورة استمارة المركبة</label>
                            <input id="vehicle-registration-image" type="file" wire:model="vehicleRegistrationImage" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm file:me-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:font-bold file:text-brand-700">
                            @if($courierDocumentExists['vehicle-registration'] ?? false)
                                <div class="flex flex-wrap gap-3 text-xs">
                                    <a class="font-bold text-brand-700 underline dark:text-brand-300" href="{{ route('admin.couriers.documents.download', ['user' => $courierId, 'document' => 'vehicle-registration']) }}">تنزيل الصورة الحالية</a>
                                    <button type="button" wire:click="removeCourierDocument('vehicle-registration')" wire:confirm="حذف صورة استمارة المركبة؟" class="font-bold text-rose-600">حذف</button>
                                </div>
                            @endif
                            @error('vehicleRegistrationImage')<p class="text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                            <p class="text-xs text-slate-500">JPG أو PNG أو WEBP، بحد أقصى 5 ميجابايت.</p>
                        </div>
                        <div class="space-y-2 rounded-xl border border-slate-200 p-4 dark:border-slate-700">
                            <label class="label" for="driving-license-image">صورة رخصة القيادة</label>
                            <input id="driving-license-image" type="file" wire:model="drivingLicenseImage" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm file:me-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:font-bold file:text-brand-700">
                            @if($courierDocumentExists['driving-license'] ?? false)
                                <div class="flex flex-wrap gap-3 text-xs">
                                    <a class="font-bold text-brand-700 underline dark:text-brand-300" href="{{ route('admin.couriers.documents.download', ['user' => $courierId, 'document' => 'driving-license']) }}">تنزيل الصورة الحالية</a>
                                    <button type="button" wire:click="removeCourierDocument('driving-license')" wire:confirm="حذف صورة رخصة القيادة؟" class="font-bold text-rose-600">حذف</button>
                                </div>
                            @endif
                            @error('drivingLicenseImage')<p class="text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                            <p class="text-xs text-slate-500">JPG أو PNG أو WEBP، بحد أقصى 5 ميجابايت.</p>
                        </div>
                    </div>
                </section>
                <label class="flex items-center gap-2 text-sm font-bold"><input type="checkbox" wire:model="courier.is_active" class="rounded text-brand-600"> الحساب مفعّل</label>
                <div class="flex gap-2">
                    <button class="btn-primary" wire:loading.attr="disabled" wire:target="saveCourier,nationalIdImage,vehicleRegistrationImage,drivingLicenseImage">حفظ الحساب</button>
                    <button type="button" wire:click="resetCourier" class="btn-outline">إلغاء</button>
                </div>
            </form>
        @endif

        <div class="card overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-slate-100 dark:border-slate-800"><tr><th class="table-th">المندوب</th><th class="table-th">البريد الإلكتروني</th><th class="table-th">الجوال</th><th class="table-th">الحالة</th><th class="table-th"></th></tr></thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($couriers as $user)
                        <tr wire:key="courier-{{ $user->id }}">
                            <td class="table-td font-bold">{{ $user->name }}</td>
                            <td class="table-td" dir="ltr">{{ $user->email }}</td>
                            <td class="table-td" dir="ltr">{{ $user->phone ?: '—' }}</td>
                            <td class="table-td"><span class="badge {{ $user->is_active ? 'badge-emerald' : 'badge-slate' }}">{{ $user->is_active ? 'مفعّل' : 'متوقف' }}</span></td>
                            <td class="table-td text-end">
                                <button type="button" wire:click="editCourier({{ $user->id }})" class="btn-ghost btn-sm !p-2" aria-label="تعديل"><x-icon name="edit" class="h-4 w-4" /></button>
                                <button type="button" wire:click="toggleCourier({{ $user->id }})" class="btn-ghost btn-sm !p-2 {{ $user->is_active ? 'text-rose-600' : 'text-emerald-600' }}">{{ $user->is_active ? 'إيقاف' : 'تفعيل' }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-0"><x-empty-state icon="users" title="لا توجد حسابات مناديب" text="أنشئ حساب مندوب ليتاح إسناد طلبات التوصيل إليه." class="!border-0 !shadow-none" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
