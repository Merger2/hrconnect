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
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-blue-400 to-blue-600 shadow-lg">
                            <span class="material-symbols-outlined text-2xl text-white">badge</span>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-ink">HRConnect</h1>
                            <p class="text-xs text-on-surface-variant">Sistem HR & Payroll</p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-6"
                        x-cloak
                        x-data="{
                            showRecoveryInput: @js($errors->has('recovery_code')),
                            code: ['', '', '', '', '', ''],
                            recovery_code: '',
                            toggleInput() {
                                this.showRecoveryInput = !this.showRecoveryInput;
                                this.code = ['', '', '', '', '', ''];
                                this.recovery_code = '';
                                $dispatch('clear-2fa-auth-code');
                                $nextTick(() => {
                                    this.showRecoveryInput
                                        ? this.$refs.recovery_code?.focus()
                                        : this.$refs.otp0?.focus();
                                });
                            },
                            handleOtpInput(index, event) {
                                const input = event.target;
                                if (input.value.length >= 1 && index < 5) {
                                    this.$refs['otp' + (index + 1)]?.focus();
                                }
                            },
                            handleOtpKeydown(index, event) {
                                if (event.key === 'Backspace' && !this.code[index] && index > 0) {
                                    this.$refs['otp' + (index - 1)]?.focus();
                                }
                            },
                        }"
                    >
                        <div x-show="!showRecoveryInput">
                            <h2 class="text-3xl font-bold tracking-tight text-ink">{{ __('Authentication code') }}</h2>
                            <p class="mt-2 text-sm text-on-surface-variant">{{ __('Enter the authentication code provided by your authenticator application.') }}</p>
                        </div>

                        <div x-show="showRecoveryInput">
                            <h2 class="text-3xl font-bold tracking-tight text-ink">{{ __('Recovery code') }}</h2>
                            <p class="mt-2 text-sm text-on-surface-variant">{{ __('Please confirm access to your account by entering one of your emergency recovery codes.') }}</p>
                        </div>

                        <form method="POST" action="{{ route('two-factor.login.store') }}">
                            @csrf

                            <div class="space-y-5 text-center">
                                <div x-show="!showRecoveryInput">
                                    <div class="my-5 flex items-center justify-center gap-2">
                                        <template x-for="(_, i) in 6" :key="i">
                                            <input
                                                :ref="'otp' + i"
                                                x-model="code[i]"
                                                @input="handleOtpInput(i, $event)"
                                                @keydown="handleOtpKeydown(i, $event)"
                                                type="text"
                                                inputmode="numeric"
                                                maxlength="1"
                                                class="h-14 w-12 rounded-xl border-2 border-fog bg-canvas text-center text-lg font-semibold text-ink transition-all focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
                                            />
                                        </template>
                                    </div>
                                </div>

                                <div x-show="showRecoveryInput">
                                    <div class="my-5">
                                        <input
                                            type="text"
                                            name="recovery_code"
                                            x-ref="recovery_code"
                                            x-bind:required="showRecoveryInput"
                                            autocomplete="one-time-code"
                                            x-model="recovery_code"
                                            class="w-full rounded-xl border-2 border-fog bg-canvas px-4 py-3 text-sm text-ink placeholder:text-on-surface-variant/60 transition-all focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
                                        />
                                    </div>
                                    @error('recovery_code')
                                        <p class="text-sm text-error">{{ $message }}</p>
                                    @enderror
                                </div>

                                <button type="submit" class="w-full rounded-xl bg-gradient-to-br from-blue-400 to-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-lg transition-all hover:-translate-y-0.5 hover:shadow-xl hover:shadow-blue-600/30 focus:outline-none focus:ring-4 focus:ring-blue-600/20">
                                    {{ __('Continue') }}
                                </button>
                            </div>

                            <div class="mt-5 text-center text-sm">
                                <span class="text-on-surface-variant">{{ __('or you can') }}</span>
                                <button type="button" @click="toggleInput()" class="ml-1 cursor-pointer font-medium text-blue-600 underline hover:text-blue-700">
                                    <span x-show="!showRecoveryInput">{{ __('login using a recovery code') }}</span>
                                    <span x-show="showRecoveryInput">{{ __('login using an authentication code') }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right: Visual -->
            <div class="relative hidden overflow-hidden bg-gradient-to-br from-blue-600 via-blue-600 to-blue-800 lg:flex lg:flex-col lg:justify-center lg:items-center lg:p-16">
                <div class="absolute -right-32 -top-32 h-96 w-96 rounded-full bg-white/10 blur-3xl"></div>
                <div class="absolute -bottom-24 -left-24 h-80 w-80 rounded-full bg-white/5 blur-3xl"></div>
                <div class="relative z-10 max-w-md text-center text-white">
                    <div class="mx-auto mb-8 flex h-20 w-20 items-center justify-center rounded-2xl bg-white/15 backdrop-blur">
                        <span class="material-symbols-outlined text-4xl">verified_user</span>
                    </div>
                    <h3 class="text-4xl font-bold leading-tight tracking-tight">{{ __('Keamanan ekstra') }}</h3>
                    <p class="mt-4 text-lg text-white/80">{{ __('Verifikasi dua langkah melindungi akun Anda dari akses tidak sah.') }}</p>
                    <div class="mt-10 flex flex-col gap-3 text-left">
                        <div class="flex items-center gap-3 rounded-xl bg-white/10 px-4 py-3 backdrop-blur">
                            <span class="material-symbols-outlined">security</span>
                            <span class="text-sm">{{ __('Lindungi data HR & payroll perusahaan') }}</span>
                        </div>
                        <div class="flex items-center gap-3 rounded-xl bg-white/10 px-4 py-3 backdrop-blur">
                            <span class="material-symbols-outlined">devices</span>
                            <span class="text-sm">{{ __('Bisa pakai Google Authenticator atau Authy') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @vite(['resources/js/app.js'])
    </body>
</html>
