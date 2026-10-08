<?php

namespace App\Livewire\Admin;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerShow extends Component
{
    use WithPagination;

    public int $userId;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public bool $is_active = true;

    public string $roleSlug = '';

    public string $newPassword = '';

    public function mount(User $user): void
    {
        $this->authorize('view', $user);
        abort_unless(auth()->user()->can('manage-customers'), 403);

        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = (string) $user->phone;
        $this->is_active = $user->is_active;
        $this->roleSlug = (string) $user->role?->slug;
    }

    private function user(): User
    {
        return User::withTrashed()->with('role')->findOrFail($this->userId);
    }

    public function save(): void
    {
        $user = $this->user();
        $this->authorize('update', $user);

        $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'newPassword' => ['nullable', 'string', 'min:8', 'max:100'],
        ], [
            'name.required' => 'الاسم مطلوب.', 'email.required' => 'البريد مطلوب.', 'email.email' => 'صيغة البريد غير صحيحة.',
            'email.unique' => 'البريد مستخدم من حساب آخر.', 'phone.regex' => 'رقم الجوال غير صحيح.', 'newPassword.min' => 'كلمة المرور 8 أحرف على الأقل.',
        ]);

        if ($user->id === auth()->id() && ! $this->is_active) {
            $this->addError('is_active', 'لا يمكنك تعطيل حسابك.');

            return;
        }

        $data = ['name' => $this->name, 'email' => strtolower($this->email), 'phone' => $this->phone ?: null];

        if ($this->newPassword !== '') {
            $data['password'] = $this->newPassword;
        }

        $user->update($data);
        $user->forceFill(['is_active' => $this->is_active])->save();

        if ($this->roleSlug !== '' && $this->roleSlug !== $user->role?->slug && auth()->user()->can('assignRole', $user)) {
            $role = Role::where('slug', $this->roleSlug)->first();

            if ($role) {
                $user->role()->associate($role)->save();
            }
        }

        $this->newPassword = '';
        $this->dispatch('notify', message: 'تم حفظ بيانات العميل', type: 'success');
    }

    public function render()
    {
        $user = $this->user();

        $stats = [
            'orders' => $user->orders()->count(),
            'spent' => (float) $user->orders()->whereIn('status', OrderStatus::revenueStatuses())->sum('grand_total'),
            'last_order' => $user->orders()->latest('id')->value('created_at'),
        ];

        return view('livewire.admin.customer-show', [
            'user' => $user,
            'stats' => $stats,
            'orders' => Order::query()->where('user_id', $user->id)->latest('id')->paginate(8),
            'roles' => Role::orderBy('id')->get(['slug', 'name']),
            'canAssignRole' => auth()->user()->can('assignRole', $user),
        ])->layout('components.layouts.admin', ['title' => 'العميل: '.$user->name]);
    }
}
