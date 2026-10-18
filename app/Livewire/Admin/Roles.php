<?php

namespace App\Livewire\Admin;

use App\Models\Role;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Roles extends Component
{
    public ?int $roleId = null;

    public string $name = '';

    public string $slug = '';

    /** @var list<string> */
    public array $selectedPermissions = [];

    public function mount(): void
    {
        $this->authorize('manage-staff');
    }

    public function create(): void
    {
        $this->authorize('manage-staff');
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $this->authorize('manage-staff');
        $role = Role::findOrFail($id);
        abort_if($role->slug === Role::SUPER_ADMIN, 403);

        $this->roleId = $role->id;
        $this->name = $role->name;
        $this->slug = $role->slug;
        $this->selectedPermissions = array_values(array_intersect($role->permissions ?? [], Permissions::keys()));
        $this->resetValidation();
    }

    public function save(): void
    {
        $this->authorize('manage-staff');

        $role = $this->roleId ? Role::findOrFail($this->roleId) : new Role;
        abort_if($role->exists && $role->slug === Role::SUPER_ADMIN, 403);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => [
                'required',
                'string',
                'max:80',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('roles', 'slug')->ignore($role->id),
            ],
            'selectedPermissions' => ['array', 'max:'.count(Permissions::ALL)],
            'selectedPermissions.*' => ['string', 'distinct', Rule::in(Permissions::keys())],
        ], [
            'name.required' => 'اسم الدور مطلوب.',
            'slug.required' => 'المعرّف مطلوب.',
            'slug.regex' => 'استخدم أحرفاً إنجليزية صغيرة وأرقاماً وشرطة فقط.',
            'slug.unique' => 'معرّف الدور مستخدم.',
            'selectedPermissions.*.in' => 'الصلاحية المحددة غير معروفة.',
        ]);

        if ($role->exists && isset(Permissions::ROLES[$role->slug]) && $validated['slug'] !== $role->slug) {
            $this->addError('slug', 'لا يمكن تغيير معرّف الدور الأساسي.');

            return;
        }

        if (! $role->exists && isset(Permissions::ROLES[$validated['slug']])) {
            $this->addError('slug', 'هذا المعرّف محجوز لدور أساسي.');

            return;
        }

        if ($validated['slug'] === Role::CUSTOMER && ($validated['selectedPermissions'] ?? []) !== []) {
            $this->addError('selectedPermissions', 'لا يمكن منح صلاحيات إدارية لدور العملاء.');

            return;
        }

        $role->fill([
            'name' => strip_tags($validated['name']),
            'slug' => $validated['slug'],
            'permissions' => array_values(array_unique($validated['selectedPermissions'] ?? [])),
        ])->save();

        $this->resetForm();
        $this->dispatch('notify', message: 'تم حفظ الدور والصلاحيات', type: 'success');
    }

    public function delete(int $id): void
    {
        $this->authorize('manage-staff');
        $role = Role::findOrFail($id);
        abort_if(isset(Permissions::ROLES[$role->slug]), 403);

        if (User::withTrashed()->where('role_id', $role->id)->exists()) {
            $this->addError('roleId', 'لا يمكن حذف دور مرتبط بمستخدمين.');

            return;
        }

        $role->delete();

        if ($this->roleId === $id) {
            $this->resetForm();
        }

        $this->dispatch('notify', message: 'تم حذف الدور', type: 'success');
    }

    private function resetForm(): void
    {
        $this->reset(['roleId', 'name', 'slug', 'selectedPermissions']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.roles', [
            'roles' => Role::query()
                ->withCount(['users' => fn ($query) => $query->withTrashed()])
                ->orderBy('id')
                ->get(),
            'permissions' => Permissions::ALL,
            'systemRoleSlugs' => array_keys(Permissions::ROLES),
        ])->layout('components.layouts.admin', ['title' => 'الأدوار والصلاحيات']);
    }
}
