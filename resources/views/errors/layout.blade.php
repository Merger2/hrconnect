<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#f8fafc">
    <title>@yield('title') | {{ config('app.name') }}</title>

    <script>
        if (localStorage.getItem('isDark') === 'true' || (!('isDark' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', async () => {
                try {
                    const registrations = await navigator.serviceWorker.getRegistrations();
                    await Promise.all(registrations.map((registration) => registration.unregister()));

                    if ('caches' in window) {
                        const cacheNames = await caches.keys();
                        await Promise.all(cacheNames.map((cacheName) => caches.delete(cacheName)));
                    }
                } catch (error) {
                    console.warn('Error page cache reset failed', error);
                }
            });
        }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-[#f6faf4] font-sans antialiased text-slate-950 selection:bg-primary-600 selection:text-white dark:bg-[#020712] dark:text-slate-100">
    <a href="#error-main" class="skip-link">{{ __('Skip to main content') }}</a>

    <div class="relative min-h-screen overflow-hidden px-4 pb-[max(1.25rem,env(safe-area-inset-bottom))] pt-[max(1.25rem,env(safe-area-inset-top))] sm:px-6">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-56 bg-gradient-to-b from-primary-100/80 via-primary-50/40 to-transparent dark:from-primary-950/35 dark:via-slate-950/10"></div>

        <main id="error-main" tabindex="-1" class="relative mx-auto flex min-h-[calc(100vh-2.5rem)] w-full max-w-[34rem] items-center justify-center">
            <section aria-labelledby="error-page-title" class="w-full overflow-hidden rounded-[2rem] border border-white/80 bg-white/92 shadow-[0_30px_80px_-52px_rgba(15,23,42,0.72)] backdrop-blur dark:border-slate-800/80 dark:bg-slate-950/92">
                <div class="border-b border-slate-100 px-5 py-5 dark:border-slate-800 sm:px-6">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-[1.35rem] bg-primary-700 text-white shadow-sm dark:bg-primary-400 dark:text-slate-950">
                                <x-branding.application-mark class="h-7 w-7 text-current" />
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-base font-semibold text-slate-950 dark:text-white">
                                    {{ config('app.name') }}
                                </p>
                                <p class="mt-0.5 text-sm font-medium text-slate-500 dark:text-slate-400">
                                    {{ __('System status') }}
                                </p>
                            </div>
                        </div>

                        <a href="{{ auth()->check() ? auth()->user()->preferredHomeUrl() : url('/') }}"
                            class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-[1.35rem] border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800"
                            aria-label="{{ __('Go Home') }}">
                            <x-heroicon-o-home class="h-5 w-5" />
                        </a>
                    </div>
                </div>

                <div class="min-w-0 p-5 sm:p-6">
                    @yield('content')
                </div>
            </section>
        </main>
    </div>
</body>
</html>
