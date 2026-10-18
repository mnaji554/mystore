<div class="mx-auto max-w-3xl space-y-5">
    <a href="{{ route('admin.users.index') }}" class="btn-ghost btn-sm"><x-icon name="chevron-right" class="h-4 w-4" /> المستخدمون</a>

    <form wire:submit="save" class="card space-y-5 p-5">
        <h2 class="text-lg font-extrabold">بيانات المستخدم</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-field label="الاسم *" model="name" />
            <x-field label="البريد الإلكتروني *" model="email" type="email" dir="ltr" />
            <x-field label="الجوال" model="phone" dir="ltr" />
            <div>
                <label class="label" for="role">الدور *</label>
                <select id="role" wire:model="roleSlug" class="input">
                    @foreach($roles as $role)<option value="{{ $role->slug }}">{{ $role->name }}</option>@endforeach
                </select>
                @error('roleSlug')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
            </div>
            <x-field label="كلمة المرور *" model="password" type="password" dir="ltr" autocomplete="new-password" />
            <x-field label="تأكيد كلمة المرور *" model="password_confirmation" type="password" dir="ltr" autocomplete="new-password" />
        </div>
        <label class="flex items-center gap-2 text-sm font-bold">
            <input type="checkbox" wire:model="is_active" class="rounded text-brand-600"> الحساب مفعّل
        </label>
        <div class="flex justify-end">
            <button class="btn-primary" wire:loading.attr="disabled" wire:target="save">إنشاء المستخدم</button>
        </div>
    </form>
</div>
