@props(['variant' => 'primary', 'size' => 'md', 'href' => null, 'icon' => null])

@php
$base = 'inline-flex items-center justify-center gap-2 rounded-md font-semibold uppercase tracking-wider transition-colors disabled:opacity-40 disabled:cursor-not-allowed';

$variants = [
    'primary' => 'bg-primary text-white hover:bg-blue-700',
    'secondary' => 'border border-outline-variant text-ink hover:bg-surface-container-high',
    'outline-coral' => 'border border-coral-400 text-coral-600 hover:bg-coral-50',
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
