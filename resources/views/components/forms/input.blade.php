@props([
    'label' => '',
    'name' => '',
    'id' => '',
    'type' => 'text',
    'required' => false,
    'placeholder' => '',
])

@php
    $id = $id ?: $name;
    $base = 'block w-full rounded-md border bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-500 focus:border-gray-900 focus:ring-0';
    $errorsBag = $errors ?? session('errors');
    $hasError = $errorsBag && method_exists($errorsBag, 'has') ? $errorsBag->has($name) : false;
    $classes = $hasError
        ? $base.' border-error'
        : $base.' border-gray-300';
@endphp

@if ($label)
    <x-forms.label for="{{ $id }}" :required="$required">{{ $label }}</x-forms.label>
@endif

<input type="{{ $type }}"
    id="{{ $id }}"
    name="{{ $name }}"
    placeholder="{{ $placeholder ?: 'Masukkan ' . strtolower($label) }}"
    @if ($required) required @endif
    {{ $attributes->merge(['class' => $classes]) }} />

<x-forms.error :name="$name" />
