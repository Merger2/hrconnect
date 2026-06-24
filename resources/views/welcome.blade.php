<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'HRConnect') }}</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:500|inter:400,500,600" rel="stylesheet" />
    @vite(['resources/css/app.css'])
    <style>
        .landing-theme {
            --color-canvas: #fffaf0;
            --color-surface-soft: #faf5e8;
            --color-surface-card: #f5f0e0;
            --color-surface-strong: #ebe6d6;
            --color-muted: #6a6a6a;
            --color-muted-soft: #9a9a9a;
            --color-hairline: #e5e5e5;
            --color-hairline-soft: #f0f0f0;
        }
    </style>
</head>
<body class="landing-theme bg-canvas text-ink font-sans antialiased">
    {{-- Top Nav --}}
    <header class="sticky top-0 z-50 flex h-16 items-center justify-between border-b border-hairline bg-canvas px-6 lg:px-10">
        <a href="/" class="flex items-center gap-3">
            <x-app-logo-icon class="size-8" />
            <span class="font-display text-xl font-medium text-ink tracking-tight">HRConnect</span>
        </a>
        <nav class="flex items-center gap-4">
            @if (Route::has('login'))
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-xl border border-hairline bg-canvas px-6 py-2.5 text-sm font-semibold text-ink" wire:navigate>
                        {{ __('Dashboard') }}
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-xl border border-hairline bg-canvas px-6 py-2.5 text-sm font-semibold text-ink" wire:navigate>
                        {{ __('Log in') }}
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-xl bg-ink px-6 py-2.5 text-sm font-semibold text-white" wire:navigate>
                            {{ __('Register') }}
                        </a>
                    @endif
                @endauth
            @endif
        </nav>
    </header>

    {{-- Hero --}}
    <section class="mx-auto max-w-7xl px-6 py-24 lg:py-32">
        <div class="mx-auto max-w-3xl text-center">
            <h1 class="font-display text-5xl font-medium tracking-tighter text-ink sm:text-6xl lg:text-7xl">
                {{ __('HR management,') }}<br>
                <span class="text-brand-teal">{{ __('simplified.') }}</span>
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg text-body">
                {{ __('HRConnect helps you manage attendance, leave, overtime, and reimbursements — all in one place.') }}
            </p>
            <div class="mt-10 flex items-center justify-center gap-4">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-xl bg-ink px-8 py-3 text-base font-semibold text-white" wire:navigate>
                            {{ __('Go to Dashboard') }}
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-xl bg-ink px-6 py-3 text-sm font-semibold text-white" wire:navigate>
                            {{ __('Get Started Free') }}
                        </a>
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-xl border border-hairline bg-canvas px-6 py-3 text-sm font-semibold text-ink" wire:navigate>
                            {{ __('Sign In') }}
                        </a>
                    @endauth
                @endif
            </div>
        </div>
    </section>

    {{-- Feature Cards --}}
    <section class="mx-auto max-w-7xl px-6 pb-24">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            {{-- Attendance --}}
            <div class="rounded-xl bg-brand-pink p-8 text-on-primary">
                <div class="mb-4 flex size-12 items-center justify-center rounded-lg bg-white/20">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold">{{ __('Smart Attendance') }}</h3>
                <p class="mt-2 text-sm text-white/80">{{ __('Clock in/out with geofence validation and real-time tracking.') }}</p>
            </div>

            {{-- Leave --}}
            <div class="rounded-xl bg-brand-teal p-8 text-on-dark">
                <div class="mb-4 flex size-12 items-center justify-center rounded-lg bg-white/20">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold">{{ __('Leave Management') }}</h3>
                <p class="mt-2 text-sm text-white/80">{{ __('Apply for leave, track quotas, and get approval in one click.') }}</p>
            </div>

            {{-- Overtime --}}
            <div class="rounded-xl bg-brand-lavender p-8 text-ink">
                <div class="mb-4 flex size-12 items-center justify-center rounded-lg bg-black/10">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold">{{ __('Overtime Tracking') }}</h3>
                <p class="mt-2 text-sm text-black/70">{{ __('Submit overtime requests and automatic payroll calculations.') }}</p>
            </div>

            {{-- Reimbursement --}}
            <div class="rounded-xl bg-brand-peach p-8 text-ink">
                <div class="mb-4 flex size-12 items-center justify-center rounded-lg bg-black/10">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 0 4.5 6h7.5a.75.75 0 0 0 .75-.75v-.75m0 0v-.75a.75.75 0 0 0-.75-.75H4.5a.75.75 0 0 0-.75.75v.75m0 0h15m0 0v.75A.75.75 0 0 0 19.5 9h-1.5m0 0v.75a.75.75 0 0 1-.75.75h-3a.75.75 0 0 1-.75-.75V9m0 0H3.75" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold">{{ __('Reimbursements') }}</h3>
                <p class="mt-2 text-sm text-black/70">{{ __('Submit expense claims with receipt uploads and approval workflow.') }}</p>
            </div>

            {{-- Payroll --}}
            <div class="rounded-xl bg-brand-ochre p-8 text-ink">
                <div class="mb-4 flex size-12 items-center justify-center rounded-lg bg-black/10">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold">{{ __('Payroll Integration') }}</h3>
                <p class="mt-2 text-sm text-black/70">{{ __('Seamless payroll processing with automated calculations.') }}</p>
            </div>

            {{-- Knowledge Base --}}
            <div class="rounded-xl bg-brand-mint p-8 text-ink">
                <div class="mb-4 flex size-12 items-center justify-center rounded-lg bg-black/10">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold">{{ __('AI Knowledge Base') }}</h3>
                <p class="mt-2 text-sm text-black/70">{{ __('Smart search and AI-powered answers for HR policies.') }}</p>
            </div>
        </div>
    </section>

    {{-- Stats Bar --}}
    <section class="bg-surface-dark py-16 text-on-dark">
        <div class="mx-auto max-w-7xl px-6">
            <div class="grid grid-cols-2 gap-8 md:grid-cols-4">
                <div class="text-center">
                    <p class="text-4xl font-display font-medium tracking-tight">99.9%</p>
                    <p class="mt-1 text-sm text-on-dark-soft">{{ __('Uptime') }}</p>
                </div>
                <div class="text-center">
                    <p class="text-4xl font-display font-medium tracking-tight">10K+</p>
                    <p class="mt-1 text-sm text-on-dark-soft">{{ __('Employees') }}</p>
                </div>
                <div class="text-center">
                    <p class="text-4xl font-display font-medium tracking-tight">50+</p>
                    <p class="mt-1 text-sm text-on-dark-soft">{{ __('Companies') }}</p>
                </div>
                <div class="text-center">
                    <p class="text-4xl font-display font-medium tracking-tight">24/7</p>
                    <p class="mt-1 text-sm text-on-dark-soft">{{ __('Support') }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA Band --}}
    <section class="mx-auto max-w-7xl px-6 py-24">
        <div class="rounded-xl bg-surface-soft p-16 text-center">
            <h2 class="font-display text-4xl font-medium tracking-tight text-ink">
                {{ __('Ready to streamline your HR?') }}
            </h2>
            <p class="mx-auto mt-4 max-w-lg text-body">
                {{ __('Join thousands of companies using HRConnect to manage their workforce.') }}
            </p>
            <div class="mt-8">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-xl bg-ink px-8 py-3 text-sm font-semibold text-white" wire:navigate>
                            {{ __('Go to Dashboard') }}
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-xl bg-ink px-8 py-3 text-sm font-semibold text-white" wire:navigate>
                            {{ __('Start Free Trial') }}
                        </a>
                    @endauth
                @endif
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="bg-surface-soft px-6 py-20">
        <div class="mx-auto max-w-7xl">
            <div class="grid grid-cols-2 gap-8 md:grid-cols-4">
                <div>
                    <p class="mb-4 text-sm font-semibold text-ink">{{ __('Product') }}</p>
                    <ul class="space-y-2 text-sm text-body">
                        <li>{{ __('Attendance') }}</li>
                        <li>{{ __('Leave') }}</li>
                        <li>{{ __('Overtime') }}</li>
                        <li>{{ __('Payroll') }}</li>
                    </ul>
                </div>
                <div>
                    <p class="mb-4 text-sm font-semibold text-ink">{{ __('Company') }}</p>
                    <ul class="space-y-2 text-sm text-body">
                        <li>{{ __('About') }}</li>
                        <li>{{ __('Blog') }}</li>
                        <li>{{ __('Careers') }}</li>
                        <li>{{ __('Contact') }}</li>
                    </ul>
                </div>
                <div>
                    <p class="mb-4 text-sm font-semibold text-ink">{{ __('Resources') }}</p>
                    <ul class="space-y-2 text-sm text-body">
                        <li>{{ __('Documentation') }}</li>
                        <li>{{ __('API Reference') }}</li>
                        <li>{{ __('Status') }}</li>
                        <li>{{ __('Support') }}</li>
                    </ul>
                </div>
                <div>
                    <p class="mb-4 text-sm font-semibold text-ink">{{ __('Legal') }}</p>
                    <ul class="space-y-2 text-sm text-body">
                        <li>{{ __('Privacy') }}</li>
                        <li>{{ __('Terms') }}</li>
                        <li>{{ __('Security') }}</li>
                        <li>{{ __('GDPR') }}</li>
                    </ul>
                </div>
            </div>
            <div class="mt-12 border-t border-hairline pt-8 text-center text-sm text-muted">
                &copy; {{ date('Y') }} {{ config('app.name', 'HRConnect') }}. All rights reserved.
            </div>
        </div>
    </footer>
</body>
</html>
