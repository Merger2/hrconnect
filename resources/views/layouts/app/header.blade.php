<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-canvas pb-20 lg:pb-0">
        <!-- ─── Desktop TopAppBar ─── -->
        <header class="sticky top-0 z-30 hidden border-b border-outline-variant bg-canvas lg:block">
            <div class="flex h-16 items-center gap-6 px-6">
                <x-app-logo href="{{ route('dashboard') }}" wire:navigate />

                <nav class="flex h-full items-center gap-1">
                    <a href="{{ route('dashboard') }}"
                       @class(['flex h-full items-center gap-2 border-b-2 px-2 text-sm font-medium transition-colors',
                               'border-ink text-ink' => request()->routeIs('dashboard'),
                               'border-transparent text-on-surface-variant hover:border-outline hover:text-ink' => !request()->routeIs('dashboard')])
                       wire:navigate>
                        <span class="material-symbols-outlined text-xl">grid_view</span>
                        {{ __('Dashboard') }}
                    </a>
                </nav>

                <div class="grow"></div>

                <div class="flex items-center gap-1">
                    <button class="flex size-10 items-center justify-center rounded-full text-on-surface-variant hover:bg-surface-container-high hover:text-ink">
                        <span class="material-symbols-outlined text-xl">search</span>
                    </button>
                    <x-desktop-user-menu />
                </div>
            </div>
        </header>

        <!-- ─── Mobile Header ─── -->
        <header class="sticky top-0 z-30 flex h-14 items-center justify-between border-b border-outline-variant bg-canvas px-4 lg:hidden">
            <x-app-logo href="{{ route('dashboard') }}" wire:navigate />
            <x-desktop-user-menu />
        </header>

        <!-- ─── Main Content ─── -->
        <div class="px-4 py-4 lg:px-6 lg:py-6">
            {{ $slot }}
        </div>

        {{-- Mobile Bottom Navigation --}}
        <x-bottom-nav />

        @persist('toast')
            <div
                x-data="toast"
                x-show="show"
                x-cloak
                x-transition
                class="fixed bottom-4 right-4 z-50 max-w-sm rounded-xl bg-ink px-6 py-4 text-sm text-white shadow-lg"
            >
                <p x-text="message"></p>
            </div>
        @endpersist

        @vite(['resources/js/app.js'])
    </body>
</html>
