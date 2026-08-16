@props([
    'tone' => 'neutral',
])

@php
    $toneClasses = match ($tone) {
        'primary' => 'border-primary-200/70 bg-primary-50',
        'amber' => 'border-amber-200/70 bg-amber-50',
        'sky' => 'border-sky-200/70 bg-sky-50',
        'violet' => 'border-violet-200/70 bg-violet-50',
        'rose' => 'border-rose-200/70 bg-rose-50',
        'emerald' => 'border-emerald-200/70 bg-emerald-50',
        default => 'border-slate-200/70 bg-slate-50',
    };
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl border ' . $toneClasses]) }}>
    {{ $slot }}
</div>
