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
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
    </head>
    <body class="font-sans antialiased">
        <x-banner />

        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            @livewire('navigation-menu')

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white dark:bg-gray-800 shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main class="pt-16">
                @yield('content', $slot ?? '')
            </main>
        </div>

        @stack('modals')

        @livewireScripts

        <x-pwa-install-prompt />
    </body>
</html>
