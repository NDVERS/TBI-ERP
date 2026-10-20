@props([
    'title' => null,
    'description' => null,
    'action' => null,
    'footer' => null,
    'padding' => 'p-5 md:p-6',
])

<div {{ $attributes->merge(['class' => 'flex flex-col bg-white border border-zinc-200 rounded-lg shadow-xs']) }}>
    @if ($title || $description || $action)
        <div class="flex items-start justify-between gap-4 p-5 md:p-6 pb-4 border-b border-zinc-100">
            <div class="flex flex-col gap-1">
                @if ($title)
                    <h3 class="text-base font-semibold text-zinc-900 tracking-tight leading-none">
                        {{ $title }}
                    </h3>
                @endif
                @if ($description)
                    <p class="text-xs md:text-sm text-zinc-500">
                        {{ $description }}
                    </p>
                @endif
            </div>

            @if ($action)
                <div class="flex items-center gap-2 shrink-0">
                    {{ $action }}
                </div>
            @endif
        </div>
    @endif

    <div class="{{ $padding }} flex-1">
        {{ $slot }}
    </div>

    @if ($footer)
        <div class="px-5 md:px-6 py-3.5 bg-zinc-50/70 border-t border-zinc-100 rounded-b-lg">
            {{ $footer }}
        </div>
    @endif
</div>
