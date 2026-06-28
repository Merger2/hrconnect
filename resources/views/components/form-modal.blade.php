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
    @open-modal.window="if ($event.detail === '{{ $name }}') open = true"
    @close-modal.window="if ($event.detail === '{{ $name }}') open = false"
    @keydown.escape.window="open = false"
    class="fixed inset-0 z-50 flex items-center justify-center"
    role="dialog"
    aria-modal="true"
>
    <div class="fixed inset-0 bg-black/40" @click="open = false"></div>
    <div class="relative z-10 w-full {{ $maxWidth }} rounded-2xl bg-canvas p-6 shadow-xl">
        <div class="mb-5 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-ink">{{ $title }}</h2>
            <button @click="open = false" class="rounded-lg p-1 text-on-surface-variant hover:bg-surface-dim hover:text-ink">
                <span class="material-symbols-outlined text-xl">close</span>
            </button>
        </div>

        @if ($formAction)
            <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data">
                @csrf
                @method(($method ?? 'POST') !== 'POST' ? $method ?? 'POST' : 'POST')
                {{ $slot }}
                @if (isset($actions))
                    <div class="mt-6 flex items-center justify-end gap-3 border-t border-outline-variant/50 pt-4">
                        {{ $actions }}
                    </div>
                @else
                    <div class="mt-6 flex items-center justify-end gap-3 border-t border-outline-variant/50 pt-4">
                        <button type="button" @click="open = false"
                            class="rounded-xl border border-outline-variant bg-canvas px-5 py-2.5 text-sm font-semibold text-ink transition-colors hover:bg-surface-dim">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit"
                            x-bind:disabled="loading"
                            class="rounded-xl bg-ink px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:opacity-90 disabled:opacity-40">
                            <span x-show="!loading">{{ $submitLabel }}</span>
                            <span x-show="loading" x-cloak>{{ $loadingLabel }}</span>
                        </button>
                    </div>
                @endif
            </form>
        @else
            {{ $slot }}
            @if (isset($actions))
                <div class="mt-6 flex items-center justify-end gap-3 border-t border-outline-variant/50 pt-4">
                    {{ $actions }}
                </div>
            @else
                @isset($footer)
                    <div class="mt-6 border-t border-outline-variant/50 pt-4">{{ $footer }}</div>
                @endisset
            @endif
        @endif
    </div>
</div>
