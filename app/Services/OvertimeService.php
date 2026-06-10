<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\RequestStatus;
use App\Models\Employee;
use App\Models\Overtime;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * OvertimeService — business logic for overtime requests.
 */
class OvertimeService
{
    public function __construct(
        protected ApprovalService $approvalService,
    ) {}

    public function createOvertime(Employee $employee, array $data): Overtime
    {
        return DB::transaction(function () use ($employee, $data) {
            $start = CarbonImmutable::parse($data['date'].' '.$data['start_time']);
            $end = CarbonImmutable::parse($data['date'].' '.$data['end_time']);

            if ($end->lessThanOrEqualTo($start)) {
                $end = $end->addDay();
            }

            $hours = $start->diffInMinutes($end) / 60;

            $overtime = Overtime::create([
                'employee_id' => $employee->id,
                'date' => $data['date'],
                'start_time' => $start,
                'end_time' => $end,
                'description' => $data['description'],
                'status' => RequestStatus::PENDING,
                'total_hours' => round($hours, 2),
            ]);

            $this->approvalService->createApprovalWorkflow($overtime);

            return $overtime;
        });
    }

    public function cancelOvertime(Overtime $overtime): void
    {
        $overtime->update(['status' => RequestStatus::CANCELLED]);
        $overtime->delete();
    }
}
