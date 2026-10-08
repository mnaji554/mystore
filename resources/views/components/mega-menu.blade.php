@props(['categories'])
{{-- Desktop mega menu: roots on the start side, sub categories + inner categories on the panel (AliExpress style). --}}
<nav class="hidden border-t border-slate-100 dark:border-slate-800 lg:block" aria-label="التصنيفات"
     x-data="{ open: false, active: {{ $categories->first()?->id ?? 'null' }} }" @mouseleave="open = false" @keydown.escape.window="open = false">
    <div class="container-x relative flex items-center gap-1 text-sm font-bold">
        <button type="button" @mouseenter="open = true" @click="open = !open" :aria-expanded="open"
                class="flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-white hover:bg-brand-700">
            <x-icon name="menu" class="h-4 w-4" /> كل الأقسام
        </button>
        <a href="{{ route('products.index') }}" class="rounded-lg px-3 py-2.5 hover:bg-slate-100 dark:hover:bg-slate-800">كل المنتجات</a>
        @foreach($categories->take(6) as $cat)
            <a href="{{ route('categories.show', $cat->slug) }}" @mouseenter="open = true; active = {{ $cat->id }}"
               class="rounded-lg px-3 py-2.5 hover:bg-slate-100 dark:hover:bg-slate-800">{{ $cat->name_ar }}</a>
        @endforeach
        <span class="ms-auto flex gap-1">
            <a href="{{ route('products.new') }}" class="rounded-lg px-3 py-2.5 text-emerald-600 hover:bg-slate-100 dark:hover:bg-slate-800">جديد</a>
            <a href="{{ route('products.bestsellers') }}" class="rounded-lg px-3 py-2.5 hover:bg-slate-100 dark:hover:bg-slate-800">الأكثر مبيعاً</a>
            <a href="{{ route('products.sale') }}" class="rounded-lg px-3 py-2.5 text-rose-600 hover:bg-slate-100 dark:hover:bg-slate-800">العروض</a>
        </span>

        <div x-show="open" x-cloak x-transition.opacity.duration.150ms
             class="card absolute inset-x-4 top-full z-50 mt-px flex overflow-hidden shadow-2xl sm:inset-x-6 lg:inset-x-8" style="height: 26rem">
            <ul class="w-60 shrink-0 overflow-y-auto border-e border-slate-100 bg-slate-50 py-2 dark:border-slate-800 dark:bg-slate-950/40">
                @foreach($categories as $cat)
                    <li>
                        <a href="{{ route('categories.show', $cat->slug) }}" @mouseenter="active = {{ $cat->id }}"
                           class="flex items-center justify-between px-4 py-2.5 transition"
                           :class="active === {{ $cat->id }} ? 'bg-white text-brand-700 dark:bg-slate-900 dark:text-brand-300' : 'hover:text-brand-600'">
                            {{ $cat->name_ar }}
                            @if($cat->children->isNotEmpty())<x-icon name="chevron-left" class="h-3.5 w-3.5 text-slate-400" />@endif
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="flex-1 overflow-y-auto p-6">
                @foreach($categories as $cat)
                    <div x-show="active === {{ $cat->id }}" x-cloak>
                        <a href="{{ route('categories.show', $cat->slug) }}" class="mb-4 inline-flex items-center gap-1 text-lg font-extrabold hover:text-brand-600">
                            {{ $cat->name_ar }} <x-icon name="chevron-left" class="h-4 w-4" />
                        </a>
                        @if($cat->children->isEmpty())
                            <p class="text-sm font-normal text-slate-500">تصفّح كل منتجات هذا القسم.</p>
                        @else
                            <div class="grid grid-cols-2 gap-x-6 gap-y-5 xl:grid-cols-3">
                                @foreach($cat->children as $sub)
                                    <div>
                                        <a href="{{ route('categories.show', $sub->slug) }}" class="mb-1.5 block font-extrabold text-slate-900 hover:text-brand-600 dark:text-white">{{ $sub->name_ar }}</a>
                                        <ul class="space-y-1 text-[13px] font-medium text-slate-500 dark:text-slate-400">
                                            @foreach($sub->children as $inner)
                                                <li><a href="{{ route('categories.show', $inner->slug) }}" class="hover:text-brand-600 hover:underline">{{ $inner->name_ar }}</a></li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</nav>
