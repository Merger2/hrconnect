@props([
    'disabled' => false,
    'buttonLabel' => __('Pilih file'),
    'emptyText' => __('Tidak ada file dipilih'),
])

@php
$inputId = $attributes->get('id', 'file-input-' . \Illuminate\Support\Str::uuid()->toString());
@endphp

<div
    x-data="{ fileName: '' }"
    {{ $attributes->only('class')->merge(['class' => 'flex min-h-11 w-full flex-col gap-2']) }}
>
    <input
        x-ref="file"
        type="file"
        id="{{ $inputId }}"
        class="sr-only"
        {{ $disabled ? 'disabled' : '' }}
        x-on:change="fileName = $refs.file.files && $refs.file.files[0] ? $refs.file.files[0].name : ''"
        {{ $attributes->except(['class', 'id', 'buttonLabel', 'emptyText']) }}
    />

    <label
        for="{{ $inputId }}"
        @class([
            'inline-flex min-h-11 w-full cursor-pointer items-center justify-center rounded-xl border border-outline-variant bg-canvas px-4 py-2 text-sm font-semibold text-ink shadow-sm transition hover:bg-surface-dim focus-within:outline-none focus-within:ring-2 focus-within:ring-ink/20',
            'pointer-events-none opacity-60' => $disabled,
        ])
    >
        {{ $buttonLabel }}
    </label>

    <span class="min-w-0 truncate text-xs text-on-surface-variant" x-text="fileName || @js($emptyText)"></span>
</div>
