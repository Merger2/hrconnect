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
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary text-on-primary">
                            <x-app-logo-icon class="size-5 fill-current text-on-primary" />
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
                        <x-forms.input label="{{ __('Email') }}" name="email" id="email" type="email" value="{{ request('email') }}" required autocomplete="email" />
                        @error('email')<p class="mt-1.5 text-xs text-error">{{ $message }}</p>@enderror
                        <x-forms.input label="{{ __('Password') }}" name="password" id="password" type="password" required autocomplete="new-password" placeholder="{{ __('Password') }}" />
                        @error('password')<p class="mt-1.5 text-xs text-error">{{ $message }}</p>@enderror
                        <x-forms.input label="{{ __('Confirm password') }}" name="password_confirmation" id="password_confirmation" type="password" required autocomplete="new-password" placeholder="{{ __('Confirm password') }}" />
                        <x-button type="submit" size="lg" class="w-full" data-test="reset-password-button">
                            {{ __('Reset password') }}
                        </x-button>
                    </form>
                </div>
            </div>
            <div class="relative hidden overflow-hidden bg-ink lg:flex lg:flex-col lg:justify-center lg:items-center lg:p-16">
                <div class="absolute -right-32 -top-32 h-96 w-96 rounded-full bg-primary/10 blur-3xl"></div>
                <div class="absolute -bottom-24 -left-24 h-80 w-80 rounded-full bg-primary/5 blur-3xl"></div>
                <div class="relative z-10 max-w-md text-center text-on-primary">
                    <div class="mx-auto mb-8 flex h-20 w-20 items-center justify-center rounded-2xl bg-primary/10 backdrop-blur">
                        <span class="material-symbols-outlined text-4xl text-primary">lock_reset</span>
                    </div>
                    <h3 class="text-4xl font-bold leading-tight tracking-tight">{{ __('Buat password baru') }}</h3>
                    <p class="mt-4 text-lg text-on-primary/80">{{ __('Pastikan password kuat dan mudah diingat.') }}</p>
                </div>
            </div>
        </div>
        @vite(['resources/js/app.js'])
    </body>
</html>
