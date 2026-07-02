<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-canvas pb-[calc(4.5rem+env(safe-area-inset-bottom))] lg:pb-0"
        x-data
    >
        <!-- ─── Desktop Sidebar ─── -->
        <aside class="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col border-e border-outline-variant bg-canvas lg:flex">
            {{-- Logo --}}
            <div class="flex h-16 items-center px-4">
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 overflow-y-auto py-2">
                @php
                    $menu = auth()->user()
                        ? app(\App\Services\NavigationService::class)->build(auth()->user())
                        : [];
                @endphp
                @foreach($menu as $group)
                <div class="px-4 pb-2 pt-5">
                    <p class="text-xs font-semibold uppercase tracking-widest text-on-surface-variant">{{ __($group['title']) }}</p>
                </div>

                @foreach($group['items'] as $item)
                @php
                    $pattern = $item['active_pattern'] ?? $item['route'];
                    $isActive = request()->routeIs($pattern . '*') || request()->routeIs($item['route']);
                @endphp
                <a href="{{ route($item['route']) }}"
                   @class(['flex h-12 items-center gap-3 px-4 text-sm font-medium transition-colors',
                           'bg-ink/5 text-ink' => $isActive,
                           'text-on-surface-variant hover:bg-ink/5 hover:text-ink' => !$isActive])
                   wire:navigate>
                    <span class="material-symbols-outlined text-2xl">{{ $item['icon'] }}</span>
                    <span>{{ __($item['label']) }}</span>
                </a>
                @endforeach
                @endforeach
            </nav>

            {{-- User --}}
            <div class="flex items-center justify-between border-t border-outline-variant p-3">
                <x-desktop-user-menu />
            </div>
        </aside>

        <!-- ─── Mobile Header ─── -->
        <header class="sticky top-0 z-30 flex h-14 items-center justify-between border-b border-outline-variant bg-canvas px-4 lg:hidden">
            <x-app-logo href="{{ route('dashboard') }}" wire:navigate />
            <div class="flex items-center gap-1">
                <x-desktop-user-menu />
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
                if (!session()->has('web_sanctum_token')) {
                    $token = auth()->user()->createToken('web-frontend');
                    session()->put('web_sanctum_token', $token->plainTextToken);
                }
            @endphp
            <script>
                window.Laravel = { sanctumToken: '{{ session('web_sanctum_token') }}' };
            </script>
        @endauth

        @vite(['resources/js/app.js'])
        @stack('scripts')
    </body>
</html>
