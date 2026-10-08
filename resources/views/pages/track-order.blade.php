@php use App\Enums\OrderStatus; @endphp
<x-layouts.app title="تتبع الطلب" :noindex="true">
    <x-breadcrumbs :items="['تتبع الطلب' => null]" />
    <div class="container-x pb-8">
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="card h-fit p-6">
                <h1 class="mb-1 text-xl font-extrabold">تتبع طلبك</h1>
                <p class="mb-4 text-sm text-slate-500">أدخل رقم الطلب والبريد الإلكتروني المستخدم عند الشراء.</p>
                <form method="POST" action="{{ route('orders.track.search') }}" class="space-y-4">
                    @csrf
                    <x-field label="رقم الطلب" name="order_number" dir="ltr" placeholder="ORD-000000-XXXXX" :value="old('order_number', request('order_number'))" required />
                    <x-field label="البريد الإلكتروني" name="email" type="email" dir="ltr" required />
                    <button class="btn-primary w-full">تتبع</button>
                </form>
            </div>

            <div class="lg:col-span-2">
                @isset($order)
                    <div class="card space-y-5 p-6">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-lg font-extrabold" dir="ltr">{{ $order->order_number }}</p>
                            <x-status-badge :status="$order->status" class="!px-3 !py-1 !text-sm" />
                        </div>
                        <p class="text-sm text-slate-500">تاريخ الطلب: {{ $order->placed_at?->format('Y/m/d H:i') }} · الإجمالي: <b>{{ money($order->grand_total) }}</b></p>

                        @if($order->tracking_number)
                            <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-brand-50 p-4 text-sm dark:bg-brand-500/10">
                                <span><x-icon name="truck" class="me-1 inline text-brand-600" /> رقم التتبع: <b dir="ltr">{{ $order->tracking_number }}</b></span>
                                @if($order->tracking_url)<a href="{{ $order->tracking_url }}" target="_blank" rel="noopener noreferrer" class="btn-primary btn-sm">تتبع الشحنة</a>@endif
                            </div>
                        @endif

                        <ol class="space-y-3 border-s-2 border-slate-200 ps-4 text-sm dark:border-slate-700">
                            @foreach($order->histories as $h)
                                <li class="relative"><span class="absolute -start-[1.4rem] top-1.5 h-2.5 w-2.5 rounded-full bg-brand-600"></span>
                                    <b>{{ $h->to_status->label() }}</b> <span class="text-xs text-slate-500">· {{ $h->created_at->format('Y/m/d H:i') }}</span>
                                </li>
                            @endforeach
                        </ol>

                        <div class="divide-y divide-slate-100 text-sm dark:divide-slate-800">
                            @foreach($order->items as $item)
                                <div class="flex justify-between py-2"><span>{{ $item->name }} × {{ $item->quantity }}</span><span class="font-bold">{{ money($item->line_total) }}</span></div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <x-empty-state icon="truck" title="تتبع شحنتك" text="ستظهر تفاصيل الطلب وحالته هنا بعد البحث." />
                @endisset
            </div>
        </div>
    </div>
</x-layouts.app>
