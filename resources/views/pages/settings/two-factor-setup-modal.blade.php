<?php

use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component {
    #[Locked]
    public bool $requiresConfirmation;

    #[Locked]
    public string $qrCodeSvg = '';

    #[Locked]
    public string $manualSetupKey = '';

    public bool $showVerificationStep = false;

    public bool $setupComplete = false;

    #[Validate('required|string|size:6', onUpdate: false)]
    public string $code = '';

    public function mount(bool $requiresConfirmation): void
    {
        $this->requiresConfirmation = $requiresConfirmation;
    }

    #[On('start-two-factor-setup')]
    public function startTwoFactorSetup(): void
    {
        $enableTwoFactorAuthentication = app(EnableTwoFactorAuthentication::class);
        $enableTwoFactorAuthentication(auth()->user());

        $this->loadSetupData();
    }

    private function loadSetupData(): void
    {
        $user = auth()->user()?->fresh();

        try {
            if (! $user || ! $user->two_factor_secret) {
                throw new Exception('Two-factor setup secret is not available.');
            }

            $this->qrCodeSvg = $user->twoFactorQrCodeSvg();
            $this->manualSetupKey = decrypt($user->two_factor_secret);
        } catch (Exception) {
            $this->addError('setupData', 'Failed to fetch setup data.');

            $this->reset('qrCodeSvg', 'manualSetupKey');
        }
    }

    public function showVerificationIfNecessary(): void
    {
        if ($this->requiresConfirmation) {
            $this->showVerificationStep = true;

            $this->resetErrorBag();

            return;
        }

        $this->closeModal();
        $this->dispatch('two-factor-enabled');
    }

    public function confirmTwoFactor(ConfirmTwoFactorAuthentication $confirmTwoFactorAuthentication): void
    {
        $this->validate();

        $confirmTwoFactorAuthentication(auth()->user(), $this->code);

        $this->setupComplete = true;

        $this->closeModal();

        $this->dispatch('two-factor-enabled');
    }

    public function resetVerification(): void
    {
        $this->reset('code', 'showVerificationStep');

        $this->resetErrorBag();
    }

    public function closeModal(): void
    {
        $this->reset(
            'code',
            'manualSetupKey',
            'qrCodeSvg',
            'showVerificationStep',
            'setupComplete',
        );

        $this->resetErrorBag();
    }

    public function getModalConfigProperty(): array
    {
        if ($this->setupComplete) {
            return [
                'title' => __('Verifikasi dua langkah diaktifkan'),
                'description' => __('Verifikasi dua langkah telah diaktifkan. Simpan kode pemulihan di tempat yang aman.'),
                'buttonText' => __('Tutup'),
            ];
        }

        if ($this->showVerificationStep) {
            return [
                'title' => __('Verifikasi kode'),
                'description' => __('Masukkan 6 digit kode dari aplikasi authenticator Anda.'),
                'buttonText' => __('Lanjutkan'),
            ];
        }

        return [
            'title' => __('Aktifkan verifikasi dua langkah'),
            'description' => __('Pindai kode QR atau masukkan kunci setup di aplikasi authenticator Anda.'),
            'buttonText' => __('Lanjutkan'),
        ];
    }
}; ?>

<div
    x-data="{ open: false }"
    x-show="open"
    x-cloak
    @open-modal.window="if ($event.detail === 'two-factor-setup-modal') open = true"
    @keydown.escape.window="open = false"
    class="fixed inset-0 z-50 flex items-center justify-center"
    role="dialog"
    aria-modal="true"
