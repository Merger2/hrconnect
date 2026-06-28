@props(['value' => 0, 'currency' => 'Rp', 'color' => false])

@php
    $formatted = Number::currency((float) $value, 'IDR', locale: 'id');
    $colorClass = match ($color) {
        'success' => 'text-success',
        'danger' => 'text-error',
        'warning' => 'text-warning',
        default => 'text-ink',
    };
@endphp

<span {{ $attributes->merge(['class' => $color ? "font-medium $colorClass" : 'font-medium']) }}>
    {{ $formatted }}
</span>
