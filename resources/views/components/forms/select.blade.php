@props([
    'label' => '',
    'name' => '',
    'id' => '',
    'required' => false,
    'options' => [],
    'placeholder' => '',
])

@php
    $id = $id ?: $name;
    $base = 'block w-full rounded-md border bg-canvas px-3 py-2 text-sm text-ink focus:border-ink focus:ring-0';
    $hasError = $errors->has($name);
    $classes = $hasError
        ? $base.' border-error'
        : $base.' border-outline-variant';
@endphp

@if ($label)
    <x-forms.label for="{{ $id }}" :required="$required">{{ $label }}</x-forms.label>
@endif

<select id="{{ $id }}"
    name="{{ $name }}"
    @if ($required) required @endif
    {{ $attributes->merge(['class' => $classes]) }}>
    @if ($placeholder)
        <option value="">{{ $placeholder }}</option>
    @endif
    @foreach ($options as $value => $text)
        <option value="{{ $value }}">{{ $text }}</option>
    @endforeach
</select>

<x-forms.error :name="$name" />
