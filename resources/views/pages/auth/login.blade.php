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
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary text-on-primary">
                            <x-app-logo-icon class="size-5 fill-current text-on-primary" />
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-ink">HRConnect</h1>
                            <p class="text-xs text-on-surface-variant">Sistem HR & Payroll</p>
                        </div>
                    </div>

                    <!-- Header -->
                    <div class="mb-8">
                        <h2 class="text-3xl font-bold tracking-tight text-ink">Masuk</h2>
                        <p class="mt-2 text-sm text-on-surface-variant">Masukkan email dan kata sandi Anda untuk melanjutkan</p>
                    </div>

                    <!-- Session Status -->
                    <x-auth-session-status class="mb-4" :status="session('status')" />

                    <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
                        @csrf

                        <!-- Email -->
                        <x-forms.input label="Email" name="email" type="email" id="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="nama@perusahaan.com" />
                        @error('email')
                            <p class="mt-1.5 text-xs text-error">{{ $message }}</p>
                        @enderror

                        <!-- Password -->
                        <div x-data="{ show: false }">
                            <div class="mb-1.5 flex items-center justify-between">
                                <x-forms.label for="password">Kata Sandi</x-forms.label>
                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}" class="text-sm font-medium text-primary hover:text-primary-deep" wire:navigate>
                                        {{ __('Lupa kata sandi?') }}
                                    </a>
                                @endif
                            </div>
                            <div class="relative">
                                <input
                                    id="password"
                                    name="password"
                                    :type="show ? 'text' : 'password'"
                                    required
                                    autocomplete="current-password"
                                    placeholder="Masukkan kata sandi"
                                    class="w-full rounded-md border bg-canvas px-3 py-2 text-sm text-ink placeholder:text-muted-soft focus:border-ink focus:ring-0 border-outline-variant"
                                />
                                <button
                                    type="button"
                                    @click="show = !show"
                                    :aria-pressed="show"
                                    :aria-label="show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                                    class="absolute end-2 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-lg text-on-surface-variant transition-colors hover:bg-surface-dim/50 hover:text-ink focus:outline-none"
                                    tabindex="-1"
                                >
                                    <span class="material-symbols-outlined text-xl" x-text="show ? 'visibility_off' : 'visibility'">visibility</span>
                                </button>
                            </div>
                            @error('password')
                                <p class="mt-1.5 text-xs text-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Remember Me -->
                        <label class="flex cursor-pointer items-center gap-2.5 text-sm text-on-background">
                            <input type="checkbox" name="remember" class="h-4 w-4 rounded border-outline-variant text-primary focus:ring-primary/30 accent-primary" />
                            {{ __('Ingat saya') }}
                        </label>

                        <x-button type="submit" size="lg" class="w-full" data-test="login-button">
                            {{ __('Masuk') }}
                        </x-button>
                    </form>
                </div>
            </div>

            <!-- Right: Visual -->
            <div class="relative hidden overflow-hidden bg-ink lg:flex lg:flex-col lg:justify-center lg:items-center lg:p-16">
                <!-- Decorative glows -->
                <div class="absolute -right-32 -top-32 h-96 w-96 rounded-full bg-primary/10 blur-3xl"></div>
                <div class="absolute -bottom-24 -left-24 h-80 w-80 rounded-full bg-primary/5 blur-3xl"></div>

                <div class="relative z-10 max-w-md text-center text-on-primary">
                    <div class="mx-auto mb-8 flex h-20 w-20 items-center justify-center rounded-2xl bg-primary/10 backdrop-blur">
                        <span class="material-symbols-outlined text-4xl text-primary">dashboard</span>
                    </div>
                    <h3 class="text-4xl font-bold leading-tight tracking-tight">Kelola HR & Payroll dalam satu platform</h3>
                    <p class="mt-4 text-lg text-on-primary/80">Absensi, cuti, lembur, dan penggajian — semua terintegrasi untuk tim Anda.</p>

                    <div class="mt-10 flex flex-col gap-3 text-left">
                        <div class="flex items-center gap-3 rounded-xl bg-primary/10 px-4 py-3 backdrop-blur">
                            <span class="material-symbols-outlined text-primary">fact_check</span>
                            <span class="text-sm text-on-primary/90">Absensi real-time dengan verifikasi wajah</span>
                        </div>
                        <div class="flex items-center gap-3 rounded-xl bg-primary/10 px-4 py-3 backdrop-blur">
                            <span class="material-symbols-outlined text-primary">payments</span>
                            <span class="text-sm text-on-primary/90">Payroll otomatis & kompliant pajak Indonesia</span>
                        </div>
                        <div class="flex items-center gap-3 rounded-xl bg-primary/10 px-4 py-3 backdrop-blur">
                            <span class="material-symbols-outlined text-primary">smart_toy</span>
                            <span class="text-sm text-on-primary/90">Asisten AI untuk kebijakan HR</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @persist('toast')
            <div id="toast-container"></div>
        @endpersist

        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/sw.js').catch(() => {});
                });
            }
        </script>

        @vite(['resources/js/app.js'])
    </body>
</html>
