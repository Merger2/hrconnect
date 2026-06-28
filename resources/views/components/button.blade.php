@props(['variant' => 'primary', 'size' => 'md', 'href' => null, 'icon' => null])

@php
$base = 'inline-flex items-center justify-center gap-2 rounded-xl font-semibold transition-colors disabled:opacity-40 disabled:cursor-not-allowed';

$variants = [
    'primary' => 'bg-ink text-white hover:opacity-90',
    'secondary' => 'border border-outline-variant text-ink hover:bg-surface-container-high',
    'danger' => 'text-error hover:bg-error/5',
    'ghost' => 'text-on-surface-variant hover:text-ink hover:bg-surface-container-high',
    'success' => 'bg-success/15 text-success hover:bg-success/25',
];
$variantClass = $variants[$variant] ?? $variants['primary'];

$sizes = [
    'sm' => 'px-3 py-1.5 text-xs',
    'md' => 'px-5 py-2.5 text-sm',
    'lg' => 'px-6 py-3 text-base',
];
$sizeClass = $sizes[$size] ?? $sizes['md'];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => "$base $variantClass $sizeClass"]) }}>
        @if ($icon) <span class="material-symbols-outlined text-lg">{{ $icon }}</span> @endif
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['class' => "$base $variantClass $sizeClass"]) }}>
        @if ($icon) <span class="material-symbols-outlined text-lg">{{ $icon }}</span> @endif
        {{ $slot }}
    </button>
@endif
