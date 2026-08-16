<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $appName ?? config('app.name', 'PT Daya Cipta Mandiri Solusi') }}</title>
    <link rel="manifest" href="/build/manifest.webmanifest">

    <!-- PWA iOS & Splash -->
    <meta name="theme-color" content="#ffffff">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ $appName ?? config('app.name', 'PT Daya Cipta Mandiri Solusi') }}">

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

    <!-- Material Symbols (icon font) — non-blocking: preload + async stylesheet.
         Dulu @import di app.css (render-blocking). display=block untuk icon font. -->
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

    <!-- Scripts -->
    {{-- Critical CSS inline (di-generate via scripts/extract-critical-css.mjs,
         disimpan di resources/css/critical-guest.css) — menghilangkan
         render-blocking CSS 622KB dari critical path. Full CSS di-defer async
         (media=print onload) + fallback noscript. CSP: style-src unsafe-inline OK. --}}
    @if (file_exists(resource_path('css/critical-guest.css')))
        {{-- {!! !!} (bukan {{ }}) — file_get_contents CSS harus RAW: {{ }} = htmlspecialchars
             meng-escape " → &quot; dll yang merusak aturan CSS (font-family, content, url).
             Sumber di resources/css/critical-guest.css (git-tracked) BUKAN public/build
             (gitignored). Regen: node scripts/extract-critical-css.mjs --urls /login
               --guest --out resources/css/critical-guest.css --}}
        <style>{!! file_get_contents(resource_path('css/critical-guest.css')) !!}</style>
        @php($fullCss = Vite::asset('resources/css/app.css'))
        <link rel="stylesheet" href="{{ $fullCss }}" media="print" onload="this.media='all'">
        <noscript><link rel="stylesheet" href="{{ $fullCss }}"></noscript>
        @vite(['resources/js/app.js'])
    @else
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

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
