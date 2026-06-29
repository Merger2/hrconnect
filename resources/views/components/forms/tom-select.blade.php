@props([
    'options' => [],
    'placeholder' => 'Pilih opsi',
    'selected' => null,
    'submitOnChange' => false,
    'disabled' => false,
    'dropdownDirection' => 'auto',
])

@php
$wireModelDirective = $attributes->wire('model');
$wireModel = $wireModelDirective->value();
$livewireSetLive = $wireModel && $wireModelDirective->hasModifier('live');
$alpineModelAttributes = $attributes->whereStartsWith('x-model');
@endphp

@once
    @push('styles')
        <style>
            .ts-control {
                background-color: rgba(248, 250, 252, 0.82);
                border: 0 !important;
                box-shadow: inset 0 0 0 1px rgba(203, 213, 225, 0.8);
                color: #0f172a;
                border-radius: 1rem;
                padding: 0 2.5rem 0 1rem;
                font-size: 0.9rem;
                line-height: 1.5rem;
                height: 2.75rem;
                min-height: 2.75rem;
                display: flex !important;
                align-items: center !important;
                flex-wrap: nowrap !important;
                overflow: hidden;
            }

            .ts-control .item,
            .ts-control .option,
            .ts-control > input {
                line-height: 1.5rem !important;
            }

            .ts-control > input {
                flex: 1 1 auto;
                display: inline-block !important;
                border: 0 !important;
                background: transparent !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 0 0 0.25rem !important;
                width: 1ch !important;
                max-width: 100% !important;
                min-width: 1ch !important;
                height: auto !important;
                color: #0f172a !important;
                font-size: 0.9rem !important;
                line-height: 1.5rem !important;
                min-height: 0 !important;
                vertical-align: middle !important;
            }

            .ts-control .item {
                flex: 0 1 auto;
                min-width: 0;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .ts-wrapper.single:not(.has-items) .ts-control > input {
                margin-left: 0 !important;
            }

            .ts-wrapper.focus .ts-control,
            .ts-wrapper.input-active .ts-control,
            .ts-wrapper.dropdown-active .ts-control {
                background-color: #ffffff !important;
                box-shadow: inset 0 0 0 2px var(--color-primary), 0 0 0 4px color-mix(in srgb, var(--color-primary) 18%, transparent) !important;
            }

            .ts-dropdown {
                background-color: #ffffff !important;
                border-color: #e5e7eb;
                color: #111827;
                border-radius: 1rem;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
                z-index: 99999 !important;
            }

            .ts-dropdown .ts-dropdown-content {
                background-color: #ffffff !important;
            }

            .ts-dropdown .option {
                padding: 0.75rem 1rem;
                font-size: 0.9rem;
                line-height: 1.4rem;
            }

            .ts-dropdown .active {
                background-color: #f3f4f6;
                color: #111827;
            }

            .dark .ts-control {
                background-color: rgba(2, 6, 23, 0.45) !important;
                box-shadow: inset 0 0 0 1px #1e293b !important;
                color: #f8fafc !important;
            }

            .dark .ts-control input {
                color: #f8fafc !important;
            }

            .dark .ts-wrapper.focus .ts-control,
            .dark .ts-wrapper.input-active .ts-control,
            .dark .ts-wrapper.dropdown-active .ts-control {
                background-color: #020617 !important;
                box-shadow: inset 0 0 0 2px var(--color-primary), 0 0 0 4px color-mix(in srgb, var(--color-primary) 24%, transparent) !important;
            }

            .dark .ts-dropdown {
                background-color: #0f172a !important;
                border-color: #1e293b !important;
                color: #e2e8f0 !important;
            }

            .dark .ts-dropdown .ts-dropdown-content {
                background-color: #0f172a !important;
            }

            .dark .ts-dropdown .option {
                color: #e2e8f0 !important;
            }

            .dark .ts-dropdown .active {
                background-color: #374151 !important;
                color: #ffffff !important;
            }

            .ts-wrapper {
                position: relative;
            }

            .ts-wrapper::after {
                content: '';
                position: absolute;
                top: 50%;
                right: 0.75rem;
                transform: translateY(-50%);
                width: 1.25rem;
                height: 1.25rem;
                pointer-events: none;
                background-repeat: no-repeat;
                background-position: center;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='%236b7280' class='w-6 h-6'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9' /%3E%3C/svg%3E");
                background-size: contain;
            }

            .dark .ts-wrapper::after {
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='%239ca3af' class='w-6 h-6'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9' /%3E%3C/svg%3E");
            }

            .ts-wrapper,
            .ts-wrapper *,
            .ts-wrapper *:after,
            .ts-wrapper *:before {
                box-sizing: border-box !important;
            }
        </style>
    @endpush
@endonce

<div
    wire:ignore
    x-data="tomSelectInput(
        @js($options),
        @js($placeholder),
        @if (isset($__livewire) && $wireModel) @entangle($attributes->wire('model')) @else @js($selected) @endif,
        @js((bool) $disabled),
        @js($wireModel),
        @js((bool) $submitOnChange),
        @js((bool) $livewireSetLive),
        @js($dropdownDirection)
    )"
    class="w-full"
    @if ($alpineModelAttributes->isNotEmpty()) x-modelable="value" {{ $alpineModelAttributes }} @endif
>
    <select
        x-ref="select"
        aria-label="{{ $attributes->get('aria-label', $placeholder) }}"
        {{ $disabled ? 'disabled' : '' }}
        {{ $attributes->whereDoesntStartWith(['wire:model', 'x-model'])->except(['options', 'placeholder', 'selected', 'class', 'aria-label']) }}
        placeholder="{{ $placeholder }}"
    >
        {{ $slot }}
    </select>
</div>
