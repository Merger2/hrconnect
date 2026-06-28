@props([
    'name' => 'confirm',
    'title' => __('Confirm'),
    'message' => __('Are you sure?'),
    'confirmLabel' => __('Confirm'),
    'cancelLabel' => __('Cancel'),
    'variant' => 'danger',
    'icon' => null,
])

@php
$iconDefault = match ($variant) {
    'danger' => 'warning',
    'warning' => 'help',
    'success' => 'check_circle',
    'info' => 'info',
    default => 'warning',
};

$buttonClass = match ($variant) {
    'danger' => 'bg-error text-white hover:opacity-90',
    'warning' => 'bg-warning text-white hover:opacity-90',
    'success' => 'bg-success text-white hover:opacity-90',
    'info' => 'bg-info text-white hover:opacity-90',
    default => 'bg-ink text-white hover:opacity-90',
};
@endphp

<div
    x-data="{ open: false, loading: false }"
    x-show="open"
    x-cloak
    @open-modal.window="if ($event.detail === '{{ $name }}') open = true"
    @close-modal.window="if ($event.detail === '{{ $name }}') open = false"
    @keydown.escape.window="open = false"
    class="fixed inset-0 z-50 flex items-center justify-center"
    role="dialog"
    aria-modal="true"
>
    <div class="fixed inset-0 bg-black/40" @click="open = false"></div>
    <div class="relative z-10 w-full max-w-sm rounded-2xl bg-canvas p-6 shadow-xl">
        <div class="flex flex-col items-center text-center">
            <div @class([
                'mb-4 flex h-12 w-12 items-center justify-center rounded-full',
                'bg-error/10' => $variant === 'danger',
                'bg-warning/10' => $variant === 'warning',
                'bg-success/10' => $variant === 'success',
                'bg-info/10' => $variant === 'info',
                'bg-surface-dim' => $variant === 'neutral',
            ])>
                <span @class([
                    'material-symbols-outlined text-2xl',
                    'text-error' => $variant === 'danger',
                    'text-warning' => $variant === 'warning',
                    'text-success' => $variant === 'success',
                    'text-info' => $variant === 'info',
                    'text-on-surface-variant' => $variant === 'neutral',
                ])>{{ $icon ?? $iconDefault }}</span>
            </div>

            <h3 class="text-lg font-semibold text-ink">{{ $title }}</h3>
            <p class="mt-1 text-sm text-on-surface-variant">{{ $message }}</p>

            @if (isset($slot) && $slot->isNotEmpty())
                <div class="mt-4 w-full">{{ $slot }}</div>
            @endif
        </div>

        <div class="mt-6 flex items-center justify-center gap-3">
            <button @click="open = false"
                class="rounded-xl border border-outline-variant bg-canvas px-5 py-2.5 text-sm font-semibold text-ink transition-colors hover:bg-surface-dim">
                {{ $cancelLabel }}
            </button>
            <button @click="$wire.{{ $name }}(); open = false"
                x-bind:disabled="loading"
                class="{{ $buttonClass }} rounded-xl px-5 py-2.5 text-sm font-semibold transition-colors disabled:opacity-40">
                <span x-show="!loading">{{ $confirmLabel }}</span>
                <span x-show="loading" x-cloak>{{ __('Processing...') }}</span>
            </button>
        </div>
    </div>
</div>
