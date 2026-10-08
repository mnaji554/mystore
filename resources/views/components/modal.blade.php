@props(['name', 'title' => null, 'maxWidth' => 'max-w-lg'])
{{-- Alpine modal: open with $dispatch('open-modal', 'name') or x-on:click="$dispatch('open-modal','name')" --}}
<div x-data="{ show: false }" x-on:open-modal.window="if ($event.detail === '{{ $name }}') show = true"
     x-on:close-modal.window="if ($event.detail === '{{ $name }}') show = false" x-on:keydown.escape.window="show = false"
     x-show="show" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center p-4" role="dialog" aria-modal="true">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" x-show="show" x-transition.opacity @click="show = false"></div>
    <div x-show="show" x-transition class="card relative w-full {{ $maxWidth }} p-6 shadow-2xl">
        @if($title)
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-extrabold">{{ $title }}</h3>
                <button type="button" @click="show = false" class="btn-ghost !p-1.5" aria-label="إغلاق"><x-icon name="close" /></button>
            </div>
        @endif
        {{ $slot }}
    </div>
</div>
