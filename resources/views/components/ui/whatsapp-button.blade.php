@props([
    'phone' => null,
    'message' => '',
    'label' => 'WhatsApp',
    'size' => 'sm',
    'title' => 'Kirim ringkasan via WhatsApp',
])

@php
    $cleanPhone = '';
    if (!empty($phone)) {
        $cleanPhone = preg_replace('/[^0-9]/', '', (string)$phone);
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '62' . substr($cleanPhone, 1);
        }
    }

    $encodedMsg = rawurlencode($message);
    $targetUrl = !empty($cleanPhone)
        ? "https://wa.me/{$cleanPhone}?text={$encodedMsg}"
        : "https://wa.me/?text={$encodedMsg}";
@endphp

<a
    href="{{ $targetUrl }}"
    target="_blank"
    rel="noopener noreferrer"
    title="{{ $title }}"
    {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-md border border-emerald-300 bg-emerald-50 hover:bg-emerald-100 active:bg-emerald-200 text-emerald-800 shadow-2xs transition-colors select-none']) }}
>
    <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 24 24">
        <path d="M12.031 2C6.495 2 2 6.495 2 12.031c0 1.768.46 3.491 1.334 5.006L2 22l5.105-1.338A10.007 10.007 0 0012.031 22C17.567 22 22 17.505 22 12.031 22 6.495 17.567 2 12.031 2zm5.834 14.156c-.244.686-1.42 1.258-1.95 1.285-.504.025-1.12-.132-3.414-1.082-2.327-.965-3.83-3.328-3.947-3.483-.117-.156-.95-1.264-.95-2.41 0-1.147.604-1.71.818-1.944.214-.233.468-.292.624-.292.156 0 .312.002.449.009.145.008.34-.055.53.402.196.471.67 1.636.729 1.753.059.117.098.254.02.41-.078.156-.117.254-.234.39-.117.137-.246.305-.351.41-.117.117-.24.244-.103.479.137.234.61 1.004 1.309 1.626.9.8 1.66 1.047 1.895 1.164.234.117.371.098.508-.059.137-.156.586-.684.742-.918.156-.234.312-.195.527-.117.215.078 1.367.645 1.602.762.234.117.39.176.449.273.059.098.059.566-.185 1.252z"/>
    </svg>
    <span>{{ $label }}</span>
</a>
