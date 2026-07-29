<div class="user-page-shell team-approvals-page" wire:poll.visible.20s>
    @php
        $tabs = [
            'leaves' => ['label' => __('Leave'), 'icon' => 'calendar', 'hint' => __('Leave Requests')],
            'reimbursements' => ['label' => __('Claim'), 'icon' => 'cash', 'hint' => __('Reimbursements')],
            'attendance-corrections' => ['label' => __('Correction'), 'icon' => 'correction', 'hint' => __('Attendance Corrections')],
            'shift-swaps' => ['label' => __('Shift Swap'), 'icon' => 'swap', 'hint' => __('Shift Swaps')],
            'overtimes' => ['label' => __('Overtime'), 'icon' => 'clock', 'hint' => __('Overtime Requests')],
            'wfh' => ['label' => __('WFH'), 'icon' => 'home', 'hint' => __('WFH')],
            'kasbons' => ['label' => __('Kasbon'), 'icon' => 'wallet', 'hint' => __('Kasbons')],
        ];

        $activePaginator = match ($activeTab) {
            'attendance-corrections' => $attendanceCorrections,
            'shift-swaps' => $shiftSwapRequests,
            'reimbursements' => $reimbursements,
            'overtimes' => $overtimes,
            'wfh' => $wfhRequests,
            'kasbons' => $kasbons,
            default => $leaves,
        };
        $activeTotal = method_exists($activePaginator, 'total') ? $activePaginator->total() : $activePaginator->count();
        $activeMeta = $tabs[$activeTab] ?? $tabs['leaves'];

        $statusClass = fn (?string $status): string => match ($status) {
            'approved', 'paid' => 'team-approval-status team-approval-status--success',
            'rejected' => 'team-approval-status team-approval-status--danger',
            'pending_finance' => 'team-approval-status team-approval-status--info',
            default => 'team-approval-status team-approval-status--warning',
        };
    @endphp

    <div class="user-page-container user-page-container--wide">
        <div class="user-page-surface team-approval-surface">
            <x-user.page-header
                :back-href="route('home')"
                :title="__('Team Approvals')"
                title-id="team-approvals-title"
                class="border-b-0">
                <x-slot name="icon">
                    <x-heroicon-o-check-badge class="h-5 w-5" />
                </x-slot>
                <x-slot name="actions">
                    <a href="{{ route('approvals.history') }}"
                        class="user-header-icon-button"
                        title="{{ __('History') }}"
                        aria-label="{{ __('Approval History') }}">
                        <x-heroicon-o-clock class="h-5 w-5" />
                    </a>
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body pt-0">
                <section class="team-approval-overview" aria-labelledby="team-approval-overview-title">
                    <div class="team-approval-overview__copy">
                        <p class="team-approval-overview__eyebrow">{{ __('Manager Inbox') }}</p>
                        <h2 id="team-approval-overview-title">{{ $activeMeta['hint'] }}</h2>
                        <p>{{ __('Review pending team requests without jumping between menus.') }}</p>
                    </div>
                    <div class="team-approval-overview__count" aria-label="{{ __('Pending approvals') }}">
                        <strong>{{ $activeTotal }}</strong>
                        <span>{{ __('Pending') }}</span>
                    </div>
                </section>

                <section class="team-approval-toolbar" aria-label="{{ __('Filter approvals') }}">
                    <label class="team-approval-search" for="team-approval-search">
                        <x-heroicon-o-magnifying-glass class="h-5 w-5" />
                        <input
                            id="team-approval-search"
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
                                        @case('home')
                                            <x-heroicon-o-home-modern class="h-4 w-4" />
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

                <section class="team-approval-list" aria-live="polite">
                    @if ($activeTab === 'leaves')
                        @forelse ($leaves as $leave)
                            <article class="team-approval-card">
                                <div class="team-approval-card__main">
                                    <img class="team-approval-card__avatar" src="{{ $leave->user->profile_photo_url }}" alt="{{ $leave->user->name }}">
                                    <div class="team-approval-card__body">
                                        <div class="team-approval-card__topline">
                                            <div>
                                                <h3>{{ $leave->user->name }}</h3>
                                                <p>{{ $leave->user->jobTitle->name ?? __('N/A') }}</p>
                                            </div>
                                            <span class="{{ $statusClass($leave->approval_status) }}">{{ __(str((string) $leave->approval_status)->headline()->toString()) }}</span>
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
                                        </div>

                                        @if($leave->note)
                                            <p class="team-approval-note">{{ $leave->note }}</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="team-approval-actions">
                                    <button type="button" wire:click="rejectLeave('{{ $leave->id }}')" class="team-approval-action team-approval-action--reject">
                                        <x-heroicon-o-x-mark class="h-4 w-4" />
                                        <span>{{ __('Reject') }}</span>
                                    </button>
                                    <button type="button" wire:click="approveLeave('{{ $leave->id }}')" class="team-approval-action team-approval-action--approve">
                                        <x-heroicon-o-check class="h-4 w-4" />
                                        <span>{{ __('Approve') }}</span>
                                    </button>
                                </div>
                            </article>
                        @empty
                            <div class="team-approval-empty">
                                <x-heroicon-o-calendar-days class="h-8 w-8" />
                                <h3>{{ __('No leave requests found') }}</h3>
                                <p>{{ __('New team leave requests will appear here.') }}</p>
                            </div>
                        @endforelse

                        {{ $leaves->links() }}
                    @elseif ($activeTab === 'attendance-corrections')
                        @forelse ($attendanceCorrections as $correction)
                            <article class="team-approval-card">
                                <div class="team-approval-card__main">
                                    <img class="team-approval-card__avatar" src="{{ $correction->user->profile_photo_url }}" alt="{{ $correction->user->name }}">
                                    <div class="team-approval-card__body">
                                        <div class="team-approval-card__topline">
                                            <div>
                                                <h3>{{ $correction->user->name }}</h3>
                                                <p>{{ $correction->requestTypeLabel() }} · {{ $correction->attendance_date->translatedFormat('d M Y') }}</p>
                                            </div>
                                            <span class="{{ $statusClass($correction->status) }}">{{ __('Pending') }}</span>
                                        </div>

                                        <div class="team-approval-facts">
                                            @if ($correction->requested_time_in)
                                                <div>
                                                    <span>{{ __('Check in') }}</span>
                                                    <strong>{{ $correction->requested_time_in->translatedFormat('d M Y H:i') }}</strong>
                                                </div>
                                            @endif
                                            @if ($correction->requested_time_out)
                                                <div>
                                                    <span>{{ __('Check out') }}</span>
                                                    <strong>{{ $correction->requested_time_out->translatedFormat('d M Y H:i') }}</strong>
                                                </div>
                                            @endif
                                            @if ($correction->requestedShift)
                                                <div>
                                                    <span>{{ __('Shift') }}</span>
                                                    <strong>{{ $correction->requestedShift->name }}</strong>
                                                </div>
                                            @endif
                                        </div>

                                        <p class="team-approval-note">{{ $correction->reason }}</p>
                                    </div>
                                </div>

                                <div class="team-approval-actions">
                                    <button type="button" wire:click="rejectAttendanceCorrection('{{ $correction->id }}')" class="team-approval-action team-approval-action--reject">
                                        <x-heroicon-o-x-mark class="h-4 w-4" />
                                        <span>{{ __('Reject') }}</span>
                                    </button>
                                    <button type="button" wire:click="approveAttendanceCorrection('{{ $correction->id }}')" class="team-approval-action team-approval-action--approve">
                                        <x-heroicon-o-check class="h-4 w-4" />
                                        <span>{{ __('Approve') }}</span>
                                    </button>
                                </div>
                            </article>
                        @empty
                            <div class="team-approval-empty">
                                <x-heroicon-o-clipboard-document-check class="h-8 w-8" />
                                <h3>{{ __('No attendance correction requests found') }}</h3>
                                <p>{{ __('Attendance corrections waiting for review will appear here.') }}</p>
                            </div>
                        @endforelse

                        {{ $attendanceCorrections->links() }}
                    @elseif ($activeTab === 'shift-swaps')
                        @forelse ($shiftSwapRequests as $request)
                            <article class="team-approval-card">
                                <div class="team-approval-card__main">
                                    <img class="team-approval-card__avatar" src="{{ $request->user->profile_photo_url }}" alt="{{ $request->user->name }}">
                                    <div class="team-approval-card__body">
                                        <div class="team-approval-card__topline">
                                            <div>
                                                <h3>{{ $request->user->name }}</h3>
                                                <p>{{ $request->effectiveScheduleDate()?->translatedFormat('d M Y') ?? __('No schedule') }}</p>
                                            </div>
                                            <span class="{{ $statusClass($request->status) }}">{{ __('Pending') }}</span>
                                        </div>

                                        <div class="team-approval-facts">
                                            <div>
                                                <span>{{ __('Current') }}</span>
                                                <strong>{{ $request->currentShift->name ?? __('No current schedule') }}</strong>
                                            </div>
                                            <div>
                                                <span>{{ __('Requested Shift') }}</span>
                                                <strong>{{ $request->requestedShift->name ?? '-' }}</strong>
                                            </div>
                                            <div>
                                                <span>{{ __('Replacement') }}</span>
                                                <strong>{{ $request->replacementUser->name ?? __('Not specified') }}</strong>
                                            </div>
                                        </div>

                                        <p class="team-approval-note">{{ $request->reason }}</p>
                                    </div>
                                </div>

                                <div class="team-approval-actions">
                                    <button type="button" wire:click="rejectShiftSwap('{{ $request->id }}')" class="team-approval-action team-approval-action--reject">
                                        <x-heroicon-o-x-mark class="h-4 w-4" />
                                        <span>{{ __('Reject') }}</span>
                                    </button>
                                    <button type="button" wire:click="approveShiftSwap('{{ $request->id }}')" class="team-approval-action team-approval-action--approve">
                                        <x-heroicon-o-check class="h-4 w-4" />
                                        <span>{{ __('Approve') }}</span>
                                    </button>
                                </div>
                            </article>
                        @empty
                            <div class="team-approval-empty">
                                <x-heroicon-o-arrows-right-left class="h-8 w-8" />
                                <h3>{{ __('No shift swap requests found') }}</h3>
                                <p>{{ __('Shift swap requests waiting for review will appear here.') }}</p>
                            </div>
                        @endforelse

                        {{ $shiftSwapRequests->links() }}
                    @elseif ($activeTab === 'reimbursements')
                        @forelse ($reimbursements as $reimbursement)
                            <article class="team-approval-card">
                                <div class="team-approval-card__main">
                                    <img class="team-approval-card__avatar" src="{{ $reimbursement->user->profile_photo_url }}" alt="{{ $reimbursement->user->name }}">
                                    <div class="team-approval-card__body">
                                        <div class="team-approval-card__topline">
                                            <div>
                                                <h3>{{ $reimbursement->user->name }}</h3>
                                                <p>{{ __(ucfirst((string) $reimbursement->type)) }} · {{ $reimbursement->date?->translatedFormat('d M Y') }}</p>
                                            </div>
                                            <span class="{{ $statusClass($reimbursement->status) }}">{{ __('Pending') }}</span>
                                        </div>

                                        <div class="team-approval-amount">Rp {{ number_format((float) $reimbursement->amount, 0, ',', '.') }}</div>

                                        @if($reimbursement->description)
                                            <p class="team-approval-note">{{ $reimbursement->description }}</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="team-approval-actions">
                                    <button type="button" wire:click="rejectReimbursement('{{ $reimbursement->id }}')" class="team-approval-action team-approval-action--reject">
                                        <x-heroicon-o-x-mark class="h-4 w-4" />
                                        <span>{{ __('Reject') }}</span>
                                    </button>
                                    <button type="button" wire:click="approveReimbursement('{{ $reimbursement->id }}')" class="team-approval-action team-approval-action--approve">
                                        <x-heroicon-o-check class="h-4 w-4" />
                                        <span>{{ __('Approve') }}</span>
                                    </button>
                                </div>
                            </article>
                        @empty
                            <div class="team-approval-empty">
                                <x-heroicon-o-banknotes class="h-8 w-8" />
                                <h3>{{ __('No reimbursement requests found') }}</h3>
                                <p>{{ __('Team reimbursement requests waiting for review will appear here.') }}</p>
                            </div>
                        @endforelse

                        {{ $reimbursements->links() }}
                    @elseif ($activeTab === 'overtimes')
                        @forelse ($overtimes as $overtime)
                            <article class="team-approval-card">
                                <div class="team-approval-card__main">
                                    <img class="team-approval-card__avatar" src="{{ $overtime->user->profile_photo_url }}" alt="{{ $overtime->user->name }}">
                                    <div class="team-approval-card__body">
                                        <div class="team-approval-card__topline">
                                            <div>
                                                <h3>{{ $overtime->user->name }}</h3>
                                                <p>{{ \Carbon\Carbon::parse($overtime->date)->translatedFormat('d M Y') }}</p>
                                            </div>
                                            <span class="{{ $statusClass($overtime->status) }}">{{ __('Pending') }}</span>
                                        </div>

                                        <div class="team-approval-facts">
                                            <div>
                                                <span>{{ __('Start Time') }}</span>
                                                <strong>{{ \Carbon\Carbon::parse($overtime->start_time)->format('H:i') }}</strong>
                                            </div>
                                            <div>
                                                <span>{{ __('End Time') }}</span>
                                                <strong>{{ \Carbon\Carbon::parse($overtime->end_time)->format('H:i') }}</strong>
                                            </div>
                                            <div>
                                                <span>{{ __('Duration') }}</span>
                                                <strong>{{ $overtime->duration_text }}</strong>
                                            </div>
                                        </div>

                                        <p class="team-approval-note">{{ $overtime->reason }}</p>
                                    </div>
                                </div>

                                <div class="team-approval-actions">
                                    <button type="button" wire:click="rejectOvertime('{{ $overtime->id }}')" class="team-approval-action team-approval-action--reject">
                                        <x-heroicon-o-x-mark class="h-4 w-4" />
                                        <span>{{ __('Reject') }}</span>
                                    </button>
                                    <button type="button" wire:click="approveOvertime('{{ $overtime->id }}')" class="team-approval-action team-approval-action--approve">
                                        <x-heroicon-o-check class="h-4 w-4" />
                                        <span>{{ __('Approve') }}</span>
                                    </button>
                                </div>
                            </article>
                        @empty
                            <div class="team-approval-empty">
                                <x-heroicon-o-clock class="h-8 w-8" />
                                <h3>{{ __('No overtime requests found') }}</h3>
                                <p>{{ __('Team overtime requests waiting for review will appear here.') }}</p>
                            </div>
                        @endforelse

                        {{ $overtimes->links() }}
                    @elseif ($activeTab === 'wfh')
                        @forelse ($wfhRequests as $request)
                            <article class="team-approval-card">
                                <div class="team-approval-card__main">
                                    <img class="team-approval-card__avatar" src="{{ $request->user->profile_photo_url }}" alt="{{ $request->user->name }}">
                                    <div class="team-approval-card__body">
                                        <div class="team-approval-card__topline">
                                            <div>
                                                <h3>{{ $request->user->name }}</h3>
                                                <p>{{ $request->date?->translatedFormat('d M Y') }}</p>
                                            </div>
                                            <span class="{{ $statusClass($request->status) }}">{{ __('Pending') }}</span>
                                        </div>

                                        <div class="team-approval-facts">
                                            <div>
                                                <span>{{ __('Start Time') }}</span>
                                                <strong>{{ $request->start_time ?: '--:--' }}</strong>
                                            </div>
                                            <div>
                                                <span>{{ __('End Time') }}</span>
                                                <strong>{{ $request->end_time ?: '--:--' }}</strong>
                                            </div>
                                        </div>

                                        @if($request->location_address)
                                            <p class="team-approval-location">{{ $request->location_address }}</p>
                                        @endif
                                        <p class="team-approval-note">{{ $request->reason }}</p>
                                    </div>
                                </div>

                                <div class="team-approval-actions">
                                    <button type="button" wire:click="rejectWfh('{{ $request->id }}')" class="team-approval-action team-approval-action--reject">
                                        <x-heroicon-o-x-mark class="h-4 w-4" />
                                        <span>{{ __('Reject') }}</span>
                                    </button>
                                    <button type="button" wire:click="approveWfh('{{ $request->id }}')" class="team-approval-action team-approval-action--approve">
                                        <x-heroicon-o-check class="h-4 w-4" />
                                        <span>{{ __('Approve') }}</span>
                                    </button>
                                </div>
                            </article>
                        @empty
                            <div class="team-approval-empty">
                                <x-heroicon-o-home-modern class="h-8 w-8" />
                                <h3>{{ __('No WFH requests found') }}</h3>
                                <p>{{ __('Team WFH requests waiting for review will appear here.') }}</p>
                            </div>
                        @endforelse

                        {{ $wfhRequests->links() }}
                    @else
                        @forelse ($kasbons as $kasbon)
                            <article class="team-approval-card">
                                <div class="team-approval-card__main">
                                    <img class="team-approval-card__avatar" src="{{ $kasbon->user->profile_photo_url }}" alt="{{ $kasbon->user->name }}">
                                    <div class="team-approval-card__body">
                                        <div class="team-approval-card__topline">
                                            <div>
                                                <h3>{{ $kasbon->user->name }}</h3>
                                                <p>{{ __('Salary Deduction Period') }} · {{ \Carbon\Carbon::create()->month($kasbon->payment_month)->translatedFormat('F') }} {{ $kasbon->payment_year }}</p>
                                            </div>
                                            <span class="{{ $statusClass($kasbon->status) }}">{{ __('Pending') }}</span>
                                        </div>

                                        <div class="team-approval-amount">Rp {{ number_format((float) $kasbon->amount, 0, ',', '.') }}</div>

                                        @if($kasbon->purpose)
                                            <p class="team-approval-note">{{ $kasbon->purpose }}</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="team-approval-actions">
                                    <button type="button" wire:click="rejectKasbon('{{ $kasbon->id }}')" class="team-approval-action team-approval-action--reject">
                                        <x-heroicon-o-x-mark class="h-4 w-4" />
                                        <span>{{ __('Reject') }}</span>
                                    </button>
                                    <button type="button" wire:click="approveKasbon('{{ $kasbon->id }}')" class="team-approval-action team-approval-action--approve">
                                        <x-heroicon-o-check class="h-4 w-4" />
                                        <span>{{ __('Approve') }}</span>
                                    </button>
                                </div>
                            </article>
                        @empty
                            <div class="team-approval-empty">
                                <x-heroicon-o-wallet class="h-8 w-8" />
                                <h3>{{ __('No kasbon requests found') }}</h3>
                                <p>{{ __('Team cash advance requests waiting for review will appear here.') }}</p>
                            </div>
                        @endforelse

                        {{ $kasbons->links() }}
                    @endif
                </section>
            </div>
        </div>
    </div>
</div>
