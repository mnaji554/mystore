<div class="grid gap-6 xl:grid-cols-3">
    <section class="card space-y-4 p-5 xl:col-span-2">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-extrabold">الأدوار</h2>
                <p class="mt-1 text-sm text-slate-500">الأدوار الأساسية لا يمكن حذفها، ودور المدير العام محمي من التعديل.</p>
            </div>
            <button type="button" wire:click="create" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> دور جديد</button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-slate-100 dark:border-slate-800">
                    <tr><th class="table-th">الدور</th><th class="table-th">المستخدمون</th><th class="table-th">الصلاحيات</th><th class="table-th"></th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($roles as $role)
                        <tr wire:key="role-{{ $role->id }}">
                            <td class="table-td"><span class="font-bold">{{ $role->name }}</span><br><span class="text-xs text-slate-500" dir="ltr">{{ $role->slug }}</span></td>
                            <td class="table-td">{{ $role->users_count }}</td>
                            <td class="table-td text-xs text-slate-500">
                                @if(in_array('*', $role->permissions ?? [], true)) كل الصلاحيات
                                @else {{ count(array_intersect($role->permissions ?? [], array_keys($permissions))) }} @endif
                            </td>
                            <td class="table-td text-end">
                                @if($role->slug === \App\Models\Role::SUPER_ADMIN)
                                    <span class="badge badge-slate">محمي</span>
                                @else
                                    <button type="button" wire:click="edit({{ $role->id }})" class="btn-ghost btn-sm">تعديل</button>
                                    @if(!in_array($role->slug, $systemRoleSlugs, true))
                                        <button type="button" wire:click="delete({{ $role->id }})" wire:confirm="حذف هذا الدور؟" class="btn-ghost btn-sm text-rose-600">حذف</button>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <form wire:submit="save" class="card space-y-4 p-5">
        <h2 class="font-extrabold">{{ $roleId ? 'تعديل الدور' : 'إضافة دور' }}</h2>
        <x-field label="اسم الدور *" model="name" />
        @php $editingSystemRole = $roleId && in_array($slug, $systemRoleSlugs, true); @endphp
        <div>
            <label class="label" for="slug">المعرّف *</label>
            <input id="slug" wire:model="slug" class="input" dir="ltr" @disabled($editingSystemRole)>
            @error('slug')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
        </div>
        <fieldset class="space-y-2">
            <legend class="label">الصلاحيات</legend>
            @if($slug === \App\Models\Role::CUSTOMER)
                <p class="text-xs text-slate-500">دور العملاء مخصص للحسابات العامة ولا يمكن منحه صلاحيات.</p>
            @endif
            @foreach($permissions as $permission => $label)
                <label wire:key="permission-{{ $permission }}" class="flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model="selectedPermissions" value="{{ $permission }}" class="rounded text-brand-600" @disabled($slug === \App\Models\Role::CUSTOMER)>
                    {{ $label }}
                </label>
            @endforeach
            @error('selectedPermissions.*')<p class="text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
            @error('selectedPermissions')<p class="text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
        </fieldset>
        @error('roleId')<p class="text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
        <div class="flex justify-end gap-2">
            <button type="button" wire:click="create" class="btn-ghost">إلغاء</button>
            <button class="btn-primary" wire:loading.attr="disabled" wire:target="save">حفظ الدور</button>
        </div>
    </form>
</div>
