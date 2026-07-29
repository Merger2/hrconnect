@props([
    'id',
    'label',
    'model',
    'error' => null,
    'type' => 'text',
    'icon' => 'heroicon-o-pencil-square',
    'modifier' => 'live',
])

<div class="user-native-field">
    <x-forms.label :for="$id" :value="$label" class="user-native-field__label" />

    <div class="user-native-field__control">
        <x-dynamic-component :component="$icon" class="user-native-field__icon" />
        <input
            id="{{ $id }}"
            type="{{ $type }}"
            aria-label="{{ $label }}"
            @if ($modifier === 'defer')
                wire:model.defer="{{ $model }}"
            @elseif ($modifier === 'live')
                wire:model.live="{{ $model }}"
            @else
                wire:model="{{ $model }}"
            @endif
            {{ $attributes->merge(['class' => 'user-native-field__input']) }}
        >
    </div>

    @if ($error)
        <x-forms.input-error :for="$error" class="mt-2" />
    @endif
</div>
