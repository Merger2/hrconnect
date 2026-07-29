<<<<<<< HEAD
@props([
    'label' => '',
    'name' => '',
    'id' => '',
    'required' => false,
    'options' => [],
    'placeholder' => 'Pilih...',
])
=======
@props(['disabled' => false])
>>>>>>> main

@php
    $requestPath = '/' . ltrim(request()->path(), '/');
    $isAdminContext = str_starts_with($requestPath, '/admin') || str_starts_with((request()->headers->get('referer') ?? ''), '/admin');
@endphp

<<<<<<< HEAD
@if ($label)
    <x-forms.label for="{{ $id }}" :required="$required">{{ $label }}</x-forms.label>
@endif

<select id="{{ $id }}"
    name="{{ $name }}"
    @if ($required) required @endif
    {{ $attributes->merge(['class' => $classes]) }}>
    <option value="">{{ $placeholder }}</option>
    @foreach ($options as $value => $text)
        <option value="{{ $value }}">{{ $text }}</option>
    @endforeach
</select>

<x-forms.error :name="$name" />
=======
@if ($isAdminContext)
    <x-forms.tom-select :disabled="$disabled" {{ $attributes }}>
        {{ $slot }}
    </x-forms.tom-select>
@else
    <x-user.tom-select-user :disabled="$disabled" {{ $attributes }}>
        {{ $slot }}
    </x-user.tom-select-user>
@endif
>>>>>>> main
