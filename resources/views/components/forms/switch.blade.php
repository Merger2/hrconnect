@props([
    'checked' => false,
    'label' => null,
    'size' => 'md',
    'checkedClass' => 'bg-primary-600',
    'uncheckedClass' => 'bg-gray-200',
    'focusClass' => 'focus-visible:ring-primary-500',
])

@php
    $sizeClasses = match ($size) {
        'sm' => [
            'track' => 'h-5 w-9',
            'thumb' => 'h-4 w-4',
            'translate' => 'translate-x-4',
        ],
        'lg' => [
            'track' => 'h-8 w-16',
            'thumb' => 'h-7 w-7',
            'translate' => 'translate-x-8',
        ],
        default => [
            'track' => 'h-6 w-11',
            'thumb' => 'h-5 w-5',
            'translate' => 'translate-x-5',
        ],
    };
@endphp

<button
    type="button"
    role="switch"
    aria-checked="{{ $checked ? 'true' : 'false' }}"
    @if ($label) aria-label="{{ $label }}" @endif
    {{ $attributes->merge([
        'class' => 'relative inline-flex shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 ' . $focusClass . ' ' . $sizeClasses['track'] . ' ' . ($checked ? $checkedClass : $uncheckedClass),
    ]) }}
>
    <span class="pointer-events-none inline-block transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $sizeClasses['thumb'] }} {{ $checked ? $sizeClasses['translate'] : 'translate-x-0' }}"></span>
</button>