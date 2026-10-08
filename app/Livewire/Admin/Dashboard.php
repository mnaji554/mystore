<?php

namespace App\Livewire\Admin;

use App\Models\Order;
use App\Services\ReportService;
use Livewire\Component;

class Dashboard extends Component
{
    public function mount(): void
    {
        $this->authorize('access-admin');
    }

    public function render(ReportService $reports)
    {
        return view('livewire.admin.dashboard', [
            'stats' => $reports->summary(),
            'daily' => $reports->dailySales(30),
            'monthly' => $reports->monthlySales(12),
            'byStatus' => $reports->ordersByStatus(),
            'topProducts' => $reports->topProducts(5),
            'topCategories' => $reports->topCategories(5),
            'lowStock' => $reports->lowStockQuery()->with('variants:id,product_id,stock,is_active,sku')->orderBy('stock')->limit(8)->get(),
            'recentOrders' => Order::query()->with('user:id,name')->latest('id')->limit(8)->get(),
            'threshold' => $reports->lowStockThreshold(),
        ])->layout('components.layouts.admin', ['title' => 'لوحة المعلومات']);
    }
}
