@props(['label' => null, 'name' => null, 'type' => 'text', 'hint' => null, 'model' => null, 'live' => false])
@php
    $name = $name ?? $model ?? $attributes->get('name');
    $errorKey = $model ?? $name;
    $id = 'f-'.str_replace(['.', '[', ']'], '-', (string) ($model ?? $name)).'-'.substr(md5(uniqid('', true)), 0, 5);
@endphp
<div class="{{ $attributes->get('wrapper', '') }}">
    @if($label)<label for="{{ $id }}" class="label">{{ $label }}</label>@endif
    <input id="{{ $id }}" type="{{ $type }}"
           @if($model) wire:model{{ $live ? '.live.debounce.400ms' : '' }}="{{ $model }}" @elseif($name) name="{{ $name }}" @endif
           @if(! $model && $name && ! $attributes->has('value') && ! in_array($type, ['password', 'file'])) value="{{ old($name) }}" @endif
           {{ $attributes->except(['wrapper', 'name'])->merge(['class' => 'input']) }}>
    @if($hint)<p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>@endif
    @if($errorKey)
        @error($errorKey)<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
    @endif
</div>
