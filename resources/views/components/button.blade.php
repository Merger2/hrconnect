@props([
    'variant' => 'primary',
    'size' => 'md',
    'icon' => null,
    'iconRight' => null,
    'href' => null,
    'type' => 'submit',
    'loading' => false,
    'disabled' => false,
])

@php
$base = 'inline-flex items-center justify-center gap-2 rounded-md font-semibold uppercase tracking-wider transition-[var(--transition-smooth)] disabled:opacity-40 disabled:cursor-not-allowed focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/30';

$variants = [
    'primary'    => 'bg-primary text-on-primary hover:bg-primary-deep shadow-[var(--shadow-soft)] hover:shadow-[var(--shadow-modal)]',
    'ink'        => 'bg-ink text-on-primary hover:bg-ink/90',
    'secondary'  => 'border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white hover:bg-slate-100 dark:hover:bg-slate-700',
    'outline'    => 'border border-primary text-primary hover:bg-primary-50 dark:hover:bg-primary-900/20',
    'outline-coral' => 'border border-coral-400 text-coral-600 hover:bg-coral-50',
    'danger'     => 'bg-error text-on-error hover:opacity-80 shadow-[var(--shadow-soft)]',
    'ghost'      => 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800',
    'success'    => 'bg-success/15 text-success hover:bg-success/25',
];

$sizes = [
    'xs'  => 'px-2 py-1 text-xs',
    'sm'  => 'px-3 py-1.5 text-xs',
    'md'  => 'px-5 py-2.5 text-sm',
    'lg'  => 'px-6 py-3 text-base',
];

$iconSizes = ['xs' => 'text-sm', 'sm' => 'text-base', 'md' => 'text-lg', 'lg' => 'text-xl'];
$iconClass = 'material-symbols-outlined ' . ($iconSizes[$size] ?? 'text-lg');
@endphp

@php
$finalDisabled = $disabled || $loading;
@endphp

@if ($href)
    <a href="{{ $href }}"
       @class([$base, $variants[$variant], $sizes[$size], 'opacity-40 cursor-not-allowed' => $disabled])>
        @if ($loading)
            <span class="material-symbols-outlined animate-spin text-lg">progress_activity</span>
        @elseif ($icon)
            <span class="{{ $iconClass }}">{{ $icon }}</span>
        @endif
        {{ $slot }}
        @if ($iconRight && !$loading)
            <span class="{{ $iconClass }}">{{ $iconRight }}</span>
        @endif
    </a>
@else
    <button type="{{ $type }}"
            @class([$base, $variants[$variant], $sizes[$size], 'cursor-not-allowed' => $finalDisabled])}>
        @if ($loading)
            <span class="material-symbols-outlined animate-spin text-lg">progress_activity</span>
        @elseif ($icon)
            <span class="{{ $iconClass }}">{{ $icon }}</span>
        @endif
        {{ $slot }}
        @if ($iconRight && !$loading)
            <span class="{{ $iconClass }}">{{ $iconRight }}</span>
        @endif
    </button>
@endif