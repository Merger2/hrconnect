@props([
    'options' => [],
    'placeholder' => 'Select an option',
    'selected' => null,
    'disabled' => false,
    'dropdownParent' => 'body',
])

@once
<style>
    /* User Theme Scope */
    .ts-wrapper-user .ts-wrapper {
        width: 100%;
    }

    .ts-wrapper-user .ts-control {
        background-color: rgba(248, 250, 252, 0.82) !important;
        border: 1px solid rgba(203, 213, 225, 0.8) !important;
        color: #0f172a !important;
        border-radius: 1rem !important;
        padding: 0 2.5rem 0 1rem !important;
        box-shadow: none !important;
        font-size: 1rem !important;
        font-weight: 500 !important;
        line-height: 1.5rem !important;
        height: 3.25rem !important;
        min-height: 3.25rem !important;
        display: flex !important;
        align-items: center !important;
        flex-wrap: nowrap !important;
        overflow: hidden !important;
    }

    .ts-wrapper-user .ts-control > input {
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
        color: #0f172a !important;
        font-size: 1rem !important;
        font-weight: 500 !important;
        vertical-align: middle !important;
    }

    .ts-wrapper-user .ts-control .item {
        flex: 0 1 auto;
        min-width: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .ts-wrapper-user .ts-wrapper.single:not(.has-items) .ts-control > input {
        margin-left: 0 !important;
    }

    .ts-wrapper-user .ts-wrapper.focus .ts-control,
    .ts-wrapper-user .ts-wrapper.input-active .ts-control,
    .ts-wrapper-user .ts-wrapper.dropdown-active .ts-control {
        border-color: #6ab45b !important; /* primary-500 */
        outline: 2px solid transparent;
        outline-offset: 2px;
        background-color: #ffffff !important;
        box-shadow: 0 0 0 4px rgba(106, 180, 91, 0.18) !important;
    }

    /* Dropdown */
    .ts-wrapper-user .ts-dropdown {
        background-color: #ffffff !important;
        border-color: #e5e7eb;
        color: #111827;
        border-radius: 1rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
        z-index: 99999 !important;
        margin-top: 4px;
    }

    .ts-wrapper-user .ts-dropdown .option {
        padding: 0.75rem 1rem;
        font-size: 0.95rem;
        line-height: 1.4rem;
    }

    .ts-wrapper-user .ts-dropdown .active {
        background-color: #f3f4f6;
        color: #111827;
    }

    /* Dark Mode */
    .dark .ts-wrapper-user .ts-control {
        background-color: rgba(2, 6, 23, 0.45) !important;
        border-color: #1e293b !important;
        color: #f8fafc !important;
    }

    .dark .ts-wrapper-user .ts-control input {
        color: #f8fafc !important;
    }

    .dark .ts-wrapper-user .ts-wrapper.focus .ts-control,
    .dark .ts-wrapper-user .ts-wrapper.input-active .ts-control,
    .dark .ts-wrapper-user .ts-wrapper.dropdown-active .ts-control {
        border-color: #6ab45b !important; /* primary-500 */
        background-color: #020617 !important;
        box-shadow: 0 0 0 4px rgba(106, 180, 91, 0.24) !important;
    }

    .dark .ts-wrapper-user .ts-dropdown {
        background-color: #0f172a !important;
        border-color: #1e293b !important;
        color: #e2e8f0 !important;
    }

    .dark .ts-wrapper-user .ts-dropdown .active {
        background-color: #374151 !important;
        color: #ffffff !important;
    }

    .user-ui .ts-wrapper-user .ts-control,
    .user-ui .profile-modal .ts-wrapper .ts-control {
        background-color: var(--user-native-surface) !important;
        border-color: var(--user-native-border) !important;
        color: inherit !important;
    }

    .user-ui .ts-wrapper-user .ts-dropdown,
    .user-ui .profile-modal .ts-dropdown {
        background-color: var(--user-native-surface-strong) !important;
        border-color: var(--user-native-border) !important;
        color: inherit !important;
    }

    /* Chevron */
    .ts-wrapper-user::after {
        content: '';
        position: absolute;
        top: 50%;
        right: 1rem;
        transform: translateY(-50%);
        width: 1.25rem;
        height: 1.25rem;
        pointer-events: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='%236b7280' class='w-6 h-6'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19.5 8.25l-7.5 7.5-7.5-7.5' /%3E%3C/svg%3E");
        background-size: contain;
        background-repeat: no-repeat;
    }
</style>
@endonce

<div wire:ignore
     x-data="tomSelectInput(
        @js($options), 
        @js($placeholder),
        @if(isset($__livewire) && $attributes->wire('model')->value()) @entangle($attributes->wire('model')) @else @js($selected) @endif,
        {{ $disabled ? 'true' : 'false' }},
        null,
        false,
        false,
        'auto',
        @js($dropdownParent)
     )"
     class="w-full ts-wrapper-user relative">
    
    <select
        x-ref="select"
        aria-label="{{ $attributes->get('aria-label', $placeholder) }}"
        {{ $attributes->whereDoesntStartWith('wire:model')->except(['options', 'placeholder', 'aria-label']) }}
        placeholder="{{ $placeholder }}">
        {{ $slot }}
    </select>
</div>
