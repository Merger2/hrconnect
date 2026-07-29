@props([
    'id',
    'label',
    'model' => null,
    'name' => null,
    'value' => null,
    'error' => null,
    'type' => 'date',
    'icon' => null,
    'modifier' => 'live',
    'min' => null,
    'max' => null,
    'required' => false,
    'placeholder' => 'Pilih tanggal',
])

@php
    $fieldIcon = $icon ?? match ($type) {
        'time' => 'heroicon-o-clock',
        default => 'heroicon-o-calendar-days',
    };
    $pickerMode = $type === 'datetime-local' ? 'datetime' : $type;
    $renderType = in_array($type, ['date', 'time', 'datetime-local'], true) ? 'text' : $type;
@endphp

<div class="user-native-field">
    <x-forms.label :for="$id" :value="$label" class="user-native-field__label" />

    <div class="user-native-field__control">
        <x-dynamic-component :component="$fieldIcon" class="user-native-field__icon" />
        <input
            id="{{ $id }}"
            @if ($name) name="{{ $name }}" @endif
            type="{{ $renderType }}"
            aria-label="{{ $label }}"
            placeholder="{{ $placeholder }}"
            @if (filled($value)) value="{{ $value }}" @endif
            @if ($required) required @endif
            @if (in_array($type, ['date', 'time', 'datetime-local'], true))
                data-ui-picker="{{ $pickerMode }}"
                autocomplete="off"
                inputmode="none"
                readonly
            @endif
            @if ($min) min="{{ $min }}" @endif
            @if ($max) max="{{ $max }}" @endif
            @if ($model)
                @if ($modifier === 'defer')
                    wire:model.defer="{{ $model }}"
                @elseif ($modifier === 'live')
                    wire:model.live="{{ $model }}"
                @else
                    wire:model="{{ $model }}"
                @endif
            @endif
<<<<<<< HEAD
            wire:loading.attr="disabled"
            wire:loading.class="opacity-50 cursor-wait"
            {{ $attributes->merge(['class' => 'h-11 w-full rounded-xl border border-outline-variant bg-canvas pl-10 pr-3 text-sm text-ink outline-none placeholder:text-on-surface-variant focus:border-ink focus:ring-1 focus:ring-ink']) }}
=======
            {{ $attributes->merge(['class' => 'user-native-field__input']) }}
>>>>>>> main
        >
        <span wire:loading.inline class="absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant">
            <span class="material-symbols-outlined text-lg animate-spin">progress_activity</span>
        </span>
    </div>

    @if ($error)
        <x-forms.input-error :for="$error" class="mt-2" />
    @endif
</div>
