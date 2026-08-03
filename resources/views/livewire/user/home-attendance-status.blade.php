<div wire:poll.30s="checkAttendanceStatus">
    @if ($approvedAbsence)
        <section aria-labelledby="attendance-status-date" class="attendance-panel">
            <div class="attendance-panel__header">
                <div class="min-w-0">
                    <p class="attendance-panel__eyebrow">{{ __('Your Status') }}</p>
                    <h2 id="attendance-status-date" class="attendance-panel__title">
                        {{ $approvedAbsence->date->translatedFormat('l, d F Y') }}
                    </h2>
                    <p class="attendance-panel__copy">{{ __('Today\'s attendance has been recorded completely.') }}</p>
                </div>

                <div class="attendance-panel__badge attendance-panel__badge--done" role="status" aria-live="polite">
                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-gradient-to-br from-primary-600 to-brand-500 text-white">
                        <x-heroicon-o-check class="h-3 w-3" />
                    </span>
                    <span>{{ __(ucfirst($approvedAbsence->status)) }}</span>
                </div>
            </div>

            <div class="attendance-panel__summary">
                <div class="attendance-panel__summary-icon">
                    <x-heroicon-o-document-text class="h-5 w-5" />
                </div>
                <div>
                    <h3 class="attendance-panel__summary-title">{{ __('Note') }}</h3>
                    <p class="attendance-panel__summary-copy">
                        {{ $approvedAbsence->note ?: __('Approved') }}
                    </p>
                </div>
            </div>
        </section>
    @elseif($requiresFaceEnrollment)
        <section aria-labelledby="face-enrollment-heading"
            class="attendance-panel">
            <div class="attendance-panel__header">
                <div class="min-w-0 flex-1">
                    <p class="attendance-panel__eyebrow">{{ __('Attendance') }}</p>
                    <h2 id="face-enrollment-heading" class="attendance-panel__title">
                        {{ __('Face ID Registration Required') }}
                    </h2>
                    <p class="attendance-panel__copy">
                        {{ __('To ensure security, you must register your face data before you can clock in/out.') }}
                    </p>
                </div>

                <div class="attendance-panel__badge attendance-panel__badge--live" aria-hidden="true">
                    <x-heroicon-o-face-smile class="h-4 w-4" />
                    <span>{{ __('Face ID') }}</span>
                </div>
            </div>

            <div class="attendance-panel__worktime" aria-hidden="true">
                <div class="attendance-panel__worktime-icon">
                    <x-heroicon-o-shield-check class="h-4 w-4" />
                </div>
                <div class="min-w-0">
                    <p class="attendance-panel__worktime-label">{{ __('Secure attendance') }}</p>
                    <p class="attendance-panel__worktime-copy">{{ __('Register Face ID Now') }}</p>
                </div>
            </div>

            @if (false)
                <button type="button"
                    aria-label="{{ __('Register Face ID Now') }}"
                    @click.prevent="$dispatch('feature-lock', { title: @js(__('Face ID Locked')), message: @js(__('Face ID Biometrics is an Enterprise Feature. Please Upgrade.')) })"
                    class="attendance-panel__cta attendance-panel__cta--primary">
                    <span class="attendance-panel__cta-icon">
                        <x-heroicon-o-lock-closed class="h-5 w-5" />
                    </span>
                    <span class="min-w-0">
                        <span class="attendance-panel__cta-label">{{ __('Register Face ID Now') }}</span>
                        <span class="attendance-panel__cta-copy">{{ __('Face ID Locked') }}</span>
                    </span>
                </button>
            @else
                <a href="{{ route('face.enrollment') }}"
                    aria-label="{{ __('Register Face ID Now') }}"
                    class="attendance-panel__cta attendance-panel__cta--primary">
                    <span class="attendance-panel__cta-icon">
                        <x-heroicon-o-camera class="h-5 w-5" />
                    </span>
                    <span class="min-w-0">
                        <span class="attendance-panel__cta-label">{{ __('Register Face ID Now') }}</span>
                        <span class="attendance-panel__cta-copy">{{ __('Secure attendance') }}</span>
                    </span>
                </a>
            @endif
        </section>
    @elseif($hasCheckedIn && $hasCheckedOut)
        <x-user.attendance-hero-card :attendance="$attendance" />
    @else
        <x-user.home-actions-card :hasCheckedIn="$hasCheckedIn" :hasCheckedOut="$hasCheckedOut" :attendance="$attendance" :hasApprovedOvertime="$hasApprovedOvertime" :shiftSummary="$todayShiftSummary" />
    @endif
</div>
