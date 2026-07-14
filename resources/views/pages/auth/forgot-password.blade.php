<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <title>@yield('title', config('app.name', 'HRConnect'))</title>
    </head>
    <body class="min-h-screen bg-canvas antialiased">
        <div class="grid min-h-screen grid-cols-1 lg:grid-cols-2">
            <!-- Left: Form -->
            <div class="flex flex-col justify-center px-6 py-12 sm:px-12 lg:px-16">
                <div class="mx-auto w-full max-w-md">
                    <!-- Brand -->
                    <div class="mb-8 flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-blue-400 to-blue-600 shadow-lg">
                            <span class="material-symbols-outlined text-2xl text-white">badge</span>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-ink">HRConnect</h1>
                            <p class="text-xs text-on-surface-variant">Sistem HR & Payroll</p>
                        </div>
                    </div>

                    <!-- Header -->
                    <div class="mb-8">
                        <h2 class="text-3xl font-bold tracking-tight text-ink">{{ __('Forgot password') }}</h2>
                        <p class="mt-2 text-sm text-on-surface-variant">{{ __('Enter your email to receive a password reset link') }}</p>
                    </div>

                    <!-- Session Status -->
                    <x-auth-session-status class="mb-4 text-center" :status="session('status')" />

                    <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-5">
                        @csrf

                        <!-- Email Address -->
                        <div>
                            <label for="email" class="mb-1.5 block text-sm font-semibold text-on-background">{{ __('Email address') }}</label>
                            <input
                                id="email" name="email" type="email" required autofocus
                                placeholder="email@example.com"
                                class="w-full rounded-xl border-2 border-fog bg-canvas px-4 py-3 text-sm text-ink placeholder:text-on-surface-variant/60 transition-all focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
                            />
                            @error('email')
                                <p class="mt-1.5 text-xs text-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="w-full rounded-xl bg-gradient-to-br from-blue-400 to-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-lg transition-all hover:-translate-y-0.5 hover:shadow-xl hover:shadow-blue-600/30 focus:outline-none focus:ring-4 focus:ring-blue-600/20" data-test="email-password-reset-link-button">
                            {{ __('Email password reset link') }}
                        </button>
                    </form>

                    <div class="mt-6 text-center text-sm text-on-surface-variant">
                        <span>{{ __('Or, return to') }}</span>
                        <a href="{{ route('login') }}" class="font-medium text-blue-600 hover:text-blue-700" wire:navigate>{{ __('log in') }}</a>
                    </div>
                </div>
            </div>

            <!-- Right: Visual -->
            <div class="relative hidden overflow-hidden bg-gradient-to-br from-blue-600 via-blue-600 to-blue-800 lg:flex lg:flex-col lg:justify-center lg:items-center lg:p-16">
                <div class="absolute -right-32 -top-32 h-96 w-96 rounded-full bg-white/10 blur-3xl"></div>
                <div class="absolute -bottom-24 -left-24 h-80 w-80 rounded-full bg-white/5 blur-3xl"></div>
                <div class="relative z-10 max-w-md text-center text-white">
                    <div class="mx-auto mb-8 flex h-20 w-20 items-center justify-center rounded-2xl bg-white/15 backdrop-blur">
                        <span class="material-symbols-outlined text-4xl">lock_reset</span>
                    </div>
                    <h3 class="text-4xl font-bold leading-tight tracking-tight">{{ __('Tenang, kami bantu') }}</h3>
                    <p class="mt-4 text-lg text-white/80">{{ __('Masukkan email terdaftar dan kami kirim tautan reset password.') }}</p>
                </div>
            </div>
        </div>

        @vite(['resources/js/app.js'])
    </body>
</html>
