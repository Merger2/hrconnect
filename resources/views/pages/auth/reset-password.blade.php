<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <title>@yield('title', config('app.name', 'HRConnect'))</title>
    </head>
    <body class="min-h-screen bg-canvas antialiased">
        <div class="grid min-h-screen grid-cols-1 lg:grid-cols-2">
            <div class="flex flex-col justify-center px-6 py-12 sm:px-12 lg:px-16">
                <div class="mx-auto w-full max-w-md">
                    <div class="mb-8 flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-blue-400 to-blue-600 shadow-lg">
                            <span class="material-symbols-outlined text-2xl text-white">badge</span>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-ink">HRConnect</h1>
                            <p class="text-xs text-on-surface-variant">Sistem HR & Payroll</p>
                        </div>
                    </div>
                    <div class="mb-8">
                        <h2 class="text-3xl font-bold tracking-tight text-ink">{{ __('Reset password') }}</h2>
                        <p class="mt-2 text-sm text-on-surface-variant">{{ __('Please enter your new password below') }}</p>
                    </div>
                    <x-auth-session-status class="mb-4 text-center" :status="session('status')" />
                    <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-5">
                        @csrf
                        <input type="hidden" name="token" value="{{ request()->route('token') }}">
                        <div>
                            <label for="email" class="mb-1.5 block text-sm font-semibold text-on-background">{{ __('Email') }}</label>
                            <input id="email" name="email" type="email" value="{{ request('email') }}" required autocomplete="email"
                                class="w-full rounded-xl border-2 border-fog bg-canvas px-4 py-3 text-sm text-ink transition-all focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" />
                            @error('email')<p class="mt-1.5 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="password" class="mb-1.5 block text-sm font-semibold text-on-background">{{ __('Password') }}</label>
                            <input id="password" name="password" type="password" required autocomplete="new-password" placeholder="{{ __('Password') }}"
                                class="w-full rounded-xl border-2 border-fog bg-canvas px-4 py-3 text-sm text-ink transition-all focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" />
                            @error('password')<p class="mt-1.5 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold text-on-background">{{ __('Confirm password') }}</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" placeholder="{{ __('Confirm password') }}"
                                class="w-full rounded-xl border-2 border-fog bg-canvas px-4 py-3 text-sm text-ink transition-all focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" />
                        </div>
                        <button type="submit" class="w-full rounded-xl bg-gradient-to-br from-blue-400 to-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-lg transition-all hover:-translate-y-0.5 hover:shadow-xl hover:shadow-blue-600/30" data-test="reset-password-button">
                            {{ __('Reset password') }}
                        </button>
                    </form>
                </div>
            </div>
            <div class="relative hidden overflow-hidden bg-gradient-to-br from-blue-600 via-blue-600 to-blue-800 lg:flex lg:flex-col lg:justify-center lg:items-center lg:p-16">
                <div class="absolute -right-32 -top-32 h-96 w-96 rounded-full bg-white/10 blur-3xl"></div>
                <div class="absolute -bottom-24 -left-24 h-80 w-80 rounded-full bg-white/5 blur-3xl"></div>
                <div class="relative z-10 max-w-md text-center text-white">
                    <div class="mx-auto mb-8 flex h-20 w-20 items-center justify-center rounded-2xl bg-white/15 backdrop-blur">
                        <span class="material-symbols-outlined text-4xl">lock_reset</span>
                    </div>
                    <h3 class="text-4xl font-bold leading-tight tracking-tight">{{ __('Buat password baru') }}</h3>
                    <p class="mt-4 text-lg text-white/80">{{ __('Pastikan password kuat dan mudah diingat.') }}</p>
                </div>
            </div>
        </div>
        @vite(['resources/js/app.js'])
    </body>
</html>
