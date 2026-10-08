@props(['value' => 0, 'count' => null, 'size' => 'h-4 w-4'])
@php $rounded = (float) $value; @endphp
<div {{ $attributes->merge(['class' => 'flex items-center gap-1']) }} @if($count !== null) title="{{ number_format($rounded, 1) }} من 5" @endif>
    <span class="flex" aria-hidden="true">
        @for($i = 1; $i <= 5; $i++)
            <x-icon name="star-solid" class="{{ $size }} {{ $i <= round($rounded) ? 'text-amber-400' : 'text-slate-300 dark:text-slate-700' }}" />
        @endfor
    </span>
    @if($count !== null)<span class="text-xs text-slate-500">({{ $count }})</span>@endif
    <span class="sr-only">تقييم {{ number_format($rounded, 1) }} من 5</span>
</div>
