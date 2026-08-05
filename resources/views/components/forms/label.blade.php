@props(['for' => '', 'required' => false, 'value' => ''])

<label for="{{ $for }}"
    {{ $attributes->merge(['class' => 'mb-1 block text-sm font-medium text-gray-900']) }}>
    {{ $value !== '' ? $value : $slot }}
    @if ($required)
        <span class="text-error">*</span>
    @endif
</label>
