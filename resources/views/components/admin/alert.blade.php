@props([
    'tone' => 'info',
])

@php
    $toneClasses = match ($tone) {
        'success' => 'border-green-200 bg-green-50 text-green-800',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-800',
        'danger' => 'border-red-200 bg-red-50 text-red-800',
        'primary' => 'border-primary-200 bg-primary-50 text-primary-800',
        default => 'border-sky-200 bg-sky-50 text-sky-800',
    };
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl border px-4 py-3 ' . $toneClasses]) }}>
    {{ $slot }}
</div>
