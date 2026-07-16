@props([
    'label' => '',
    'name' => '',
    'id' => '',
    'required' => false,
    'placeholder' => 'Tulis di sini...',
    'rows' => 3,
])

@php
    $id = $id ?: $name;
    $base = 'block w-full rounded-md border bg-canvas px-3 py-2 text-sm text-ink placeholder:text-muted-soft focus:border-ink focus:ring-0';
    $hasError = $errors->has($name);
    $classes = $hasError
        ? $base.' border-error'
        : $base.' border-outline-variant';
@endphp

@if ($label)
    <x-forms.label for="{{ $id }}" :required="$required">{{ $label }}</x-forms.label>
@endif

<textarea id="{{ $id }}"
    name="{{ $name }}"
    rows="{{ $rows }}"
    placeholder="{{ $placeholder }}"
    @if ($required) required @endif
    {{ $attributes->merge(['class' => $classes]) }}></textarea>

<x-forms.error :name="$name" />
