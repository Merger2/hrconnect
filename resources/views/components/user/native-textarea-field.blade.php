@props([
    'id',
    'label',
    'model',
    'error' => null,
    'icon' => 'heroicon-o-pencil-square',
    'modifier' => 'live',
    'rows' => 4,
])

<div class="user-native-field">
    <x-forms.label :for="$id" :value="$label" class="user-native-field__label" />

    <div class="user-native-field__control user-native-field__control--textarea">
        <x-dynamic-component :component="$icon" class="user-native-field__icon user-native-field__icon--top" />
        <textarea
            id="{{ $id }}"
            rows="{{ $rows }}"
            aria-label="{{ $label }}"
            @if ($modifier === 'defer')
                wire:model.defer="{{ $model }}"
            @elseif ($modifier === 'live')
                wire:model.live="{{ $model }}"
            @else
                wire:model="{{ $model }}"
            @endif
            {{ $attributes->merge(['class' => 'user-native-field__input user-native-field__input--textarea']) }}
        ></textarea>
    </div>

    @if ($error)
        <x-forms.input-error :for="$error" class="mt-2" />
    @endif
</div>