>
    <div class="fixed inset-0 bg-black/40" @click="$wire.closeModal(); open = false"></div>
    <div class="relative z-10 w-full max-w-md rounded-lg bg-canvas p-6 shadow-xl md:min-w-md">
        <div class="space-y-6">
            <div class="flex flex-col items-center space-y-4">
                <div class="w-auto rounded-full border border-outline-variant bg-canvas p-0.5 shadow-sm dark:border-hairline dark:bg-surface-dark-elevated">
                    <div class="relative overflow-hidden rounded-full border border-outline-variant bg-surface-soft p-2.5 dark:border-hairline dark:bg-surface-strong">
                        <div class="absolute inset-0 flex w-full items-stretch justify-around divide-x divide-outline-variant opacity-50 dark:divide-hairline-soft [&>div]:flex-1">
                            @for ($i = 1; $i <= 5; $i++)
                                <div></div>
                            @endfor
                        </div>

                        <div class="absolute inset-0 flex w-full flex-col items-stretch justify-around divide-y divide-outline-variant opacity-50 dark:divide-hairline-soft [&>div]:flex-1">
                            @for ($i = 1; $i <= 5; $i++)
                                <div></div>
                            @endfor
                        </div>

                        <span class="material-symbols-outlined relative z-20 text-3xl text-ink dark:text-white">qr_code_scanner</span>
                    </div>
                </div>

                <div class="space-y-2 text-center">
                    <h3 class="text-lg font-semibold text-ink">{{ $this->modalConfig['title'] }}</h3>
                    <p class="text-sm text-on-surface-variant">{{ $this->modalConfig['description'] }}</p>
                </div>
            </div>

            @if ($showVerificationStep)
                <div class="space-y-6">
                    <div class="flex flex-col items-center justify-center space-y-3">
                        <div class="flex gap-2">
                            <template x-for="(_, i) in 6" :key="i">
                                <input
                                    type="text"
                                    inputmode="numeric"
                                    maxlength="1"
                                    wire:model="code"
                                    class="h-12 w-10 rounded-xl border border-outline-variant bg-canvas text-center text-lg font-semibold text-ink focus:border-ink focus:ring-1 focus:ring-ink"
                                />
                            </template>
                        </div>
                        @error('code')
                            <p class="text-xs text-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            class="flex-1 rounded-xl border border-outline-variant bg-canvas px-6 py-2.5 text-sm font-semibold text-ink"
                            wire:click="resetVerification"
                        >
                            {{ __('Kembali') }}
                        </button>

                        <button
                            class="flex-1 rounded-xl bg-ink px-6 py-2.5 text-sm font-semibold text-white"
                            wire:click="confirmTwoFactor"
                            x-bind:disabled="$wire.code.length < 6"
                        >
                            {{ __('Konfirmasi') }}
                        </button>
                    </div>
                </div>
            @else
                @error('setupData')
                    <div class="rounded-xl border-l-4 border-error-container bg-surface-container-low p-4">
                        <p class="font-semibold text-error">{{ $message }}</p>
                    </div>
                @enderror

                <div class="flex justify-center">
                    <div class="relative aspect-square w-64 overflow-hidden rounded-lg border border-outline-variant dark:border-hairline">
                        @empty($qrCodeSvg)
                            <div class="absolute inset-0 flex animate-pulse items-center justify-center bg-canvas dark:bg-surface-dark-elevated">
                                <span class="material-symbols-outlined text-2xl text-on-surface-variant">sync</span>
                            </div>
                        @else
                            <div x-data class="flex h-full items-center justify-center p-4">
                                <div
                                    class="rounded bg-canvas p-3"
                                >
                                    {!! $qrCodeSvg !!}
                                </div>
                            </div>
                        @endempty
                    </div>
                </div>

                <div>
                    <button
                        :disabled="$errors->has('setupData') ? 'true' : 'false'"
                        class="w-full rounded-xl bg-ink px-6 py-2.5 text-sm font-semibold text-white disabled:opacity-50"
                        wire:click="showVerificationIfNecessary"
                    >
                        {{ $this->modalConfig['buttonText'] }}
                    </button>
                </div>

                <div class="space-y-4">
                    <div class="relative flex w-full items-center justify-center">
                        <div class="absolute inset-0 top-1/2 h-px w-full bg-outline-variant dark:bg-hairline"></div>
                        <span class="relative bg-canvas px-2 text-sm text-muted dark:text-muted-soft">
                            {{ __('atau, masukkan kode manual') }}
                        </span>
                    </div>

                    <div
                        class="flex items-center gap-2"
                        x-data="{
                            copied: false,
                            async copy() {
                                try {
                                    await navigator.clipboard.writeText('{{ $manualSetupKey }}');
                                    this.copied = true;
                                    setTimeout(() => this.copied = false, 1500);
                                } catch (e) {
                                    console.warn('Could not copy to clipboard');
                                }
                            }
                        }"
                    >
                        <div class="flex w-full items-stretch rounded-xl border border-outline-variant dark:border-hairline">
                            @empty($manualSetupKey)
                                <div class="flex w-full items-center justify-center bg-surface-soft p-3 dark:bg-surface-dark-elevated">
                                    <span class="material-symbols-outlined text-base text-on-surface-variant">sync</span>
                                </div>
                            @else
                                <input
                                    type="text"
                                    readonly
                                    value="{{ $manualSetupKey }}"
                                    class="w-full bg-transparent p-3 text-ink outline-none dark:text-on-dark"
                                />

                                <button
                                    @click="copy()"
                                    class="cursor-pointer border-l border-outline-variant px-3 transition-colors dark:border-hairline"
                                >
                                    <span class="material-symbols-outlined text-lg text-ink" x-show="!copied">content_copy</span>
                                    <span class="material-symbols-outlined text-lg text-success" x-show="copied" x-cloak>check</span>
                                </button>
                            @endempty
                        </div>
                    </div>
                </div>
            @endif

            <!-- Close button -->
            <div class="flex justify-end">
                <button @click="$wire.closeModal(); open = false" class="rounded-xl border border-outline-variant bg-canvas px-6 py-2.5 text-sm font-semibold text-ink">
                    {{ __('Close') }}
                </button>
            </div>
        </div>
    </div>
</div>
