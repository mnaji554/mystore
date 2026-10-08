<div>
    @php
        $crumbs = [];
        if ($product->category?->parent) { $crumbs[$product->category->parent->name_ar] = $product->category->parent->url; }
        if ($product->category) { $crumbs[$product->category->name_ar] = $product->category->url; }
        $crumbs[$product->name_ar] = null;

        $variant = $this->variant;
        $regular = $variant ? (float) $variant->price : (float) $product->price;
        $final = $variant ? $variant->final_price : $product->final_price;
        $stock = $this->stock;
        $sku = $variant?->sku ?? $product->sku;
        $imageList = $product->images->map(fn ($i) => ['url' => $i->url, 'thumb' => $i->thumb_url, 'alt' => $i->alt ?: $product->name_ar])->values();
    @endphp

    <x-breadcrumbs :items="$crumbs" />

    <div class="container-x">
        <div class="card grid gap-8 p-5 sm:p-8 lg:grid-cols-2">
            {{-- Gallery --}}
            <div x-data="gallery(@js($imageList))">
                <div class="relative aspect-square overflow-hidden rounded-2xl bg-slate-100 dark:bg-slate-800">
                    <template x-if="current">
                        <img :src="current.url" :alt="current.alt" class="h-full w-full object-contain" fetchpriority="high">
                    </template>
                    <template x-if="!current">
                        <span class="grid h-full w-full place-items-center text-slate-300"><x-icon name="photo" class="h-20 w-20" /></span>
                    </template>
                    @if($product->discount_percent > 0 && $regular > $final)
                        <span class="badge absolute start-3 top-3 bg-rose-600 text-white">-{{ $product->discount_percent }}%</span>
                    @endif
                </div>
                <div class="mt-3 flex gap-2 overflow-x-auto pb-1" x-show="images.length > 1">
                    <template x-for="(img, i) in images" :key="i">
                        <button type="button" @click="active = i" class="h-16 w-16 shrink-0 overflow-hidden rounded-xl border-2 transition"
                                :class="active === i ? 'border-brand-600' : 'border-transparent opacity-70 hover:opacity-100'">
                            <img :src="img.thumb" :alt="img.alt" loading="lazy" class="h-full w-full object-cover">
                        </button>
                    </template>
                </div>
            </div>

            {{-- Details --}}
            <div class="flex flex-col">
                @if($product->category)<a href="{{ $product->category->url }}" class="text-sm font-bold text-brand-600">{{ $product->category->name_ar }}</a>@endif
                <h1 class="mt-1 text-2xl font-extrabold leading-snug sm:text-3xl">{{ $product->name_ar }}</h1>
                @if($product->name_en)<p class="text-sm text-slate-500" dir="ltr" style="text-align:right">{{ $product->name_en }}</p>@endif

                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <x-rating :value="$product->rating_avg" :count="$product->reviews_count" />
                    <span class="text-xs text-slate-500">SKU: <span dir="ltr">{{ $sku }}</span></span>
                </div>

                <x-price :regular="$regular" :final="$final" size="lg" class="mt-4" />
                @if($product->short_description)<p class="mt-4 leading-8 text-slate-600 dark:text-slate-400">{{ $product->short_description }}</p>@endif

                @foreach($this->optionGroups as $name => $options)
                    <div class="mt-5">
                        <p class="label">{{ \App\Livewire\ProductShow::OPTION_LABELS[$name] ?? $name }}: <span class="font-normal text-slate-500">{{ $selected[$name] ?? '' }}</span></p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($options as $option)
                                @php $isSelected = ($selected[$name] ?? null) === $option->value; @endphp
                                <button type="button" wire:click="selectOption(@js($name), @js($option->value))" wire:key="opt-{{ $name }}-{{ $option->id }}"
                                        class="{{ $name === 'color' && $option->color_hex ? 'h-10 w-10 rounded-full border-2' : 'min-w-12 rounded-xl border px-4 py-2 text-sm font-bold' }} transition {{ $isSelected ? 'border-brand-600 ring-2 ring-brand-500/40' : 'border-slate-300 hover:border-brand-400 dark:border-slate-700' }}"
                                        @if($name === 'color' && $option->color_hex) style="background-color: {{ $option->color_hex }}" title="{{ $option->value }}" aria-label="{{ $option->value }}" @endif>
                                    @unless($name === 'color' && $option->color_hex){{ $option->value }}@endunless
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div class="mt-5">
                    @if($product->has_variants && ! $variant)
                        <p class="text-sm font-bold text-rose-600">هذه التركيبة من الخيارات غير متوفرة.</p>
                    @elseif($stock > 0)
                        <p class="text-sm font-bold text-emerald-600"><x-icon name="check-circle" class="me-1 inline h-4 w-4" /> متوفر{{ $stock <= 5 ? " (بقي {$stock} فقط)" : '' }}</p>
                    @else
                        <p class="text-sm font-bold text-rose-600"><x-icon name="x-circle" class="me-1 inline h-4 w-4" /> نفدت الكمية</p>
                    @endif
                </div>

                <div class="mt-5 flex flex-wrap items-center gap-3">
                    <div class="flex items-center rounded-xl border border-slate-300 dark:border-slate-700">
                        <button type="button" wire:click="decrement" class="grid h-11 w-11 place-items-center" aria-label="إنقاص"><x-icon name="minus" class="h-4 w-4" /></button>
                        <span class="w-10 text-center font-extrabold">{{ $quantity }}</span>
                        <button type="button" wire:click="increment" class="grid h-11 w-11 place-items-center" aria-label="زيادة"><x-icon name="plus" class="h-4 w-4" /></button>
                    </div>
                    <button type="button" wire:click="addSelectedToCart" wire:loading.attr="disabled" wire:target="addSelectedToCart" @disabled($stock < 1) class="btn-primary flex-1 !py-3 sm:flex-none sm:!px-10">
                        <x-icon name="cart" class="h-5 w-5" /> أضف إلى السلة
                    </button>
                    <button type="button" wire:click="toggleWishlist({{ $product->id }})" class="btn-outline !p-3 {{ in_array($product->id, $this->wishlistIds) ? '!text-rose-600' : '' }}" aria-label="المفضلة">
                        <x-icon name="heart" class="h-5 w-5 {{ in_array($product->id, $this->wishlistIds) ? 'fill-current' : '' }}" />
                    </button>
                </div>

                <ul class="mt-6 grid gap-2 border-t border-slate-100 pt-5 text-sm text-slate-600 dark:border-slate-800 dark:text-slate-400">
                    <li class="flex items-center gap-2"><x-icon name="truck" class="h-5 w-5 text-brand-600" /> شحن سريع خلال 1-3 أيام عمل</li>
                    <li class="flex items-center gap-2"><x-icon name="shield" class="h-5 w-5 text-brand-600" /> دفع آمن وضمان استرجاع</li>
                </ul>
            </div>
        </div>

        {{-- Description + reviews --}}
        <div id="reviews" class="mt-8 grid gap-8 lg:grid-cols-3" x-data="{ tab: location.hash === '#reviews' ? 'reviews' : 'desc' }">
            <div class="card p-5 sm:p-8 lg:col-span-2">
                <div class="mb-5 flex gap-2 border-b border-slate-200 dark:border-slate-800">
                    <button type="button" @click="tab = 'desc'" class="-mb-px border-b-2 px-4 py-2 font-extrabold" :class="tab === 'desc' ? 'border-brand-600 text-brand-700 dark:text-brand-300' : 'border-transparent text-slate-500'">الوصف</button>
                    <button type="button" @click="tab = 'reviews'" class="-mb-px border-b-2 px-4 py-2 font-extrabold" :class="tab === 'reviews' ? 'border-brand-600 text-brand-700 dark:text-brand-300' : 'border-transparent text-slate-500'">التقييمات ({{ $product->reviews_count }})</button>
                </div>

                <div x-show="tab === 'desc'" class="prose-store whitespace-pre-line">{{ $product->description ?: 'لا يوجد وصف لهذا المنتج.' }}</div>

                <div x-show="tab === 'reviews'" x-cloak class="space-y-5">
                    @forelse($this->reviews as $review)
                        <article wire:key="review-{{ $review->id }}" class="border-b border-slate-100 pb-5 last:border-0 dark:border-slate-800">
                            <div class="flex items-center justify-between">
                                <p class="font-extrabold">{{ $review->user->name }}</p>
                                <span class="text-xs text-slate-500">{{ $review->created_at->diffForHumans() }}</span>
                            </div>
                            <x-rating :value="$review->rating" class="mt-1" />
                            @if($review->title)<p class="mt-2 font-bold">{{ $review->title }}</p>@endif
                            @if($review->comment)<p class="mt-1 whitespace-pre-line leading-7 text-slate-600 dark:text-slate-400">{{ $review->comment }}</p>@endif
                            @if($review->images->isNotEmpty())
                                <div class="mt-3 flex gap-2">
                                    @foreach($review->images as $img)
                                        <a href="{{ $img->url }}" target="_blank" rel="noopener"><img src="{{ $img->url }}" alt="صورة من العميل" loading="lazy" class="h-20 w-20 rounded-xl object-cover"></a>
                                    @endforeach
                                </div>
                            @endif
                        </article>
                    @empty
                        <x-empty-state icon="star" title="لا توجد تقييمات بعد" text="كن أول من يقيّم هذا المنتج بعد شرائه." class="!border-0 !shadow-none" />
                    @endforelse

                    @if($this->reviews->count() < $product->reviews_count)
                        <button type="button" wire:click="loadMoreReviews" class="btn-outline w-full">عرض المزيد</button>
                    @endif
                </div>
            </div>

            <aside class="card h-fit p-5 sm:p-6">
                <h2 class="mb-3 text-lg font-extrabold">قيّم هذا المنتج</h2>
                @guest
                    <p class="text-sm text-slate-500">يرجى <a href="{{ route('login') }}" class="font-bold text-brand-600">تسجيل الدخول</a> لكتابة تقييم.</p>
                @else
                    @if(! $this->canReview)
                        <p class="text-sm leading-7 text-slate-500">يمكنك تقييم المنتج بعد شرائه واستلامه.</p>
                    @else
                        <form wire:submit="saveReview" class="space-y-3">
                            @if($this->myReview)
                                <p class="rounded-lg bg-amber-50 p-2 text-xs font-bold text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">
                                    تقييمك الحالي: {{ $this->myReview->status === 'approved' ? 'معتمد' : ($this->myReview->status === 'pending' ? 'بانتظار الموافقة' : 'مرفوض') }}
                                </p>
                            @endif
                            <div>
                                <p class="label">التقييم</p>
                                <div class="flex gap-1" dir="ltr">
                                    @for($i = 1; $i <= 5; $i++)
                                        <button type="button" wire:click="$set('rating', {{ $i }})" aria-label="{{ $i }} نجوم">
                                            <x-icon name="star-solid" class="h-8 w-8 {{ $i <= $rating ? 'text-amber-400' : 'text-slate-300 dark:text-slate-700' }}" />
                                        </button>
                                    @endfor
                                </div>
                                @error('rating')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <x-field label="عنوان التقييم" model="title" maxlength="120" />
                            <div>
                                <label class="label" for="comment">تعليقك</label>
                                <textarea id="comment" wire:model="comment" rows="4" maxlength="2000" class="input"></textarea>
                                @error('comment')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="label" for="photos">صور (اختياري، حتى 3)</label>
                                <input id="photos" type="file" wire:model="photos" multiple accept="image/png,image/jpeg,image/webp" class="block w-full text-sm file:me-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:font-bold file:text-brand-700">
                                @error('photos')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                                @error('photos.*')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div class="flex gap-2">
                                <button class="btn-primary flex-1" wire:loading.attr="disabled" wire:target="saveReview,photos">{{ $this->myReview ? 'تحديث التقييم' : 'إرسال التقييم' }}</button>
                                @if($this->myReview)
                                    <button type="button" wire:click="deleteReview" wire:confirm="هل تريد حذف تقييمك؟" class="btn-outline !text-rose-600" aria-label="حذف"><x-icon name="trash" class="h-4 w-4" /></button>
                                @endif
                            </div>
                        </form>
                    @endif
                @endguest
            </aside>
        </div>

        @if($related->isNotEmpty())
            <section class="mt-12">
                <h2 class="mb-5 text-2xl font-extrabold">منتجات مشابهة</h2>
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    @foreach($related as $item)
                        <x-product-card :product="$item" :in-wishlist="in_array($item->id, $this->wishlistIds)" wire:key="rel-{{ $item->id }}" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>
