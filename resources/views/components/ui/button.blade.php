@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
    'disabled' => false,
    'icon' => null,
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-medium transition-colors select-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-1 disabled:opacity-50 disabled:pointer-events-none rounded-md gap-2';

    $variants = [
        'primary' => 'bg-zinc-900 text-white hover:bg-zinc-800 active:bg-zinc-950 focus-visible:ring-zinc-900 shadow-xs',
        'secondary' => 'bg-zinc-100 text-zinc-900 hover:bg-zinc-200 active:bg-zinc-300 border border-zinc-200 focus-visible:ring-zinc-400',
        'outline' => 'bg-white text-zinc-700 hover:bg-zinc-50 active:bg-zinc-100 border border-zinc-300 focus-visible:ring-zinc-400 shadow-xs',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 active:bg-red-800 focus-visible:ring-red-600 shadow-xs',
        'ghost' => 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 active:bg-zinc-200 focus-visible:ring-zinc-400',
        'emerald' => 'bg-emerald-600 text-white hover:bg-emerald-700 active:bg-emerald-800 focus-visible:ring-emerald-600 shadow-xs',
    ];

    $sizes = [
        'sm' => 'h-8 px-2.5 text-xs',
        'md' => 'h-11 md:h-10 px-4 text-sm min-h-[44px] md:min-h-0', // >=44px tap target on tablet
        'lg' => 'h-12 px-5 text-base',
        'icon' => 'h-11 w-11 md:h-10 md:w-10 p-2 text-sm min-h-[44px] md:min-h-0',
    ];

    $classes = "{$baseClasses} " . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)
            <span class="shrink-0">{{ $icon }}</span>
        @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $disabled ? 'disabled' : '' }} {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)
            <span class="shrink-0">{{ $icon }}</span>
        @endif
        {{ $slot }}
    </button>
@endif
