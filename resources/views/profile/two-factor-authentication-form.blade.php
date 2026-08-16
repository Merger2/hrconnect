<div class="profile-section__card">
    <div class="profile-section__header">
        <div class="min-w-0">
            <h3 class="profile-section__title">{{ __('Two Factor Authentication') }}</h3>
            <p class="profile-section__desc">{{ __('Add additional security to your account using two factor authentication.') }}</p>
        </div>
    </div>

    <div class="profile-section__body">
        <h3 class="text-base font-semibold text-slate-900">
            @if ($this->enabled)
                @if ($showingConfirmation)
                    {{ __('Finish enabling two factor authentication.') }}
                @else
                    {{ __('You have enabled two factor authentication.') }}
                @endif
            @else
                {{ __('You have not enabled two factor authentication.') }}
            @endif
        </h3>

        <p class="mt-2 text-sm text-slate-600">
            {{ __('When two factor authentication is enabled, you will be prompted for a secure, random token during authentication. You may retrieve this token from your phone\'s Google Authenticator application.') }}
        </p>

        @if ($this->enabled)
            @if ($showingQrCode)
                <div class="mt-4 text-sm text-slate-600">
                    <p class="font-semibold">
                        @if ($showingConfirmation)
                            {{ __('To finish enabling two factor authentication, scan the following QR code using your phone\'s authenticator application or enter the setup key and provide the generated OTP code.') }}
                        @else
                            {{ __('Two factor authentication is now enabled. Scan the following QR code using your phone\'s authenticator application or enter the setup key.') }}
                        @endif
                    </p>
                </div>

                <div class="mt-4 inline-block rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-200">
                    {!! $this->user->twoFactorQrCodeSvg() !!}
                </div>

                <div class="mt-4 text-sm text-slate-600">
                    <p class="font-semibold">{{ __('Setup Key') }}: <span class="font-mono text-primary-600">{{ decrypt($this->user->two_factor_secret) }}</span></p>
                </div>

                @if ($showingConfirmation)
                    <div class="mt-4">
                        <label class="profile-field__label" for="code">{{ __('Code') }}</label>
                        <input id="code" type="text" name="code"
                            class="profile-field__input mt-1 block w-1/2"
                            inputmode="numeric" autofocus autocomplete="one-time-code"
                            wire:model="code"
                            wire:keydown.enter="confirmTwoFactorAuthentication" />
                        @error('code') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif
            @endif

            @if ($showingRecoveryCodes)
                <div class="mt-4 text-sm text-slate-600">
                    <p class="font-semibold">{{ __('Store these recovery codes in a secure password manager. They can be used to recover access to your account if your two factor authentication device is lost.') }}</p>
                </div>
                <div class="mt-4 grid gap-1 rounded-xl bg-slate-100 px-4 py-4 font-mono text-sm text-slate-800">
                    @foreach (json_decode(decrypt($this->user->two_factor_recovery_codes), true) as $code)
                        <div>{{ $code }}</div>
                    @endforeach
                </div>
            @endif
        @endif

        <div class="mt-5 flex flex-wrap items-center gap-3">
            {{-- Konfirmasi password memakai komponen x-overlays.confirms-password
                 (modal + wire:then) — JANGAN inline startConfirmingPassword tanpa
                 modal: modal tidak akan muncul dan event 'then' tak terdengar
                 (aksi seperti Enable/Disable jadi mati). --}}
            @if (! $this->enabled)
                <x-overlays.confirms-password wire:then="enableTwoFactorAuthentication">
                    <button type="button" wire:loading.attr="disabled"
                        class="rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                        {{ __('Enable') }}
                    </button>
                </x-overlays.confirms-password>
            @else
                @if ($showingRecoveryCodes)
                    <x-overlays.confirms-password wire:then="regenerateRecoveryCodes">
                        <button type="button" wire:loading.attr="disabled"
                            class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2">
                            {{ __('Regenerate Recovery Codes') }}
                        </button>
                    </x-overlays.confirms-password>
                @elseif ($showingConfirmation)
                    <x-overlays.confirms-password wire:then="confirmTwoFactorAuthentication">
                        <button type="button" class="rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                                wire:loading.attr="disabled">
                            {{ __('Confirm') }}
                        </button>
                    </x-overlays.confirms-password>
                @else
                    <x-overlays.confirms-password wire:then="showRecoveryCodes">
                        <button type="button" wire:loading.attr="disabled"
                            class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2">
                            {{ __('Show Recovery Codes') }}
                        </button>
                    </x-overlays.confirms-password>
                @endif

                @if ($showingConfirmation)
                    <x-overlays.confirms-password wire:then="disableTwoFactorAuthentication">
                        <button type="button" wire:loading.attr="disabled"
                            class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2">
                            {{ __('Cancel') }}
                        </button>
                    </x-overlays.confirms-password>
                @else
                    <x-overlays.confirms-password wire:then="disableTwoFactorAuthentication">
                        <button type="button"
                            class="rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                            wire:loading.attr="disabled">
                            {{ __('Disable') }}
                        </button>
                    </x-overlays.confirms-password>
                @endif
            @endif
        </div>
    </div>
</div>
