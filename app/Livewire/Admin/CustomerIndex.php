<?php

namespace App\Livewire\Admin;

use App\Enums\OrderStatus;
use App\Models\Role;
use App\Models\User;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    #[Url]
    public string $role = 'customer';

    #[Url]
    public string $state = '';

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function toggleActive(int $id): void
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);

        abort_if($user->id === auth()->id(), 422, 'لا يمكنك تعطيل حسابك.');

        $user->forceFill(['is_active' => ! $user->is_active])->save();
        $this->dispatch('notify', message: $user->is_active ? 'تم تفعيل الحساب' : 'تم تعطيل الحساب', type: 'success');
    }

    public function delete(int $id): void
    {
        $user = User::findOrFail($id);
        $this->authorize('delete', $user);

        $user->tokens()->delete();
        $user->delete();
        $this->dispatch('notify', message: 'تم حذف الحساب', type: 'success');
    }

    public function render()
    {
        $users = User::query()
            ->with('role:id,slug,name')
            ->withCount('orders')
            ->withSum(['orders as total_spent' => fn ($q) => $q->whereIn('status', OrderStatus::revenueStatuses())], 'grand_total')
            ->withMax('orders as last_order_at', 'created_at')
            ->when($this->search !== '', function ($q) {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $this->search).'%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('phone', 'like', $like));
            })
            ->when($this->role !== '', fn ($q) => $q->whereHas('role', fn ($r) => $r->where('slug', $this->role)))
            ->when($this->state === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->state === 'disabled', fn ($q) => $q->where('is_active', false))
            ->latest('id')->paginate((int) config('store.pagination.admin'));

        return view('livewire.admin.customer-index', [
            'users' => $users,
            'roles' => Role::orderBy('id')->get(['slug', 'name']),
        ])->layout('components.layouts.admin', ['title' => 'العملاء']);
    }
}
