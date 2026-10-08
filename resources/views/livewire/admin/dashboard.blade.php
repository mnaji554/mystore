<div class="space-y-6">
    @php
        $cards = [
            ['إجمالي المبيعات', money($stats['total_sales']), 'money', 'emerald'],
            ['مبيعات اليوم', money($stats['today_sales']), 'chart', 'sky'],
            ['مبيعات الشهر', money($stats['month_sales']), 'chart', 'indigo'],
            ['الأرباح', money($stats['profit']), 'money', 'violet'],
            ['عدد الطلبات', number_format($stats['orders_count']), 'orders', 'slate'],
            ['طلبات جديدة', number_format($stats['new_orders']), 'bell', 'amber'],
            ['متوسط قيمة الطلب', money($stats['avg_order_value']), 'tag', 'sky'],
            ['العملاء', number_format($stats['customers_count']), 'users', 'indigo'],
            ['المنتجات', number_format($stats['products_count']), 'cube', 'slate'],
            ['مخزون منخفض', number_format($stats['low_stock_count']), 'warning', 'rose'],
        ];
    @endphp

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
        @foreach($cards as [$label, $value, $icon, $color])
            <div class="card p-4">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500">{{ $label }}</span>
                    <span class="badge badge-{{ $color }} !p-1.5"><x-icon :name="$icon" class="h-4 w-4" /></span>
                </div>
                <p class="mt-2 whitespace-nowrap text-lg font-extrabold sm:text-xl">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card p-5 lg:col-span-2">
            <h2 class="mb-3 font-extrabold">المبيعات اليومية (آخر 30 يوم)</h2>
            <div class="h-72" wire:ignore x-data="chart(@js(['type' => 'line', 'data' => ['labels' => $daily['labels'], 'datasets' => [['label' => 'المبيعات', 'data' => $daily['data'], 'borderColor' => '#4f46e5', 'backgroundColor' => 'rgba(79,70,229,.12)', 'fill' => true, 'tension' => .35, 'pointRadius' => 0]]], 'options' => ['responsive' => true, 'maintainAspectRatio' => false, 'plugins' => ['legend' => ['display' => false]]]]))">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>
        <div class="card p-5">
            <h2 class="mb-3 font-extrabold">الطلبات حسب الحالة</h2>
            <div class="h-72" wire:ignore x-data="chart(@js(['type' => 'doughnut', 'data' => ['labels' => $byStatus['labels'], 'datasets' => [['data' => $byStatus['data'], 'backgroundColor' => $byStatus['colors'], 'borderWidth' => 0]]], 'options' => ['responsive' => true, 'maintainAspectRatio' => false, 'plugins' => ['legend' => ['position' => 'bottom']]]]))">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>
        <div class="card p-5 lg:col-span-2">
            <h2 class="mb-3 font-extrabold">المبيعات الشهرية</h2>
            <div class="h-64" wire:ignore x-data="chart(@js(['type' => 'bar', 'data' => ['labels' => $monthly['labels'], 'datasets' => [['label' => 'المبيعات', 'data' => $monthly['data'], 'backgroundColor' => '#6366f1', 'borderRadius' => 6]]], 'options' => ['responsive' => true, 'maintainAspectRatio' => false, 'plugins' => ['legend' => ['display' => false]]]]))">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>
        <div class="card p-5">
            <h2 class="mb-3 font-extrabold">أفضل التصنيفات (مبيعات)</h2>
            <div class="h-64" wire:ignore x-data="chart(@js(['type' => 'bar', 'data' => ['labels' => $topCategories['labels'], 'datasets' => [['label' => 'المبيعات', 'data' => $topCategories['data'], 'backgroundColor' => '#10b981', 'borderRadius' => 6]]], 'options' => ['indexAxis' => 'y', 'responsive' => true, 'maintainAspectRatio' => false, 'plugins' => ['legend' => ['display' => false]]]]))">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>
        <div class="card p-5 lg:col-span-3">
            <h2 class="mb-3 font-extrabold">أفضل المنتجات (الكمية المباعة)</h2>
            <div class="h-64" wire:ignore x-data="chart(@js(['type' => 'bar', 'data' => ['labels' => $topProducts['labels'], 'datasets' => [['label' => 'الكمية', 'data' => $topProducts['data'], 'backgroundColor' => '#f59e0b', 'borderRadius' => 6]]], 'options' => ['responsive' => true, 'maintainAspectRatio' => false, 'plugins' => ['legend' => ['display' => false]]]]))">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 p-4 dark:border-slate-800"><h2 class="font-extrabold">آخر الطلبات</h2><a href="{{ route('admin.orders.index') }}" class="text-sm font-bold text-brand-600">عرض الكل</a></div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($recentOrders as $order)
                    <a href="{{ route('admin.orders.show', $order) }}" class="flex flex-wrap items-center justify-between gap-2 p-3 text-sm hover:bg-slate-50 dark:hover:bg-slate-800/50">
                        <span class="font-extrabold" dir="ltr">{{ $order->order_number }}</span>
                        <span class="text-slate-500">{{ $order->customer_name }}</span>
                        <x-status-badge :status="$order->status" />
                        <span class="font-bold">{{ money($order->grand_total) }}</span>
                    </a>
                @empty
                    <p class="p-6 text-center text-sm text-slate-500">لا توجد طلبات.</p>
                @endforelse
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 p-4 dark:border-slate-800"><h2 class="font-extrabold">منتجات منخفضة المخزون (≤ {{ $threshold }})</h2><a href="{{ route('admin.products.index', ['stock' => 'low']) }}" class="text-sm font-bold text-brand-600">عرض الكل</a></div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($lowStock as $product)
                    <a href="{{ route('admin.products.edit', $product) }}" class="flex items-center justify-between gap-2 p-3 text-sm hover:bg-slate-50 dark:hover:bg-slate-800/50">
                        <span class="font-bold">{{ $product->name_ar }}</span>
                        <span class="badge badge-rose">{{ $product->available_stock }} متبقي</span>
                    </a>
                @empty
                    <p class="p-6 text-center text-sm text-slate-500">كل المنتجات بمخزون كافٍ ✓</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
