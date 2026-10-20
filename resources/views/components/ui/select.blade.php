@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'error' => null,
    'helper' => null,
    'required' => false,
    'disabled' => false,
    'placeholder' => null,
    'options' => [], // optional array of [value => label] or simple list
    'selected' => null,
])

@php
    $selectId = $id ?? $name ?? 'select-' . uniqid();
    $errorMessage = $error ?? ($name ? $errors->first($name) : null);
    $hasError = !empty($errorMessage);

    $selectClasses = 'w-full appearance-none rounded-md border text-zinc-900 focus:outline-none focus:ring-1 transition-colors '
        . 'h-11 md:h-10 text-base md:text-sm pl-3 pr-10 ' // >=44px tablet touch target
        . ($hasError
            ? 'border-red-500 focus:border-red-600 focus:ring-red-600 bg-red-50/20 '
            : 'border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900 bg-white ')
        . ($disabled ? 'bg-zinc-100 text-zinc-500 cursor-not-allowed ' : '');
@endphp

<div class="flex flex-col gap-1.5 w-full">
    @if ($label)
        <label for="{{ $selectId }}" class="text-xs md:text-sm font-medium text-zinc-700 flex items-center justify-between">
            <span>
                {{ $label }}
                @if ($required)
                    <span class="text-red-500 font-bold">*</span>
                @endif
            </span>
        </label>
    @endif

    <div class="relative flex items-center w-full">
        <select
            id="{{ $selectId }}"
            name="{{ $name }}"
            {{ $required ? 'required' : '' }}
            {{ $disabled ? 'disabled' : '' }}
            {{ $attributes->merge(['class' => $selectClasses]) }}
        >
            @php
                $selectedValue = $name ? old($name, $selected) : $selected;
            @endphp

            @if ($placeholder)
                <option value="" {{ $selectedValue === null || $selectedValue === '' ? 'selected' : '' }}>
                    {{ $placeholder }}
                </option>
            @endif

            @if (!empty($options))
                @foreach ($options as $val => $text)
                    @php
                        $optionVal = is_int($val) && !is_string($val) ? $text : $val;
                        $isSelected = (string) $selectedValue === (string) $optionVal;
                    @endphp
                    <option value="{{ $optionVal }}" {{ $isSelected ? 'selected' : '' }}>
                        {{ $text }}
                    </option>
                @endforeach
            @endif

            {{ $slot }}
        </select>

        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-zinc-500">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
            </svg>
        </div>
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
