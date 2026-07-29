<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\Contracts\AttendanceServiceInterface;
use App\Domain\Attendance\ValueObjects\LeaveRequestResult;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\LeaveType;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\LeaveRequested;
use App\Notifications\LeaveRequestedEmail;
use App\Support\LeaveEntitlementService;
use App\Support\UserNotificationRecipientService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class LeaveRequestService
{
    public function __construct(
        protected AttendanceServiceInterface $attendanceService,
        protected LeaveEntitlementService $leaveEntitlements,
        protected UserNotificationRecipientService $notificationRecipients,
    ) {}

    public function getApplyLeaveData(User $user): array
    {
        $attendance = Attendance::query()
            ->where('employee_id', $user->employee?->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        $requireAttachment = Setting::getValue('leave.require_attachment', '1') === '1';
        $leaveTypes = LeaveType::query()
            ->active()
            ->ordered()
            ->get();
        $annualSummary = $this->leaveEntitlements->summaryFor($user, $leaveTypes->firstWhere('deducts_from_quota', true));

        return [
            'attendance' => $attendance,
            'annualQuota' => (int) floor($annualSummary['total_allocated']),
            'usedExcused' => $annualSummary['used_days'],
            'remainingExcused' => (int) floor($annualSummary['remaining_days']),
            'annualLeaveExpiresAt' => $annualSummary['expires_at'],
            'annualLeaveExpired' => $annualSummary['is_expired'],
            'leaveEntitlements' => $this->leaveEntitlements->quotaSummariesFor($user),
            'requireAttachment' => $requireAttachment,
            'leaveTypes' => $leaveTypes,
        ];
    }

    public function submitLeaveRequest(
        User $user,
        string $status,
        string $note,
        Carbon $fromDate,
        Carbon $toDate,
        ?UploadedFile $attachment = null,
        ?float $lat = null,
        ?float $lng = null,
        ?LeaveType $leaveType = null,
    ): LeaveRequestResult {
        if ($leaveType !== null) {
            $status = $leaveType->attendanceStatus();
        }

        $requestedDays = $fromDate->diffInDays($toDate) + 1;
        $quotaError = $this->leaveEntitlements->quotaErrorForRequest($user, $status, $leaveType, $fromDate, $toDate, $requestedDays);

        if ($quotaError !== null) {
            return LeaveRequestResult::error($quotaError);
        }

        $employeeId = $user->employee?->id;

        $existingClockRecords = $this->existingClockRecords($employeeId, $fromDate, $toDate);
        if ($existingClockRecords->isNotEmpty()) {
            $blockedDates = $this->formatDates($existingClockRecords);

            return LeaveRequestResult::error(
                "Tidak dapat mengajukan izin. Anda sudah melakukan absensi (clock in/out) pada tanggal: {$blockedDates}"
            );
        }

        $existingLeaveRequests = $this->existingLeaveRequests($employeeId, $fromDate, $toDate);
        if ($existingLeaveRequests->isNotEmpty()) {
            $blockedDates = $this->formatDates($existingLeaveRequests);

            return LeaveRequestResult::error(
                "Tidak dapat mengajukan izin. Anda sudah memiliki pengajuan izin (Pending/Disetujui) pada tanggal: {$blockedDates}"
            );
        }

        $storedAttachment = $attachment ? $this->attendanceService->storeAttachment($attachment) : null;

        $fromDate->copy()->range($toDate)->forEach(function (Carbon $date) use ($employeeId, $status, $note, $storedAttachment, $lat, $lng, $leaveType) {
            $existing = Attendance::query()
                ->where('employee_id', $employeeId)
                ->whereDate('date', $date->toDateString())
                ->first();

            $payload = [
                'employee_id' => $employeeId,
                'status' => $status,
                'leave_type_id' => $leaveType?->id,
                'note' => $note,
                'attachment' => $storedAttachment ?? $existing?->attachment,
                'lat_in' => $lat ?? $existing?->lat_in,
                'long_in' => $lng ?? $existing?->long_in,
                'approval_status' => Attendance::STATUS_PENDING,
            ];

            if ($existing) {
                if (is_null($existing->clock_in) && is_null($existing->clock_out)) {
                    $existing->update($payload);
                }

                return;
            }

            Attendance::create($payload + [
                'date' => $date->toDateString(),
            ]);
        });

        ActivityLog::record('Leave Request', "User submitted {$status} request from {$fromDate->format('Y-m-d')} to {$toDate->format('Y-m-d')}");

        $latestAttendance = Attendance::query()
            ->where('employee_id', $employeeId)
            ->latest('date')
            ->latest('created_at')
            ->first();

        if ($latestAttendance) {
            $this->notifyLeaveRequest($user, $latestAttendance, $fromDate, $toDate);
        }

        return LeaveRequestResult::success();
    }

    protected function existingClockRecords(?int $employeeId, Carbon $fromDate, Carbon $toDate): Collection
    {
        return Attendance::query()
            ->where('employee_id', $employeeId)
            ->whereBetween('date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->where(function ($query) {
                $query->whereNotNull('clock_in')
                    ->orWhereNotNull('clock_out');
            })
            ->get();
    }

    protected function existingLeaveRequests(?int $employeeId, Carbon $fromDate, Carbon $toDate): Collection
    {
        return Attendance::query()
            ->where('employee_id', $employeeId)
            ->whereBetween('date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->whereIn('approval_status', [Attendance::STATUS_PENDING, Attendance::STATUS_APPROVED])
            ->get();
    }

    protected function formatDates(Collection $records): string
    {
        return $records->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->format('d M Y'))
            ->join(', ');
    }

    protected function notifyLeaveRequest(User $user, Attendance $attendance, Carbon $fromDate, Carbon $toDate): void
    {
        $notifiable = $this->notificationRecipients->leaveApprovers($user);

        if ($notifiable->isNotEmpty()) {
            Notification::send($notifiable, new LeaveRequested($attendance, $fromDate, $toDate));
            Notification::send($notifiable, new LeaveRequestedEmail($attendance, $fromDate, $toDate));
        }

        $adminEmail = Setting::getValue('notif.admin_email');
        if (! empty($adminEmail) && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            try {
                Notification::route('mail', $adminEmail)
                    ->notify(new LeaveRequestedEmail($attendance, $fromDate, $toDate));
            } catch (\Throwable $e) {
                Log::warning('Failed to send admin email notification: '.$e->getMessage());
            }
        }
    }
}
