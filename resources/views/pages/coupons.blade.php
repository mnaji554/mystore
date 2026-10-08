<x-layouts.app title="كوبونات الخصم" description="أحدث كوبونات الخصم المتاحة في المتجر.">
    <x-breadcrumbs :items="['الكوبونات' => null]" />
    <div class="container-x pb-8">
        <h1 class="mb-6 text-2xl font-extrabold sm:text-3xl">كوبونات الخصم</h1>
        @if($coupons->isEmpty())
            <x-empty-state icon="ticket" title="لا توجد كوبونات متاحة حالياً" text="تابعنا باستمرار لمعرفة أحدث العروض." />
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($coupons as $coupon)
                    <div class="card relative overflow-hidden p-5" x-data="{ copied: false }">
                        <span class="absolute -start-3 top-1/2 h-6 w-6 -translate-y-1/2 rounded-full bg-slate-50 dark:bg-slate-950"></span>
                        <span class="absolute -end-3 top-1/2 h-6 w-6 -translate-y-1/2 rounded-full bg-slate-50 dark:bg-slate-950"></span>
                        <p class="text-3xl font-extrabold text-brand-600">{{ $coupon->label }} <span class="text-sm font-bold text-slate-500">خصم</span></p>
                        <p class="mt-1 min-h-10 text-sm text-slate-500">{{ $coupon->description ?: 'كوبون خصم على مشترياتك.' }}</p>
                        <ul class="mt-2 space-y-0.5 text-xs text-slate-500">
                            @if($coupon->min_order_amount)<li>الحد الأدنى للطلب: {{ money($coupon->min_order_amount) }}</li>@endif
                            @if($coupon->max_discount)<li>أقصى خصم: {{ money($coupon->max_discount) }}</li>@endif
                            @if($coupon->expires_at)<li>ينتهي: {{ $coupon->expires_at->format('Y/m/d') }}</li>@endif
                            @unless($coupon->isGlobal())<li>على منتجات / تصنيفات محددة</li>@endunless
                        </ul>
                        <div class="mt-4 flex items-center gap-2">
                            <code class="flex-1 rounded-xl border-2 border-dashed border-brand-300 bg-brand-50 px-3 py-2 text-center text-lg font-extrabold tracking-widest text-brand-700 dark:border-brand-700 dark:bg-brand-500/10 dark:text-brand-300" dir="ltr">{{ $coupon->code }}</code>
                            <button type="button" class="btn-secondary btn-sm"
                                    @click="navigator.clipboard.writeText('{{ $coupon->code }}'); copied = true; $store.toasts.add('تم نسخ الكود'); setTimeout(() => copied = false, 2000)"
                                    x-text="copied ? 'تم ✓' : 'نسخ'"></button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.app>
