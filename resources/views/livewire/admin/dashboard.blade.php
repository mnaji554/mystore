<div class="space-y-6">
    @php
        $cards = [
            ['إجمالي المبيعات', money($stats['total_sales']), 'money', 'emerald', route('admin.orders.index')],
            ['مبيعات اليوم', money($stats['today_sales']), 'chart', 'sky', route('admin.orders.index', ['from' => today()->toDateString(), 'to' => today()->toDateString()])],
            ['مبيعات الشهر', money($stats['month_sales']), 'chart', 'indigo', route('admin.orders.index', ['from' => now()->startOfMonth()->toDateString(), 'to' => today()->toDateString()])],
            ['الأرباح', money($stats['profit']), 'money', 'violet', route('admin.orders.index')],
            ['عدد الطلبات', number_format($stats['orders_count']), 'orders', 'slate', route('admin.orders.index')],
            ['طلبات جديدة', number_format($stats['new_orders']), 'bell', 'amber', route('admin.orders.index', ['status' => \App\Enums\OrderStatus::Pending->value])],
            ['متوسط قيمة الطلب', money($stats['avg_order_value']), 'tag', 'sky', route('admin.orders.index')],
            ['العملاء', number_format($stats['customers_count']), 'users', 'indigo', route('admin.customers.index', ['role' => \App\Models\Role::CUSTOMER])],
            ['المنتجات', number_format($stats['products_count']), 'cube', 'slate', route('admin.products.index')],
            ['مخزون منخفض', number_format($stats['low_stock_count']), 'warning', 'rose', route('admin.products.index', ['stock' => 'low'])],
        ];
    @endphp

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
        @foreach($cards as [$label, $value, $icon, $color, $url])
            <a href="{{ $url }}" class="card group p-4 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:hover:border-brand-700">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500">{{ $label }}</span>
                    <span class="badge badge-{{ $color }} !p-1.5 transition group-hover:scale-110"><x-icon :name="$icon" class="h-4 w-4" /></span>
                </div>
                <p class="mt-2 whitespace-nowrap text-lg font-extrabold transition group-hover:text-brand-700 dark:group-hover:text-brand-300 sm:text-xl">{{ $value }}</p>
            </a>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card p-5 lg:col-span-2">
            <h2 class="mb-3 font-extrabold"><a href="{{ route('admin.orders.index', ['from' => now()->subDays(29)->toDateString(), 'to' => today()->toDateString()]) }}" class="hover:text-brand-600">المبيعات اليومية (آخر 30 يوم)</a></h2>
            <div class="h-72" wire:ignore x-data="chart(@js(['type' => 'line', 'links' => $dailyLinks, 'data' => ['labels' => $daily['labels'], 'datasets' => [['label' => 'المبيعات', 'data' => $daily['data'], 'borderColor' => '#4f46e5', 'backgroundColor' => 'rgba(79,70,229,.12)', 'fill' => true, 'tension' => .35, 'pointRadius' => 4, 'pointHoverRadius' => 6]]], 'options' => ['responsive' => true, 'maintainAspectRatio' => false, 'plugins' => ['legend' => ['display' => false]]]]))">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>
        <div class="card p-5">
            <h2 class="mb-3 font-extrabold"><a href="{{ route('admin.orders.index') }}" class="hover:text-brand-600">الطلبات حسب الحالة</a></h2>
            <div class="h-72" wire:ignore x-data="chart(@js(['type' => 'doughnut', 'links' => $statusLinks, 'data' => ['labels' => $byStatus['labels'], 'datasets' => [['data' => $byStatus['data'], 'backgroundColor' => $byStatus['colors'], 'borderWidth' => 0]]], 'options' => ['responsive' => true, 'maintainAspectRatio' => false, 'plugins' => ['legend' => ['position' => 'bottom']]]]))">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>
        <div class="card p-5 lg:col-span-2">
            <h2 class="mb-3 font-extrabold"><a href="{{ route('admin.orders.index', ['from' => now()->startOfMonth()->subMonths(11)->toDateString(), 'to' => today()->toDateString()]) }}" class="hover:text-brand-600">المبيعات الشهرية</a></h2>
            <div class="h-64" wire:ignore x-data="chart(@js(['type' => 'bar', 'links' => $monthlyLinks, 'data' => ['labels' => $monthly['labels'], 'datasets' => [['label' => 'المبيعات', 'data' => $monthly['data'], 'backgroundColor' => '#6366f1', 'borderRadius' => 6]]], 'options' => ['responsive' => true, 'maintainAspectRatio' => false, 'plugins' => ['legend' => ['display' => false]]]]))">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>
        <div class="card p-5">
            <h2 class="mb-3 font-extrabold"><a href="{{ route('admin.categories') }}" class="hover:text-brand-600">أفضل التصنيفات (مبيعات)</a></h2>
            <div class="h-64" wire:ignore x-data="chart(@js(['type' => 'bar', 'links' => $topCategoryLinks, 'data' => ['labels' => $topCategories['labels'], 'datasets' => [['label' => 'المبيعات', 'data' => $topCategories['data'], 'backgroundColor' => '#10b981', 'borderRadius' => 6]]], 'options' => ['indexAxis' => 'y', 'responsive' => true, 'maintainAspectRatio' => false, 'plugins' => ['legend' => ['display' => false]]]]))">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>
        <div class="card p-5 lg:col-span-3">
            <h2 class="mb-3 font-extrabold"><a href="{{ route('admin.products.index', ['sort' => 'sales_count', 'dir' => 'desc']) }}" class="hover:text-brand-600">أفضل المنتجات (الكمية المباعة)</a></h2>
            <div class="h-64" wire:ignore x-data="chart(@js(['type' => 'bar', 'links' => $topProductLinks, 'data' => ['labels' => $topProducts['labels'], 'datasets' => [['label' => 'الكمية', 'data' => $topProducts['data'], 'backgroundColor' => '#f59e0b', 'borderRadius' => 6]]], 'options' => ['responsive' => true, 'maintainAspectRatio' => false, 'plugins' => ['legend' => ['display' => false]]]]))">
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
