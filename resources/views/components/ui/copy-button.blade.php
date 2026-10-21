@props([
    'text' => '',
    'label' => null,
    'size' => 'sm',
    'title' => 'Salin teks ke clipboard',
])

<button
    type="button"
    x-data="{ copied: false }"
    @click.stop="
        navigator.clipboard.writeText('{{ addslashes($text) }}');
        copied = true;
        setTimeout(() => copied = false, 2000);
    "
    title="{{ $title }}"
    {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-md border border-zinc-200 bg-white hover:bg-zinc-50 active:bg-zinc-100 text-zinc-700 shadow-2xs transition-all cursor-pointer select-none']) }}
>
    <span x-show="!copied" class="inline-flex items-center gap-1">
        <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
        </svg>
        @if ($label)
            <span>{{ $label }}</span>
        @endif
    </span>

    <span x-show="copied" style="display: none;" class="inline-flex items-center gap-1 text-emerald-600 font-bold">
        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
        </svg>
        <span>Tersalin!</span>
    </span>
</button>
