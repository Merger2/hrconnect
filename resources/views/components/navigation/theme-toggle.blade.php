@props(['size' => 'md'])

@php
$sizeClass = match ($size) {
    'sm' => 'h-9 w-9',
    'lg' => 'h-11 w-11',
    default => 'h-10 w-10',
};
@endphp

<button type="button"
    x-data
    @click="$store.darkMode.toggle()"
    :aria-pressed="$store.darkMode.on.toString()"
    aria-label="{{ __('Toggle tema') }}"
    class="inline-flex {{ $sizeClass }} items-center justify-center rounded-xl text-on-surface-variant transition-colors hover:bg-surface-dim hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-ink/20"
>
    <span class="material-symbols-outlined" x-show="$store.darkMode.on">light_mode</span>
    <span class="material-symbols-outlined" x-show="!$store.darkMode.on">dark_mode</span>
</button>
