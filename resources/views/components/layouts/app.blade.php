@props([
    'title' => null,
    'description' => null,
    'canonical' => null,
    'image' => null,
    'ogType' => 'website',
    'noindex' => false,
    'jsonLd' => null,
])
@php
    $storeName = setting('store_name');
    $pageTitle = $title ? $title.' | '.$storeName : $storeName.' - '.setting('store_tagline');
    $metaDescription = \Illuminate\Support\Str::limit(strip_tags($description ?: setting('meta_description')), 160);
    $canonicalUrl = $canonical ?: url()->current();
    $ogImage = $image ?: \App\Models\Setting::logoUrl();
    $categoriesTree = app(\App\Repositories\CategoryRepository::class)->tree();
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    @if($noindex)<meta name="robots" content="noindex, nofollow">@endif

    <meta property="og:site_name" content="{{ $storeName }}">
    <meta property="og:locale" content="ar_AR">
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:title" content="{{ $title ?: $storeName }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    @if($ogImage)<meta property="og:image" content="{{ $ogImage }}">@endif
    <meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $title ?: $storeName }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    @if($ogImage)<meta name="twitter:image" content="{{ $ogImage }}">@endif

    @if($jsonLd)
        @foreach((isset($jsonLd[0]) ? $jsonLd : [$jsonLd]) as $schema)
            <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
        @endforeach
    @endif

    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex min-h-screen flex-col"
      x-data
      x-init="@if(session('success')) $store.toasts.add(@js(session('success')), 'success'); @endif
              @if(session('error')) $store.toasts.add(@js(session('error')), 'error'); @endif
              @if(session('status')) $store.toasts.add(@js(session('status')), 'success'); @endif">

    <div class="bg-brand-700 px-4 py-2 text-center text-xs font-bold text-white sm:text-sm">
        <x-icon name="truck" class="me-1 inline h-4 w-4" /> شحن سريع لجميع المدن · دفع آمن · إرجاع سهل خلال 14 يوماً
    </div>

    <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/90 backdrop-blur dark:border-slate-800 dark:bg-slate-900/90"
            x-data="{ mobile: false }">
        <div class="container-x flex h-16 items-center gap-3">
            <button class="btn-ghost !p-2 lg:hidden" @click="mobile = !mobile" aria-label="القائمة">
                <x-icon name="menu" class="h-6 w-6" x-show="!mobile" />
                <x-icon name="close" class="h-6 w-6" x-show="mobile" x-cloak />
            </button>

            <a href="{{ route('home') }}" class="flex items-center gap-2 text-xl font-extrabold text-brand-700 dark:text-brand-400">
                @if($logo = \App\Models\Setting::logoUrl())
                    <img src="{{ $logo }}" alt="{{ $storeName }}" class="h-9 w-auto" loading="eager">
                @else
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-600 text-white"><x-icon name="cart" class="h-5 w-5" /></span>
                @endif
                <span class="hidden sm:inline">{{ $storeName }}</span>
            </a>

            <form action="{{ route('search') }}" method="GET" class="mx-2 hidden flex-1 md:block lg:mx-6" role="search">
                <div class="relative">
                    <input type="search" name="q" value="{{ request('q') }}" maxlength="100" placeholder="ابحث عن منتج، رمز SKU أو تصنيف…"
                           class="input !rounded-full !pe-12" aria-label="بحث">
                    <button class="absolute inset-y-0 end-1 my-1 grid w-10 place-items-center rounded-full bg-brand-600 text-white hover:bg-brand-700" aria-label="بحث">
                        <x-icon name="search" class="h-4 w-4" />
                    </button>
                </div>
            </form>

            <div class="ms-auto flex items-center gap-1 md:ms-0">
                <button class="btn-ghost !p-2" @click="$store.theme.toggle()" aria-label="تبديل الوضع الداكن">
                    <x-icon name="moon" class="h-5 w-5" x-show="!$store.theme.dark" />
                    <x-icon name="sun" class="h-5 w-5" x-show="$store.theme.dark" x-cloak />
                </button>

                <livewire:wishlist-counter />
                <livewire:cart-counter />

                @auth
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                        <button class="btn-ghost !px-2" @click="open = !open" aria-haspopup="true" :aria-expanded="open">
                            <x-icon name="user" class="h-5 w-5" />
                            <span class="hidden max-w-24 truncate text-sm lg:inline">{{ auth()->user()->name }}</span>
                            <x-icon name="chevron-down" class="h-4 w-4" />
                        </button>
                        <div x-show="open" x-cloak x-transition.opacity class="card absolute end-0 mt-2 w-56 overflow-hidden py-1 shadow-xl">
                            @can('access-admin')
                                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm font-bold text-brand-700 hover:bg-slate-50 dark:text-brand-300 dark:hover:bg-slate-800"><x-icon name="chart" class="h-4 w-4" /> لوحة التحكم</a>
                            @endcan
                            <a href="{{ route('account.dashboard') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-slate-50 dark:hover:bg-slate-800"><x-icon name="user" class="h-4 w-4" /> حسابي</a>
                            <a href="{{ route('account.orders') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-slate-50 dark:hover:bg-slate-800"><x-icon name="orders" class="h-4 w-4" /> طلباتي</a>
                            <a href="{{ route('wishlist') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-slate-50 dark:hover:bg-slate-800"><x-icon name="heart" class="h-4 w-4" /> المفضلة</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="flex w-full items-center gap-2 border-t border-slate-100 px-4 py-2.5 text-sm text-rose-600 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800"><x-icon name="logout" class="h-4 w-4" /> تسجيل الخروج</button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="btn-primary btn-sm ms-1">دخول</a>
                @endauth
            </div>
        </div>

        <x-mega-menu :categories="$categoriesTree" />

        <div x-show="mobile" x-cloak x-transition class="border-t border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900 lg:hidden">
            <form action="{{ route('search') }}" method="GET" class="mb-3 md:hidden">
                <input type="search" name="q" placeholder="ابحث عن منتج…" class="input" aria-label="بحث">
            </form>
            <div class="grid grid-cols-2 gap-2 text-sm font-bold">
                <a class="btn-outline" href="{{ route('products.index') }}">كل المنتجات</a>
                <a class="btn-outline" href="{{ route('products.new') }}">جديد</a>
                <a class="btn-outline" href="{{ route('products.bestsellers') }}">الأكثر مبيعاً</a>
                <a class="btn-outline" href="{{ route('products.sale') }}">العروض</a>
            </div>
            <div class="mt-3 divide-y divide-slate-100 text-sm font-bold dark:divide-slate-800">
                @foreach($categoriesTree as $cat)
                    <div x-data="{ o: false }">
                        <div class="flex items-center justify-between">
                            <a href="{{ route('categories.show', $cat->slug) }}" class="flex-1 py-2.5">{{ $cat->name_ar }}</a>
                            @if($cat->children->isNotEmpty())
                                <button type="button" @click="o = !o" class="p-2" aria-label="الأقسام الفرعية"><x-icon name="chevron-down" class="h-4 w-4 transition" ::class="o ? 'rotate-180' : ''" /></button>
                            @endif
                        </div>
                        @if($cat->children->isNotEmpty())
                            <div x-show="o" x-cloak class="space-y-2 pb-3 ps-3 font-medium">
                                @foreach($cat->children as $sub)
                                    <div>
                                        <a href="{{ route('categories.show', $sub->slug) }}" class="block py-1 font-bold text-brand-700 dark:text-brand-300">{{ $sub->name_ar }}</a>
                                        <div class="flex flex-wrap gap-x-4 gap-y-1 text-slate-500">
                                            @foreach($sub->children as $inner)<a href="{{ route('categories.show', $inner->slug) }}">{{ $inner->name_ar }}</a>@endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </header>

    <main class="flex-1">
        {{ $slot }}
    </main>

    <footer class="mt-16 border-t border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <div class="container-x grid gap-8 py-10 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <p class="text-lg font-extrabold text-brand-700 dark:text-brand-400">{{ $storeName }}</p>
                <p class="mt-2 text-sm leading-7 text-slate-500 dark:text-slate-400">{{ setting('store_tagline') }}</p>
            </div>
            <div>
                <p class="mb-3 font-extrabold">تسوق</p>
                <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-400">
                    <li><a class="hover:text-brand-600" href="{{ route('products.index') }}">كل المنتجات</a></li>
                    <li><a class="hover:text-brand-600" href="{{ route('products.new') }}">وصل حديثاً</a></li>
                    <li><a class="hover:text-brand-600" href="{{ route('products.bestsellers') }}">الأكثر مبيعاً</a></li>
                    <li><a class="hover:text-brand-600" href="{{ route('products.sale') }}">المنتجات المخفضة</a></li>
                    <li><a class="hover:text-brand-600" href="{{ route('coupons') }}">الكوبونات</a></li>
                </ul>
            </div>
            <div>
                <p class="mb-3 font-extrabold">خدمة العملاء</p>
                <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-400">
                    <li><a class="hover:text-brand-600" href="{{ route('orders.track') }}">تتبع الطلب</a></li>
                    <li><a class="hover:text-brand-600" href="{{ route('contact') }}">تواصل معنا</a></li>
                    <li><a class="hover:text-brand-600" href="{{ route('pages.returns') }}">سياسة الاسترجاع</a></li>
                    <li><a class="hover:text-brand-600" href="{{ route('pages.about') }}">من نحن</a></li>
                    <li><a class="hover:text-brand-600" href="{{ route('pages.privacy') }}">سياسة الخصوصية</a></li>
                    <li><a class="hover:text-brand-600" href="{{ route('pages.terms') }}">الشروط والأحكام</a></li>
                </ul>
            </div>
            <div>
                <p class="mb-3 font-extrabold">تواصل معنا</p>
                <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-400">
                    <li class="flex items-center gap-2"><x-icon name="phone" class="h-4 w-4" /> <span dir="ltr">{{ setting('contact_phone') }}</span></li>
                    <li class="flex items-center gap-2"><x-icon name="mail" class="h-4 w-4" /> {{ setting('contact_email') }}</li>
                    <li class="flex items-center gap-2"><x-icon name="map-pin" class="h-4 w-4" /> {{ setting('contact_address') }}</li>
                </ul>
            </div>
        </div>
        <div class="border-t border-slate-100 py-4 text-center text-xs text-slate-500 dark:border-slate-800">
            © {{ date('Y') }} {{ $storeName }}. جميع الحقوق محفوظة.
        </div>
    </footer>

    <x-toast-container />
    @livewireScriptConfig
</body>
</html>
