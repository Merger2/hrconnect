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
            background-color: rgba(248, 250, 252, 0.82);
            border: 0 !important;
            box-shadow: inset 0 0 0 1px rgba(203, 213, 225, 0.8);
            color: #0f172a;
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
            color: #0f172a !important;
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
            background-color: #ffffff !important;
            box-shadow: inset 0 0 0 1px #6ab45b, 0 0 0 4px rgba(106, 180, 91, 0.18) !important;
        }

        /* Dropdown */
        .ts-dropdown {
            background-color: #ffffff !important;
            border-color: #e5e7eb;
            color: #111827;
            border-radius: 1rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
            z-index: 99999 !important;
            opacity: 1 !important;
        }

        .ts-dropdown .ts-dropdown-content {
            background-color: #ffffff !important;
        }

        .ts-dropdown .option {
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            line-height: 1.4rem;
        }

        .ts-dropdown .active {
            background-color: #f3f4f6;
            /* gray-100 */
            color: #111827;
        }

        /* Dark Mode - Root selector to ensure specificity */
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
            box-shadow: inset 0 0 0 1px #6ab45b, 0 0 0 4px rgba(106, 180, 91, 0.24) !important;
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
            /* bg-gray-700 */
            color: #ffffff !important;
        }

        .dark .ts-dropdown .option:hover,
        .dark .ts-dropdown .option.active {
            background-color: #374151 !important;
            color: #ffffff !important;
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
            border-color: #6ab45b !important;
            box-shadow: 0 0 0 4px rgba(106, 180, 91, 0.22) !important;
        }

        .user-ui .ts-wrapper-user .ts-dropdown,
        .user-ui .profile-modal .ts-dropdown {
            background-color: var(--user-native-surface-strong) !important;
            border-color: var(--user-native-border) !important;
            color: inherit !important;
        }

        /* Input placeholder color in dark mode */
        .dark .ts-control ::placeholder {
            color: #64748b !important;
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

        .dark .ts-wrapper::after {
            /* Heroicons Chevron Down - Gray 400 */
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='%239ca3af' class='w-6 h-6'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9' /%3E%3C/svg%3E");
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



<div wire:ignore x-data="tomSelectInput(
    @js($options),
    @js($placeholder),
    @if (isset($__livewire) && $wireModel) @entangle($attributes->wire('model')) @else @js($selected) @endif,
    @js((bool) $disabled),
    @js($wireModel),
    @js((bool) $submitOnChange),
    @js((bool) $livewireSetLive),
    @js($dropdownDirection)
)" class="{{ $wrapperClass }}" @if ($alpineModelAttributes->isNotEmpty()) x-modelable="value" {{ $alpineModelAttributes }} @endif>

    <select
        x-ref="select"
        aria-label="{{ $attributes->get('aria-label', $placeholder) }}"
        {{ $disabled ? 'disabled' : '' }}
        {{ $attributes->whereDoesntStartWith(['wire:model', 'x-model'])->except(['options', 'placeholder', 'selected', 'class', 'aria-label']) }}
        placeholder="{{ $placeholder }}">
        {{ $slot }}
    </select>
</div>
