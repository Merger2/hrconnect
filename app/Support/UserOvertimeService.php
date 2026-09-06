<?php

namespace App\Support;

use App\Models\Overtime;
use App\Models\User;
use App\Notifications\OvertimeRequested;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class UserOvertimeService
{
    public function __construct(
        protected OvertimeCalculator $overtimeCalculator,
        protected UserNotificationRecipientService $notificationRecipients,
    ) {}

    public function paginateForUser(string|int $userId, int $perPage = 10): LengthAwarePaginator
    {
        $employeeId = User::find($userId)?->employee?->id;

        return Overtime::query()
            ->where('employee_id', $employeeId)
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{ok: bool, overtime?: Overtime, field?: string, message?: string}
     */
    public function submit(User $user, array $data): array
    {
        $employeeId = $user->employee?->id;

        if (! $employeeId) {
            return [
                'ok' => false,
                'field' => 'employee',
                'message' => __('Employee record not found.'),
            ];
        }

        [$start, $end] = $this->overtimeCalculator->resolveWindow($data['date'], $data['start_time'], $data['end_time']);
        $duration = $this->overtimeCalculator->durationInMinutes($start, $end);

        if ($duration <= 0) {
            return [
                'ok' => false,
                'field' => 'end_time',
                'message' => __('Overtime duration must be greater than zero.'),
            ];
        }

        $existingOvertimes = Overtime::query()
            ->where('employee_id', $employeeId)
            ->whereDate('date', $data['date'])
            ->whereIn('status', ['pending', 'approved'])
            ->get();

        if ($this->overtimeCalculator->hasOverlap($existingOvertimes, $start, $end)) {
            return [
                'ok' => false,
                'field' => 'start_time',
                'message' => __('This overtime request overlaps with an existing pending or approved request.'),
            ];
        }

        $overtime = Overtime::create([
            'employee_id' => $employeeId,
            'date' => $data['date'],
            'start_time' => $start,
            'end_time' => $end,
            'duration' => $duration,
            // Kolom tabel bernama `description` (bukan `reason`) — key lama
            // `reason` tidak fillable → alasan HILANG diam-diam dari DB (bug P1).
            'description' => $data['reason'] ?? null,
            'status' => 'pending',
        ]);

        $overtime->loadMissing('employee.user');

        if (class_exists(OvertimeRequested::class)) {
            $recipientCount = $this->notificationRecipients->notifyOvertimeRequested($overtime);

            if ($recipientCount > 0) {
                Log::info('Overtime request notifications sent.', [
                    'overtime_id' => $overtime->id,
                    'recipient_count' => $recipientCount,
                ]);
            } else {
                Log::warning('No overtime approver recipients found.', [
                    'overtime_id' => $overtime->id,
                    'user_id' => $user->id,
                ]);
            }
        }

        return [
            'ok' => true,
            'overtime' => $overtime,
        ];
    }
}
