<?php

use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component {
    #[Locked]
    public array $recoveryCodes = [];

    public function mount(): void
    {
        $this->loadRecoveryCodes();
    }

    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generateNewRecoveryCodes): void
    {
        $generateNewRecoveryCodes(auth()->user());

        $this->loadRecoveryCodes();
    }

    private function loadRecoveryCodes(): void
    {
        $user = auth()->user();

        if ($user->hasEnabledTwoFactorAuthentication() && $user->two_factor_recovery_codes) {
            try {
                $this->recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
            } catch (Exception) {
                $this->addError('recoveryCodes', 'Failed to load recovery codes');

                $this->recoveryCodes = [];
            }
        }
    }
}; ?>

<div
    class="space-y-6 rounded-xl border border-outline-variant bg-surface-container-low py-6 shadow-sm"
    wire:cloak
    x-data="{ showRecoveryCodes: false }"
>
    <div class="space-y-2 px-6">
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-lg text-ink">lock</span>
            <h3 class="text-lg font-semibold text-ink">{{ __('Kode pemulihan 2FA') }}</h3>
        </div>
        <p class="text-sm text-on-surface-variant">
            {{ __('Kode pemulihan digunakan jika Anda kehilangan akses ke perangkat 2FA. Simpan di tempat yang aman.') }}
        </p>
    </div>

    <div class="px-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <button
                x-show="!showRecoveryCodes"
                class="inline-flex items-center gap-2 rounded-xl bg-ink px-6 py-2.5 text-sm font-semibold text-white"
                @click="showRecoveryCodes = true"
                aria-expanded="false"
                aria-controls="recovery-codes-section"
            >
                <span class="material-symbols-outlined text-base">visibility</span>
                {{ __('Lihat kode pemulihan') }}
            </button>

            <button
                x-show="showRecoveryCodes"
                class="inline-flex items-center gap-2 rounded-xl bg-ink px-6 py-2.5 text-sm font-semibold text-white"
                @click="showRecoveryCodes = false"
                aria-expanded="true"
                aria-controls="recovery-codes-section"
            >
                <span class="material-symbols-outlined text-base">visibility_off</span>
                {{ __('Sembunyikan kode') }}
            </button>

            @if (filled($recoveryCodes))
                <button
                    x-show="showRecoveryCodes"
                    class="inline-flex items-center gap-2 rounded-xl border border-outline-variant bg-canvas px-6 py-2.5 text-sm font-semibold text-ink"
                    wire:click="regenerateRecoveryCodes"
                >
                    <span class="material-symbols-outlined text-base">autorenew</span>
                    {{ __('Buat kode baru') }}
                </button>
            @endif
        </div>

        <div
            x-show="showRecoveryCodes"
            x-transition
            id="recovery-codes-section"
            class="relative overflow-hidden"
            x-bind:aria-hidden="!showRecoveryCodes"
        >
            <div class="mt-3 space-y-3">
                @error('recoveryCodes')
                    <div class="rounded-xl border-l-4 border-error-container bg-surface-container-low p-4">
                        <p class="font-semibold text-error">{{ $message }}</p>
                    </div>
                @enderror

                @if (filled($recoveryCodes))
                    <div
                        class="grid gap-1 rounded-lg bg-surface-soft p-4 font-mono text-sm dark:bg-on-dark/5"
                        role="list"
                        aria-label="{{ __('Recovery codes') }}"
                    >
                        @foreach($recoveryCodes as $code)
                            <div
                                role="listitem"
                                class="select-text"
                                wire:loading.class="animate-pulse opacity-50"
                            >
                                {{ $code }}
                            </div>
                        @endforeach
                    </div>
                    <p class="text-xs text-on-surface-variant">
                        {{ __('Setiap kode hanya bisa digunakan sekali. Jika habis, klik Buat kode baru.') }}
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>
