<div class="flex items-start max-md:flex-col">
    {{-- Sidebar Nav — Desktop only --}}
    <div class="me-10 hidden w-full pb-4 md:block md:w-[220px]">
        <nav aria-label="{{ __('Settings') }}">
            <ul class="space-y-1">
                <li>
                    <a href="{{ route('profile.edit') }}"
                       @class(['flex rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                               'bg-ink/5 text-ink' => request()->routeIs('profile.edit'),
                               'text-ink hover:bg-surface-container-high' => !request()->routeIs('profile.edit')])
                       wire:navigate>{{ __('Profile') }}</a>
                </li>
                <li>
                    <a href="{{ route('security.edit') }}"
                       @class(['flex rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                               'bg-ink/5 text-ink' => request()->routeIs('security.edit'),
                               'text-ink hover:bg-surface-container-high' => !request()->routeIs('security.edit')])
                       wire:navigate>{{ __('Security') }}</a>
                </li>
                <li>
                    <a href="{{ route('appearance.edit') }}"
                       @class(['flex rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                               'bg-ink/5 text-ink' => request()->routeIs('appearance.edit'),
                               'text-ink hover:bg-surface-container-high' => !request()->routeIs('appearance.edit')])
                       wire:navigate>{{ __('Appearance') }}</a>
                </li>
            </ul>
        </nav>
    </div>

    <div class="flex-1 self-stretch max-md:pt-6">
        <h2 class="text-lg font-semibold text-ink">{{ $heading ?? '' }}</h2>
        <p class="text-sm text-on-surface-variant">{{ $subheading ?? '' }}</p>

        <div class="mt-5 w-full max-w-lg">
            {{ $slot }}
        </div>
    </div>
</div>
