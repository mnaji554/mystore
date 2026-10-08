<div class="container-x py-8">
    <h1 class="mb-6 text-2xl font-extrabold sm:text-3xl">سلة المشتريات</h1>

    @php $summary = $this->summary; @endphp

    @if(! $summary || $summary->isEmpty())
        <x-empty-state icon="cart" title="سلتك فارغة" text="لم تضف أي منتجات بعد. ابدأ التسوق واكتشف عروضنا.">
            <a href="{{ route('products.index') }}" class="btn-primary">تصفح المنتجات</a>
        </x-empty-state>
    @else
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-3 lg:col-span-2">
                @foreach($summary->lines as $line)
                    <article wire:key="line-{{ $line->itemId }}" class="card flex gap-4 p-4 {{ $line->issue ? '!border-rose-300 dark:!border-rose-800' : '' }}">
                        <a href="{{ $line->product->url }}" class="h-24 w-24 shrink-0 overflow-hidden rounded-xl bg-slate-100 dark:bg-slate-800">
                            @if($line->product->primaryImage)
                                <img src="{{ $line->product->thumb_url }}" alt="{{ $line->name() }}" loading="lazy" class="h-full w-full object-cover">
                            @else
                                <span class="grid h-full place-items-center text-slate-300"><x-icon name="photo" class="h-8 w-8" /></span>
                            @endif
                        </a>
                        <div class="flex min-w-0 flex-1 flex-col">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <a href="{{ $line->product->url }}" class="line-clamp-2 font-extrabold hover:text-brand-600">{{ $line->name() }}</a>
                                    @if($line->variantLabel())<p class="text-xs text-slate-500">{{ $line->variantLabel() }}</p>@endif
                                </div>
                                <button type="button" wire:click="remove({{ $line->itemId }})" class="text-slate-400 hover:text-rose-600" aria-label="حذف"><x-icon name="trash" /></button>
                            </div>
                            @if($line->issue)<p class="mt-1 text-xs font-bold text-rose-600">{{ $line->issue }}</p>@endif
                            <div class="mt-auto flex flex-wrap items-end justify-between gap-2 pt-3">
                                <div class="flex items-center rounded-xl border border-slate-300 dark:border-slate-700">
                                    <button type="button" wire:click="updateQuantity({{ $line->itemId }}, {{ $line->quantity - 1 }})" class="grid h-9 w-9 place-items-center" aria-label="إنقاص"><x-icon name="minus" class="h-4 w-4" /></button>
                                    <span class="w-8 text-center font-extrabold">{{ $line->quantity }}</span>
                                    <button type="button" wire:click="updateQuantity({{ $line->itemId }}, {{ $line->quantity + 1 }})" class="grid h-9 w-9 place-items-center" aria-label="زيادة"><x-icon name="plus" class="h-4 w-4" /></button>
                                </div>
                                <div class="text-end">
                                    <p class="font-extrabold">{{ money($line->total()) }}</p>
                                    @if($line->savings() > 0)<p class="text-xs text-slate-400 line-through">{{ money($line->regularTotal()) }}</p>@endif
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <aside class="space-y-4 lg:sticky lg:top-40 lg:self-start">
                <div class="card p-5">
                    <h2 class="mb-3 font-extrabold">كود الخصم</h2>
                    @if($summary->coupon)
                        <div class="flex items-center justify-between rounded-xl bg-emerald-50 p-3 text-sm font-bold text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300">
                            <span><x-icon name="ticket" class="me-1 inline h-4 w-4" /> {{ $summary->coupon->code }} (-{{ money($summary->couponDiscount) }})</span>
                            <button type="button" wire:click="removeCoupon" class="text-xs underline">إزالة</button>
                        </div>
                    @else
                        <form wire:submit="applyCoupon" class="flex gap-2">
                            <input type="text" wire:model="couponCode" class="input uppercase" placeholder="أدخل الكود" maxlength="50" dir="ltr" aria-label="كود الخصم">
                            <button class="btn-secondary shrink-0" wire:loading.attr="disabled">تطبيق</button>
                        </form>
                        @error('couponCode')<p class="mt-2 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                    @endif
                    @if($summary->couponError)<p class="mt-2 text-xs font-bold text-rose-600">{{ $summary->couponError }}</p>@endif
                </div>

                <div class="card space-y-3 p-5 text-sm">
                    <h2 class="font-extrabold">ملخص الطلب</h2>
                    <div class="flex justify-between"><span class="text-slate-500">المجموع الفرعي</span><span>{{ money($summary->subtotal) }}</span></div>
                    @if($summary->discount > 0)<div class="flex justify-between text-emerald-600"><span>خصم المنتجات</span><span>-{{ money($summary->discount) }}</span></div>@endif
                    @if($summary->couponDiscount > 0)<div class="flex justify-between text-emerald-600"><span>خصم الكوبون</span><span>-{{ money($summary->couponDiscount) }}</span></div>@endif
                    <div class="flex justify-between"><span class="text-slate-500">الضريبة ({{ (int) setting('tax_rate') }}%)</span><span>{{ money($summary->tax) }}</span></div>
                    <div class="flex justify-between text-slate-500"><span>الشحن</span><span>يُحسب عند الدفع</span></div>
                    <div class="flex justify-between border-t border-slate-200 pt-3 text-base font-extrabold dark:border-slate-800"><span>الإجمالي</span><span>{{ money($summary->total) }}</span></div>

                    @if($summary->hasIssues())
                        <p class="rounded-lg bg-rose-50 p-2 text-xs font-bold text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">يرجى تعديل المنتجات غير المتاحة قبل المتابعة.</p>
                        <button class="btn-primary w-full !py-3" disabled>إتمام الطلب</button>
                    @else
                        <a href="{{ route('checkout') }}" class="btn-primary w-full !py-3">إتمام الطلب</a>
                    @endif
                    <a href="{{ route('products.index') }}" class="btn-ghost w-full">متابعة التسوق</a>
                </div>
            </aside>
        </div>
    @endif
</div>
