@props([
    'title' => null,
    'header' => null,
    'action' => null,
])

<x-layouts.app :title="$title" :header="$header" :action="$action" {{ $attributes }}>
    {{ $slot }}
</x-layouts.app>
