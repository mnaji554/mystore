<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class ReportService
{
    private function revenueOrders(): Builder
    {
        return Order::query()->whereIn('status', OrderStatus::revenueStatuses());
    }

    public function lowStockThreshold(): int
    {
        return (int) setting('low_stock_threshold', 5);
    }

    public function lowStockQuery(): Builder
    {
        $t = $this->lowStockThreshold();

        return Product::query()->active()->where(function (Builder $q) use ($t) {
            $q->where(fn (Builder $w) => $w->whereDoesntHave('variants', fn ($v) => $v->where('is_active', true))->where('stock', '<=', $t))
                ->orWhereHas('variants', fn ($v) => $v->where('is_active', true)->where('stock', '<=', $t));
        });
    }

    public function summary(): array
    {
        return Cache::remember('admin.summary', 60, function () {
            $orders = $this->revenueOrders();
            $total = (float) (clone $orders)->sum('grand_total');
            $count = (clone $orders)->count();

            $profit = (float) OrderItem::query()
                ->whereNotNull('cost_price')
                ->whereHas('order', fn ($q) => $q->whereIn('status', OrderStatus::revenueStatuses()))
                ->selectRaw('COALESCE(SUM(line_total - cost_price * quantity), 0) as p')->value('p');

            return [
                'total_sales' => $total,
                'today_sales' => (float) (clone $orders)->whereDate('created_at', today())->sum('grand_total'),
                'month_sales' => (float) (clone $orders)->where('created_at', '>=', now()->startOfMonth())->sum('grand_total'),
                'orders_count' => Order::count(),
                'new_orders' => Order::where('status', OrderStatus::Pending)->count(),
                'customers_count' => User::whereHas('role', fn ($q) => $q->where('slug', Role::CUSTOMER))->count(),
                'products_count' => Product::count(),
                'low_stock_count' => $this->lowStockQuery()->count(),
                'avg_order_value' => $count ? round($total / $count, 2) : 0.0,
                'profit' => round($profit, 2),
            ];
        });
    }

    /** @return array{labels: list<string>, data: list<float>} */
    public function dailySales(int $days = 30): array
    {
        $start = now()->subDays($days - 1)->startOfDay();

        $rows = $this->revenueOrders()->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as d, SUM(grand_total) as total')->groupBy('d')->pluck('total', 'd');

        $labels = $data = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $start->copy()->addDays($i);
            $labels[] = $day->format('m/d');
            $data[] = round((float) ($rows[$day->toDateString()] ?? 0), 2);
        }

        return compact('labels', 'data');
    }

    /** @return array{labels: list<string>, data: list<float>} */
    public function monthlySales(int $months = 12): array
    {
        $start = now()->startOfMonth()->subMonths($months - 1);

        $rows = $this->revenueOrders()->where('created_at', '>=', $start)
            ->selectRaw('SUBSTR(DATE(created_at), 1, 7) as m, SUM(grand_total) as total')->groupBy('m')->pluck('total', 'm');

        $labels = $data = [];
        for ($i = 0; $i < $months; $i++) {
            $month = $start->copy()->addMonths($i);
            $labels[] = $month->format('Y/m');
            $data[] = round((float) ($rows[$month->format('Y-m')] ?? 0), 2);
        }

        return compact('labels', 'data');
    }

    /** @return array{labels: list<string>, data: list<int>, colors: list<string>} */
    public function ordersByStatus(): array
    {
        $rows = Order::query()->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');
        $palette = ['amber' => '#f59e0b', 'sky' => '#0ea5e9', 'indigo' => '#6366f1', 'violet' => '#8b5cf6', 'emerald' => '#10b981', 'rose' => '#f43f5e', 'slate' => '#64748b'];

        $labels = $data = $colors = [];
        foreach (OrderStatus::cases() as $status) {
            $labels[] = $status->label();
            $data[] = (int) ($rows[$status->value] ?? 0);
            $colors[] = $palette[$status->color()];
        }

        return compact('labels', 'data', 'colors');
    }

    public function topProducts(int $limit = 5): array
    {
        $rows = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.status', OrderStatus::revenueStatuses())
            ->selectRaw('order_items.product_id, order_items.name as name, SUM(order_items.quantity) as qty, SUM(order_items.line_total) as revenue')
            ->groupBy('order_items.product_id', 'order_items.name')->orderByDesc('qty')->limit($limit)->get();

        return ['labels' => $rows->pluck('name')->all(), 'data' => $rows->pluck('qty')->map(fn ($v) => (int) $v)->all(), 'rows' => $rows];
    }

    public function topCategories(int $limit = 5): array
    {
        $rows = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->whereIn('orders.status', OrderStatus::revenueStatuses())
            ->selectRaw('categories.id as category_id, categories.name_ar as name, SUM(order_items.line_total) as revenue')
            ->groupBy('categories.id', 'categories.name_ar')->orderByDesc('revenue')->limit($limit)->get();

        return [
            'labels' => $rows->pluck('name')->all(),
            'data' => $rows->pluck('revenue')->map(fn ($v) => round((float) $v, 2))->all(),
            'rows' => $rows,
        ];
    }
}
