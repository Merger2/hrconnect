@php
    $correctionStatusClass = fn (?string $status): string => match ($status) {
        'approved' => 'team-approval-status team-approval-status--success',
        'rejected' => 'team-approval-status team-approval-status--danger',
        default => 'team-approval-status team-approval-status--info',
    };
@endphp

<section class="team-approval-list" aria-live="polite">
    @forelse ($attendanceCorrections as $correction)
        <article class="team-approval-card">
            <div class="team-approval-card__main">
                <img
                    class="team-approval-card__avatar"
                    src="{{ $correction->user->profile_photo_url }}"
                    alt="{{ $correction->user->name }}">

                <div class="team-approval-card__body">
                    <div class="team-approval-card__topline">
                        <div class="min-w-0">
                            <h3>{{ $correction->user->name }}</h3>
                            <p>{{ $correction->user->jobTitle->name ?? __('N/A') }}</p>
                        </div>

                        <span class="{{ $correctionStatusClass($correction->status) }}">
                            {{ $correction->statusLabel() }}
                        </span>
                    </div>

                    <div class="team-approval-facts">
                        <div>
                            <span>{{ __('Request') }}</span>
                            <strong>{{ $correction->requestTypeLabel() }}</strong>
                        </div>
                        <div>
                            <span>{{ __('Attendance Date') }}</span>
                            <strong>{{ $correction->attendance_date->translatedFormat('d M Y') }}</strong>
                        </div>
                        <div>
                            <span>{{ __('Processed By') }}</span>
                            <strong>
                                {{ $correction->status === 'pending_admin'
                                    ? ($correction->headApprover?->name ?? __('Supervisor'))
                                    : ($correction->reviewer?->name ?? __('System')) }}
                            </strong>
                        </div>
                        <div>
                            <span>{{ __('Submitted') }}</span>
                            <strong>{{ $correction->created_at->diffForHumans() }}</strong>
                        </div>
                    </div>

                    @if ($correction->requested_time_in || $correction->requested_time_out || $correction->requestedShift)
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
                    @endif

                    @if ($correction->reason)
                        <p class="team-approval-note">{{ $correction->reason }}</p>
                    @endif
                </div>
            </div>
        </article>
    @empty
        <div class="team-approval-empty">
            <x-heroicon-o-clipboard-document-check class="h-10 w-10" />
            <h3>{{ __('No attendance correction history found') }}</h3>
            <p>{{ __('Attendance corrections waiting for review.') }}</p>
        </div>
    @endforelse

    @if ($attendanceCorrections->hasPages())
        <div>
            {{ $attendanceCorrections->links() }}
        </div>
    @endif
</section>
