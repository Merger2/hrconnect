<?php

use App\Concerns\PasswordValidationRules;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Pengaturan Keamanan')] class extends Component {
    use PasswordValidationRules;

    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public bool $canManageTwoFactor;

    public bool $twoFactorEnabled;

    public bool $requiresConfirmation;

    public function mount(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $this->canManageTwoFactor = Features::canManageTwoFactorAuthentication();

        if ($this->canManageTwoFactor) {
            if (Fortify::confirmsTwoFactorAuthentication() && is_null(auth()->user()->two_factor_confirmed_at)) {
                $disableTwoFactorAuthentication(auth()->user());
            }

            $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
            $this->requiresConfirmation = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
        }
    }

    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('toast', variant: 'success', text: __('Kata sandi diperbarui.'));
    }

    #[On('two-factor-enabled')]
    public function onTwoFactorEnabled(): void
    {
        $this->twoFactorEnabled = true;
    }

    public function disable(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $disableTwoFactorAuthentication(auth()->user());

        $this->twoFactorEnabled = false;
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <h2 class="sr-only">{{ __('Security settings') }}</h2>

    <x-pages::settings.layout :heading="__('Perbarui kata sandi')" :subheading="__('Gunakan kata sandi yang kuat dan unik untuk keamanan akun Anda')">
        <form method="POST" wire:submit="updatePassword" class="mt-6 space-y-6">
            <div>
                <x-forms.label for="current_password" value="{{ __('Kata sandi saat ini') }}" />
                <input
                    wire:model="current_password"
                    type="password"
                    required
                    autocomplete="current-password"
                    class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink placeholder:text-on-surface-variant/60 focus:border-ink focus:ring-1 focus:ring-ink"
                />
                @error('current_password')
                    <p class="mt-1 text-xs text-error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <x-forms.label for="password" value="{{ __('Kata sandi baru') }}" />
                <input
                    wire:model="password"
                    type="password"
                    required
                    autocomplete="new-password"
                    class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink placeholder:text-on-surface-variant/60 focus:border-ink focus:ring-1 focus:ring-ink"
                />
                @error('password')
                    <p class="mt-1 text-xs text-error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <x-forms.label for="password_confirmation" value="{{ __('Konfirmasi kata sandi') }}" />
                <input
                    wire:model="password_confirmation"
                    type="password"
                    required
                    autocomplete="new-password"
                    class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink placeholder:text-on-surface-variant/60 focus:border-ink focus:ring-1 focus:ring-ink"
                />
                @error('password_confirmation')
                    <p class="mt-1 text-xs text-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-4">
                <button type="submit" class="rounded-xl bg-ink px-6 py-2.5 text-sm font-semibold text-white" data-test="update-password-button">
                    {{ __('Simpan') }}
                </button>
            </div>
        </form>

        @if ($canManageTwoFactor)
            <section class="mt-12">
                <h2 class="text-lg font-semibold text-ink">{{ __('Two-factor authentication') }}</h2>
                <p class="text-sm text-on-surface-variant">{{ __('Manage your two-factor authentication settings') }}</p>

                <div class="mx-auto flex w-full flex-col space-y-6 text-sm" wire:cloak>
                    @if ($twoFactorEnabled)
                        <div class="space-y-4">
                            <p class="text-sm text-on-surface-variant">
                                {{ __('You will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.') }}
                            </p>

                            <div class="flex justify-start">
                                <button
                                    class="rounded-xl bg-error px-6 py-2.5 text-sm font-semibold text-white"
                                    wire:click="disable"
                                >
                                    {{ __('Disable 2FA') }}
                                </button>
                            </div>

                            <livewire:pages::settings.two-factor.recovery-codes :$requiresConfirmation />
                        </div>
                    @else
                        <div class="space-y-4">
                            <p class="text-sm text-on-surface-variant">
                                {{ __('When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.') }}
                            </p>

                            <button
                                class="rounded-xl bg-ink px-6 py-2.5 text-sm font-semibold text-white"
                                x-data
                                @click="$dispatch('open-modal', 'two-factor-setup-modal'); $wire.dispatch('start-two-factor-setup')"
                            >
                                {{ __('Enable 2FA') }}
                            </button>

                            <livewire:pages::settings.two-factor-setup-modal :requires-confirmation="$requiresConfirmation" />
                        </div>
                    @endif
                </div>
            </section>
        @endif
    </x-pages::settings.layout>
</section>
