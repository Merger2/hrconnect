@props(['show' => false, 'maxWidth' => 'lg', 'closeable' => true, 'wireModel' => null])

@php
$maxWidthClasses = [
    'sm' => 'sm:max-w-sm', 'md' => 'sm:max-w-md', 'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl', '2xl' => 'sm:max-w-2xl', '3xl' => 'sm:max-w-3xl',
    '4xl' => 'sm:max-w-4xl', '5xl' => 'sm:max-w-5xl', '6xl' => 'sm:max-w-6xl',
    '7xl' => 'sm:max-w-7xl', 'full' => 'sm:max-w-full',
][$maxWidth] ?? 'sm:max-w-lg';

$wireModel = $wireModel ?? $attributes->get('wire:model');
$var = $wireModel !== null ? 'show' : 'open';
@endphp

<div @if($wireModel !== null) x-data="{ show: @entangle($wireModel) }" x-on:keydown.escape.window="show = false" @else x-data="{ open: @js($show) }" x-on:keydown.escape.window="if (open) { open = false }" @endif>
    <template x-teleport="body">
        <div x-show="{{ $var }}" x-cloak x-effect="if ({{ $var }}) { $nextTick(() => window.initUiPickers?.($el)) }"
            @if($closeable) x-on:click.self="{{ $var }} = false" @endif
            class="fixed inset-0 z-[90] flex items-start justify-center overflow-y-auto px-4 py-[calc(1rem+env(safe-area-inset-top))] sm:items-center sm:px-6 sm:py-[calc(1.5rem+env(safe-area-inset-top))]"
            role="dialog" aria-modal="true"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" aria-hidden="true"></div>
            <div x-show="{{ $var }}" class="relative z-10 mx-auto w-full {{ $maxWidthClasses }} transform overflow-y-auto rounded-lg bg-canvas shadow-xl"
                style="max-height: calc(100dvh - 2rem - env(safe-area-inset-top) - env(safe-area-inset-bottom));"
                x-on:click.stop x-trap.inert.noscroll="{{ $var }}"
                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                @if (isset($title) || $closeable)
                    <div class="flex items-center justify-between border-b border-outline-variant/50 px-6 py-4">
                        <h2 class="text-lg font-semibold text-ink">{{ $title ?? '' }}</h2>
                        @if ($closeable)
                            <button @click="{{ $var }} = false" class="rounded-xl p-1 text-on-surface-variant hover:text-ink hover:bg-surface-container-high">
                                <span class="material-symbols-outlined text-lg">close</span>
                            </button>
                        @endif
                    </div>
                @endif
                <div class="px-6 py-4">{{ $slot }}</div>
                @if (isset($footer))
                    <div class="flex items-center justify-end gap-3 border-t border-outline-variant/50 px-6 py-4">{{ $footer }}</div>
                @endif
            </div>
        </div>
    </template>
</div>
