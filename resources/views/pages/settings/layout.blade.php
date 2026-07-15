{{-- settings/layout.blade.php — Settings sidebar nav (HRConnect design system) --}}
<div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
    @include('partials.settings-heading')

    <div class="mt-6 flex flex-col gap-8 md:flex-row md:items-start">
        {{-- Sidebar --}}
        <aside class="w-full shrink-0 md:w-56">
            <nav aria-label="{{ __('Pengaturan') }}" class="flex flex-wrap gap-1.5 md:flex-col">
                @php
                    $items = [
                        ['route' => 'profile.edit',    'hash' => '',         'icon' => 'person',            'label' => __('Profile')],
                        ['route' => 'profile.edit',    'hash' => '#security', 'icon' => 'shield',           'label' => __('Keamanan')],
                        ['route' => 'appearance.edit', 'hash' => '',         'icon' => 'palette',           'label' => __('Tampilan')],
                    ];
                @endphp

                @foreach ($items as $item)
                    @php
                        // Fragment (hash) is client-side only and not available server-side.
                        // A hash entry (e.g. '#security') on the same route is only active
                        // when the user explicitly navigated there; we cannot detect this
                        // server-side, so we treat it as never-active for now.
                        $isActive = $item['hash']
                            ? false
                            : request()->routeIs($item['route']);
                    @endphp
                    <a href="{{ route($item['route'], $item['hash'] ? [] : []) }}{{ $item['hash'] }}"
                       wire:navigate
                       class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors {{ $isActive
                           ? 'bg-primary/10 text-primary'
                           : 'text-on-surface-variant hover:bg-surface-container-low hover:text-ink' }}">
                        <span class="material-symbols-outlined text-[20px]">{{ $item['icon'] }}</span>
                        <span>{{ $item['label'] }}</span>
                        @if ($isActive)
                            <span class="ms-auto hidden h-1.5 w-1.5 rounded-full bg-primary md:block"></span>
                        @endif
                    </a>
                @endforeach
            </nav>
        </aside>

        {{-- Content --}}
        <div class="min-w-0 flex-1">
            <div class="space-y-6">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
