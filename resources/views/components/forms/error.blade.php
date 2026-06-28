@props(['name' => ''])

@error($name)
    <p {{ $attributes->merge(['class' => 'mt-1 text-xs text-error']) }}>{{ $message }}</p>
@enderror
