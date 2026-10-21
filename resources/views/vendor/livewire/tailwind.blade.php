@php
if (! isset($scrollTo)) {
    $scrollTo = 'body';
}

$scrollIntoViewJsSnippet = ($scrollTo !== false)
    ? <<<JS
       (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
    JS
    : '';

$current = $paginator->currentPage();
$last = $paginator->lastPage();

// Calculate compact page list: 1, (current-1), current, (current+1), last
$rawPages = [1, $last];
if ($current > 1) {
    $rawPages[] = $current - 1;
}
$rawPages[] = $current;
if ($current < $last) {
    $rawPages[] = $current + 1;
}

// Filter, unique, and sort
$rawPages = array_values(array_unique(array_filter($rawPages, fn ($p) => $p >= 1 && $p <= $last)));
sort($rawPages);

// Build list with ellipses where gap > 1
$compactPages = [];
$prev = null;
foreach ($rawPages as $p) {
    if ($prev !== null && $p - $prev > 1) {
        $compactPages[] = '...';
    }
    $compactPages[] = $p;
    $prev = $p;
}
@endphp

<div>
    @if ($paginator->hasPages() || isset($this->perPage) || $paginator->total() > 0)
        <nav role="navigation" aria-label="Pagination Navigation" class="flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-zinc-600">
            {{-- Mobile Pagination (< sm) --}}
            <div class="flex flex-col gap-2 w-full sm:hidden">
                <div class="flex items-center justify-between w-full">
                    @if ($paginator->onFirstPage())
                        <span class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-zinc-400 bg-zinc-50 border border-zinc-200 rounded-md cursor-not-allowed">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            <span>Sebelumnya</span>
                        </span>
                    @else
                        <button
                            type="button"
                            wire:click="previousPage('{{ $paginator->getPageName() }}')"
                            x-on:click="{{ $scrollIntoViewJsSnippet }}"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-zinc-700 bg-white hover:bg-zinc-50 border border-zinc-200 rounded-md transition-colors shadow-2xs"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            <span>Sebelumnya</span>
                        </button>
                    @endif

                    <span class="text-xs text-zinc-500 font-medium">
                        Hal <span class="font-semibold text-zinc-800">{{ $current }}</span> / {{ $last }}
                    </span>

                    @if ($paginator->hasMorePages())
                        <button
                            type="button"
                            wire:click="nextPage('{{ $paginator->getPageName() }}')"
                            x-on:click="{{ $scrollIntoViewJsSnippet }}"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-zinc-700 bg-white hover:bg-zinc-50 border border-zinc-200 rounded-md transition-colors shadow-2xs"
                        >
                            <span>Berikutnya</span>
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    @else
                        <span class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-zinc-400 bg-zinc-50 border border-zinc-200 rounded-md cursor-not-allowed">
                            <span>Berikutnya</span>
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </span>
                    @endif
                </div>

                {{-- Mobile Jump & Rows Control --}}
                <div class="flex items-center justify-between pt-2 border-t border-zinc-100 text-xs text-zinc-500">
                    @if (isset($this->perPage))
                        <div class="inline-flex items-center gap-1">
                            <span>Baris:</span>
                            <select
                                wire:model.live="perPage"
                                class="h-7 px-1.5 text-xs font-medium rounded border border-zinc-200 bg-white text-zinc-800 focus:outline-none focus:ring-1 focus:ring-zinc-900 shadow-2xs"
                            >
                                <option value="5">5</option>
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    @else
                        <div></div>
                    @endif

                    <div
                        x-data="{ jumpPage: '' }"
                        class="inline-flex items-center gap-1"
                    >
                        <span>Ke:</span>
                        <input
                            type="number"
                            min="1"
                            max="{{ $last }}"
                            placeholder="{{ $current }}"
                            x-model="jumpPage"
                            @keydown.enter.prevent="
                                let p = parseInt(jumpPage);
                                if (!isNaN(p)) {
                                    p = Math.max(1, Math.min(p, {{ $last }}));
                                    $wire.gotoPage(p, '{{ $paginator->getPageName() }}');
                                    jumpPage = '';
                                }
                            "
                            class="w-11 h-7 px-1 text-center text-xs font-mono font-semibold rounded border border-zinc-200 bg-white text-zinc-800 focus:outline-none focus:ring-1 focus:ring-zinc-900 shadow-2xs"
                        />
                        <button
                            type="button"
                            @click="
                                let p = parseInt(jumpPage);
                                if (!isNaN(p)) {
                                    p = Math.max(1, Math.min(p, {{ $last }}));
                                    $wire.gotoPage(p, '{{ $paginator->getPageName() }}');
                                    jumpPage = '';
                                }
                            "
                            class="h-7 px-2 text-xs font-medium text-zinc-700 bg-white hover:bg-zinc-50 border border-zinc-200 rounded transition-colors shadow-2xs"
                        >
                            Go
                        </button>
                    </div>
                </div>
            </div>

            {{-- Desktop Pagination (>= sm) --}}
            <div class="hidden sm:flex sm:items-center sm:justify-between w-full">
                {{-- Left: Record Summary & Rows Per Page --}}
                <div class="inline-flex items-center gap-3 text-xs text-zinc-500">
                    <div>
                        Menampilkan
                        <span class="font-semibold text-zinc-800">{{ number_format($paginator->firstItem() ?? 0, 0, ',', '.') }}</span>
                        sampai
                        <span class="font-semibold text-zinc-800">{{ number_format($paginator->lastItem() ?? 0, 0, ',', '.') }}</span>
                        dari
                        <span class="font-semibold text-zinc-900">{{ number_format($paginator->total(), 0, ',', '.') }}</span>
                        data
                    </div>

                    @if (isset($this->perPage))
                        <div class="inline-flex items-center gap-1.5 pl-3 border-l border-zinc-200">
                            <span>Tampilkan:</span>
                            <select
                                wire:model.live="perPage"
                                class="h-8 px-2 text-xs font-medium rounded-md border border-zinc-200 bg-white text-zinc-800 focus:outline-none focus:ring-1 focus:ring-zinc-900 shadow-2xs"
                            >
                                <option value="5">5</option>
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <span>per hal</span>
                        </div>
                    @endif
                </div>

                {{-- Right: Page Navigation Strip + Jump to Page --}}
                <div class="inline-flex items-center gap-2">
                    {{-- Compact Numbers Strip --}}
                    <div class="inline-flex items-center gap-1">
                        {{-- Previous Page Button --}}
                        @if ($paginator->onFirstPage())
                            <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}" class="inline-flex items-center justify-center h-8 w-8 text-zinc-300 bg-zinc-50/50 border border-zinc-200/60 rounded-md cursor-not-allowed">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </span>
                        @else
                            <button
                                type="button"
                                wire:click="previousPage('{{ $paginator->getPageName() }}')"
                                x-on:click="{{ $scrollIntoViewJsSnippet }}"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center justify-center h-8 w-8 text-zinc-600 hover:text-zinc-900 bg-white hover:bg-zinc-50 border border-zinc-200 hover:border-zinc-300 rounded-md transition-colors shadow-2xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-900"
                                aria-label="{{ __('pagination.previous') }}"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>
                        @endif

                        {{-- Compact Page Numbers --}}
                        @foreach ($compactPages as $item)
                            @if ($item === '...')
                                <span class="inline-flex items-center justify-center h-8 min-w-7 px-1 text-xs text-zinc-400 select-none">
                                    …
                                </span>
                            @elseif ($item == $current)
                                <span aria-current="page" class="inline-flex items-center justify-center h-8 min-w-8 px-2 text-xs font-semibold text-white bg-zinc-900 rounded-md shadow-xs select-none">
                                    {{ $item }}
                                </span>
                            @else
                                <button
                                    type="button"
                                    wire:click="gotoPage({{ $item }}, '{{ $paginator->getPageName() }}')"
                                    x-on:click="{{ $scrollIntoViewJsSnippet }}"
                                    class="inline-flex items-center justify-center h-8 min-w-8 px-2 text-xs font-medium text-zinc-600 hover:text-zinc-900 bg-white hover:bg-zinc-50 border border-zinc-200 hover:border-zinc-300 rounded-md transition-colors shadow-2xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-900"
                                    aria-label="{{ __('Go to page :page', ['page' => $item]) }}"
                                >
                                    {{ $item }}
                                </button>
                            @endif
                        @endforeach

                        {{-- Next Page Button --}}
                        @if ($paginator->hasMorePages())
                            <button
                                type="button"
                                wire:click="nextPage('{{ $paginator->getPageName() }}')"
                                x-on:click="{{ $scrollIntoViewJsSnippet }}"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center justify-center h-8 w-8 text-zinc-600 hover:text-zinc-900 bg-white hover:bg-zinc-50 border border-zinc-200 hover:border-zinc-300 rounded-md transition-colors shadow-2xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-900"
                                aria-label="{{ __('pagination.next') }}"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        @else
                            <span aria-disabled="true" aria-label="{{ __('pagination.next') }}" class="inline-flex items-center justify-center h-8 w-8 text-zinc-300 bg-zinc-50/50 border border-zinc-200/60 rounded-md cursor-not-allowed">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </span>
                        @endif
                    </div>

                    {{-- Jump To Page Input (Desktop) --}}
                    @if ($last > 1)
                        <div class="h-4 w-px bg-zinc-200 mx-1"></div>
                        <div
                            x-data="{ jumpPage: '' }"
                            class="inline-flex items-center gap-1.5 text-xs text-zinc-500"
                        >
                            <span class="hidden md:inline">Ke hal:</span>
                            <input
                                type="number"
                                min="1"
                                max="{{ $last }}"
                                placeholder="{{ $current }}"
                                x-model="jumpPage"
                                @keydown.enter.prevent="
                                    let p = parseInt(jumpPage);
                                    if (!isNaN(p)) {
                                        p = Math.max(1, Math.min(p, {{ $last }}));
                                        $wire.gotoPage(p, '{{ $paginator->getPageName() }}');
                                        jumpPage = '';
                                    }
                                "
                                class="w-12 h-8 px-1.5 text-center text-xs font-semibold font-mono rounded-md border border-zinc-200 bg-white text-zinc-800 focus:outline-none focus:ring-1 focus:ring-zinc-900 shadow-2xs"
                                title="Ketik nomor halaman lalu tekan Enter"
                            />
                            <button
                                type="button"
                                @click="
                                    let p = parseInt(jumpPage);
                                    if (!isNaN(p)) {
                                        p = Math.max(1, Math.min(p, {{ $last }}));
                                        $wire.gotoPage(p, '{{ $paginator->getPageName() }}');
                                        jumpPage = '';
                                    }
                                "
                                class="h-8 px-2 text-xs font-medium text-zinc-700 bg-white hover:bg-zinc-50 border border-zinc-200 hover:border-zinc-300 rounded-md transition-colors shadow-2xs"
                                title="Lompat ke halaman"
                            >
                                Go
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </nav>
    @endif
</div>
