<x-app-layout>
    <div class="user-page-shell">
        <div class="user-page-container user-page-container--wide">
            <section
                aria-labelledby="payslip-pin-title"
                class="user-page-surface payslip-pin-page relative"
            >
                <x-user.page-header
                    :back-href="route('my-payslips')"
                    :title="__('Download Payslip')"
                    title-id="payslip-pin-title"
                    module="payroll"
                    class="border-b-0">
                    <x-slot name="icon">
                        <x-heroicon-o-lock-closed class="h-5 w-5" />
                    </x-slot>
                </x-user.page-header>

                <div class="user-page-body pt-0">
                    <form method="POST" action="{{ route('payslip.download', $payroll) }}" class="payslip-secure-panel user-accent-card user-accent-card--payroll">
                        @csrf

                        <div class="solid-head rounded-2xl p-4 flex items-center gap-4">
                            <div class="payslip-secure-panel__icon">
                                <x-heroicon-o-lock-closed class="h-7 w-7" />
                            </div>

                            <div class="min-w-0">
                                <p class="payslip-eyebrow">{{ __('Private payroll access') }}</p>
                                <h2 class="payslip-secure-panel__title">{{ __('Enter your payslip PIN') }}</h2>
                                <p class="payslip-secure-panel__copy">
                                    {{ __('Your payslip PDF is encrypted. Enter the password you set to open it.') }}
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-4">
                            <div class="user-native-field">
                                <x-forms.label for="pin" :value="__('Payslip PIN')" class="user-native-field__label" />

                                <div class="user-native-field__control">
                                    <x-heroicon-o-key class="user-native-field__icon" />
                                    <input
                                        id="pin"
                                        name="pin"
                                        type="password"
                                        class="user-native-field__input"
                                        placeholder="********"
                                        autocomplete="current-password"
                                        inputmode="text"
                                        autofocus
                                        required
                                    >
                                </div>

                                <x-forms.input-error for="pin" class="mt-2" />
                            </div>
                        </div>

                        <div class="payslip-secure-panel__actions">
                            <a href="{{ route('my-payslips') }}" class="user-secondary-action">
                                {{ __('Cancel') }}
                            </a>
                            <button type="submit" class="user-primary-action">
                                <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
                                {{ __('Verify & Download') }}
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
