@props([
    'options' => [],
    'placeholder' => 'Select an option',
    'selected' => null,
    'submitOnChange' => false,
    'disabled' => false,
    'dropdownDirection' => 'auto',
])

@php
    $requestPath = '/' . ltrim(request()->path(), '/');
    $refererPath = parse_url(request()->headers->get('referer') ?? '', PHP_URL_PATH) ?? '';
    $isAdminContext = str_starts_with($requestPath, '/admin') || str_starts_with($refererPath, '/admin');
    $wireModelDirective = $attributes->wire('model');
    $wireModel = $wireModelDirective->value();
    $livewireSetLive = $wireModel && $wireModelDirective->hasModifier('live');
    $alpineModelAttributes = $attributes->whereStartsWith('x-model');
    $wrapperClass = trim(collect([
        $attributes->get('class') ? null : 'w-full',
        $isAdminContext ? 'ts-wrapper-admin' : null,
        $isAdminContext ? null : 'ts-wrapper-user relative',
        $attributes->get('class'),
    ])->filter()->implode(' '));
@endphp

@once
    <style>
        .ts-control {
            background-color: color-mix(in srgb, var(--color-primary-50) 82%, transparent);
            border: 0 !important;
            box-shadow: inset 0 0 0 1px var(--color-primary-300);
            color: var(--color-primary-900);
            border-radius: 1rem;
            padding: 0 2.5rem 0 1rem;
            font-size: 1rem;
            font-weight: 500;
            line-height: 1.5rem;
            height: 3.25rem;
            min-height: 3.25rem;
            display: flex !important;
            align-items: center !important;
            flex-wrap: nowrap !important;
            overflow: hidden;
        }

        .ts-wrapper-admin.ts-wrapper {
            min-height: 2.75rem !important;
            height: 2.75rem !important;
        }

        .ts-wrapper-admin .ts-control {
            height: 2.75rem !important;
            min-height: 2.75rem !important;
            padding: 0 2.5rem 0 1rem !important;
            line-height: 1.25rem !important;
        }

        .ts-wrapper-admin .ts-control .item,
        .ts-wrapper-admin .ts-control > input {
            line-height: 1.25rem !important;
            height: 1.25rem !important;
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
            color: var(--color-primary-900) !important;
            font-size: 1rem !important;
            font-weight: 500 !important;
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

        .ts-wrapper.single:not(.has-items) .ts-control>input {
            margin-left: 0 !important;
        }

        .ts-wrapper.focus .ts-control,
        .ts-wrapper.input-active .ts-control,
        .ts-wrapper.dropdown-active .ts-control {
            background-color: var(--color-surface) !important;
            box-shadow: inset 0 0 0 1px var(--color-primary-700), 0 0 0 4px color-mix(in srgb, var(--color-primary-700) 18%, transparent) !important;
        }

        /* Dropdown */
        .ts-dropdown {
            background-color: var(--color-surface) !important;
            border-color: var(--color-primary-200);
            color: var(--color-primary-900);
            border-radius: 1rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
            z-index: 99999 !important;
            opacity: 1 !important;
        }

        .ts-dropdown .ts-dropdown-content {
            background-color: var(--color-surface) !important;
        }

        .ts-dropdown .option {
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            line-height: 1.4rem;
        }

        .ts-dropdown .active {
            background-color: var(--color-primary-100);
            /* gray-100 */
            color: var(--color-primary-900);
        }

        .user-ui .ts-wrapper-user .ts-control,
        .user-ui .profile-modal .ts-wrapper .ts-control {
            background-color: var(--user-native-surface) !important;
            border: 1px solid var(--user-native-border) !important;
            color: inherit !important;
            border-radius: 1.05rem !important;
            box-shadow: none !important;
        }

        .user-ui .ts-wrapper-user.focus .ts-control,
        .user-ui .ts-wrapper-user.input-active .ts-control,
        .user-ui .ts-wrapper-user.dropdown-active .ts-control,
        .user-ui .ts-wrapper-user .ts-wrapper.focus .ts-control,
        .user-ui .ts-wrapper-user .ts-wrapper.input-active .ts-control,
        .user-ui .ts-wrapper-user .ts-wrapper.dropdown-active .ts-control,
        .user-ui .profile-modal .ts-wrapper.focus .ts-control,
        .user-ui .profile-modal .ts-wrapper.input-active .ts-control,
        .user-ui .profile-modal .ts-wrapper.dropdown-active .ts-control {
            border-color: var(--color-primary-700) !important;
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--color-primary-700) 22%, transparent) !important;
        }

        .user-ui .ts-wrapper-user .ts-dropdown,
        .user-ui .profile-modal .ts-dropdown {
            background-color: var(--user-native-surface-strong) !important;
            border-color: var(--user-native-border) !important;
            color: inherit !important;
        }

        /* Chevron Arrow */
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
            /* Heroicons Chevron Down - Gray 500 */
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='%236b7280' class='w-6 h-6'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9' /%3E%3C/svg%3E");
            background-size: contain;
        }

        /* High Z-Index for Dropdown */
        .ts-dropdown {
            z-index: 99999 !important;
        }

        .ts-wrapper,
        .ts-wrapper *,
        .ts-wrapper *:after,
        .ts-wrapper *:before {
            box-sizing: border-box !important;
        }

        @supports (-moz-appearance: none) {
            .ts-wrapper-admin.ts-wrapper,
            .ts-wrapper-admin .ts-control {
                height: 2.75rem !important;
                min-height: 2.75rem !important;
            }
        }
    </style>
@endonce



<div wire:ignore x-data="window.tomSelectInput ? tomSelectInput(
    @js($options),
    @js($placeholder),
    @if (isset($__livewire) && $wireModel) @entangle($attributes->wire('model')) @else @js($selected) @endif,
    @js((bool) $disabled)
) : {}" class="{{ $wrapperClass }}" @if ($alpineModelAttributes->isNotEmpty()) x-modelable="value" {{ $alpineModelAttributes }} @endif>

    <select
        x-ref="select"
        data-ui-tomselect
        aria-label="{{ $attributes->get('aria-label', $placeholder) }}"
        {{ $disabled ? 'disabled' : '' }}
        {{ $attributes->whereDoesntStartWith(['wire:model', 'x-model'])->except(['options', 'placeholder', 'selected', 'class', 'aria-label']) }}
        placeholder="{{ $placeholder }}">
        {{ $slot }}
    </select>
</div>
