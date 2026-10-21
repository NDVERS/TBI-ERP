@props([
    'label' => true,
    'size' => 'sm',
])

<button
    type="button"
    x-data="{
        compact: localStorage.getItem('tbi_table_compact') === 'true',
        toggle() {
            this.compact = !this.compact;
            localStorage.setItem('tbi_table_compact', this.compact);
            window.dispatchEvent(new CustomEvent('tbi-density-toggle', { detail: this.compact }));
        }
    }"
    @click="toggle()"
    :title="compact ? 'Tampilan Kompak aktif. Klik untuk kembali ke Normal.' : 'Beralih ke mode tampilan Kompak (rapat).'"
    {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-semibold rounded-md border transition-all cursor-pointer select-none']) }}
    :class="compact ? 'bg-zinc-900 text-white border-zinc-900 shadow-xs' : 'bg-white hover:bg-zinc-50 text-zinc-700 border-zinc-200 shadow-2xs'"
>
    {{-- Icon Normal (Dense lines) vs Compact (Super dense) --}}
    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
    </svg>
    @if ($label)
        <span x-text="compact ? 'Tabel: Kompak' : 'Tabel: Normal'"></span>
    @endif
</button>
