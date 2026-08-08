@props([
    'variant' => 'primary',
    'size' => 'md',
    'label' => null,
])

@php
    $baseClass = 'wcag-touch-target inline-flex items-center justify-center gap-2 rounded-xl border text-sm font-semibold shadow-sm transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60';

    $variantClass = [
        'primary' => 'border-transparent bg-primary-700 text-white hover:bg-primary-800 focus:ring-primary-600 active:bg-primary-900',
        'secondary' => 'border-gray-300 bg-white text-gray-800 hover:bg-gray-50 focus:ring-primary-600',
        'success' => 'border-transparent bg-emerald-700 text-white hover:bg-emerald-800 focus:ring-emerald-600 active:bg-emerald-900',
        'warning' => 'border-transparent bg-amber-500 text-white hover:bg-amber-600 focus:ring-amber-500 active:bg-amber-700',
        'danger' => 'border-transparent bg-red-600 text-white hover:bg-red-700 focus:ring-red-600 active:bg-red-800',
        'ghost' => 'border-transparent bg-transparent text-gray-700 shadow-none hover:bg-gray-100 focus:ring-primary-600',
        'soft-primary' => 'border-transparent bg-primary-50 text-primary-700 shadow-none hover:bg-primary-100 focus:ring-primary-600',
        'soft-success' => 'border-transparent bg-emerald-50 text-emerald-700 shadow-none hover:bg-emerald-100 focus:ring-emerald-600',
        'soft-warning' => 'border-transparent bg-amber-50 text-amber-700 shadow-none hover:bg-amber-100 focus:ring-amber-600',
        'soft-danger' => 'border-transparent bg-red-50 text-red-700 shadow-none hover:bg-red-100 focus:ring-red-600',
    ][$variant] ?? 'border-transparent bg-primary-700 text-white hover:bg-primary-800 focus:ring-primary-600 active:bg-primary-900';

    $sizeClass = [
        'sm' => 'px-3 py-2 text-xs',
        'md' => 'px-4 py-2.5',
        'lg' => 'px-5 py-3 text-base',
        'icon' => 'h-10 w-10 p-0',
    ][$size] ?? 'px-4 py-2.5';

    $class = trim($baseClass . ' ' . $variantClass . ' ' . $sizeClass);

    $accessibilityAttributes = [];
    if ($label) {
        $accessibilityAttributes['aria-label'] = $label;
        $accessibilityAttributes['title'] = $label;
    }

    $htmlContent = $slot->toHtml();
    $textContent = trim(strip_tags($htmlContent));
    if (empty($textContent) && $label) {
        $textContent = trim($label);
    }
    $isAddButton = str_starts_with(strtolower($textContent), 'add ') || 
                   str_starts_with(strtolower($textContent), 'create ') || 
                   str_starts_with(strtolower($textContent), 'tambah ') || 
                   str_starts_with(strtolower($textContent), 'buat ');
                   
    $hasIcon = strpos($htmlContent, '<svg') !== false;
@endphp

@if (!isset($attributes['href']))
  <button {{ $attributes->merge(array_merge(['type' => 'submit', 'class' => $class], $accessibilityAttributes)) }}>
    @if ($isAddButton && !$hasIcon && $size !== 'icon')
        <x-heroicon-m-plus class="h-4 w-4 shrink-0" />
    @elseif ($isAddButton && !$hasIcon && $size === 'icon')
        <x-heroicon-m-plus class="h-5 w-5" />
    @endif
    {{ $slot }}
  </button>
@else
  <a {{ $attributes->merge(array_merge(['class' => $class], $accessibilityAttributes)) }}>
    @if ($isAddButton && !$hasIcon && $size !== 'icon')
        <x-heroicon-m-plus class="h-4 w-4 shrink-0" />
    @elseif ($isAddButton && !$hasIcon && $size === 'icon')
        <x-heroicon-m-plus class="h-5 w-5" />
    @endif
    {{ $slot }}
  </a>
@endif
