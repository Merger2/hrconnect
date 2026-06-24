@props([
    'title',
    'description',
])

<div class="flex w-full flex-col text-center">
    <h1 class="text-2xl font-semibold text-ink">{{ $title }}</h1>
    <p class="text-sm text-on-surface-variant">{{ $description }}</p>
</div>
