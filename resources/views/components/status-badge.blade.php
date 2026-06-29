@props(['tone' => 'neutral', 'pill' => false])

@php
$baseClass = $pill
    ? 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset'
    : 'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset';

$toneClass = match ($tone) {
    'success' => 'bg-success/10 text-success ring-success/20',
    'warning' => 'bg-warning/10 text-warning ring-warning/20',
    'error', 'danger' => 'bg-error/10 text-error ring-error/20',
    'info' => 'bg-info/10 text-info ring-info/20',
    'primary' => 'bg-primary/10 text-primary ring-primary/20',
    'accent' => 'bg-purple-500/10 text-purple-700 ring-purple-500/20 dark:text-purple-300 dark:ring-purple-400/20',
    'neutral' => 'bg-surface-dim text-on-surface-variant ring-outline-variant/30',
    default => 'bg-surface-dim text-on-surface-variant ring-outline-variant/30',
};
@endphp

<span {{ $attributes->merge(['class' => $baseClass . ' ' . $toneClass]) }}>
    {{ $slot }}
</span>
