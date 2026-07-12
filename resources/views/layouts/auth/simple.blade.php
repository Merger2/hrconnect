<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-canvas antialiased">
        <div class="flex min-h-svh flex-col items-center justify-center gap-8 p-6 md:p-10">
            <div class="flex w-full max-w-sm flex-col items-center gap-8">
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                    <x-app-logo-icon class="size-10" />
                    <span class="font-display text-xl font-medium tracking-tight text-ink">{{ config('app.name', 'Laravel') }}</span>
                </a>

                <div class="w-full rounded-xl border border-hairline bg-surface-card text-ink shadow-xs">
                    <div class="px-8 py-8">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
        @persist('toast')
            <div id="toast-container"></div>
        @endpersist

        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/service-worker.js').catch(() => {});
                });
            }
        </script>

        @vite(['resources/js/app.js'])
    </body>
</html>
