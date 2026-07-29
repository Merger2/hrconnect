<div class="user-page-shell team-approvals-history-page">
    @php
        $tabs = [
            'leaves' => ['label' => __('Leave'), 'icon' => 'calendar', 'hint' => __('Leave Requests')],
            'reimbursements' => ['label' => __('Claim'), 'icon' => 'cash', 'hint' => __('Reimbursements')],
            'attendance-corrections' => ['label' => __('Correction'), 'icon' => 'correction', 'hint' => __('Attendance Corrections')],
            'shift-swaps' => ['label' => __('Shift Swap'), 'icon' => 'swap', 'hint' => __('Shift Swaps')],
            'overtimes' => ['label' => __('Overtime'), 'icon' => 'clock', 'hint' => __('Overtime Requests')],
            'kasbons' => ['label' => __('Kasbon'), 'icon' => 'wallet', 'hint' => __('Kasbons')],
        ];

        $activePaginator = match ($activeTab) {
            'attendance-corrections' => $attendanceCorrections,
            'shift-swaps' => $shiftSwapRequests,
            'reimbursements' => $reimbursements,
            'overtimes' => $overtimes,
            'kasbons' => $kasbons,
            default => $leaves,
        };
        $activeTotal = method_exists($activePaginator, 'total') ? $activePaginator->total() : $activePaginator->count();
        $activeMeta = $tabs[$activeTab] ?? $tabs['leaves'];

        $statusClass = fn (?string $status): string => match ($status) {
            'approved', 'paid' => 'team-approval-status team-approval-status--success',
            'rejected' => 'team-approval-status team-approval-status--danger',
            'pending_finance', 'pending_admin' => 'team-approval-status team-approval-status--info',
            default => 'team-approval-status team-approval-status--warning',
        };

        $statusLabel = fn (?string $status): string => match ($status) {
            'pending_finance' => __('Pending Finance'),
            'pending_admin' => __('Pending Admin'),
            default => __(str((string) $status)->headline()->toString()),
        };
    @endphp

    <div class="user-page-container user-page-container--wide">
        <div class="user-page-surface team-approval-surface">
            <x-user.page-header
                :back-href="route('approvals')"
                :title="__('Approval History')"
                title-id="approval-history-title"
                class="border-b-0">
                <x-slot name="icon">
                    <x-heroicon-o-clock class="h-5 w-5" />
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body pt-0">
                <section class="team-approval-overview" aria-labelledby="approval-history-overview-title">
                    <div class="team-approval-overview__copy">
                        <p class="team-approval-overview__eyebrow">{{ __('Approval History') }}</p>
                        <h2 id="approval-history-overview-title">{{ $activeMeta['hint'] }}</h2>
                        <p>{{ __('Track processed team requests and decisions.') }}</p>
                    </div>

                    <div class="team-approval-overview__count" aria-label="{{ __('History') }}">
                        <strong>{{ $activeTotal }}</strong>
                        <span>{{ __('History') }}</span>
                    </div>
                </section>

                <section class="team-approval-toolbar" aria-label="{{ __('Filter approvals') }}">
                    <label class="team-approval-search" for="team-approval-history-search">
                        <x-heroicon-o-magnifying-glass class="h-5 w-5" />
                        <input
                            id="team-approval-history-search"
                            wire:model.live.debounce.300ms="search"
                            type="search"
                            placeholder="{{ __('Search employee...') }}"
                            autocomplete="off" />
                    </label>

                    <nav class="team-approval-tabs" aria-label="{{ __('Approval type') }}">
                        @foreach($tabs as $tab => $meta)
                            <button
                                type="button"
                                wire:click="switchTab('{{ $tab }}')"
                                class="team-approval-tab"
                                aria-label="{{ $meta['hint'] }}"
                                aria-selected="{{ $activeTab === $tab ? 'true' : 'false' }}">
                                <span class="team-approval-tab__icon" aria-hidden="true">
                                    @switch($meta['icon'])
                                        @case('cash')
                                            <x-heroicon-o-banknotes class="h-4 w-4" />
                                            @break
                                        @case('correction')
                                            <x-heroicon-o-clipboard-document-check class="h-4 w-4" />
                                            @break
                                        @case('swap')
                                            <x-heroicon-o-arrows-right-left class="h-4 w-4" />
                                            @break
                                        @case('clock')
                                            <x-heroicon-o-clock class="h-4 w-4" />
                                            @break
                                        @case('wallet')
                                            <x-heroicon-o-wallet class="h-4 w-4" />
                                            @break
                                        @default
                                            <x-heroicon-o-calendar-days class="h-4 w-4" />
                                    @endswitch
                                </span>
                                <span>{{ $meta['label'] }}</span>
                            </button>
                        @endforeach
                    </nav>
                </section>

                @if ($activeTab === 'leaves')
                    <section class="team-approval-list" aria-live="polite">
                        @forelse ($leaves as $leave)
                            <article class="team-approval-card">
                                <div class="team-approval-card__main">
                                    <img
                                        class="team-approval-card__avatar"
                                        src="{{ $leave->user->profile_photo_url }}"
                                        alt="{{ $leave->user->name }}">

                                    <div class="team-approval-card__body">
                                        <div class="team-approval-card__topline">
                                            <div class="min-w-0">
                                                <h3>{{ $leave->user->name }}</h3>
                                                <p>{{ $leave->user->jobTitle->name ?? __('N/A') }}</p>
                                            </div>

                                            <span class="{{ $statusClass($leave->approval_status) }}">
                                                {{ $statusLabel($leave->approval_status) }}
                                            </span>
                                        </div>

                                        <div class="team-approval-facts">
                                            <div>
                                                <span>{{ __('Type') }}</span>
                                                <strong>{{ __(ucfirst((string) $leave->status)) }}</strong>
                                            </div>
                                            <div>
                                                <span>{{ __('Date') }}</span>
                                                <strong>{{ \Carbon\Carbon::parse($leave->date)->translatedFormat('d M Y') }}</strong>
                                            </div>
                                            <div>
                                                <span>{{ __('Processed By') }}</span>
                                                <strong>{{ $leave->approvedBy?->name ?? __('System') }}</strong>
                                            </div>
                                            <div>
                                                <span>{{ __('Submitted') }}</span>
                                                <strong>{{ $leave->created_at?->diffForHumans() ?? '-' }}</strong>
                                            </div>
                                        </div>

                                        @if ($leave->note)
                                            <p class="team-approval-note">{{ $leave->note }}</p>
                                        @endif

                                        @if ($leave->approval_note)
                                            <p class="team-approval-note team-approval-note--danger">{{ $leave->approval_note }}</p>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="team-approval-empty">
                                <x-heroicon-o-calendar-days class="h-10 w-10" />
                                <h3>{{ __('No leave requests found') }}</h3>
                                <p>{{ __('Team leave history will appear here.') }}</p>
                            </div>
                        @endforelse

                        @if ($leaves->hasPages())
                            <div>{{ $leaves->links() }}</div>
                        @endif
                    </section>
                @elseif ($activeTab === 'attendance-corrections')
                    @include('livewire.user.partials.team-attendance-corrections-history')
                @elseif ($activeTab === 'shift-swaps')
                    @include('livewire.user.partials.team-shift-swaps-history')
                @elseif ($activeTab === 'reimbursements')
                    <section class="team-approval-list" aria-live="polite">
                        @forelse ($reimbursements as $reimbursement)
                            <article class="team-approval-card">
                                <div class="team-approval-card__main">
                                    <img
                                        class="team-approval-card__avatar"
                                        src="{{ $reimbursement->user->profile_photo_url }}"
                                        alt="{{ $reimbursement->user->name }}">

                                    <div class="team-approval-card__body">
                                        <div class="team-approval-card__topline">
                                            <div class="min-w-0">
                                                <h3>{{ $reimbursement->user->name }}</h3>
                                                <p>{{ $reimbursement->user->jobTitle->name ?? __('N/A') }}</p>
                                            </div>

                                            <span class="{{ $statusClass($reimbursement->status) }}">
                                                {{ $statusLabel($reimbursement->status) }}
                                            </span>
                                        </div>

                                        <div class="team-approval-amount">
                                            Rp {{ number_format((float) $reimbursement->amount, 0, ',', '.') }}
                                        </div>

                                        <div class="team-approval-facts">
                                            <div>
                                                <span>{{ __('Type') }}</span>
                                                <strong>{{ __(ucfirst((string) $reimbursement->type)) }}</strong>
                                            </div>
                                            <div>
                                                <span>{{ __('Date') }}</span>
                                                <strong>
                                                    {{ $reimbursement->date ? \Carbon\Carbon::parse($reimbursement->date)->translatedFormat('d M Y') : '-' }}
                                                </strong>
                                            </div>
                                            <div>
                                                <span>{{ __('Processed By') }}</span>
                                                <strong>{{ $reimbursement->approvedBy?->name ?? __('System') }}</strong>
                                            </div>
                                        </div>

                                        @if ($reimbursement->description)
                                            <p class="team-approval-note">{{ $reimbursement->description }}</p>
                                        @endif

                                        @if ($reimbursement->admin_note)
                                            <p class="team-approval-note team-approval-note--danger">{{ $reimbursement->admin_note }}</p>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="team-approval-empty">
                                <x-heroicon-o-banknotes class="h-10 w-10" />
                                <h3>{{ __('No reimbursement requests found') }}</h3>
                                <p>{{ __('Team reimbursement history will appear here.') }}</p>
                            </div>
                        @endforelse

                        @if ($reimbursements->hasPages())
                            <div>{{ $reimbursements->links() }}</div>
                        @endif
                    </section>
                @elseif ($activeTab === 'overtimes')
                    <section class="team-approval-list" aria-live="polite">
                        @forelse ($overtimes as $overtime)
                            @php
                                $startTime = $overtime->start_time ? \Carbon\Carbon::parse($overtime->start_time) : null;
                                $endTime = $overtime->end_time ? \Carbon\Carbon::parse($overtime->end_time) : null;
                                $durationText = $startTime && $endTime ? $startTime->diff($endTime)->format('%h hr %i min') : '-';
                            @endphp

                            <article class="team-approval-card">
                                <div class="team-approval-card__main">
                                    <img
                                        class="team-approval-card__avatar"
                                        src="{{ $overtime->user->profile_photo_url }}"
                                        alt="{{ $overtime->user->name }}">

                                    <div class="team-approval-card__body">
                                        <div class="team-approval-card__topline">
                                            <div class="min-w-0">
                                                <h3>{{ $overtime->user->name }}</h3>
                                                <p>{{ $overtime->user->jobTitle->name ?? __('N/A') }}</p>
                                            </div>

                                            <span class="{{ $statusClass($overtime->status) }}">
                                                {{ $statusLabel($overtime->status) }}
                                            </span>
                                        </div>

                                        <div class="team-approval-facts">
                                            <div>
                                                <span>{{ __('Date') }}</span>
                                                <strong>
                                                    {{ $overtime->date ? \Carbon\Carbon::parse($overtime->date)->translatedFormat('d M Y') : '-' }}
                                                </strong>
                                            </div>
                                            <div>
                                                <span>{{ __('Time') }}</span>
                                                <strong>{{ $startTime?->format('H:i') ?? '--:--' }} - {{ $endTime?->format('H:i') ?? '--:--' }}</strong>
                                            </div>
                                            <div>
                                                <span>{{ __('Duration') }}</span>
                                                <strong>{{ $durationText }}</strong>
                                            </div>
                                            <div>
                                                <span>{{ __('Processed By') }}</span>
                                                <strong>{{ $overtime->approvedBy?->name ?? __('System') }}</strong>
                                            </div>
                                        </div>

                                        @if ($overtime->reason)
                                            <p class="team-approval-note">{{ $overtime->reason }}</p>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="team-approval-empty">
                                <x-heroicon-o-clock class="h-10 w-10" />
                                <h3>{{ __('No overtime records found') }}</h3>
                                <p>{{ __('Team overtime history will appear here.') }}</p>
                            </div>
                        @endforelse

                        @if ($overtimes->hasPages())
                            <div>{{ $overtimes->links() }}</div>
                        @endif
                    </section>
                @else
                    <section class="team-approval-list" aria-live="polite">
                        @forelse ($kasbons as $kasbon)
                            @php
                                $paymentPeriod = $kasbon->payment_month
                                    ? \Carbon\Carbon::create(null, (int) $kasbon->payment_month, 1)->translatedFormat('F').' '.$kasbon->payment_year
                                    : '-';
                            @endphp

                            <article class="team-approval-card">
                                <div class="team-approval-card__main">
                                    <img
                                        class="team-approval-card__avatar"
                                        src="{{ $kasbon->user->profile_photo_url }}"
                                        alt="{{ $kasbon->user->name }}">

                                    <div class="team-approval-card__body">
                                        <div class="team-approval-card__topline">
                                            <div class="min-w-0">
                                                <h3>{{ $kasbon->user->name }}</h3>
                                                <p>{{ $kasbon->user->jobTitle->name ?? __('N/A') }}</p>
                                            </div>

                                            <span class="{{ $statusClass($kasbon->status) }}">
                                                {{ $statusLabel($kasbon->status) }}
                                            </span>
                                        </div>

                                        <div class="team-approval-amount">
                                            Rp {{ number_format((float) $kasbon->amount, 0, ',', '.') }}
                                        </div>

                                        <div class="team-approval-facts">
                                            <div>
                                                <span>{{ __('Payment Month') }}</span>
                                                <strong>{{ $paymentPeriod }}</strong>
                                            </div>
                                            <div>
                                                <span>{{ __('Processed By') }}</span>
                                                <strong>{{ $kasbon->approvedBy?->name ?? __('System') }}</strong>
                                            </div>
                                            <div>
                                                <span>{{ __('Submitted') }}</span>
                                                <strong>{{ $kasbon->created_at?->diffForHumans() ?? '-' }}</strong>
                                            </div>
                                        </div>

                                        @if ($kasbon->purpose)
                                            <p class="team-approval-note">{{ $kasbon->purpose }}</p>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="team-approval-empty">
                                <x-heroicon-o-wallet class="h-10 w-10" />
                                <h3>{{ __('No kasbon records found') }}</h3>
                                <p>{{ __('Team cash advance history will appear here.') }}</p>
                            </div>
                        @endforelse

                        @if ($kasbons->hasPages())
                            <div>{{ $kasbons->links() }}</div>
                        @endif
                    </section>
                @endif
            </div>
        </div>
    </div>
</div>
