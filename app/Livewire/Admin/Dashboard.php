<?php

namespace App\Livewire\Admin;

use App\Enums\OrderStatus;
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
        $dailyStart = now()->subDays(29)->startOfDay();
        $dailyLinks = collect(range(0, 29))
            ->map(fn (int $day) => route('admin.orders.index', [
                'from' => $dailyStart->copy()->addDays($day)->toDateString(),
                'to' => $dailyStart->copy()->addDays($day)->toDateString(),
            ]))
            ->all();

        $monthStart = now()->startOfMonth()->subMonths(11);
        $monthlyLinks = collect(range(0, 11))
            ->map(function (int $month) use ($monthStart): string {
                $start = $monthStart->copy()->addMonths($month);
                $end = $start->copy()->addMonth()->subDay()->min(today());

                return route('admin.orders.index', [
                    'from' => $start->toDateString(),
                    'to' => $end->toDateString(),
                ]);
            })
            ->all();

        $statusLinks = collect(OrderStatus::cases())
            ->map(fn (OrderStatus $status) => route('admin.orders.index', ['status' => $status->value]))
            ->all();
        $topProducts = $reports->topProducts(5);
        $topCategories = $reports->topCategories(5);

        return view('livewire.admin.dashboard', [
            'stats' => $reports->summary(),
            'daily' => $reports->dailySales(30),
            'dailyLinks' => $dailyLinks,
            'monthly' => $reports->monthlySales(12),
            'monthlyLinks' => $monthlyLinks,
            'byStatus' => $reports->ordersByStatus(),
            'statusLinks' => $statusLinks,
            'topProducts' => $topProducts,
            'topProductLinks' => $topProducts['rows']->map(fn ($product) => $product->product_id
                ? route('admin.products.edit', $product->product_id)
                : route('admin.products.index'))->all(),
            'topCategories' => $topCategories,
            'topCategoryLinks' => $topCategories['rows']->map(fn ($category) => route('admin.categories', [
                'edit' => $category->category_id,
            ]))->all(),
            'lowStock' => $reports->lowStockQuery()->with('variants:id,product_id,stock,is_active,sku')->orderBy('stock')->limit(8)->get(),
            'recentOrders' => Order::query()->with('user:id,name')->latest('id')->limit(8)->get(),
            'threshold' => $reports->lowStockThreshold(),
        ])->layout('components.layouts.admin', ['title' => 'لوحة المعلومات']);
    }
}
