<div class="user-page-shell">
    <div class="user-page-container user-page-container--wide">
        <section
            aria-labelledby="my-kasbon-title"
            class="user-page-surface my-kasbon-page relative"
            @unless($showCreateModal) wire:poll.visible.20s @endunless
        >
            <x-user.page-header
                :back-href="! $showCreateModal ? route('home') : null"
                :title="$showCreateModal ? __('Request Kasbon') : __('My Kasbon')"
                title-id="my-kasbon-title"
                class="border-b-0">
                <x-slot name="actions">
                    @if ($showCreateModal)
                        <button wire:click="$set('showCreateModal', false)" aria-label="{{ __('Back') }}" title="{{ __('Back') }}"
                            class="user-header-icon-action">
                            <x-heroicon-o-arrow-left class="h-5 w-5" />
                        </button>
                    @elseif (! $canRequestCashAdvance)
                        <button type="button" disabled title="{{ __('Kasbon is available after your basic salary has been updated.') }}"
                            class="user-header-icon-action opacity-45">
                            <x-heroicon-m-plus class="h-5 w-5" />
                        </button>
                    @else
                        <button wire:click="openCreateModal" aria-label="{{ __('Request Kasbon') }}" title="{{ __('Request Kasbon') }}"
                            class="user-header-icon-action bg-primary-600 text-white hover:bg-primary-700 hover:text-white dark:bg-primary-400 dark:text-slate-950 dark:hover:bg-primary-300">
                            <x-heroicon-m-plus class="h-5 w-5" />
                        </button>
                    @endif
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body pt-0">
                @unless ($canRequestCashAdvance)
                    <div class="kasbon-alert">
                        <x-heroicon-o-information-circle class="h-5 w-5 shrink-0" />
                        <span>{{ __('Kasbon is available after your basic salary has been updated.') }}</span>
                    </div>
                @endunless

                @if ($showCreateModal)
                    <form wire:submit.prevent="submit" class="kasbon-request-panel">
                        <div>
                            <p class="kasbon-eyebrow">{{ __('Cash advance request') }}</p>
                            <h2 class="kasbon-request-panel__title">{{ __('Choose amount and payroll deduction') }}</h2>
                            <p class="kasbon-request-panel__copy">
                                {{ __('If approved, this amount will be deducted from the selected payroll period.') }}
                            </p>
                        </div>

                        <div class="user-native-field">
                            <x-forms.label for="kasbon-amount" value="{{ __('Kasbon Amount') }}" class="user-native-field__label" />
                            <div class="user-native-field__control">
                                <span class="user-native-field__prefix">{{ __('Rp') }}</span>
                                <input
                                    id="kasbon-amount"
                                    type="text"
                                    inputmode="numeric"
                                    x-data
                                    x-mask:dynamic="$money($input, '.', ',')"
                                    wire:model.defer="amount"
                                    placeholder="0"
                                    class="user-native-field__input user-native-field__input--with-prefix"
                                    aria-label="{{ __('Kasbon Amount') }}">
                            </div>
                            <x-forms.input-error for="amount" class="mt-2" />
                        </div>

                        <x-user.native-textarea-field
                            id="kasbon-purpose"
                            model="purpose"
                            modifier="defer"
                            icon="heroicon-o-chat-bubble-left-right"
                            rows="3"
                            label="{{ __('Kasbon Purpose') }}"
                            placeholder="{{ __('Explain the purpose of this kasbon') }}"
                            error="purpose" />

                        @php
                            $deductionPeriods = collect(range(0, 2))->map(fn (int $offset) => now()->startOfMonth()->addMonths($offset));
                            $selectedPeriodKey = sprintf('%04d-%02d', (int) ($payment_year ?: now()->year), (int) ($payment_month ?: now()->month));
                        @endphp

                        <div class="user-native-field">
                            <x-forms.label for="kasbon-payment-period" value="{{ __('Salary Deduction Period') }}" class="user-native-field__label" />
                            <div class="user-native-field__control">
                                <x-heroicon-o-calendar-days class="user-native-field__icon" />
                                <select
                                    id="kasbon-payment-period"
                                    x-data
                                    x-on:change="
                                        const [year, month] = $event.target.value.split('-');
                                        $wire.set('payment_year', Number(year));
                                        $wire.set('payment_month', Number(month));
                                    "
                                    class="user-native-field__input kasbon-native-select">
                                    @foreach ($deductionPeriods as $period)
                                        @php
                                            $periodKey = $period->format('Y-m');
                                        @endphp
                                        <option value="{{ $periodKey }}" @selected($selectedPeriodKey === $periodKey)>
                                            {{ $period->translatedFormat('F Y') }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-heroicon-o-chevron-down class="kasbon-native-select__chevron" />
                            </div>
                            <p class="kasbon-field-hint">{{ __('Only the current month through the next two months can be selected.') }}</p>
                            <x-forms.input-error for="payment_month" class="mt-2" />
                            <x-forms.input-error for="payment_year" class="mt-2" />
                        </div>

                        <div class="kasbon-request-note">
                            <x-heroicon-o-shield-check class="h-5 w-5 shrink-0" />
                            <span>{{ __('Finance and your manager can review this request before payroll deduction.') }}</span>
                        </div>

                        <div class="kasbon-request-panel__actions">
                            <button type="button" wire:click="$set('showCreateModal', false)" class="user-secondary-action">
                                {{ __('Cancel') }}
                            </button>
                            <button type="submit" wire:loading.attr="disabled" class="user-primary-action">
                                {{ __('Submit Kasbon Request') }}
                            </button>
                        </div>
                    </form>
                @else
                    <div class="kasbon-summary">
                        <div class="kasbon-summary__item kasbon-summary__item--warning">
                            <span>{{ __('Unpaid') }}</span>
                            <strong>{{ __('Rp') }} {{ number_format($totalUnpaid, 0, ',', '.') }}</strong>
                        </div>
                        <div class="kasbon-summary__item kasbon-summary__item--success">
                            <span>{{ __('Paid') }}</span>
                            <strong>{{ __('Rp') }} {{ number_format($totalPaid, 0, ',', '.') }}</strong>
                        </div>
                        <div class="kasbon-summary__item kasbon-summary__item--neutral">
                            <span>{{ __('Limit') }}</span>
                            <strong>{{ __('Rp') }} {{ number_format($basicSalary, 0, ',', '.') }}</strong>
                        </div>
                    </div>

                    @if ($advances->isEmpty())
                        <div class="user-empty-state">
                            <div class="user-empty-state__icon">
                                <x-heroicon-o-banknotes class="h-8 w-8" />
                            </div>
                            <h3 class="user-empty-state__title">{{ __('No cash advance data found.') }}</h3>
                            <p class="user-empty-state__copy">{{ __('No cash advance requests yet.') }}</p>
                        </div>
                    @else
                        <div class="kasbon-list">
                            @foreach ($advances as $advance)
                                @php
                                    $statusTone = match ($advance->status) {
                                        'approved' => 'success',
                                        'paid' => 'info',
                                        'rejected' => 'danger',
                                        default => 'warning',
                                    };
                                    $statusLabel = match ($advance->status) {
                                        'approved' => __('Approved'),
                                        'paid' => __('Paid'),
                                        'rejected' => __('Rejected'),
                                        default => __('Pending'),
                                    };
                                @endphp

                                <article class="kasbon-card">
                                    <div class="kasbon-card__icon" aria-hidden="true">
                                        <x-heroicon-o-banknotes class="h-5 w-5" />
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex min-w-0 items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <h3 class="kasbon-card__title">
                                                    {{ \Carbon\Carbon::create()->month((int) $advance->payment_month)->translatedFormat('F') }} {{ $advance->payment_year }}
                                                </h3>
                                                <p class="kasbon-card__meta">
                                                    {{ __('Deduction Target') }} · {{ $advance->created_at->format('d M Y') }}
                                                </p>
                                            </div>

                                            <span class="kasbon-card__status kasbon-card__status--{{ $statusTone }}">
                                                {{ $statusLabel }}
                                            </span>
                                        </div>

                                        <div class="kasbon-card__amount">
                                            <span>{{ __('Rp') }}</span>{{ number_format($advance->amount, 0, ',', '.') }}
                                        </div>
                                    </div>

                                    @if ($advance->status === 'pending')
                                        <button wire:click="delete({{ $advance->id }})"
                                            wire:confirm="{{ __('Are you sure you want to cancel this request?') }}"
                                            class="kasbon-card__cancel"
                                            aria-label="{{ __('Cancel') }} {{ __('Request Kasbon') }}">
                                            <x-heroicon-o-x-mark class="h-5 w-5" />
                                        </button>
                                    @endif
                                </article>
                            @endforeach
                        </div>

                        <div class="mt-4">
                            {{ $advances->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </section>
    </div>
</div>
