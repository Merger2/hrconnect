<div class="user-page-shell">
    <div class="user-page-container user-page-container--wide">
        <section
            aria-labelledby="my-payslips-title"
            class="user-page-surface my-payslips-page relative"
            @unless($needsSetup) wire:poll.visible.20s @endunless
        >
            <x-user.page-header
                :back-href="!($needsSetup && Auth::user()->hasValidPayslipPassword()) ? route('home') : null"
                :title="$needsSetup ? __('Secure Access') : __('Payslip')"
                title-id="my-payslips-title"
                class="border-b-0">
                <x-slot name="actions">
                    @if ($needsSetup && Auth::user()->hasValidPayslipPassword())
                        <button wire:click="cancelReset" aria-label="{{ __('Back') }}" title="{{ __('Back') }}"
                            class="user-header-icon-action">
                            <x-heroicon-o-arrow-left class="h-5 w-5" />
                        </button>
                    @elseif (! $needsSetup)
                        <button wire:click="triggerReset" aria-label="{{ __('Reset Password') }}" title="{{ __('Reset Password') }}"
                            class="user-header-icon-action">
                            <x-heroicon-o-lock-closed class="h-5 w-5" />
                        </button>
                    @endif
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body pt-0">
                @if ($needsSetup)
                    <form wire:submit.prevent="setupPassword" class="payslip-secure-panel">
                        <div class="payslip-secure-panel__icon">
                            <x-heroicon-o-lock-closed class="h-7 w-7" />
                        </div>

                        <div>
                            <p class="payslip-eyebrow">{{ __('Private payroll access') }}</p>
                            <h2 class="payslip-secure-panel__title">{{ __('Secure Your Payslips') }}</h2>
                            <p class="payslip-secure-panel__copy">
                                {{ __('Create a password used to open encrypted payslip PDF files.') }}
                            </p>
                        </div>

                        <div class="grid gap-4">
                            <x-user.native-text-field
                                id="new_password"
                                type="password"
                                model="new_password"
                                modifier="defer"
                                icon="heroicon-o-key"
                                label="{{ __('New Password') }}"
                                placeholder="********"
                                autocomplete="new-password"
                                required />
                            <x-forms.input-error for="new_password" />

                            <x-user.native-text-field
                                id="new_password_confirmation"
                                type="password"
                                model="new_password_confirmation"
                                modifier="defer"
                                icon="heroicon-o-shield-check"
                                label="{{ __('Confirm Password') }}"
                                placeholder="********"
                                autocomplete="new-password"
                                required />
                        </div>

                        <div class="payslip-secure-panel__actions">
                            @if (Auth::user()->hasValidPayslipPassword())
                                <button type="button" wire:click="cancelReset" wire:loading.attr="disabled" class="user-secondary-action">
                                    {{ __('Cancel') }}
                                </button>
                            @endif
                            <button type="submit" wire:loading.attr="disabled" class="user-primary-action">
                                {{ __('Save Password') }}
                            </button>
                        </div>
                    </form>
                @else
                    @php
                        $payrollCollection = method_exists($payrolls, 'getCollection') ? $payrolls->getCollection() : collect($payrolls);
                        $latestPayroll = $payrollCollection->first();
                        $paidCount = $payrollCollection->where('status', 'paid')->count();
                    @endphp

                    <div class="payslip-summary">
                        <div class="min-w-0">
                            <p class="payslip-eyebrow">{{ __('Payroll archive') }}</p>
                            <h2 class="payslip-summary__title">
                                {{ $latestPayroll ? \Carbon\Carbon::createFromDate(null, $latestPayroll->month)->translatedFormat('F') . ' ' . $latestPayroll->year : __('No Payslips Yet') }}
                            </h2>
                            <p class="payslip-summary__copy">
                                {{ __('Encrypted PDF statements are available after payroll is marked paid.') }}
                            </p>
                        </div>
                        <div class="payslip-summary__metric">
                            <span>{{ $paidCount }}</span>
                            <small>{{ __('Paid') }}</small>
                        </div>
                    </div>

                    @if ($payrolls->isEmpty())
                        <div class="user-empty-state">
                            <div class="user-empty-state__icon">
                                <x-heroicon-o-document-text class="h-12 w-12" />
                            </div>
                            <h3 class="user-empty-state__title">{{ __('No Payslips Yet') }}</h3>
                            <p class="user-empty-state__copy">{{ __('Salary statements will appear here.') }}</p>
                        </div>
                    @else
                        <div class="payslip-list">
                            @foreach ($payrolls as $payroll)
                                <article class="payslip-card" x-data="{ show: false }">
                                    <div class="payslip-card__period" aria-hidden="true">
                                        <span>{{ \Carbon\Carbon::createFromDate(null, $payroll->month)->translatedFormat('M') }}</span>
                                        <small>{{ $payroll->year }}</small>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex min-w-0 items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <h3 class="payslip-card__title">
                                                    {{ \Carbon\Carbon::createFromDate(null, $payroll->month)->translatedFormat('F') }} {{ $payroll->year }}
                                                </h3>
                                                <p class="payslip-card__meta">
                                                    {{ __('Generated on') }} {{ $payroll->created_at->format('d/m/Y') }}
                                                </p>
                                            </div>

                                            <span class="payslip-card__status">
                                                {{ __(ucfirst($payroll->status)) }}
                                            </span>
                                        </div>

                                        <div class="payslip-card__amount">
                                            <span x-show="!show">{{ __('Masked salary amount') }}</span>
                                            <span x-cloak x-show="show">Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}</span>
                                            <button type="button" @click="show = !show"
                                                :aria-label="show ? @js(__('Hide salary')) : @js(__('Show salary'))"
                                                class="payslip-card__icon-button">
                                                <x-heroicon-o-eye x-show="!show" class="h-4 w-4" />
                                                <x-heroicon-o-eye-slash x-cloak x-show="show" class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </div>

                                    <button wire:click="download('{{ $payroll->id }}')" class="payslip-card__download"
                                        aria-label="{{ __('Download payslip') }} {{ \Carbon\Carbon::createFromDate(null, $payroll->month)->translatedFormat('F') }} {{ $payroll->year }}">
                                        <x-heroicon-o-arrow-down-tray class="h-5 w-5" />
                                    </button>
                                </article>
                            @endforeach
                        </div>

                        <div class="mt-4">
                            {{ $payrolls->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </section>
    </div>
</div>
