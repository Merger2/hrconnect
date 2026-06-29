@props([
    'label',
    'icon',
    'variant' => 'neutral',
    'href' => null,
])

@php
$variantClasses = [
    'neutral' => 'text-on-surface-variant hover:bg-surface-dim hover:text-ink',
    'primary' => 'text-primary hover:bg-primary/10',
    'success' => 'text-success hover:bg-success/10',
    'warning' => 'text-warning hover:bg-warning/10',
    'danger' => 'text-error hover:bg-error/10',
][$variant] ?? 'text-on-surface-variant hover:bg-surface-dim hover:text-ink';

$classes = 'inline-flex h-10 w-10 items-center justify-center rounded-xl transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-ink/20 disabled:pointer-events-none disabled:opacity-50 ' . $variantClasses;
@endphp

@if ($href)
    <a
        href="{{ $href }}"
        aria-label="{{ $label }}"
        title="{{ $label }}"
        {{ $attributes->merge(['class' => $classes]) }}
    >
        <span class="material-symbols-outlined text-lg">{{ $icon }}</span>
        {{ $slot }}
    </a>
@else
    <button
        type="button"
        aria-label="{{ $label }}"
        title="{{ $label }}"
        {{ $attributes->merge(['class' => $classes]) }}
    >
        <span class="material-symbols-outlined text-lg">{{ $icon }}</span>
        {{ $slot }}
    </button>
@endif
