<x-layouts::auth :title="__('Two-factor authentication')">
    <div class="flex flex-col gap-6">
        <div
            class="relative w-full h-auto"
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
                <x-auth-header
                    :title="__('Authentication code')"
                    :description="__('Enter the authentication code provided by your authenticator application.')"
                />
            </div>

            <div x-show="showRecoveryInput">
                <x-auth-header
                    :title="__('Recovery code')"
                    :description="__('Please confirm access to your account by entering one of your emergency recovery codes.')"
                />
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
                                    class="h-12 w-10 rounded-xl border border-outline-variant bg-canvas text-center text-lg font-semibold text-ink focus:border-ink focus:ring-1 focus:ring-ink"
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
                                class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink placeholder:text-on-surface-variant/60 focus:border-ink focus:ring-1 focus:ring-ink"
                            />
                        </div>

                        @error('recovery_code')
                            <p class="text-sm text-error">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <button
                        type="submit"
                        class="w-full rounded-xl bg-ink px-6 py-2.5 text-sm font-semibold text-white"
                    >
                        {{ __('Continue') }}
                    </button>
                </div>

                <div class="mt-5 space-x-0.5 text-center text-sm leading-5">
                    <span class="opacity-50">{{ __('or you can') }}</span>
                    <div class="inline cursor-pointer font-medium opacity-80 underline">
                        <span x-show="!showRecoveryInput" @click="toggleInput()">{{ __('login using a recovery code') }}</span>
                        <span x-show="showRecoveryInput" @click="toggleInput()">{{ __('login using an authentication code') }}</span>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-layouts::auth>
