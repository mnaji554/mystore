@props(['status'])
@php
    $enum = $status instanceof \BackedEnum ? $status : null;
    $color = $enum ? $enum->color() : 'slate';
    $label = $enum ? $enum->label() : $status;
@endphp
<span {{ $attributes->merge(['class' => "badge badge-{$color}"]) }}>{{ $label }}</span>
