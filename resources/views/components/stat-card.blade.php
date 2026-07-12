@props([
    'label' => null,
    'value' => null,
    'icon' => 'trending_up',
    'tone' => 'neutral',
    'trend' => null,
    'trendLabel' => null,
    'hint' => null,
])

@php
$toneStyles = match ($tone) {
    'primary' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-600', 'ring' => 'ring-blue-100'],
    'success' => ['bg' => 'bg-success/10', 'text' => 'text-success', 'ring' => 'ring-success/20'],
    'warning' => ['bg' => 'bg-warning/10', 'text' => 'text-warning', 'ring' => 'ring-warning/20'],
    'error' => ['bg' => 'bg-error/10', 'text' => 'text-error', 'ring' => 'ring-error/20'],
    'coral' => ['bg' => 'bg-coral-50', 'text' => 'text-coral-600', 'ring' => 'ring-coral-100'],
    'ink' => ['bg' => 'bg-ink', 'text' => 'text-on-primary', 'ring' => 'ring-ink/20'],
    default => ['bg' => 'bg-cloud', 'text' => 'text-on-surface-variant', 'ring' => 'ring-outline-variant/30'],
};

$trendColor = match (true) {
    $trend > 0 => 'text-success',
    $trend < 0 => 'text-error',
    default => 'text-on-surface-variant',
};

$trendIcon = match (true) {
    $trend > 0 => 'trending_up',
    $trend < 0 => 'trending_down',
    default => 'trending_flat',
};
@endphp

<div class="flex min-h-[5rem] flex-col justify-between rounded-xl border border-outline-variant/50 bg-canvas shadow-soft p-4 transition-smooth hover:shadow-modal">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0 flex-1">
            @if ($label)
                <p class="text-caption-md font-medium uppercase tracking-wide text-on-surface-variant/70">{{ $label }}</p>
            @endif
            <p class="mt-1 text-display-sm font-bold text-ink tabular-nums">
                {{ $value }}
            </p>
        </div>
        <div @class([
            'flex h-11 w-11 shrink-0 items-center justify-center rounded-lg ring-1 ring-inset',
            $toneStyles['bg'], $toneStyles['ring'],
        ])>
            <span @class(['material-symbols-outlined text-xl', $toneStyles['text']])>{{ $icon }}</span>
        </div>
    </div>

    @if ($trend !== null || $hint)
        <div class="mt-3 flex items-center gap-2 text-caption-sm">
            @if ($trend !== null)
                <span @class(['flex items-center gap-0.5 font-medium', $trendColor])>
                    <span class="material-symbols-outlined text-sm">{{ $trendIcon }}</span>
                    {{ abs($trend) }}%
                </span>
                @if ($trendLabel)
                    <span class="text-on-surface-variant/60">{{ $trendLabel }}</span>
                @endif
            @endif
            @if ($hint)
                <span class="text-on-surface-variant/60">{{ $hint }}</span>
            @endif
        </div>
    @endif
</div>