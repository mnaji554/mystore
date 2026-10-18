<?php

namespace App\Livewire\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Component;

class UserCreate extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $roleSlug = Role::CUSTOMER;

    public bool $is_active = true;

    public function mount(): void
    {
        $this->authorize('create', User::class);
    }

    public function save(): void
    {
        $this->authorize('create', User::class);

        $roleSlugs = auth()->user()->can('manage-staff')
            ? Role::query()->pluck('slug')->all()
            : [Role::CUSTOMER];

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
            'roleSlug' => ['required', 'string', Rule::in($roleSlugs)],
            'is_active' => ['boolean'],
        ], [
            'name.required' => 'الاسم مطلوب.',
            'email.required' => 'البريد مطلوب.',
            'email.email' => 'صيغة البريد غير صحيحة.',
            'email.unique' => 'البريد مستخدم من حساب آخر.',
            'phone.regex' => 'رقم الجوال غير صحيح.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.min' => 'كلمة المرور 8 أحرف على الأقل.',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق.',
            'roleSlug.in' => 'الدور المحدد غير مسموح.',
        ]);

        $user = new User;
        $user->fill([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'phone' => $validated['phone'] ?: null,
            'password' => $validated['password'],
        ]);
        $user->forceFill([
            'role_id' => Role::where('slug', $validated['roleSlug'])->firstOrFail()->id,
            'is_active' => $validated['is_active'],
        ])->save();

        $this->dispatch('notify', message: 'تم إنشاء المستخدم بنجاح', type: 'success');

        $this->redirectRoute('admin.users.show', $user);
    }

    public function render()
    {
        $roles = Role::query()
            ->when(! auth()->user()->can('manage-staff'), fn ($query) => $query->where('slug', Role::CUSTOMER))
            ->orderBy('id')
            ->get(['slug', 'name']);

        return view('livewire.admin.user-create', ['roles' => $roles])
            ->layout('components.layouts.admin', ['title' => 'إضافة مستخدم']);
    }
}
