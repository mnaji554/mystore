@props(['title', 'heading' => null])
<x-layouts.app :title="$title" :noindex="true">
    <x-account-shell :title="$title" :heading="$heading">{{ $slot }}</x-account-shell>
</x-layouts.app>
