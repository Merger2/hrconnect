@php
    $tabs = [
        [
            'name' => __('Dasbor'),
            'icon' => 'home',
            'route' => 'dashboard',
            'patterns' => ['dashboard'],
        ],
        [
            'name' => __('Absen'),
            'icon' => 'fact_check',
            'route' => 'attendance.index',
            'patterns' => ['attendance.*'],
        ],
        [
            'name' => __('Inbox'),
            'icon' => 'inbox',
            'route' => '#',
            'patterns' => [],
            'disabled' => true,
        ],
        [
            'name' => __('Profil'),
            'icon' => 'person',
            'route' => 'profile.edit',
            'patterns' => ['profile.edit', 'security.edit', 'appearance.edit'],
        ],
    ];
@endphp

<nav class="fixed inset-x-0 bottom-0 z-40 flex h-20 items-center justify-around border-t border-outline-variant bg-canvas lg:hidden">
    @foreach ($tabs as $tab)
        @php
            $isActive = !empty($tab['patterns']) && request()->routeIs(...$tab['patterns']);
        @endphp

        @if (!empty($tab['disabled']))
            <button disabled class="flex h-full flex-1 flex-col items-center justify-center gap-0.5 text-on-surface-variant opacity-40">
                <span class="material-symbols-outlined text-2xl">{{ $tab['icon'] }}</span>
                <span class="text-[11px] font-medium leading-tight">{{ $tab['name'] }}</span>
            </button>
        @else
            <a href="{{ route($tab['route']) }}"
               @class([
                   'flex h-full flex-1 flex-col items-center justify-center gap-0.5 transition-colors',
                   'text-ink' => $isActive,
                   'text-on-surface-variant hover:text-ink' => !$isActive,
               ])
               wire:navigate>
                <span class="material-symbols-outlined {{ $isActive ? 'text-2xl' : 'text-2xl' }}">
                    {{ $tab['icon'] }}
                </span>
                <span class="text-[11px] font-medium leading-tight">{{ $tab['name'] }}</span>
                @if ($isActive)
                    <span class="absolute bottom-0 h-1 w-6 rounded-full bg-ink"></span>
                @endif
            </a>
        @endif
    @endforeach
</nav>
