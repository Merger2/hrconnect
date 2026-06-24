<x-layouts::auth :title="__('Forgot password')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Forgot password')" :description="__('Enter your email to receive a password reset link')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <div>
                <label class="mb-1 block text-sm font-medium text-on-background">{{ __('Email address') }}</label>
                <input
                    name="email"
                    type="email"
                    required
                    autofocus
                    placeholder="email@example.com"
                    class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink placeholder:text-on-surface-variant/60 focus:border-ink focus:ring-1 focus:ring-ink"
                />
                @error('email')
                    <p class="mt-1 text-xs text-error">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="w-full rounded-xl bg-ink px-6 py-2.5 text-sm font-semibold text-white" data-test="email-password-reset-link-button">
                {{ __('Email password reset link') }}
            </button>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-muted-soft">
            <span>{{ __('Or, return to') }}</span>
            <a href="{{ route('login') }}" class="text-ink underline hover:text-on-surface-variant" wire:navigate>{{ __('log in') }}</a>
        </div>
    </div>
</x-layouts::auth>
