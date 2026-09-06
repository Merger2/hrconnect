@props([
    'options' => [],
    'placeholder' => 'Select an option',
    'selected' => null,
    'disabled' => false,
    'dropdownParent' => 'body',
    'clearable' => false,
])

@once
<style>
    /* User Theme Scope */
    .ts-wrapper-user .ts-wrapper {
        width: 100%;
    }

    .ts-wrapper-user .ts-control {
        background-color: color-mix(in srgb, var(--color-primary-50) 82%, transparent) !important;
        border: 1px solid var(--color-primary-300) !important;
        color: var(--color-primary-900) !important;
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
        color: var(--color-primary-900) !important;
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
        border-color: var(--color-primary-700) !important; /* primary-700 (slate) */
        outline: 2px solid transparent;
        outline-offset: 2px;
        background-color: var(--color-surface) !important;
        box-shadow: 0 0 0 4px color-mix(in srgb, var(--color-primary-700) 18%, transparent) !important;
    }

    /* Dropdown */
    .ts-wrapper-user .ts-dropdown {
        background-color: var(--color-surface) !important;
        border-color: var(--color-primary-200);
        color: var(--color-primary-900);
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
        background-color: var(--color-primary-100);
        color: var(--color-primary-900);
    }

    {{-- Dropdown body-level (dropdownParent=body): .ts-wrapper-user .ts-dropdown
         tidak match lagi karena dropdown pindah ke <body>.
         Gunakan .ts-dropdown langsung sebagai fallback. --}}
    .ts-dropdown {
        background-color: var(--color-surface) !important;
        border-color: var(--color-primary-200);
        color: var(--color-primary-900);
        border-radius: 1rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
        z-index: 99999 !important;
        margin-top: 4px;
    }

    .ts-dropdown .option {
        padding: 0.75rem 1rem;
        font-size: 0.95rem;
        line-height: 1.4rem;
    }

    .user-ui .ts-wrapper-user .ts-control,
    .user-ui .profile-modal .ts-wrapper .ts-control {
        background-color: var(--user-native-surface) !important;
        border-color: var(--user-native-border) !important;
        color: inherit !important;
    }

    .user-ui .ts-dropdown,
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

{{-- Inisialisasi TomSelect DIHAPUS dari x-data (fix race 2026-08-11, lihat
     komponen forms.tom-select untuk penjelasan lengkap) — init sepenuhnya di
     initUiPickers (resources/js/app.js). --}}
<div wire:ignore
     x-data="{ value: @if(isset($__livewire) && $attributes->wire('model')->value()) @entangle($attributes->wire('model')) @else @js($selected) @endif }"
     data-ui-tomselect-root
     class="w-full ts-wrapper-user relative">
    
    <select
        x-ref="select"
        data-ui-tomselect
        {{-- Dropdown dirender ke <body> (dropdownParent, lihat initUiPickers) —
             tanpa ini dropdown hidup di dalam stacking context .user-list-card
             (position:relative + z-index:0) dan TERTUTUP card berikutnya →
             opsi tidak bisa diklik di /shift-swap-requests (fix 2026-09-05). --}}
        data-tomselect-dropdown-parent="body"
        aria-label="{{ $attributes->get('aria-label', $placeholder) }}"
        {{ $attributes->whereDoesntStartWith('wire:model')->except(['options', 'placeholder', 'aria-label']) }}
        placeholder="{{ $placeholder }}">
        {{-- Render :options (fix 2026-08-11: $options sebelumnya tidak pernah
             dirender → dropdown berbasis options kosong). Bentuk didukung:
             list ['id'=>, 'name'=>] (attendance-history, shift-swap) dan
             asosiatif value=>label. Slot tetap dirender setelahnya. --}}
        @if (count($options))
            @foreach ($options as $optionKey => $option)
                @php
                    if (is_array($option)) {
                        $optionValue = $option['id'] ?? $optionKey;
                        $optionLabel = $option['name'] ?? $optionValue;
                    } else {
                        $optionValue = $optionKey;
                        $optionLabel = $option;
                    }
                @endphp
                <option value="{{ $optionValue }}" @selected((string) $optionValue === (string) $selected)>{{ $optionLabel }}</option>
            @endforeach
        @endif
        {{ $slot }}
    </select>
</div>
