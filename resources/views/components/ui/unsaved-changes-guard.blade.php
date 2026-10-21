@props([
    'message' => 'Ada data pada form yang belum disimpan. Yakin ingin meninggalkan halaman ini?',
    'showIndicator' => true,
])

<div
    x-data="{
        isDirty: false,
        init() {
            const form = this.$el.closest('form');
            if (form) {
                form.addEventListener('input', () => { this.isDirty = true; });
                form.addEventListener('change', () => { this.isDirty = true; });
                form.addEventListener('submit', () => { this.isDirty = false; });
            }
            window.addEventListener('beforeunload', (e) => {
                if (this.isDirty) {
                    e.preventDefault();
                    e.returnValue = '{{ addslashes($message) }}';
                    return e.returnValue;
                }
            });
        }
    }"
    @form-saved.window="isDirty = false"
    @reset-dirty.window="isDirty = false"
    class="inline-block"
>
    @if ($showIndicator)
        <span
            x-show="isDirty"
            style="display: none;"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-mono bg-amber-50 text-amber-800 border border-amber-200"
        >
            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
            Belum Disimpan
        </span>
    @endif
</div>
