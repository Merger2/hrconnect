<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Broadcast Configuration for Echo -->
        <script>
            window.PasPapanBroadcast = {
                enabled: {{ config('broadcasting.default') !== 'null' ? 'true' : 'false' }},
                connection: '{{ config('broadcasting.default') }}',
                csrfToken: '{{ csrf_token() }}',
                authEndpoint: '/broadcasting/auth',
                @if(config('broadcasting.default') === 'reverb')
                reverb: {
                    key: '{{ config('broadcasting.connections.reverb.key') }}',
                    host: '{{ config('broadcasting.connections.reverb.options.host') }}',
                    port: {{ config('broadcasting.connections.reverb.options.port', 443) }},
                    scheme: '{{ config('broadcasting.connections.reverb.options.scheme', 'https') }}',
                },
                @elseif(config('broadcasting.default') === 'pusher')
                pusher: {
                    key: '{{ config('broadcasting.connections.pusher.key') }}',
                    cluster: '{{ config('broadcasting.connections.pusher.options.cluster') }}',
                    host: '{{ config('broadcasting.connections.pusher.options.host') }}',
                    port: {{ config('broadcasting.connections.pusher.options.port', 443) }},
                    scheme: '{{ config('broadcasting.connections.pusher.options.scheme', 'https') }}',
                },
                @endif
            };
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Material Symbols (icon font) — non-blocking: preload + async stylesheet.
             Dulu @import di app.css (render-blocking). display=block sesuai rekomendasi
             Google untuk icon font (hindari glyph salah saat FOIT). -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="preload" as="style"
            href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,400..700,0,0&display=block">
        <link rel="stylesheet" media="print" onload="this.media='all'"
            href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,400..700,0,0&display=block">
        <noscript>
            <link rel="stylesheet"
                href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,400..700,0,0&display=block">
        </noscript>

        <!-- PWA -->
        <link rel="manifest" href="/build/manifest.webmanifest">
        <meta name="theme-color" content="#0a0a0a">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="HRConnect">
        <link rel="apple-touch-icon" href="/apple-icon-180.png">

        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', async () => {
                    try {
                        const registration = await navigator.serviceWorker.register('/build/sw.js', { updateViaCache: 'none' });
                        await registration.update();
                        if (registration.waiting) {
                            registration.waiting.postMessage({ type: 'SKIP_WAITING' });
                        }
                    } catch (error) {
                        console.warn('SW registration failed', error);
                    }
                });
            }
        </script>

        <!-- Scripts -->
        {{-- Critical CSS TIDAK dipakai utk layout app (revert 2026-08-08):
             A/B Lighthouse /home — critical (32KB gzip + full CSS async) vs
             fallback (full CSS render-blocking): FCP -2.6s TAPI TBT +760ms
             (full CSS async apply di tengah JS = reflow storm) → skor net -10.
             Di prod (nginx gzip) fallback makin unggul. Guest (login) tetap
             critical (58KB inline, apply ringan, +3 skor terbukti).
             Detail: scripts/extract-critical-css.mjs + resources/css/critical-guest.css --}}
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
    </head>
    @php $isAdminRoute = request()->routeIs('admin.*'); @endphp
    <body class="font-sans antialiased {{ $isAdminRoute ? 'admin-ui' : 'user-ui' }}">

        <div class="min-h-screen app-canvas {{ ! $isAdminRoute ? 'pb-[calc(6.5rem+env(safe-area-inset-bottom))] md:pb-0' : '' }}">
            @livewire('navigation-menu')

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main class="{{ $isAdminRoute ? 'pt-[calc(4rem+env(safe-area-inset-top))]' : 'pt-2 sm:pt-1 md:pt-[calc(4rem+env(safe-area-inset-top))]' }}">
                @yield('content', $slot ?? '')
            </main>

        </div>

@stack('modals')

@livewireScripts

@unless ($isAdminRoute)
    <x-user.app-bottom-navigation />
@endunless

@stack('scripts')

<x-pwa-install-prompt />

<script src="{{ asset('js/pulltorefresh.js') }}"></script>
    </body>
</html>
