@props([
    'name' => 'modal',
    'title' => '',
    'submitLabel' => __('Save'),
    'loadingLabel' => __('Saving...'),
    'size' => 'md',
    'formAction' => null,
])

@php
$maxWidth = match ($size) {
    'sm' => 'max-w-sm',
    'md' => 'max-w-md',
    'lg' => 'max-w-lg',
    'xl' => 'max-w-xl',
    '2xl' => 'max-w-2xl',
    default => 'max-w-md',
};
@endphp

<div
    x-data="{ open: false, loading: false }"
    x-show="open"
    x-cloak
    x-effect="if (open) { $nextTick(() => window.initUiPickers?.($el)) }"
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
    <div class="relative z-10 mx-auto w-full {{ $maxWidth }} transform overflow-hidden rounded-xl bg-canvas shadow-xl"
        style="max-height: calc(100dvh - 2rem - env(safe-area-inset-top) - env(safe-area-inset-bottom));"
        x-on:click.stop
        x-trap.inert.noscroll="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
        <div class="flex items-center justify-between border-b border-outline-variant/50 px-6 py-4">
            <h2 class="text-lg font-semibold text-ink">{{ $title }}</h2>
            <button @click="open = false" class="rounded-xl p-1.5 text-on-surface-variant hover:text-ink hover:bg-surface-container-high transition-colors">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <div class="px-6 py-4">
            @if ($formAction)
                <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data">
                    @csrf
                    @method(($method ?? 'POST') !== 'POST' ? $method ?? 'POST' : 'POST')
                    {{ $slot }}
                </form>
            @else
                {{ $slot }}
            @endif
        </div>

        @if ($formAction)
            @if (isset($actions))
                <div class="flex items-center justify-end gap-3 border-t border-outline-variant/50 bg-surface-dim/30 px-6 py-4">{{ $actions }}</div>
            @else
                <div class="flex items-center justify-end gap-3 border-t border-outline-variant/50 bg-surface-dim/30 px-6 py-4">
                    <button type="button" @click="open = false"
                        class="rounded-xl border border-outline-variant bg-canvas px-5 py-2.5 text-sm font-semibold text-ink transition-colors hover:bg-surface-dim">
                        {{ __('Batal') }}
                    </button>
                    <button type="submit"
                        x-bind:disabled="loading"
                        class="rounded-xl bg-ink px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:opacity-90 disabled:opacity-40">
                        <span x-show="!loading">{{ $submitLabel }}</span>
                        <span x-show="loading" x-cloak>{{ $loadingLabel }}</span>
                    </button>
                </div>
            @endif
        @else
            @if (isset($actions))
                <div class="flex items-center justify-end gap-3 border-t border-outline-variant/50 bg-surface-dim/30 px-6 py-4">{{ $actions }}</div>
            @else
                @isset($footer)
                    <div class="flex items-center justify-end gap-3 border-t border-outline-variant/50 bg-surface-dim/30 px-6 py-4">{{ $footer }}</div>
                @endisset
            @endif
        @endif
    </div>
</div>
