@props([
    'id',
    'label',
    'model',
    'error' => null,
    'icon' => 'edit_note',
    'modifier' => 'live',
    'rows' => 4,
])

<div class="space-y-1">
    <label for="{{ $id }}" class="text-sm font-medium text-ink">{{ $label }}</label>

    <div class="relative">
        <span class="absolute left-3 top-3 text-on-surface-variant">
            <span class="material-symbols-outlined text-lg">{{ $icon }}</span>
        </span>
        <textarea
            id="{{ $id }}"
            rows="{{ $rows }}"
            aria-label="{{ $label }}"
            @if ($modifier === 'defer') wire:model.defer="{{ $model }}"
            @elseif ($modifier === 'live') wire:model.live="{{ $model }}"
            @else wire:model="{{ $model }}" @endif
            {{ $attributes->merge(['class' => 'w-full rounded-xl border border-outline-variant bg-canvas pl-10 pr-3 py-2.5 text-sm text-ink outline-none placeholder:text-on-surface-variant focus:border-ink focus:ring-1 focus:ring-ink']) }}
        ></textarea>
    </div>

    @if ($error)
        <p class="text-xs text-error">{{ $error }}</p>
    @endif
</div>
