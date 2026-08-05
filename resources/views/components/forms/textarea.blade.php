@props(['disabled' => false, 'value' => null])

<textarea {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge([
    'class' =>
        'w-full rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400
         focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500
         disabled:cursor-not-allowed disabled:bg-gray-100 disabled:opacity-55
         read-only:cursor-not-allowed read-only:bg-gray-100 read-only:opacity-55',
]) !!}>{{ $value ?? $slot }}</textarea>
