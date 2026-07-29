@props(['disabled' => false])

@php
    $requestPath = '/' . ltrim(request()->path(), '/');
    $isAdminContext = str_starts_with($requestPath, '/admin') || str_starts_with((request()->headers->get('referer') ?? ''), '/admin');
@endphp

@if ($isAdminContext)
    <x-forms.tom-select :disabled="$disabled" {{ $attributes }}>
        {{ $slot }}
    </x-forms.tom-select>
@else
    <x-user.tom-select-user :disabled="$disabled" {{ $attributes }}>
        {{ $slot }}
    </x-user.tom-select-user>
@endif