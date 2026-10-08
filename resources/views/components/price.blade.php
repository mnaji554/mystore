@props(['regular', 'final', 'from' => false, 'size' => 'md'])
@php $onSale = (float) $regular > (float) $final; @endphp
<div {{ $attributes->merge(['class' => 'flex flex-wrap items-baseline gap-x-2']) }}>
    @if($from)<span class="text-xs text-slate-500">ابتداءً من</span>@endif
    <span class="{{ $size === 'lg' ? 'text-3xl' : 'text-base' }} font-extrabold {{ $onSale ? 'text-rose-600' : 'text-slate-900 dark:text-white' }}">{{ money($final) }}</span>
    @if($onSale)
        <span class="{{ $size === 'lg' ? 'text-base' : 'text-xs' }} text-slate-400 line-through">{{ money($regular) }}</span>
    @endif
</div>
