@props(['tone' => 'info'])

@php
$toneClasses = match ($tone) {
    'success' => 'border-success/30 bg-success/5 text-success',
    'warning' => 'border-warning/30 bg-warning/5 text-warning',
    'danger' => 'border-error/30 bg-error/5 text-error',
    'info' => 'border-info/30 bg-info/5 text-info',
    default => 'border-surface-dim bg-surface-dim text-on-surface-variant',
};
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl border px-4 py-3 text-sm ' . $toneClasses]) }}>
    {{ $slot }}
</div>
