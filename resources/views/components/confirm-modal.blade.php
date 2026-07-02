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
    class="fixed inset-0 z-[90] flex items-start justify-center overflow-y-auto px-4 py-[calc(1rem+env(safe-area-inset-top))] sm:items-center sm:px-6 sm:py-[calc(1.5rem+env(safe-area-inset-top))]"
    role="dialog"
    aria-modal="true"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
>
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="open = false" aria-hidden="true"></div>
    <div class="relative z-10 mx-auto w-full max-w-sm transform overflow-hidden rounded-xl bg-canvas shadow-xl"
        x-on:click.stop
        x-trap.inert.noscroll="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
        <div class="px-6 py-5">
            <div @class([
                'mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full',
                'bg-error/10' => $variant === 'danger',
                'bg-warning/10' => $variant === 'warning',
                'bg-success/10' => $variant === 'success',
                'bg-info/10' => $variant === 'info',
                'bg-surface-dim' => $variant === 'neutral',
            ])>
                <span @class([
                    'material-symbols-outlined text-3xl',
                    'text-error' => $variant === 'danger',
                    'text-warning' => $variant === 'warning',
                    'text-success' => $variant === 'success',
                    'text-info' => $variant === 'info',
                    'text-on-surface-variant' => $variant === 'neutral',
                ])>{{ $icon ?? $iconDefault }}</span>
            </div>

            <h3 class="text-center text-lg font-semibold text-ink">{{ $title }}</h3>

            @if ((isset($slot) && $slot->isNotEmpty()) || $message !== __('Are you sure?'))
                <div class="mt-2 text-center text-sm text-on-surface-variant">
                    @if (isset($slot) && $slot->isNotEmpty())
                        {{ $slot }}
                    @else
                        <p>{{ $message }}</p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center justify-center gap-3 border-t border-outline-variant/50 bg-surface-dim/30 px-6 py-4">
            @if (isset($actions))
                {{ $actions }}
            @else
                <button @click="open = false"
                    class="rounded-xl border border-outline-variant bg-canvas px-5 py-2.5 text-sm font-semibold text-ink transition-colors hover:bg-surface-dim">
                    {{ $cancelLabel }}
                </button>
                <button @click="$wire.{{ $name }}(); open = false"
                    x-bind:disabled="loading"
                    class="{{ $buttonClass }} rounded-xl px-5 py-2.5 text-sm font-semibold transition-colors disabled:opacity-40">
                    <span x-show="!loading">{{ $confirmLabel }}</span>
                    <span x-show="loading" x-cloak>{{ __('Memproses...') }}</span>
                </button>
            @endif
        </div>
    </div>
</div>
