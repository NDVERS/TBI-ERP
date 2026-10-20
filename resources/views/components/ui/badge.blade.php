@props([
    'variant' => 'zinc',
    'size' => 'md',
    'dot' => false,
])

@php
    $baseClasses = 'inline-flex items-center font-medium rounded-md border select-none tracking-tight';

    $variants = [
        'emerald' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
        'amber' => 'bg-amber-50 text-amber-800 border-amber-200',
        'red' => 'bg-red-50 text-red-800 border-red-200',
        'zinc' => 'bg-zinc-100 text-zinc-700 border-zinc-200',
        'blue' => 'bg-blue-50 text-blue-800 border-blue-200',
    ];

    $dotColors = [
        'emerald' => 'bg-emerald-500',
        'amber' => 'bg-amber-500',
        'red' => 'bg-red-500',
        'zinc' => 'bg-zinc-400',
        'blue' => 'bg-blue-500',
    ];

    $sizes = [
        'sm' => 'text-[11px] px-1.5 py-0.5 gap-1',
        'md' => 'text-xs px-2.5 py-1 gap-1.5',
    ];

    $selectedVariant = $variants[$variant] ?? $variants['zinc'];
    $selectedSize = $sizes[$size] ?? $sizes['md'];
    $dotColor = $dotColors[$variant] ?? $dotColors['zinc'];
@endphp

<span {{ $attributes->merge(['class' => "{$baseClasses} {$selectedVariant} {$selectedSize}"]) }}>
    @if ($dot)
        <span class="w-1.5 h-1.5 rounded-full shrink-0 {{ $dotColor }}"></span>
    @endif
    {{ $slot }}
</span>
