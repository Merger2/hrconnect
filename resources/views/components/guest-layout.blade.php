<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $appName ?? config('app.name', 'HRConnect') }}</title>
    <link rel="manifest" href="/build/manifest.webmanifest">

    <!-- PWA iOS & Splash -->
    <meta name="theme-color" content="#ffffff">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ $appName ?? config('app.name', 'HRConnect') }}">

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', async () => {
                try {
                    const isNativeApp = !!(window.Capacitor && window.Capacitor.isNativePlatform && window.Capacitor
                        .isNativePlatform());
                    const url = new URL(window.location.href);
                    const shouldReset = isNativeApp || url.searchParams.get('reset-sw') === '1';

                    if (shouldReset) {
                        const registrations = await navigator.serviceWorker.getRegistrations();
                        await Promise.all(registrations.map((registration) => registration.unregister()));

                        if ('caches' in window) {
                            const cacheNames = await caches.keys();
                            await Promise.all(cacheNames.map((cacheName) => caches.delete(cacheName)));
                        }

                        if (isNativeApp) {
                            return;
                        }

                        url.searchParams.delete('reset-sw');
                        window.location.replace(url.toString());
                        return;
                    }

                    const registration = await navigator.serviceWorker.register('/build/sw.js', {
                        updateViaCache: 'none',
                    });

                    await registration.update();

                    if (registration.waiting) {
                        registration.waiting.postMessage({
                            type: 'SKIP_WAITING'
                        });
                    }
                } catch (error) {
                    console.warn('Service worker registration failed', error);
                }
            });
        }
    </script>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Styles -->
    @livewireStyles
</head>
<body class="font-sans antialiased text-gray-900 bg-surface">
    <main>
        {{ $slot }}
    </main>
    @livewireScripts
    <x-pwa-install-prompt />
</body>
</html>
