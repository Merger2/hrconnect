@props([
    'checked' => false,
    'label' => null,
    'size' => 'md',
    'disabled' => false,
    'onValue' => '1',
    'offValue' => '0',
])

@php
$sizeClasses = match ($size) {
    'sm' => [
        'track' => 'h-6 w-11',
        'thumb' => 'h-5 w-5',
        'translate' => 'translate-x-5',
    ],
    'lg' => [
        'track' => 'h-7 w-14',
        'thumb' => 'h-6 w-6',
        'translate' => 'translate-x-7',
    ],
    default => [
        'track' => 'h-7 w-12',
        'thumb' => 'h-5 w-5',
        'translate' => 'translate-x-5',
    ],
};
@endphp

<label class="inline-flex items-center gap-3 {{ $disabled ? 'opacity-50' : 'cursor-pointer' }}">
    <button
        type="button"
        role="switch"
        aria-checked="{{ $checked ? 'true' : 'false' }}"
        @if ($label) aria-label="{{ $label }}" @endif
        @disabled($disabled)
        {{ $attributes->merge([
            'class' => 'relative inline-flex shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-ink/20 ' . $sizeClasses['track'] . ' ' . ($checked ? 'bg-primary' : 'bg-outline-variant'),
        ]) }}
    >
        <span class="pointer-events-none inline-block transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out {{ $sizeClasses['thumb'] }} {{ $checked ? $sizeClasses['translate'] : 'translate-x-0' }}"></span>
    </button>
    @if ($label)
        <span class="select-none text-sm text-ink">{{ $label }}</span>
    @endif
</label>
