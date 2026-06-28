@props(['for' => '', 'required' => false])

<label for="{{ $for }}"
    {{ $attributes->merge(['class' => 'mb-1 block text-sm font-medium text-ink']) }}>
    {{ $slot }}
    @if ($required)
        <span class="text-error">*</span>
    @endif
</label>
