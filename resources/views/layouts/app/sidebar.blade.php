<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-cloud pb-[calc(4.5rem+env(safe-area-inset-bottom))] lg:pb-0"
        x-data
    >
        <!-- ─── Desktop Sidebar ─── -->
        <aside class="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col border-e border-outline-variant bg-canvas lg:flex">
            {{-- Logo --}}
            <div class="flex h-16 items-center border-b border-outline-variant/60 px-4">
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 overflow-y-auto py-2">
                @php
                    $user = auth()->user();
                    $menu = $user ? (new \App\Services\NavigationService)->build($user) : [];
                @endphp
                @if (empty($menu))
                <div class="px-4 py-8 text-center text-xs text-on-surface-variant/40">{{ __('Tidak ada menu') }}</div>
                @endif
                @foreach($menu as $group)
                <div class="px-4 pb-2 pt-5 first:pt-2">
                    <p class="text-[0.65rem] font-bold uppercase tracking-[0.18em] text-on-surface-variant">{{ __($group['title']) }}</p>
                </div>

                @foreach($group['items'] as $item)
                @php
                    $pattern = $item['active_pattern'] ?? $item['route'];
                    $isActive = request()->routeIs($pattern . '*') || request()->routeIs($item['route']);
                @endphp
                <a href="{{ route($item['route']) }}"
                   @class(['group flex h-11 items-center gap-3 rounded-xl px-3 text-sm transition duration-150',
                           'bg-primary/10 font-semibold text-primary' => $isActive,
                           'font-medium text-on-surface-variant hover:bg-surface-container-low hover:text-ink' => !$isActive])
                   wire:navigate>
                    <span @class(['material-symbols-outlined text-xl transition duration-150',
                                 'text-primary' => $isActive,
                                 'text-on-surface-variant group-hover:text-ink' => !$isActive])>{{ $item['icon'] }}</span>
                    <span class="leading-5">{{ __($item['label']) }}</span>
                </a>
                @endforeach
                @endforeach
            </nav>

            {{-- User --}}
            <div class="flex items-center border-t border-white/10 p-3">
                <x-desktop-user-menu />
            </div>
        </aside>

        <!-- ─── Mobile Header ─── -->
        <header class="sticky top-0 z-30 flex h-14 items-center justify-between border-b border-outline-variant bg-canvas px-4 shadow-soft lg:hidden">
            <x-app-logo href="{{ route('dashboard') }}" wire:navigate />
            <div class="flex items-center gap-1">
                <x-desktop-user-menu :dropUp="false" />
            </div>
        </header>

        <!-- ─── Main Content ─── -->
        <div class="lg:pl-64">
            {{ $slot }}
        </div>

        {{-- Mobile Bottom Navigation --}}
        <x-bottom-nav />

        @persist('toast')
            <div id="toast-container"></div>
        @endpersist

        @auth
            @php
                session()->forget('web_sanctum_token');
            @endphp
        @endauth

        @vite(['resources/js/app.js'])
        @stack('scripts')

        {{-- PWA Install Prompt --}}
        @auth
            <x-pwa-install-prompt />
        @endauth
    </body>
</html>
