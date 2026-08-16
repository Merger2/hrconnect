@props([
    'label',
    'variant' => 'neutral',
])

@php
    $variantClasses = [
        'neutral' => 'border-transparent bg-transparent text-gray-700 shadow-none hover:bg-gray-100 focus:ring-primary-600',
        'primary' => 'border-transparent bg-primary-50 text-primary-700 shadow-none hover:bg-primary-100 focus:ring-primary-600',
        'success' => 'border-transparent bg-emerald-50 text-emerald-700 shadow-none hover:bg-emerald-100 focus:ring-emerald-600',
        'warning' => 'border-transparent bg-amber-50 text-amber-700 shadow-none hover:bg-amber-100 focus:ring-amber-600',
        'danger' => 'border-transparent bg-red-50 text-red-700 shadow-none hover:bg-red-100 focus:ring-red-600',
    ][$variant] ?? 'border-transparent bg-transparent text-gray-700 shadow-none hover:bg-gray-100 focus:ring-primary-600';

    $classes = 'wcag-touch-target inline-flex h-10 w-10 items-center justify-center gap-2 rounded-xl border p-0 text-sm font-semibold transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 ' . $variantClasses;
@endphp

@if (!isset($attributes['href']))
    <button
        type="button"
        aria-label="{{ $label }}"
        title="{{ $label }}"
        {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@else
    <a
        aria-label="{{ $label }}"
        title="{{ $label }}"
        {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@endif
