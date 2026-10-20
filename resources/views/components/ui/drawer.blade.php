@props([
    'model' => 'open',
    'title' => null,
    'description' => null,
    'maxWidth' => 'max-w-md',
])

<div
    x-show="{{ $model }}"
    class="fixed inset-0 z-50 overflow-hidden"
    style="display: none;"
    x-cloak
    @keydown.escape.window="{{ $model }} = false"
>
    <!-- Neutral Backdrop (Zinc Anti-Slop) -->
    <div
        x-show="{{ $model }}"
        x-transition:enter="transition-opacity ease-linear duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="{{ $model }} = false"
        class="fixed inset-0 bg-zinc-950/40 backdrop-blur-xs"
    ></div>

    <div class="fixed inset-y-0 right-0 max-w-full flex pl-6 sm:pl-10">
        <!-- Slide-Over Panel Container -->
        <div
            x-show="{{ $model }}"
            x-transition:enter="transform transition ease-in-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in-out duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="w-screen {{ $maxWidth }} bg-white border-l border-zinc-200 shadow-xl flex flex-col justify-between"
        >
            <!-- Drawer Header -->
            <div class="p-5 md:p-6 pb-4 border-b border-zinc-100 flex items-start justify-between gap-4">
                <div class="flex flex-col gap-1 min-w-0 pr-2">
                    @if ($title)
                        <h3 class="text-base font-semibold text-zinc-900 tracking-tight leading-snug">
                            {{ $title }}
                        </h3>
                    @endif
                    @if ($description)
                        <p class="text-xs text-zinc-500">
                            {{ $description }}
                        </p>
                    @endif
                </div>

                <!-- Touch-friendly Close Button (>= 44px tap target) -->
                <button
                    type="button"
                    @click="{{ $model }} = false"
                    class="h-11 w-11 shrink-0 flex items-center justify-center text-zinc-400 hover:text-zinc-700 hover:bg-zinc-100 active:bg-zinc-200 rounded-md focus:outline-none focus:ring-2 focus:ring-zinc-900 transition-colors"
                    aria-label="Tutup Drawer"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Drawer Body -->
            <div class="p-5 md:p-6 flex-1 overflow-y-auto">
                {{ $slot }}
            </div>

            <!-- Drawer Footer (Optional) -->
            @if (isset($footer))
                <div class="p-4 md:p-5 bg-zinc-50/80 border-t border-zinc-100 flex items-center justify-end gap-2.5">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
</div>
