@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'error' => null,
    'helper' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'mono' => false,
    'prefix' => null,
    'suffix' => null,
    'inputmode' => null,
    'clearable' => false,
    'clearTarget' => 'search',
    'hasValue' => false,
    'loadingTarget' => null,
])

@php
    $inputId = $id ?? $name ?? 'input-' . uniqid();
    $errorMessage = $error ?? ($name ? $errors->first($name) : null);
    $hasError = !empty($errorMessage);

    // Auto set inputmode decimal for numeric inputs (touch tablet friendly)
    $resolvedInputMode = $inputmode;
    if (!$resolvedInputMode && ($type === 'number' || in_array($type, ['decimal', 'numeric']))) {
        $resolvedInputMode = 'decimal';
    }

    $isMonospace = $mono || in_array($type, ['number', 'date', 'time']);

    $inputClasses = 'w-full rounded-md border text-zinc-900 placeholder:text-zinc-400 focus:outline-none focus:ring-1 transition-colors '
        . 'h-11 md:h-10 text-base md:text-sm px-3 ' // >=44px tablet touch target
        . ($isMonospace ? 'font-mono tabular-nums ' : '')
        . ($hasError
            ? 'border-red-500 focus:border-red-600 focus:ring-red-600 bg-red-50/20 '
            : 'border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 bg-white ')
        . ($disabled ? 'bg-zinc-100 text-zinc-500 cursor-not-allowed ' : '')
        . ($prefix ? 'pl-9 ' : '')
        . ($suffix ? 'pr-12 ' : '')
        . (($clearable || $loadingTarget) && !$suffix ? 'pr-11 ' : '');
@endphp

<div class="flex flex-col gap-1.5 w-full">
    @if ($label)
        <label for="{{ $inputId }}" class="text-xs md:text-sm font-medium text-zinc-700 flex items-center justify-between">
            <span>
                {{ $label }}
                @if ($required)
                    <span class="text-red-500 font-bold">*</span>
                @endif
            </span>
        </label>
    @endif

    <div class="relative flex items-center w-full">
        @if ($prefix)
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-500 text-xs md:text-sm">
                {{ $prefix }}
            </div>
        @endif

        <input
            id="{{ $inputId }}"
            name="{{ $name }}"
            type="{{ $type === 'decimal' ? 'number' : $type }}"
            @if ($resolvedInputMode) inputmode="{{ $resolvedInputMode }}" @endif
            @if ($type === 'number') step="any" @endif
            value="{{ $name ? old($name, $value) : $value }}"
            placeholder="{{ $placeholder }}"
            {{ $required ? 'required' : '' }}
            {{ $disabled ? 'disabled' : '' }}
            {{ $readonly ? 'readonly' : '' }}
            {{ $attributes->merge(['class' => $inputClasses]) }}
        />

        @if ($suffix)
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-zinc-500 text-xs md:text-sm font-medium">
                {{ $suffix }}
            </div>
        @elseif ($clearable || $loadingTarget)
            <div class="absolute inset-y-0 right-0 pr-2.5 flex items-center gap-1.5 z-10">
                @if ($loadingTarget)
                    <span wire:loading wire:target="{{ $loadingTarget }}" class="text-zinc-400">
                        <svg class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                @endif

                @if ($clearable && $hasValue)
                    <button
                        type="button"
                        wire:click="$set('{{ $clearTarget }}', '')"
                        @if ($loadingTarget) wire:loading.remove wire:target="{{ $loadingTarget }}" @endif
                        class="text-zinc-400 hover:text-zinc-700 p-1 rounded-md transition-colors cursor-pointer"
                        title="Hapus pencarian"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                @endif
            </div>
        @endif
    </div>

    @if ($errorMessage)
        <p class="text-xs text-red-600 font-medium">
            {{ $errorMessage }}
        </p>
    @elseif ($helper)
        <p class="text-xs text-zinc-500">
            {{ $helper }}
        </p>
    @endif
</div>
