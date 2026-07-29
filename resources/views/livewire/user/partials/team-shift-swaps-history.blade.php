@php
    $shiftSwapStatusClass = fn (?string $status): string => match ($status) {
        'approved' => 'team-approval-status team-approval-status--success',
        'rejected' => 'team-approval-status team-approval-status--danger',
        default => 'team-approval-status team-approval-status--info',
    };
@endphp

<section class="team-approval-list" aria-live="polite">
    @forelse ($shiftSwapRequests as $request)
        <article class="team-approval-card">
            <div class="team-approval-card__main">
                <img
                    class="team-approval-card__avatar"
                    src="{{ $request->user->profile_photo_url }}"
                    alt="{{ $request->user->name }}">

                <div class="team-approval-card__body">
                    <div class="team-approval-card__topline">
                        <div class="min-w-0">
                            <h3>{{ $request->user->name }}</h3>
                            <p>{{ $request->user->jobTitle->name ?? __('N/A') }}</p>
                        </div>

                        <span class="{{ $shiftSwapStatusClass($request->status) }}">
                            {{ $request->statusLabel() }}
                        </span>
                    </div>

                    <div class="team-approval-facts">
                        <div>
                            <span>{{ __('Schedule') }}</span>
                            <strong>{{ $request->effectiveScheduleDate()?->translatedFormat('d M Y') ?? '-' }}</strong>
                        </div>
                        <div>
                            <span>{{ __('Current') }}</span>
                            <strong>{{ $request->currentShift->name ?? __('No current schedule') }}</strong>
                        </div>
                        <div>
                            <span>{{ __('Requested Shift') }}</span>
                            <strong>{{ $request->requestedShift->name ?? '-' }}</strong>
                        </div>
                        <div>
                            <span>{{ __('Processed By') }}</span>
                            <strong>{{ $request->reviewer?->name ?? __('System') }}</strong>
                        </div>
                    </div>

                    @if ($request->reason)
                        <p class="team-approval-note">{{ $request->reason }}</p>
                    @endif

                    @if ($request->rejection_note)
                        <p class="team-approval-note team-approval-note--danger">{{ $request->rejection_note }}</p>
                    @endif
                </div>
            </div>
        </article>
    @empty
        <div class="team-approval-empty">
            <x-heroicon-o-arrows-right-left class="h-10 w-10" />
            <h3>{{ __('No shift swap requests found') }}</h3>
            <p>{{ __('Your shift swap requests are waiting for review.') }}</p>
        </div>
    @endforelse

    @if ($shiftSwapRequests->hasPages())
        <div>
            {{ $shiftSwapRequests->links() }}
        </div>
    @endif
</section>
