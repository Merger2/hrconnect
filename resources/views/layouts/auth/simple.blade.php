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
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        <div id="pwa-install-banner" class="hidden fixed bottom-0 inset-x-0 p-4 bg-canvas border-t border-hairline shadow-lg z-50">
            <div class="flex items-center justify-between max-w-sm mx-auto">
                <div class="flex items-center gap-3">
                    <img src="/icon-192.svg" alt="HRConnect" class="size-10 rounded-lg" />
                    <div>
                        <p class="text-sm font-medium text-ink">Install HRConnect</p>
                        <p class="text-xs text-muted">Akses cepat dari layar utama</p>
                    </div>
                </div>
                <button onclick="installPwa()" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-on-primary">Install</button>
            </div>
        </div>

        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/service-worker.js').catch(() => {});
                });
            }
        </script>

        @fluxScripts
    </body>
</html>
