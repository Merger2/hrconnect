@props(['disabled' => false, 'value' => null])

<textarea {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge([
    'class' =>
        'w-full rounded-lg border border-rule bg-paper px-4 py-2.5 text-sm text-ink placeholder:text-ink-2
         focus:outline-none focus:ring-2 focus:ring-accent focus:border-accent
         disabled:cursor-not-allowed disabled:bg-paper-3 disabled:opacity-55
         read-only:cursor-not-allowed read-only:bg-paper-3 read-only:opacity-55',
]) !!}>{{ $value ?? $slot }}</textarea>
