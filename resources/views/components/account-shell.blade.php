@props(['title', 'heading' => null])
    <div class="container-x py-8">
        <div class="grid gap-6 lg:grid-cols-4">
            <aside class="lg:col-span-1">
                <nav class="card overflow-hidden p-2 text-sm font-bold" aria-label="حسابي">
                    <div class="mb-1 flex items-center gap-3 border-b border-slate-100 p-3 dark:border-slate-800">
                        <span class="grid h-11 w-11 place-items-center rounded-full bg-brand-600 text-lg font-extrabold text-white">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                        <div class="min-w-0"><p class="truncate font-extrabold">{{ auth()->user()->name }}</p><p class="truncate text-xs font-normal text-slate-500" dir="ltr">{{ auth()->user()->email }}</p></div>
                    </div>
                    @foreach([
                        ['account.dashboard', 'لوحة الحساب', 'home'],
                        ['account.orders', 'طلباتي', 'orders'],
                        ['wishlist', 'المفضلة', 'heart'],
                        ['account.addresses', 'عناويني', 'map-pin'],
                        ['account.reviews', 'تقييماتي', 'star'],
                        ['coupons', 'الكوبونات', 'ticket'],
                        ['account.profile', 'الملف الشخصي', 'user'],
                        ['account.password', 'تغيير كلمة المرور', 'lock'],
                    ] as [$route, $label, $icon])
                        <a href="{{ route($route) }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition {{ request()->routeIs($route) || request()->routeIs($route.'.*') ? 'bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300' : 'hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                            <x-icon :name="$icon" class="h-5 w-5" /> {{ $label }}
                        </a>
                    @endforeach
                </nav>
            </aside>
            <section class="lg:col-span-3">
                <h1 class="mb-5 text-2xl font-extrabold">{{ $heading ?? $title }}</h1>
                {{ $slot }}
            </section>
        </div>
    </div>
