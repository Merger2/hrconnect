<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-gradient-to-br from-cloud to-white pb-[calc(4.5rem+env(safe-area-inset-bottom))] lg:pb-0"
        x-data
    >
        <!-- ─── Desktop Sidebar ─── -->
        <aside class="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col border-e border-blue-900/20 bg-gradient-to-b from-blue-950 via-blue-900 to-blue-950 lg:flex">
            {{-- Logo --}}
            <div class="flex h-16 items-center px-4">
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
                    <p class="text-[0.65rem] font-bold uppercase tracking-[0.18em] text-blue-200/60">{{ __($group['title']) }}</p>
                </div>

                @foreach($group['items'] as $item)
                @php
                    $pattern = $item['active_pattern'] ?? $item['route'];
                    $isActive = request()->routeIs($pattern . '*') || request()->routeIs($item['route']);
                @endphp
                <a href="{{ route($item['route']) }}"
                   @class(['group flex h-11 items-center gap-3 rounded-r-xl border-l-[3px] px-4 text-sm transition duration-150',
                           'border-l-white bg-white/10 font-semibold text-white' => $isActive,
                           'border-l-transparent font-medium text-blue-200/80 hover:border-l-white/50 hover:bg-white/5 hover:text-white' => !$isActive])
                   wire:navigate>
                    <span @class(['material-symbols-outlined text-xl transition duration-150',
                                 'text-white' => $isActive,
                                 'text-blue-300/70 group-hover:text-white' => !$isActive])>{{ $item['icon'] }}</span>
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
        <header class="sticky top-0 z-30 flex h-14 items-center justify-between border-b border-blue-100 bg-gradient-to-r from-blue-950 via-blue-900 to-blue-950 px-4 shadow-soft lg:hidden">
            <x-app-logo href="{{ route('dashboard') }}" wire:navigate />
            <div class="flex items-center gap-1">
                <x-desktop-user-menu :dropUp="false" />
            </div>
        </header>

        <!-- ─── Main Content ─── -->
        <div class="px-4 py-4 lg:ms-64 lg:px-6 lg:py-6">
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
