@props(['tone' => 'info', 'dismissible' => false])

@php
$toneClasses = match ($tone) {
    'success' => 'border-success/30 bg-success/5 text-success',
    'warning' => 'border-warning/30 bg-warning/10 text-warning',
    'danger' => 'border-error/30 bg-error/10 text-error',
    'info' => 'border-info/30 bg-info/5 text-info',
    'primary' => 'border-outline-variant bg-surface-dim text-ink',
    default => 'border-outline-variant/50 bg-surface-dim/50 text-on-surface-variant',
};
@endphp

<div
    x-data="{ show: true }"
    x-show="show"
    x-transition
    {{ $attributes->merge(['class' => 'rounded-xl border px-4 py-3 text-sm ' . $toneClasses]) }}
    role="alert"
>
    <div class="flex items-start gap-3">
        <span class="material-symbols-outlined text-lg shrink-0">
            {{ $icon ?? match ($tone) {
                'success' => 'check_circle',
                'warning' => 'warning',
                'danger' => 'error',
                'info' => 'info',
                default => 'campaign',
            } }}
        </span>
        <div class="flex-1 min-w-0">{{ $slot }}</div>
        @if ($dismissible)
            <button @click="show = false" class="shrink-0 rounded-lg p-1 hover:bg-black/5">
                <span class="material-symbols-outlined text-base">close</span>
            </button>
        @endif
    </div>
</div>
