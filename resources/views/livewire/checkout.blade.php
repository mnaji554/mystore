<div class="container-x py-8">
    <h1 class="mb-6 text-2xl font-extrabold sm:text-3xl">إتمام الطلب</h1>

    {{-- Stepper --}}
    <ol class="mb-8 flex flex-wrap gap-2 text-xs font-bold sm:text-sm">
        @foreach($steps as $n => $label)
            <li>
                <button type="button" wire:click="goTo({{ $n }})" @disabled($n >= $step)
                        class="flex items-center gap-2 rounded-full px-3 py-1.5 transition {{ $n === $step ? 'bg-brand-600 text-white' : ($n < $step ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-slate-200 text-slate-500 dark:bg-slate-800') }}">
                    <span class="grid h-5 w-5 place-items-center rounded-full bg-white/30 text-[11px]">{{ $n < $step ? '✓' : $n }}</span>
                    <span class="hidden sm:inline">{{ $label }}</span>
                </button>
            </li>
        @endforeach
    </ol>

    @php $summary = $this->summary; @endphp

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <div class="card animate-fade-in p-5 sm:p-7" wire:key="step-{{ $step }}">
                {{-- 1: customer --}}
                @if($step === 1)
                    <h2 class="mb-5 text-lg font-extrabold">معلومات العميل</h2>
                    @guest
                        <p class="mb-4 rounded-xl bg-brand-50 p-3 text-sm dark:bg-brand-500/10">لديك حساب؟ <a href="{{ route('login') }}" class="font-extrabold text-brand-700 dark:text-brand-300">سجّل الدخول</a> لاستخدام عناوينك المحفوظة وتتبع طلباتك.</p>
                    @endguest
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-field label="الاسم الكامل" model="name" autocomplete="name" wrapper="sm:col-span-2" />
                        <x-field label="البريد الإلكتروني" model="email" type="email" autocomplete="email" dir="ltr" :readonly="auth()->check()" />
                        <x-field label="رقم الجوال" model="phone" type="tel" autocomplete="tel" dir="ltr" />
                    </div>
                @endif

                {{-- 2: address --}}
                @if($step === 2)
                    <h2 class="mb-5 text-lg font-extrabold">عنوان الشحن</h2>
                    @if($this->savedAddresses->isNotEmpty())
                        <div class="mb-5 grid gap-3 sm:grid-cols-2">
                            @foreach($this->savedAddresses as $saved)
                                <label wire:key="addr-{{ $saved->id }}" class="flex cursor-pointer gap-3 rounded-xl border p-4 transition {{ $addressId === $saved->id ? 'border-brand-600 bg-brand-50 dark:bg-brand-500/10' : 'border-slate-200 dark:border-slate-700' }}">
                                    <input type="radio" wire:click="selectAddress({{ $saved->id }})" @checked($addressId === $saved->id) name="addr" class="mt-1 text-brand-600">
                                    <span class="text-sm"><b>{{ $saved->label ?: $saved->full_name }}</b><br><span class="text-slate-500">{{ $saved->summary }}</span><br><span dir="ltr" class="text-slate-500">{{ $saved->phone }}</span></span>
                                </label>
                            @endforeach
                            <label class="flex cursor-pointer gap-3 rounded-xl border p-4 {{ ! $addressId ? 'border-brand-600 bg-brand-50 dark:bg-brand-500/10' : 'border-slate-200 dark:border-slate-700' }}">
                                <input type="radio" wire:click="selectAddress(null)" @checked(! $addressId) name="addr" class="mt-1 text-brand-600">
                                <span class="text-sm font-bold">استخدام عنوان جديد</span>
                            </label>
                        </div>
                    @endif
                    @error('addressId')<p class="mb-3 text-sm font-bold text-rose-600">{{ $message }}</p>@enderror

                    @if(! $addressId)
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-field label="اسم المستلم" model="address.full_name" />
                            <x-field label="جوال المستلم" model="address.phone" type="tel" dir="ltr" />
                            <x-field label="الدولة" model="address.country" />
                            <x-field label="المدينة" model="address.city" />
                            <x-field label="الحي" model="address.district" />
                            <x-field label="الشارع" model="address.street" />
                            <x-field label="رقم المبنى / الشقة" model="address.building" />
                            <x-field label="الرمز البريدي" model="address.postal_code" dir="ltr" />
                        </div>
                        @auth
                            <label class="mt-4 flex items-center gap-2 text-sm font-bold"><input type="checkbox" wire:model="saveAddress" class="rounded text-brand-600"> حفظ العنوان في حسابي</label>
                        @endauth
                    @endif
                @endif

                {{-- 3: shipping --}}
                @if($step === 3)
                    <h2 class="mb-5 text-lg font-extrabold">طريقة الشحن</h2>
                    <div class="space-y-3">
                        @foreach($this->shippingMethods as $method)
                            @php $cost = app(\App\Services\ShippingService::class)->cost($method, $summary->netSubtotal() - $summary->couponDiscount); @endphp
                            <label wire:key="ship-{{ $method->id }}" class="flex cursor-pointer items-center gap-3 rounded-xl border p-4 transition {{ $shippingMethodId === $method->id ? 'border-brand-600 bg-brand-50 dark:bg-brand-500/10' : 'border-slate-200 dark:border-slate-700' }}">
                                <input type="radio" wire:model.live="shippingMethodId" value="{{ $method->id }}" class="text-brand-600">
                                <x-icon name="truck" class="h-6 w-6 text-brand-600" />
                                <span class="flex-1 text-sm">
                                    <b>{{ $method->name }}</b>@if($method->company) <span class="text-slate-500">({{ $method->company->name }})</span>@endif<br>
                                    <span class="text-slate-500">التوصيل خلال {{ $method->delivery_estimate }}@if($method->free_shipping_threshold) · شحن مجاني فوق {{ money($method->free_shipping_threshold) }}@endif</span>
                                </span>
                                <span class="font-extrabold {{ $cost == 0 ? 'text-emerald-600' : '' }}">{{ $cost == 0 ? 'مجاني' : money($cost) }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('shippingMethodId')<p class="mt-2 text-sm font-bold text-rose-600">{{ $message }}</p>@enderror
                @endif

                {{-- 4: coupon --}}
                @if($step === 4)
                    <h2 class="mb-5 text-lg font-extrabold">كود الخصم</h2>
                    @if($summary->coupon)
                        <div class="flex items-center justify-between rounded-xl bg-emerald-50 p-4 font-bold text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300">
                            <span><x-icon name="ticket" class="me-1 inline" /> {{ $summary->coupon->code }} — وفّرت {{ money($summary->couponDiscount) }}</span>
                            <button type="button" wire:click="removeCoupon" class="text-sm underline">إزالة</button>
                        </div>
                    @else
                        <form wire:submit="applyCoupon" class="flex gap-2">
                            <input type="text" wire:model="couponCode" class="input uppercase" dir="ltr" placeholder="أدخل كود الخصم (اختياري)" maxlength="50" aria-label="كود الخصم">
                            <button class="btn-secondary shrink-0">تطبيق</button>
                        </form>
                        @error('couponCode')<p class="mt-2 text-sm font-bold text-rose-600">{{ $message }}</p>@enderror
                        @if($summary->couponError)<p class="mt-2 text-sm font-bold text-rose-600">{{ $summary->couponError }}</p>@endif
                        <p class="mt-3 text-xs text-slate-500">لا تملك كوداً؟ تخطَّ هذه الخطوة بالضغط على «التالي».</p>
                    @endif
                @endif

                {{-- 5: payment --}}
                @if($step === 5)
                    <h2 class="mb-5 text-lg font-extrabold">طريقة الدفع</h2>
                    <div class="space-y-3">
                        @foreach($this->gateways as $key => $gateway)
                            <label wire:key="pay-{{ $key }}" class="flex cursor-pointer items-center gap-3 rounded-xl border p-4 transition {{ $paymentMethod === $key ? 'border-brand-600 bg-brand-50 dark:bg-brand-500/10' : 'border-slate-200 dark:border-slate-700' }}">
                                <input type="radio" wire:model.live="paymentMethod" value="{{ $key }}" class="text-brand-600">
                                <x-icon :name="$key === 'cod' ? 'money' : 'credit-card'" class="h-6 w-6 text-brand-600" />
                                <span class="text-sm"><b>{{ $gateway->label() }}</b><br><span class="text-slate-500">{{ $gateway->description() }}</span></span>
                            </label>
                        @endforeach
                    </div>
                    @error('paymentMethod')<p class="mt-2 text-sm font-bold text-rose-600">{{ $message }}</p>@enderror
                @endif

                {{-- 6: review --}}
                @if($step === 6)
                    @php $method = $this->shippingMethods->firstWhere('id', $shippingMethodId); @endphp
                    <h2 class="mb-5 text-lg font-extrabold">مراجعة الطلب وتأكيده</h2>
                    <div class="grid gap-4 text-sm sm:grid-cols-2">
                        <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800/50"><p class="mb-1 font-extrabold">العميل</p>{{ $name }}<br><span dir="ltr">{{ $email }}</span><br><span dir="ltr">{{ $phone }}</span></div>
                        <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800/50"><p class="mb-1 font-extrabold">العنوان</p>
                            @if($addressId && ($saved = $this->savedAddresses->firstWhere('id', $addressId))){{ $saved->full_name }}<br>{{ $saved->summary }}
                            @else{{ $address['full_name'] }}<br>{{ collect([$address['street'], $address['building'], $address['district'], $address['city'], $address['country']])->filter()->implode('، ') }}@endif
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800/50"><p class="mb-1 font-extrabold">الشحن</p>{{ $method?->name }} · {{ $method?->delivery_estimate }}</div>
                        <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800/50"><p class="mb-1 font-extrabold">الدفع</p>{{ $this->gateways->get($paymentMethod)?->label() }}</div>
                    </div>
                    <div class="mt-5 divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($summary->lines as $line)
                            <div class="flex items-center justify-between gap-3 py-2.5 text-sm">
                                <span>{{ $line->name() }}@if($line->variantLabel()) <span class="text-slate-500">({{ $line->variantLabel() }})</span>@endif × {{ $line->quantity }}</span>
                                <span class="font-bold">{{ money($line->total()) }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4">
                        <label for="note" class="label">ملاحظات للطلب (اختياري)</label>
                        <textarea id="note" wire:model="note" rows="2" maxlength="500" class="input"></textarea>
                    </div>
                    @if($summary->hasIssues())
                        <p class="mt-4 rounded-lg bg-rose-50 p-3 text-sm font-bold text-rose-700">{{ implode(' · ', $summary->issues()) }} — <a href="{{ route('cart') }}" class="underline">عدّل السلة</a></p>
                    @endif
                    <p class="mt-4 text-xs text-slate-500">بالضغط على «تأكيد الطلب» فإنك توافق على <a class="underline" href="{{ route('pages.terms') }}" target="_blank">الشروط والأحكام</a>.</p>
                @endif

                {{-- nav --}}
                <div class="mt-7 flex items-center justify-between gap-3">
                    @if($step > 1)<button type="button" wire:click="back" class="btn-outline">السابق</button>@else<span></span>@endif
                    @if($step < count($steps))
                        <button type="button" wire:click="next" wire:loading.attr="disabled" class="btn-primary !px-8">التالي</button>
                    @else
                        <button type="button" wire:click="placeOrder" wire:loading.attr="disabled" wire:target="placeOrder" @disabled($summary->hasIssues() || $summary->isEmpty()) class="btn-primary !px-8 !py-3">
                            <span wire:loading.remove wire:target="placeOrder"><x-icon name="lock" class="me-1 inline h-4 w-4" /> تأكيد الطلب</span>
                            <span wire:loading wire:target="placeOrder">جارٍ المعالجة…</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- Summary --}}
        <aside class="lg:sticky lg:top-40 lg:self-start">
            <div class="card space-y-3 p-5 text-sm">
                <h2 class="font-extrabold">ملخص الطلب ({{ $summary->itemsCount() }} منتج)</h2>
                <div class="flex justify-between"><span class="text-slate-500">المجموع الفرعي</span><span>{{ money($summary->subtotal) }}</span></div>
                @if($summary->discount > 0)<div class="flex justify-between text-emerald-600"><span>خصم المنتجات</span><span>-{{ money($summary->discount) }}</span></div>@endif
                @if($summary->couponDiscount > 0)<div class="flex justify-between text-emerald-600"><span>خصم الكوبون ({{ $summary->coupon?->code }})</span><span>-{{ money($summary->couponDiscount) }}</span></div>@endif
                <div class="flex justify-between"><span class="text-slate-500">الشحن</span><span>{{ $summary->shippingMethod ? ($summary->shipping == 0 ? 'مجاني' : money($summary->shipping)) : '—' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">الضريبة ({{ (int) setting('tax_rate') }}%)</span><span>{{ money($summary->tax) }}</span></div>
                <div class="flex justify-between border-t border-slate-200 pt-3 text-base font-extrabold dark:border-slate-800"><span>الإجمالي</span><span>{{ money($summary->total) }}</span></div>
                <a href="{{ route('cart') }}" class="btn-ghost btn-sm w-full">تعديل السلة</a>
            </div>
        </aside>
    </div>
</div>
