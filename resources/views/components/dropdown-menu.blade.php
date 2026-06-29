@props(['align' => 'right', 'width' => '48', 'contentClasses' => 'py-1', 'dropdownClasses' => '', 'id' => null])

@php
$dropdownId = $id ?: 'dropdown-' . uniqid();

$alignmentClasses = match ($align) {
    'left' => 'ltr:origin-top-left rtl:origin-top-right start-0',
    'top' => 'origin-top',
    'none', 'false' => '',
    default => 'ltr:origin-top-right rtl:origin-top-left end-0',
};

$widthClass = match ($width) {
    '48' => 'w-48',
    '56' => 'w-56',
    '64' => 'w-64',
    default => $width,
};
@endphp

<div class="relative inline-block text-left" x-data="{ open: false }" @click.away="open = false" @keydown.escape.window="open = false" @close.stop="open = false">
    <div @click.stop="open = !open">
        {{ $trigger ?? $slot }}
    </div>

    <div id="{{ $dropdownId }}" x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute z-50 mt-2 {{ $widthClass }} {{ $alignmentClasses }} {{ $dropdownClasses }}"
        style="display: none;"
        @click="open = false">
        <div class="rounded-xl border border-outline-variant bg-canvas shadow-lg ring-1 ring-black/5 {{ $contentClasses }}">
            {{ $content ?? $slot }}
        </div>
    </div>
</div>
