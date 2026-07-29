@props(['for'])

@error($for)
    <p {{ $attributes->merge(['class' => 'text-sm text-error dark:text-error']) }}>{{ $message }}</p>
@enderror
