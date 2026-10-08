@props(['title' => 'لوحة التحكم'])
@php
    $unread = auth()->user()->unreadNotifications()->count();
    $nav = [
        ['admin.dashboard', 'لوحة المعلومات', 'chart', 'access-admin'],
        ['admin.products.index', 'المنتجات', 'cube', 'manage-products'],
        ['admin.categories', 'التصنيفات', 'grid', 'manage-categories'],
        ['admin.orders.index', 'الطلبات', 'orders', 'manage-orders'],
        ['admin.customers.index', 'العملاء', 'users', 'manage-customers'],
        ['admin.coupons', 'الكوبونات', 'ticket', 'manage-coupons'],
        ['admin.shipping', 'الشحن', 'truck', 'manage-shipping'],
        ['admin.reviews', 'التقييمات', 'star', 'manage-reviews'],
        ['admin.messages', 'الرسائل', 'mail', 'manage-settings'],
        ['admin.settings', 'الإعدادات', 'cog', 'manage-settings'],
    ];
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} | {{ setting('store_name') }}</title>
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body x-data="{ sidebar: false }"
      x-init="@if(session('success')) $store.toasts.add(@js(session('success')), 'success'); @endif
              @if(session('error')) $store.toasts.add(@js(session('error')), 'error'); @endif">
    <div class="flex min-h-screen">
        <aside class="fixed inset-y-0 start-0 z-50 w-64 transform border-e border-slate-200 bg-white transition dark:border-slate-800 dark:bg-slate-900 lg:static lg:translate-x-0 max-lg:rtl:translate-x-full max-lg:ltr:-translate-x-full"
               :class="sidebar ? 'max-lg:translate-x-0!' : ''">
            <div class="flex h-16 items-center justify-between border-b border-slate-200 px-5 dark:border-slate-800">
                <a href="{{ route('admin.dashboard') }}" class="text-lg font-extrabold text-brand-700 dark:text-brand-400">{{ setting('store_name') }}</a>
                <button class="lg:hidden" @click="sidebar = false" aria-label="إغلاق"><x-icon name="close" /></button>
            </div>
            <nav class="space-y-1 p-3">
                @foreach($nav as [$route, $label, $icon, $perm])
                    @can($perm)
                        @php $active = request()->routeIs($route) || (str_ends_with($route, '.index') && request()->routeIs(\Illuminate\Support\Str::beforeLast($route, '.index').'.*')); @endphp
                        <a href="{{ route($route) }}"
                           class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-bold transition {{ $active ? 'bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800' }}">
                            <x-icon :name="$icon" class="h-5 w-5" /> {{ $label }}
                        </a>
                    @endcan
                @endforeach
            </nav>
        </aside>
        <div x-show="sidebar" x-cloak class="fixed inset-0 z-40 bg-black/40 lg:hidden" @click="sidebar = false"></div>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/90 px-4 backdrop-blur dark:border-slate-800 dark:bg-slate-900/90 sm:px-6">
                <button class="btn-ghost !p-2 lg:hidden" @click="sidebar = true" aria-label="القائمة"><x-icon name="menu" class="h-6 w-6" /></button>
                <h1 class="text-lg font-extrabold">{{ $title }}</h1>
                <div class="ms-auto flex items-center gap-1">
                    <a href="{{ route('home') }}" target="_blank" class="btn-ghost btn-sm">عرض المتجر</a>
                    <button class="btn-ghost !p-2" @click="$store.theme.toggle()" aria-label="الوضع الداكن">
                        <x-icon name="moon" x-show="!$store.theme.dark" />
                        <x-icon name="sun" x-show="$store.theme.dark" x-cloak />
                    </button>
                    <a href="{{ route('admin.notifications') }}" class="btn-ghost relative !p-2" aria-label="الإشعارات">
                        <x-icon name="bell" />
                        @if($unread)<span class="absolute end-0.5 top-0.5 grid h-4 min-w-4 place-items-center rounded-full bg-rose-600 px-1 text-[10px] font-bold text-white">{{ $unread }}</span>@endif
                    </a>
                    <form method="POST" action="{{ route('logout') }}">@csrf
                        <button class="btn-ghost btn-sm text-rose-600"><x-icon name="logout" class="h-4 w-4" /> خروج</button>
                    </form>
                </div>
            </header>
            <main class="flex-1 p-4 sm:p-6">
                {{ $slot }}
            </main>
        </div>
    </div>
    <x-toast-container />
    @livewireScriptConfig
</body>
</html>
