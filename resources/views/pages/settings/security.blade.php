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
            'password_changed_at' => now(),
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
        $this->dispatch('toast', variant: 'success', text: __('2FA dinonaktifkan.'));
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('Keamanan')" :subheading="__('Perbarui kata sandi dan verifikasi dua langkah')">

        {{-- ── Kata Sandi ── --}}
        <div class="ess-card">
            <div class="border-b border-outline-variant/50 px-6 py-4">
                <h3 class="ess-eyebrow">{{ __('Kata Sandi') }}</h3>
            </div>

            <form wire:submit="updatePassword" class="space-y-5 p-6">
                <div>
                    <x-forms.label for="current_password" value="{{ __('Kata sandi saat ini') }}" />
                    <x-forms.input
                        wire:model="current_password"
                        type="password"
                        required
                        autocomplete="current-password"
                        class="mt-1.5 w-full"
                        data-test="current-password-input"
                    />
                    @error('current_password')
                        <p class="mt-1 text-xs text-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-forms.label for="password" value="{{ __('Kata sandi baru') }}" />
                    <x-forms.input
                        wire:model="password"
                        type="password"
                        required
                        autocomplete="new-password"
                        class="mt-1.5 w-full"
                        data-test="password-input"
                    />
                    @error('password')
                        <p class="mt-1 text-xs text-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-forms.label for="password_confirmation" value="{{ __('Konfirmasi kata sandi') }}" />
                    <x-forms.input
                        wire:model="password_confirmation"
                        type="password"
                        required
                        autocomplete="new-password"
                        class="mt-1.5 w-full"
                    />
                    @error('password_confirmation')
                        <p class="mt-1 text-xs text-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <x-button
                        type="submit"
                        variant="primary"
                        icon="lock"
                        data-test="update-password-button"
                    >
                        {{ __('Simpan Kata Sandi') }}
                    </x-button>
                </div>
            </form>
        </div>

        {{-- ── Verifikasi Dua Langkah ── --}}
        @if ($canManageTwoFactor)
            <div class="ess-card">
                <div class="border-b border-outline-variant/50 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h3 class="ess-eyebrow">{{ __('Verifikasi Dua Langkah') }}</h3>
                        @if ($twoFactorEnabled)
                            <span class="badge-success text-[0.65rem]">Aktif</span>
                        @else
                            <span class="rounded-full bg-surface-container-low px-2.5 py-0.5 text-[0.65rem] font-semibold text-on-surface-variant">Nonaktif</span>
                        @endif
                    </div>
                </div>

                <div class="p-6" wire:cloak>
                    @if ($twoFactorEnabled)
                        {{-- 2FA AKTIF --}}
                        <div class="flex flex-col gap-4">
                            <div class="flex items-start gap-3 rounded-xl border border-success/20 bg-success/5 p-4">
                                <span class="material-symbols-outlined mt-0.5 text-xl text-success">verified_user</span>
                                <div>
                                    <p class="text-sm font-medium text-ink">{{ __('Verifikasi dua langkah aktif') }}</p>
                                    <p class="mt-1 text-xs text-on-surface-variant">
                                        {{ __('Anda akan diminta kode dari aplikasi authenticator saat login.') }}
                                    </p>
                                </div>
                            </div>

                            <x-button
                                variant="ghost"
                                icon="shield"
                                class="self-start text-error hover:bg-error/10"
                                wire:click="disable"
                                data-test="disable-2fa-button"
                            >
                                {{ __('Nonaktifkan 2FA') }}
                            </x-button>

                            <livewire:pages::settings.two-factor.recovery-codes :$requiresConfirmation />
                        </div>
                    @else
                        {{-- 2FA NONAKTIF --}}
                        <div class="flex flex-col gap-4">
                            <div class="flex items-start gap-3 rounded-xl border border-outline-variant/50 bg-surface-container-low p-4">
                                <span class="material-symbols-outlined mt-0.5 text-xl text-on-surface-variant">info</span>
                                <div>
                                    <p class="text-sm font-medium text-ink">{{ __('Belum diaktifkan') }}</p>
                                    <p class="mt-1 text-xs text-on-surface-variant">
                                        {{ __('Saat diaktifkan, Anda akan diminta kode acak dari aplikasi authenticator saat login.') }}
                                    </p>
                                </div>
                            </div>

                            <x-button
                                variant="primary"
                                icon="security"
                                x-data
                                @click="$dispatch('open-modal', 'two-factor-setup-modal'); $wire.dispatch('start-two-factor-setup')"
                                data-test="enable-2fa-button"
                            >
                                {{ __('Aktifkan 2FA') }}
                            </x-button>

                            <livewire:pages::settings.two-factor-setup-modal :requires-confirmation="$requiresConfirmation" />
                        </div>
                    @endif
                </div>
            </div>
        @endif

    </x-pages::settings.layout>
</section>
