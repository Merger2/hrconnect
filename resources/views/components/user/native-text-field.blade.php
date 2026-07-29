@props([
    'id',
    'label',
    'model',
    'error' => null,
    'type' => 'text',
    'icon' => 'heroicon-o-pencil-square',
    'modifier' => 'live',
    'placeholder' => 'Masukkan ' . strtolower($label),
])

<div class="user-native-field">
    <x-forms.label :for="$id" :value="$label" class="user-native-field__label" />

    <div class="user-native-field__control">
        <x-dynamic-component :component="$icon" class="user-native-field__icon" />
        <input
            id="{{ $id }}"
            type="{{ $type }}"
            aria-label="{{ $label }}"
<<<<<<< HEAD
            placeholder="{{ $placeholder }}"
            @if ($modifier === 'defer') wire:model.defer="{{ $model }}"
            @elseif ($modifier === 'live') wire:model.live="{{ $model }}"
            @else wire:model="{{ $model }}" @endif
            wire:loading.attr="disabled"
            wire:loading.class="opacity-50 cursor-wait"
            {{ $attributes->merge(['class' => 'h-11 w-full rounded-xl border border-outline-variant bg-canvas pl-10 pr-3 text-sm text-ink outline-none placeholder:text-on-surface-variant focus:border-ink focus:ring-1 focus:ring-ink']) }}
=======
            @if ($modifier === 'defer')
                wire:model.defer="{{ $model }}"
            @elseif ($modifier === 'live')
                wire:model.live="{{ $model }}"
            @else
                wire:model="{{ $model }}"
            @endif
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
