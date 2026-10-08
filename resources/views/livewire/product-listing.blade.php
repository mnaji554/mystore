<div>
    <x-breadcrumbs :items="$crumbs" />

    <div class="container-x">
        <div class="mb-6">
            <h1 class="text-2xl font-extrabold sm:text-3xl">{{ $heading }}</h1>
            @if($intro)<p class="mt-1 text-sm text-slate-500">{{ $intro }}</p>@endif
        </div>

        @if($category && $category->children->isNotEmpty())
            <div class="mb-6 flex flex-wrap gap-2">
                @foreach($category->children as $child)
                    <a href="{{ $child->url }}" class="badge badge-slate !px-4 !py-2 text-sm hover:bg-brand-100">{{ $child->name_ar }}</a>
                @endforeach
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-4" x-data="{ filters: false }">
            <aside class="lg:col-span-1">
                <button type="button" class="btn-outline mb-3 w-full lg:hidden" @click="filters = !filters"><x-icon name="filter" class="h-4 w-4" /> الفلاتر</button>
                <div class="card space-y-6 p-5 max-lg:hidden" :class="filters ? 'max-lg:block!' : ''">
                    <div>
                        <label class="label" for="search-q">بحث</label>
                        <input id="search-q" type="search" wire:model.live.debounce.400ms="q" class="input" placeholder="اسم المنتج أو SKU">
                    </div>
                    <div>
                        <p class="label">السعر</p>
                        <div class="flex items-center gap-2">
                            <input type="number" min="0" wire:model.live.debounce.600ms="min" class="input" placeholder="من" aria-label="السعر الأدنى">
                            <span class="text-slate-400">-</span>
                            <input type="number" min="0" wire:model.live.debounce.600ms="max" class="input" placeholder="إلى" aria-label="السعر الأعلى">
                        </div>
                    </div>
                    <label class="flex cursor-pointer items-center gap-2 text-sm font-bold">
                        <input type="checkbox" wire:model.live="inStock" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        المتوفر فقط
                    </label>
                    <div>
                        <p class="label">التصنيفات</p>
                        <ul class="space-y-1 text-sm">
                            @foreach($sidebarCategories as $cat)
                                <li>
                                    <a href="{{ $cat->url }}" class="block rounded-lg px-2 py-1.5 hover:bg-slate-100 dark:hover:bg-slate-800 {{ $category?->id === $cat->id ? 'bg-brand-50 font-extrabold text-brand-700 dark:bg-brand-500/15' : '' }}">{{ $cat->name_ar }}</a>
                                    @php $inTree = $category && ($category->id === $cat->id || $cat->children->contains(fn ($c) => $c->id === $category->id || $c->children->contains('id', $category->id))); @endphp
                                    @if($inTree && $cat->children->isNotEmpty())
                                        <ul class="ms-4 border-s border-slate-200 ps-2 dark:border-slate-700">
                                            @foreach($cat->children as $child)
                                                <li><a href="{{ $child->url }}" class="block rounded-lg px-2 py-1 text-slate-600 hover:text-brand-600 dark:text-slate-400 {{ $category?->id === $child->id ? 'font-extrabold text-brand-700' : '' }}">{{ $child->name_ar }}</a>
                                                    @if($category->id === $child->id || $child->children->contains('id', $category->id))
                                                        <ul class="ms-3 border-s border-slate-200 ps-2 text-[13px] dark:border-slate-700">
                                                            @foreach($child->children as $inner)<li><a href="{{ $inner->url }}" class="block py-0.5 text-slate-500 hover:text-brand-600 {{ $category->id === $inner->id ? 'font-extrabold text-brand-700' : '' }}">{{ $inner->name_ar }}</a></li>@endforeach
                                                        </ul>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <button type="button" wire:click="clearFilters" class="btn-ghost btn-sm w-full">مسح الفلاتر</button>
                </div>
            </aside>

            <section class="lg:col-span-3">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm text-slate-500">{{ $products->total() }} منتج</p>
                    <div class="flex items-center gap-2">
                        <label for="sort" class="text-sm font-bold">ترتيب:</label>
                        <select id="sort" wire:model.live="sort" class="input !w-auto !py-2">
                            <option value="">الافتراضي</option>
                            @foreach($sorts as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                        </select>
                    </div>
                </div>

                <div wire:loading.delay.class="hidden" wire:target="q,sort,min,max,inStock,clearFilters,gotoPage,nextPage,previousPage">
                    @if($products->isEmpty())
                        <x-empty-state icon="search" title="لا توجد منتجات مطابقة" text="جرّب تعديل كلمات البحث أو إزالة بعض الفلاتر.">
                            <button type="button" wire:click="clearFilters" class="btn-primary">مسح الفلاتر</button>
                        </x-empty-state>
                    @else
                        <div class="grid grid-cols-2 gap-4 xl:grid-cols-3">
                            @foreach($products as $product)
                                <x-product-card :product="$product" :in-wishlist="in_array($product->id, $this->wishlistIds)" wire:key="p-{{ $product->id }}" />
                            @endforeach
                        </div>
                        <div class="mt-8">{{ $products->links() }}</div>
                    @endif
                </div>

                <div wire:loading.delay wire:target="q,sort,min,max,inStock,clearFilters,gotoPage,nextPage,previousPage">
                    <x-skeleton-grid :count="6" />
                </div>
            </section>
        </div>
    </div>
</div>
