<x-layouts::auth :title="__('Masuk')">
    <div class="flex flex-col gap-6">

        <!-- Brand accent line -->
        <div class="flex gap-1.5">
            <div class="h-1 w-8 rounded-full bg-brand-lavender"></div>
            <div class="h-1 w-8 rounded-full bg-brand-teal"></div>
            <div class="h-1 w-8 rounded-full bg-brand-peach"></div>
        </div>

        <!-- Header -->
        <div class="flex flex-col gap-1">
            <h1 class="text-2xl font-semibold text-ink">Masuk</h1>
            <p class="text-sm text-on-surface-variant">Masukkan email dan kata sandi Anda</p>
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
            @csrf

            <!-- Email Address -->
            <div>
                <label class="mb-1 block text-sm font-medium text-on-background">Email</label>
                <input
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="nama@perusahaan.com"
                    class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink placeholder:text-on-surface-variant/60 focus:border-ink focus:ring-1 focus:ring-ink"
                />
                @error('email')
                    <p class="mt-1 text-xs text-error">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div class="relative">
                <label class="mb-1 block text-sm font-medium text-on-background">Kata Sandi</label>
                <input
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    placeholder="Masukkan kata sandi"
                    class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink placeholder:text-on-surface-variant/60 focus:border-ink focus:ring-1 focus:ring-ink"
                />
                @error('password')
                    <p class="mt-1 text-xs text-error">{{ $message }}</p>
                @enderror

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="absolute end-0 top-0 text-sm text-brand-lavender hover:text-brand-teal" wire:navigate>
                        {{ __('Lupa kata sandi?') }}
                    </a>
                @endif
            </div>

            <!-- Remember Me -->
            <label class="flex items-center gap-2 text-sm text-on-background">
                <input type="checkbox" name="remember" class="rounded border-outline-variant text-ink focus:ring-ink" />
                {{ __('Ingat saya') }}
            </label>

            <button type="submit" class="w-full rounded-xl bg-ink px-6 py-2.5 text-sm font-semibold text-white" data-test="login-button">
                {{ __('Masuk') }}
            </button>
        </form>
    </div>
</x-layouts::auth>
