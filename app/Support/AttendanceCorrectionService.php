<?php

namespace App\Support;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\Employee;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\User;
use App\Notifications\AttendanceCorrectionStatusUpdated;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceCorrectionService
{
    public function __construct(
        protected ApprovalActorService $approvalActors,
    ) {}

    public function submit(User $user, array $payload): AttendanceCorrection
    {
        $attendanceDate = Carbon::parse($payload['attendance_date'])->toDateString();
        $attendance = Attendance::query()
            ->whereHas('employee', fn ($q) => $q->where('user_id', $user->id))
            ->whereDate('date', $attendanceDate)
            ->first();

        return AttendanceCorrection::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance?->id,
            'attendance_date' => $attendanceDate,
            'request_type' => $payload['request_type'],
            'requested_time_in' => $this->nullableDateTime($payload['requested_time_in'] ?? null),
            'requested_time_out' => $this->nullableDateTime($payload['requested_time_out'] ?? null),
            'requested_shift_id' => $payload['requested_shift_id'] ?: null,
            'current_snapshot' => $this->snapshot($attendance),
            'reason' => trim((string) $payload['reason']),
            'status' => $this->needsSupervisorReview($user)
                ? AttendanceCorrection::STATUS_PENDING
                : AttendanceCorrection::STATUS_PENDING_ADMIN,
        ]);
    }

    public function managementQuery(User $actor, string $statusFilter = 'pending', string $typeFilter = 'all', string $search = ''): Builder
    {
        return AttendanceCorrection::query()
            ->with(['user.jobTitle', 'attendance.shift', 'requestedShift', 'headApprover', 'reviewer'])
            ->when(! $actor->can('manageAttendanceCorrections'), function (Builder $query) use ($actor) {
                $query->whereIn('user_id', $this->approvalActors->subordinateIds($actor))
                    ->where('status', AttendanceCorrection::STATUS_PENDING);
            })
            ->when($statusFilter !== 'all', fn (Builder $query) => $query->where('status', $statusFilter))
            ->when($typeFilter !== 'all', fn (Builder $query) => $query->where('request_type', $typeFilter))
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $nested) use ($search) {
                    $nested->where('reason', 'like', '%'.$search.'%')
                        ->orWhereHas('user', function (Builder $userQuery) use ($search) {
                            $userQuery->where('name', 'like', '%'.$search.'%')
                                ->orWhere('nip', 'like', '%'.$search.'%');
                        });
                });
            })
            ->orderByRaw("CASE WHEN status = 'pending_admin' THEN 0 WHEN status = 'pending' THEN 1 ELSE 2 END")
            ->orderByDesc('attendance_date')
            ->orderByDesc('created_at');
    }

    public function approve(AttendanceCorrection $correction, User $actor): string
    {
        $message = DB::transaction(function () use ($correction, $actor): string {
            $correction = $this->lock($correction);

            if (! $actor->can('manageAttendanceCorrections')) {
                if (! $this->canSupervisorReview($correction, $actor) || $correction->status !== AttendanceCorrection::STATUS_PENDING) {
                    throw new AuthorizationException;
                }

                $correction->update([
                    'status' => AttendanceCorrection::STATUS_PENDING_ADMIN,
                    'head_approved_by' => $actor->id,
                    'head_approved_at' => now(),
                    'rejection_note' => null,
                ]);

                return __('Attendance correction forwarded to admin for final review.');
            }

            $this->ensureReviewable($correction, [
                AttendanceCorrection::STATUS_PENDING,
                AttendanceCorrection::STATUS_PENDING_ADMIN,
            ]);

            $correction->loadMissing(['user', 'attendance.shift', 'requestedShift']);

            $employeeId = $correction->user->employee->id
                ?? Employee::where('user_id', $correction->user_id)->value('id');

            $attendance = $correction->attendance ?? Attendance::query()->firstOrNew([
                'employee_id' => $employeeId,
                'date' => $correction->attendance_date->toDateString(),
            ]);

            $attendance->employee_id = $employeeId;
            $attendance->date = $correction->attendance_date->toDateString();

            if ($correction->requested_shift_id) {
                $attendance->shift_id = $correction->requested_shift_id;
            }

            if ($correction->requested_time_in) {
                $attendance->time_in = $correction->requested_time_in;
            }

            if ($correction->requested_time_out) {
                $attendance->time_out = $correction->requested_time_out;
            }

            $attendance->status = $this->resolvedStatus(
                $attendance->time_in ? Carbon::parse($attendance->time_in) : null,
                $correction->requestedShift ?? $attendance->shift,
                (int) Setting::getValue('attendance.grace_period', 10),
                $attendance->status,
            );

            $attendance->save();

            $correction->update([
                'attendance_id' => $attendance->id,
                'status' => AttendanceCorrection::STATUS_APPROVED,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'rejection_note' => null,
            ]);

            Attendance::clearUserAttendanceCache($correction->user, Carbon::parse($correction->attendance_date));

            return __('Attendance correction approved and applied.');
        });

        $this->notifyStatusUpdated($correction);

        return $message;
    }

    public function reject(AttendanceCorrection $correction, User $actor, ?string $note = null): string
    {
        DB::transaction(function () use ($correction, $actor, $note): void {
            $correction = $this->lock($correction);

            if (! $actor->can('manageAttendanceCorrections')) {
                if (! $this->canSupervisorReview($correction, $actor) || $correction->status !== AttendanceCorrection::STATUS_PENDING) {
                    throw new AuthorizationException;
                }
            } else {
                $this->ensureReviewable($correction, [
                    AttendanceCorrection::STATUS_PENDING,
                    AttendanceCorrection::STATUS_PENDING_ADMIN,
                ]);
            }

            $correction->update([
                'status' => AttendanceCorrection::STATUS_REJECTED,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'rejection_note' => $note,
            ]);
        });

        $this->notifyStatusUpdated($correction);

        return __('Attendance correction rejected.');
    }

    /**
     * Normalize a stored attendance time into a full datetime on the correct
     * calendar day, accounting for overnight shifts on the check-out side.
     */
    public function snapshotDateTime(?Attendance $attendance, string $direction, string $fallbackDate): ?Carbon
    {
        if (! $attendance) {
            return null;
        }

        $rawValue = $direction === 'in' ? $attendance->time_in : $attendance->time_out;

        if (! $rawValue) {
            return null;
        }

        $baseDate = Carbon::parse($attendance->date ?? $fallbackDate);
        $rawDateTime = Carbon::parse($rawValue);
        $normalized = $baseDate->copy()->setTime(
            (int) $rawDateTime->format('H'),
            (int) $rawDateTime->format('i'),
            (int) $rawDateTime->format('s')
        );

        if ($direction !== 'out') {
            return $normalized;
        }

        $normalizedTimeIn = $this->snapshotDateTime($attendance, 'in', $fallbackDate);

        if ($attendance->shift?->is_overnight || ($normalizedTimeIn && $normalized->lessThanOrEqualTo($normalizedTimeIn))) {
            $normalized->addDay();
        }

        return $normalized;
    }

    /**
     * Compute the default check-in/out datetime to prefill a correction form:
     * the existing snapshot when present, otherwise the shift boundary.
     */
    public function defaultRequestedDateTime(?Attendance $attendance, string $attendanceDate, int|string|null $requestedShiftId, string $direction): string
    {
        $date = Carbon::parse($attendanceDate);

        $snapshotValue = $this->snapshotDateTime($attendance, $direction, $attendanceDate);

        if ($snapshotValue) {
            return $snapshotValue->format('Y-m-d H:i');
        }

        $shift = $attendance?->shift
            ?? ($requestedShiftId ? Shift::query()->find($requestedShiftId) : null);

        if ($direction === 'in') {
            return $date->copy()
                ->setTimeFromTimeString($shift?->start_time ?: '08:00:00')
                ->format('Y-m-d H:i');
        }

        $defaultOut = $date->copy()->setTimeFromTimeString($shift?->end_time ?: '17:00:00');

        if ($shift?->is_overnight) {
            $defaultOut->addDay();
        }

        return $defaultOut->format('Y-m-d H:i');
    }

    /**
     * Infer the correction request type from the requested times, the existing
     * attendance record, and whether a shift change was requested.
     */
    public function inferRequestType(?Attendance $attendance, ?Carbon $requestedTimeIn, ?Carbon $requestedTimeOut, bool $includeShift): string
    {
        if ($includeShift && ! $requestedTimeIn && ! $requestedTimeOut) {
            return AttendanceCorrection::TYPE_WRONG_SHIFT;
        }

        if ($requestedTimeIn && $requestedTimeOut) {
            return AttendanceCorrection::TYPE_WRONG_TIME;
        }

        if ($requestedTimeIn) {
            return $attendance?->time_in
                ? AttendanceCorrection::TYPE_WRONG_TIME
                : AttendanceCorrection::TYPE_MISSING_CHECK_IN;
        }

        if ($requestedTimeOut) {
            return $attendance?->time_out
                ? AttendanceCorrection::TYPE_WRONG_TIME
                : AttendanceCorrection::TYPE_MISSING_CHECK_OUT;
        }

        return AttendanceCorrection::TYPE_WRONG_SHIFT;
    }

    private function snapshot(?Attendance $attendance): ?array
    {
        if (! $attendance) {
            return null;
        }

        return [
            'status' => $attendance->status,
            'shift_id' => $attendance->shift_id,
            'shift_name' => $attendance->shift?->name,
            'time_in' => $attendance->time_in?->format('Y-m-d H:i:s'),
            'time_out' => $attendance->time_out?->format('Y-m-d H:i:s'),
        ];
    }

    private function nullableDateTime(mixed $value): ?Carbon
    {
        if (! filled($value)) {
            return null;
        }

        return Carbon::parse((string) $value);
    }

    private function resolvedStatus(?Carbon $timeIn, ?Shift $shift, int $gracePeriod, ?string $fallback): string
    {
        if (! $timeIn) {
            return $fallback ?: 'present';
        }

        if (! $shift) {
            return 'present';
        }

        $shiftStart = Carbon::parse($shift->start_time)->setDate($timeIn->year, $timeIn->month, $timeIn->day);

        return $timeIn->gt($shiftStart->copy()->addMinutes($gracePeriod)) ? 'late' : 'present';
    }

    private function needsSupervisorReview(User $user): bool
    {
        return (bool) optional($user->supervisor)->id;
    }

    private function canSupervisorReview(AttendanceCorrection $correction, User $actor): bool
    {
        return $this->approvalActors->subordinateIds($actor)->contains($correction->user_id);
    }

    private function notifyStatusUpdated(AttendanceCorrection $correction): void
    {
        $correction->refresh()->loadMissing('user');
        $correction->user?->notify(new AttendanceCorrectionStatusUpdated($correction));
    }

    private function lock(AttendanceCorrection $correction): AttendanceCorrection
    {
        return AttendanceCorrection::query()
            ->whereKey($correction->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * @param  list<string>  $allowedStatuses
     */
    private function ensureReviewable(AttendanceCorrection $correction, array $allowedStatuses): void
    {
        if (! in_array((string) $correction->status, $allowedStatuses, true)) {
            throw new AuthorizationException(__('This attendance correction has already been reviewed.'));
        }
    }
}
