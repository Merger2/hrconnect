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
            <flux:heading size="xl" class="font-display text-ink">Masuk</flux:heading>
            <flux:subheading class="text-body">Masukkan email dan kata sandi Anda</flux:subheading>
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
            @csrf

            <!-- Email Address -->
            <div>
                <flux:input
                    name="email"
                    label="Email"
                    :value="old('email')"
                    type="email"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="nama@perusahaan.com"
                />
            </div>

            <!-- Password -->
            <div class="relative">
                <flux:input
                    name="password"
                    label="Kata Sandi"
                    type="password"
                    required
                    autocomplete="current-password"
                    placeholder="Masukkan kata sandi"
                    viewable
                />

                @if (Route::has('password.request'))
                    <flux:link class="absolute top-0 text-sm end-0 text-brand-lavender hover:text-brand-teal" :href="route('password.request')" wire:navigate>
                        {{ __('Lupa kata sandi?') }}
                    </flux:link>
                @endif
            </div>

            <!-- Remember Me -->
            <flux:checkbox name="remember" :label="__('Ingat saya')" />

            <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                {{ __('Masuk') }}
            </flux:button>
        </form>
    </div>
</x-layouts::auth>
