@props(['icon' => 'cube', 'title', 'text' => null])
<div {{ $attributes->merge(['class' => 'card flex flex-col items-center px-6 py-14 text-center']) }}>
    <span class="grid h-16 w-16 place-items-center rounded-full bg-slate-100 text-slate-400 dark:bg-slate-800"><x-icon :name="$icon" class="h-8 w-8" /></span>
    <h3 class="mt-4 text-lg font-extrabold">{{ $title }}</h3>
    @if($text)<p class="mt-1 max-w-md text-sm text-slate-500">{{ $text }}</p>@endif
    <div class="mt-5">{{ $slot }}</div>
</div>
