<div>
    <section class="container-x pt-6">
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-l from-brand-700 via-brand-600 to-violet-600 px-6 py-12 text-white shadow-lg sm:px-12 sm:py-16">
            <div class="absolute -start-16 -top-16 h-64 w-64 rounded-full bg-white/10 blur-2xl"></div>
            <div class="absolute -bottom-20 end-10 h-72 w-72 rounded-full bg-amber-300/20 blur-3xl"></div>
            <div class="relative max-w-xl animate-slide-up">
                <span class="badge bg-white/15 text-white">عروض الموسم</span>
                <h1 class="mt-4 text-3xl font-extrabold leading-tight sm:text-5xl">{{ setting('store_name') }}<br>تسوّق بذكاء، وفّر أكثر</h1>
                <p class="mt-4 text-base leading-8 text-white/85 sm:text-lg">{{ setting('store_tagline') }}. آلاف المنتجات بأسعار تنافسية وشحن سريع إلى باب بيتك.</p>
                <div class="mt-7 flex flex-wrap gap-3">
                    <a href="{{ route('products.index') }}" class="btn bg-white !px-6 !py-3 text-brand-700 hover:bg-brand-50">تسوّق الآن</a>
                    <a href="{{ route('products.sale') }}" class="btn border border-white/40 !px-6 !py-3 text-white hover:bg-white/10">شاهد العروض</a>
                </div>
            </div>
        </div>
    </section>

    <section class="container-x mt-8">
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach([['truck', 'شحن سريع', 'توصيل خلال 1-3 أيام'], ['shield', 'دفع آمن', 'حماية كاملة لبياناتك'], ['tag', 'أفضل الأسعار', 'خصومات وكوبونات دائمة'], ['orders', 'إرجاع سهل', 'استرجاع خلال 14 يوماً']] as [$icon, $t, $d])
                <div class="card flex items-center gap-3 p-4">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300"><x-icon :name="$icon" class="h-6 w-6" /></span>
                    <div><p class="text-sm font-extrabold">{{ $t }}</p><p class="text-xs text-slate-500">{{ $d }}</p></div>
                </div>
            @endforeach
        </div>
    </section>

    @if($categories->isNotEmpty())
        <section class="container-x mt-12">
            <div class="mb-5 flex items-end justify-between">
                <h2 class="text-2xl font-extrabold">تسوّق حسب التصنيف</h2>
                <a href="{{ route('products.index') }}" class="text-sm font-bold text-brand-600 hover:underline">عرض الكل</a>
            </div>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                @foreach($categories->take(10) as $category)
                    <a href="{{ route('categories.show', $category->slug) }}" class="card group flex flex-col items-center gap-3 p-5 text-center transition hover:-translate-y-0.5 hover:shadow-lg">
                        @if($category->image_url)
                            <img src="{{ $category->image_url }}" alt="{{ $category->name_ar }}" loading="lazy" class="h-16 w-16 rounded-full object-cover">
                        @else
                            <span class="grid h-16 w-16 place-items-center rounded-full bg-gradient-to-br from-brand-100 to-violet-100 text-brand-700 dark:from-brand-500/20 dark:to-violet-500/20 dark:text-brand-300"><x-icon name="grid" class="h-7 w-7" /></span>
                        @endif
                        <span class="text-sm font-extrabold group-hover:text-brand-600">{{ $category->name_ar }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @foreach([['المنتجات المميزة', $featured, route('products.index')], ['وصل حديثاً', $newest, route('products.new')], ['الأكثر مبيعاً', $bestsellers, route('products.bestsellers')], ['عروض وخصومات', $onSale, route('products.sale')]] as $sectionIndex => [$heading, $items, $url])
        @if($items->isNotEmpty())
            <section class="container-x mt-12">
                <div class="mb-5 flex items-end justify-between">
                    <h2 class="text-2xl font-extrabold">{{ $heading }}</h2>
                    <a href="{{ $url }}" class="text-sm font-bold text-brand-600 hover:underline">عرض المزيد</a>
                </div>
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    @foreach($items as $product)
                        <x-product-card :product="$product" :in-wishlist="in_array($product->id, $this->wishlistIds)" wire:key="home-{{ $sectionIndex }}-{{ $product->id }}" />
                    @endforeach
                </div>
            </section>
        @endif
    @endforeach

    @if($featured->isEmpty() && $newest->isEmpty() && $bestsellers->isEmpty() && $onSale->isEmpty())
        <section class="container-x mt-12">
            <x-empty-state icon="cube" title="لا توجد منتجات بعد" text="سيتم عرض المنتجات هنا فور إضافتها من لوحة التحكم." />
        </section>
    @endif
</div>
