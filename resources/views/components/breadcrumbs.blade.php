@props(['items' => []])
<nav aria-label="مسار التنقل" class="container-x py-4 text-sm text-slate-500">
    <ol class="flex flex-wrap items-center gap-1.5">
        <li><a href="{{ route('home') }}" class="hover:text-brand-600">الرئيسية</a></li>
        @foreach($items as $label => $url)
            <li class="flex items-center gap-1.5">
                <x-icon name="chevron-left" class="h-3.5 w-3.5" />
                @if($url && ! $loop->last)
                    <a href="{{ $url }}" class="hover:text-brand-600">{{ $label }}</a>
                @else
                    <span class="font-bold text-slate-700 dark:text-slate-300" aria-current="page">{{ $label }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
