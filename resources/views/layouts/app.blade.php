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

        <!-- Global Alpine dark mode store -->
        <script>
            document.addEventListener('alpine:init', () => {
                const isDark = localStorage.getItem('dark') === 'true';
                if (isDark) {
                    document.documentElement.classList.add('dark');
                }

                Alpine.store('darkMode', {
                    on: isDark,
                    toggle() {
                        this.on = !this.on;
                        localStorage.setItem('dark', this.on ? 'true' : 'false');
                        document.documentElement.classList.toggle('dark', this.on);
                    },
                });
            });
        </script>
    </head>
    @php $isAdminRoute = request()->routeIs('admin.*'); @endphp
    <body class="font-sans antialiased {{ $isAdminRoute ? 'admin-ui' : 'user-ui' }}">

        <div class="min-h-screen bg-gray-100 dark:bg-gray-900 {{ ! $isAdminRoute ? 'pb-[calc(6.5rem+env(safe-area-inset-bottom))]' : '' }}">
            @if ($isAdminRoute)
                @livewire('navigation-menu')
            @endif

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white dark:bg-gray-800 shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main class="{{ $isAdminRoute ? 'pt-16' : 'pt-4 sm:pt-2' }}">
                @yield('content', $slot ?? '')
            </main>
        </div>

<x-shared.feature-lock-modal />

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
