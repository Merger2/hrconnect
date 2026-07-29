<div class="user-page-shell team-kasbon-page" wire:poll.visible.20s>
    @php
        $statusOptions = [
            'pending' => __('Pending'),
            'pending_finance' => __('Pending Finance'),
            'approved' => __('Approved'),
            'paid' => __('Paid'),
            'rejected' => __('Rejected'),
            'all' => __('All statuses'),
        ];

        $statusLabel = fn (?string $status): string => match ($status) {
            'pending_finance' => __('Pending Finance'),
            'approved' => __('Approved'),
            'paid' => __('Paid'),
            'rejected' => __('Rejected'),
            default => __('Pending'),
        };

        $statusClass = fn (?string $status): string => match ($status) {
            'approved', 'paid' => 'team-approval-status team-approval-status--success',
            'rejected' => 'team-approval-status team-approval-status--danger',
            'pending_finance' => 'team-approval-status team-approval-status--info',
            default => 'team-approval-status team-approval-status--warning',
        };

        $activeTotal = $activeTab === 'users'
            ? (method_exists($userGrouped, 'total') ? $userGrouped->total() : $userGrouped->count())
            : (method_exists($advances, 'total') ? $advances->total() : $advances->count());
    @endphp

    <div class="user-page-container user-page-container--wide">
        <div class="user-page-surface team-approval-surface">
            <x-user.page-header
                :back-href="route('home')"
                :title="__('Team Kasbon')"
                title-id="team-kasbon-title"
                class="border-b-0">
                <x-slot name="icon">
                    <x-heroicon-o-wallet class="h-5 w-5" />
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body pt-0">
                <section class="team-approval-overview" aria-labelledby="team-kasbon-overview-title">
                    <div class="team-approval-overview__copy">
                        <p class="team-approval-overview__eyebrow">{{ __('Finance Review') }}</p>
                        <h2 id="team-kasbon-overview-title">
                            {{ $activeTab === 'users' ? __('Employee Balances') : __('Kasbon Requests') }}
                        </h2>
                        <p>{{ __('Review team cash advance requests and payroll deduction targets.') }}</p>
                    </div>
                    <div class="team-approval-overview__count" aria-label="{{ __('Team Kasbon') }}">
                        <strong>{{ $activeTotal }}</strong>
                        <span>{{ $activeTab === 'users' ? __('Employees') : __('Requests') }}</span>
                    </div>
                </section>

                <section class="team-approval-toolbar" aria-label="{{ __('Filter approvals') }}">
                    <label class="team-approval-search" for="team-kasbon-search">
                        <x-heroicon-o-magnifying-glass class="h-5 w-5" />
                        <input
                            id="team-kasbon-search"
                            wire:model.live.debounce.300ms="search"
                            type="search"
                            placeholder="{{ __('Search employee...') }}"
                            autocomplete="off" />
                    </label>

                    <nav class="team-approval-tabs" aria-label="{{ __('Kasbon views') }}">
                        <button
                            type="button"
                            wire:click="switchTab('requests')"
                            class="team-approval-tab"
                            aria-label="{{ __('All Requests') }}"
                            aria-selected="{{ $activeTab === 'requests' ? 'true' : 'false' }}">
                            <span class="team-approval-tab__icon" aria-hidden="true">
                                <x-heroicon-o-document-text class="h-4 w-4" />
                            </span>
                            {{ __('All Requests') }}
                        </button>
                        <button
                            type="button"
                            wire:click="switchTab('users')"
                            class="team-approval-tab"
                            aria-label="{{ __('Group by Employee') }}"
                            aria-selected="{{ $activeTab === 'users' ? 'true' : 'false' }}">
                            <span class="team-approval-tab__icon" aria-hidden="true">
                                <x-heroicon-o-users class="h-4 w-4" />
                            </span>
                            {{ __('Group by Employee') }}
                        </button>
                    </nav>

                    @if ($activeTab === 'requests')
                        <div class="team-kasbon-status-filters" aria-label="{{ __('Cash advance status') }}">
                            @foreach ($statusOptions as $status => $label)
                                <button
                                    type="button"
                                    wire:click="setStatusFilter('{{ $status }}')"
                                    class="team-kasbon-status-chip"
                                    aria-pressed="{{ $statusFilter === $status ? 'true' : 'false' }}">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                </section>

                @if ($activeTab === 'requests')
                    <section class="team-approval-list" aria-live="polite">
                        @forelse($advances as $advance)
                            @php
                                $canApprove = in_array($advance->status, ['pending', 'pending_finance'], true)
                                    && Auth::user()->can('approve', $advance);
                                $deductionTarget = \Carbon\Carbon::create()
                                    ->month((int) $advance->payment_month)
                                    ->translatedFormat('F').' '.$advance->payment_year;
                            @endphp

                            <article class="team-approval-card team-kasbon-card">
                                <div class="team-approval-card__main">
                                    <img class="team-approval-card__avatar" src="{{ $advance->user->profile_photo_url }}" alt="{{ $advance->user->name }}">

                                    <div class="team-approval-card__body">
                                        <div class="team-approval-card__topline">
                                            <div class="min-w-0">
                                                <h3>{{ $advance->user->name }}</h3>
                                                <p>{{ $advance->user->jobTitle->name ?? __('Employee') }}</p>
                                            </div>
                                            <span class="{{ $statusClass($advance->status) }}">{{ $statusLabel($advance->status) }}</span>
                                        </div>

                                        <div class="team-approval-amount">
                                            {{ __('Rp') }} {{ number_format((float) $advance->amount, 0, ',', '.') }}
                                        </div>

                                        <div class="team-approval-facts">
                                            <div>
                                                <span>{{ __('Request Date') }}</span>
                                                <strong>{{ $advance->created_at->translatedFormat('d M Y') }}</strong>
                                            </div>
                                            <div>
                                                <span>{{ __('Deduction Target') }}</span>
                                                <strong>{{ $deductionTarget }}</strong>
                                            </div>
                                        </div>

                                        @if ($advance->purpose)
                                            <p class="team-approval-note">{{ $advance->purpose }}</p>
                                        @endif

                                        @if($advance->head_approved_by || $advance->finance_approved_by || $advance->approved_by)
                                            <div class="team-kasbon-approval-path">
                                                <span>{{ __('Approval Path') }}</span>
                                                <div>
                                                    @if($advance->head_approved_by)
                                                        <strong>{{ __('Head') }}:</strong> {{ $advance->headApprover->name ?? '-' }}
                                                    @endif
                                                    @if($advance->finance_approved_by || $advance->approved_by)
                                                        <strong>{{ __('Finance') }}:</strong> {{ $advance->financeApprover->name ?? $advance->approver->name ?? '-' }}
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="team-approval-actions">
                                    @if($canApprove)
                                        <button type="button" wire:click="reject('{{ $advance->id }}')" wire:confirm="{{ __('Reject this request?') }}" class="team-approval-action team-approval-action--reject">
                                            <x-heroicon-m-x-circle class="h-5 w-5" />
                                            {{ __('Reject') }}
                                        </button>
                                        <button type="button" wire:click="approve('{{ $advance->id }}')" wire:confirm="{{ __('Approve this request?') }}" class="team-approval-action team-approval-action--approve">
                                            <x-heroicon-m-check-circle class="h-5 w-5" />
                                            {{ __('Approve') }}
                                        </button>
                                    @else
                                        <span class="team-kasbon-processed">
                                            {{ $advance->status === 'paid' ? __('Deducted') : __('Processed') }}
                                        </span>
                                    @endif

                                    @if(Auth::user()->can('delete', $advance))
                                        <button type="button" wire:click="delete('{{ $advance->id }}')" wire:confirm="{{ __('Delete permanently?') }}" class="team-approval-action team-approval-action--reject">
                                            <x-heroicon-m-trash class="h-5 w-5" />
                                            {{ __('Delete') }}
                                        </button>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <div class="team-approval-empty">
                                <x-heroicon-o-wallet class="h-10 w-10" />
                                <h3>{{ __('No cash advance data found.') }}</h3>
                                <p>{{ __('No cash advance requests match your current filters.') }}</p>
                            </div>
                        @endforelse

                        @if($advances->hasPages())
                            <div>
                                {{ $advances->links() }}
                            </div>
                        @endif
                    </section>
                @else
                    <section class="team-approval-list" aria-live="polite">
                        @forelse($userGrouped as $user)
                            @php
                                $activeAdvances = $user->cashAdvances->whereIn('status', ['paid', 'approved', 'pending', 'pending_finance']);
                                $groupedByMonth = $activeAdvances
                                    ->groupBy(function ($item) {
                                        return $item->payment_year.'-'.str_pad($item->payment_month, 2, '0', STR_PAD_LEFT);
                                    })
                                    ->sortKeysDesc();
                            @endphp

                            <article class="team-approval-card team-kasbon-card">
                                <div class="team-approval-card__main">
                                    <img class="team-approval-card__avatar" src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}">

                                    <div class="team-approval-card__body">
                                        <div class="team-approval-card__topline">
                                            <div class="min-w-0">
                                                <h3>{{ $user->name }}</h3>
                                                <p>{{ $user->jobTitle->name ?? __('Employee') }}</p>
                                            </div>
                                            <span class="team-approval-status team-approval-status--info">
                                                {{ $activeAdvances->count() }} {{ __('Requests') }}
                                            </span>
                                        </div>

                                        <div class="team-approval-amount">
                                            {{ __('Rp') }} {{ number_format($activeAdvances->sum('amount'), 0, ',', '.') }}
                                        </div>

                                        <div class="team-kasbon-months">
                                            <span>{{ __('Deduction Breakdown') }}</span>
                                            @forelse($groupedByMonth as $key => $items)
                                                <div class="team-kasbon-month-row">
                                                    <strong>{{ \Carbon\Carbon::createFromFormat('Y-m', $key)->translatedFormat('M Y') }}</strong>
                                                    <span>{{ __('Rp') }} {{ number_format($items->sum('amount'), 0, ',', '.') }}</span>
                                                </div>
                                            @empty
                                                <p>{{ __('No kasbon data found.') }}</p>
                                            @endforelse
                                        </div>

                                        <div class="team-kasbon-history">
                                            <span>{{ __('Recent History') }}</span>
                                            @foreach($user->cashAdvances->sortByDesc('created_at')->take(3) as $hist)
                                                <div class="team-kasbon-history__item">
                                                    <div>
                                                        <strong>{{ __('Rp') }} {{ number_format($hist->amount, 0, ',', '.') }}</strong>
                                                        <small>{{ $hist->created_at->translatedFormat('d M') }} · {{ __('Deduction') }} {{ \Carbon\Carbon::create()->month((int) $hist->payment_month)->translatedFormat('F') }}</small>
                                                    </div>
                                                    <span class="{{ $statusClass($hist->status) }}">{{ $statusLabel($hist->status) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="team-approval-empty">
                                <x-heroicon-o-users class="h-10 w-10" />
                                <h3>{{ __('No kasbon data found.') }}</h3>
                                <p>{{ __('No employee cash advance summaries are available right now.') }}</p>
                            </div>
                        @endforelse

                        @if($userGrouped->hasPages())
                            <div>
                                {{ $userGrouped->links() }}
                            </div>
                        @endif
                    </section>
                @endif
            </div>
        </div>
    </div>
</div>
