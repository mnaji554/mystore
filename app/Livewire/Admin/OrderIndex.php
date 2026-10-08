<?php

namespace App\Livewire\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Repositories\OrderRepository;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class OrderIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $payment = '';

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Order::class);
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'status', 'payment', 'from', 'to');
        $this->resetPage();
    }

    public function render(OrderRepository $orders)
    {
        $status = OrderStatus::tryFrom($this->status)?->value;
        $payment = PaymentStatus::tryFrom($this->payment)?->value;

        return view('livewire.admin.order-index', [
            'orders' => $orders->adminQuery([
                'search' => $this->search, 'status' => $status, 'payment' => $payment,
                'from' => $this->validDate($this->from), 'to' => $this->validDate($this->to),
            ])->latest('id')->paginate((int) config('store.pagination.admin')),
        ])->layout('components.layouts.admin', ['title' => 'الطلبات']);
    }

    private function validDate(string $value): ?string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }
}
