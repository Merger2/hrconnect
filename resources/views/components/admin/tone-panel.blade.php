@props([
    'tone' => 'neutral',
])

@php
    $toneClasses = match ($tone) {
        'primary' => 'border-primary-200/70 bg-primary-50/70',
        'amber' => 'border-amber-200/70 bg-amber-50/80',
        'sky' => 'border-sky-200/70 bg-sky-50/80',
        'violet' => 'border-violet-200/70 bg-violet-50/80',
        'rose' => 'border-rose-200/70 bg-rose-50/80',
        'emerald' => 'border-emerald-200/70 bg-emerald-50/80',
        default => 'border-slate-200/70 bg-slate-50/80',
    };
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl border ' . $toneClasses]) }}>
    {{ $slot }}
</div>
