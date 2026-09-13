@props(['hasCheckedIn', 'hasCheckedOut', 'attendance', 'hasApprovedOvertime' => false, 'shiftSummary' => []])

@php
    $shiftName = data_get($shiftSummary, 'is_off')
        ? __('Off Day')
        : (data_get($shiftSummary, 'name') ?: __('No shift assigned'));
    $shiftStart = data_get($shiftSummary, 'start');
    $shiftEnd = data_get($shiftSummary, 'end');
    $shiftDuration = data_get($shiftSummary, 'duration');
    $workHours = $shiftStart && $shiftEnd ? "{$shiftStart} - {$shiftEnd}" : __('Flexible');
@endphp

<section aria-labelledby="attendance-card-title" class="attendance-panel">
    <div class="attendance-panel__header">
        <div class="min-w-0">
            <p class="attendance-panel__eyebrow">{{ __('Attendance') }}</p>
            <h2 id="attendance-card-title" class="attendance-panel__title">
                {{ __('Today') }}
            </h2>
            <p class="attendance-panel__copy">{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</p>
        </div>

        <div class="attendance-panel__badge attendance-panel__badge--live" role="status" aria-live="polite">
            <span class="relative flex h-2 w-2">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-primary-400 opacity-75"></span>
                <span class="relative inline-flex h-2 w-2 rounded-full bg-primary-700"></span>
            </span>
            <span>{{ __('Live') }}</span>
        </div>
    </div>

    <div class="attendance-panel__worktime" aria-label="{{ __('Working hours') }}">
        <div class="attendance-panel__worktime-icon">
            <x-heroicon-o-calendar-days class="h-4 w-4" />
        </div>
        <div class="min-w-0">
            <p class="attendance-panel__worktime-label">{{ $shiftName }}</p>
            <p class="attendance-panel__worktime-copy">
                {{ __('Working hours') }}: <span class="font-semibold text-slate-950">{{ $workHours }}</span>
                @if ($shiftDuration)
                    <span class="text-slate-400">•</span>
                    {{ $shiftDuration }}
                @endif
            </p>
        </div>
    </div>

    <div class="attendance-panel__timeline" role="list" aria-label="{{ __('Today attendance times') }}">
        <article class="attendance-panel__step {{ $hasCheckedIn ? 'is-complete' : 'is-current' }}" role="listitem">
            <span class="attendance-panel__step-icon attendance-panel__step-icon--in">
                @if ($hasCheckedIn)
                    <x-heroicon-o-check class="h-4 w-4" />
                @else
                    <x-heroicon-o-arrow-left-on-rectangle class="h-4 w-4" />
                @endif
            </span>

            <div class="attendance-panel__step-body">
                <div>
                    <p class="attendance-panel__step-label">{{ __('Check In') }}</p>
                    <p class="attendance-panel__step-copy">
                        {{ $attendance?->time_in ? __('Already recorded') : __('Start your day') }}
                    </p>
                </div>
                <span class="attendance-panel__step-time">
                    {{ $attendance?->time_in ? \Carbon\Carbon::parse($attendance->time_in)->format('H:i') : '--:--' }}
                </span>
            </div>
        </article>

        <article class="attendance-panel__step {{ $hasCheckedOut ? 'is-complete' : ($hasCheckedIn ? 'is-current' : 'is-locked') }}" role="listitem">
            <span class="attendance-panel__step-icon attendance-panel__step-icon--out">
                @if ($hasCheckedOut)
                    <x-heroicon-o-check class="h-4 w-4" />
                @else
                    <x-heroicon-o-arrow-right-on-rectangle class="h-4 w-4" />
                @endif
            </span>

            <div class="attendance-panel__step-body">
                <div>
                    <p class="attendance-panel__step-label">{{ __('Check Out') }}</p>
                    <p class="attendance-panel__step-copy">
                        {{ $attendance?->time_out ? __('Already recorded') : ($hasCheckedIn ? __('Complete today') : __('Available after check in')) }}
                    </p>
                </div>
                <span class="attendance-panel__step-time">
                    {{ $attendance?->time_out ? \Carbon\Carbon::parse($attendance->time_out)->format('H:i') : '--:--' }}
                </span>
            </div>
        </article>
    </div>

    @if ($hasCheckedIn && !$hasCheckedOut)
        @php
            $shiftEndTime = ($attendance && $attendance->shift)
                ? \Carbon\Carbon::parse($attendance->date)->format('Y-m-d') . ' ' . $attendance->shift->end_time
                : null;
        @endphp

        <div x-data="window.shiftCountdown('{{ $shiftEndTime }}', @js((bool) $hasApprovedOvertime))" class="attendance-panel__helper">
            <template x-if="endTime && remaining > 0">
                <p>
                    {{ __('Shift ends in') }}:
                    <span class="font-mono font-bold text-primary-600" x-text="formatted"></span>
                </p>
            </template>
            <template x-if="endTime && remaining <= 0">
                <p
                    class="animate-pulse"
                    :class="hasApprovedOvertime ? 'text-amber-500' : 'text-orange-500'">
                    <span x-text="hasApprovedOvertime ? '{{ __('Overtime') }}' : '{{ __('Clock Out Pending') }}'"></span>
                </p>
            </template>
            <template x-if="!endTime">
                <p>{{ __('Don\'t forget to clock out when you\'re done.') }}</p>
            </template>
        </div>

@pushOnce('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('shiftCountdown', (initialEndTime, hasApprovedOvertime) => ({
            endTime: null,
            now: new Date().getTime(),
            remaining: 0,
            timer: null,
            hasApprovedOvertime,

            init() {
                if (initialEndTime) {
                    try {
                        let target = new Date(initialEndTime);
                        if (!isNaN(target.getTime())) {
                            this.endTime = target.getTime();
                            this.startTimer();
                        }
                    } catch (e) {
                        console.error('Timer init error', e);
                    }
                }
            },

            startTimer() {
                this.check();
                this.timer = setInterval(() => this.check(), 1000);
            },

            check() {
                this.now = new Date().getTime();
                this.remaining = this.endTime - this.now;
            },

            get formatted() {
                if (!this.endTime) return '--:--:--';
                if (this.remaining < 0) {
                    return this.hasApprovedOvertime ? '{{ __('Overtime') }}' : '{{ __('Clock Out Pending') }}';
                }

                let diff = this.remaining;
                let hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                let minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                let seconds = Math.floor((diff % (1000 * 60)) / 1000);

                return String(hours).padStart(2, '0') + ':' +
                    String(minutes).padStart(2, '0') + ':' +
                    String(seconds).padStart(2, '0');
            }
        }));
    });
</script>
@endpushOnce
    @endif
</section>
