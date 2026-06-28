@props(['show' => false, 'maxWidth' => 'lg', 'closeable' => true])

@php
$maxWidthClasses = match ($maxWidth) {
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
    default => 'sm:max-w-lg',
};
@endphp

<div x-data="{ open: @js($show) }"
    x-show="open"
    x-cloak
    @if ($closeable) @@clickaway="open = false" @@keydown.window.escape="open = false" @endif
    class="fixed inset-0 z-50 flex items-end justify-center sm:items-center"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0">

    <div class="fixed inset-0 bg-black/30 backdrop-blur-sm" aria-hidden="true"></div>

    <div x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="w-full {{ $maxWidthClasses }} mx-4 mb-0 overflow-hidden rounded-t-2xl sm:rounded-2xl bg-canvas shadow-xl">
        @if (isset($title) || $closeable)
            <div class="flex items-center justify-between border-b border-outline-variant/50 px-6 py-4">
                <h2 class="text-lg font-semibold text-ink">{{ $title ?? '' }}</h2>
                @if ($closeable)
                    <button @@click="open = false" class="rounded-xl p-1 text-on-surface-variant hover:text-ink hover:bg-surface-container-high">
                        <span class="material-symbols-outlined text-lg">close</span>
                    </button>
                @endif
            </div>
        @endif

        <div class="px-6 py-4">
            {{ $slot }}
        </div>

        @if (isset($footer))
            <div class="flex items-center justify-end gap-3 border-t border-outline-variant/50 px-6 py-4">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>
