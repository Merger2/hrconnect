@props([
    'title',
    'description' => null,
    'framed' => false,
    'showDescription' => false,
])

@php
    $baseClass = $framed
        ? 'mx-auto max-w-xl rounded-xl border border-gray-200 bg-white p-3 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-4'
        : 'mx-auto max-w-xl px-4 py-4 text-center sm:py-5';
@endphp

<div {{ $attributes->merge(['class' => $baseClass]) }}>
    @isset($icon)
        <div class="mx-auto mb-2 flex justify-center">
            {{ $icon }}
        </div>
    @endisset

    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $title }}</h3>

    @if ($description)
        <p class="{{ $showDescription ? 'mt-1.5 text-sm text-gray-500 dark:text-gray-400' : 'sr-only' }}">{{ $description }}</p>
    @endif

    @isset($actions)
        <div class="mt-2">
            {{ $actions }}
        </div>
    @endisset
</div>
