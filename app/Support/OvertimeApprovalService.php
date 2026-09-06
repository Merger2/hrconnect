<?php

namespace App\Support;

use App\Enums\RequestStatus;
use App\Models\Overtime;
use App\Models\User;
use App\Notifications\OvertimeStatusUpdated;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class OvertimeApprovalService
{
    public function managementQuery(string $statusFilter = 'pending', string $search = ''): Builder
    {
        return Overtime::query()
            ->with(['employee.division', 'employee.position'])
            ->whereHas('employee')
            ->when(in_array($statusFilter, ['pending', 'approved_l1'], true), fn (Builder $query) => $query->where('status', $statusFilter))
            ->when($statusFilter === 'all', fn (Builder $query) => $query->whereIn('status', [RequestStatus::PENDING->value, RequestStatus::APPROVED_L1->value, RequestStatus::APPROVED->value]))
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $subQuery) use ($search) {
                    $subQuery
                        ->where('reason', 'like', '%'.$search.'%')
                        ->orWhere('rejection_reason', 'like', '%'.$search.'%')
                        ->orWhereHas('employee.user', function (Builder $userQuery) use ($search) {
                            $userQuery->where('name', 'like', '%'.$search.'%');
                        })
                        ->orWhereHas('employee', function (Builder $employeeQuery) use ($search) {
                            $employeeQuery->where('employee_number', 'like', '%'.$search.'%');
                        })
                        ->orWhereHas('employee.division', function (Builder $divisionQuery) use ($search) {
                            $divisionQuery->where('name', 'like', '%'.$search.'%');
                        });
                });
            })
            ->orderBy('date', 'desc');
    }

    public function approve(Overtime $overtime, User $actor): void
    {
        DB::transaction(function () use ($overtime, $actor): void {
            $overtime = $this->lock($overtime);
            $this->ensurePending($overtime);

            $overtime->update([
                'status' => RequestStatus::APPROVED_L1,
                'approved_by' => $actor->employee?->id,
            ]);
        });

        $this->notifyStatusUpdated($overtime);
    }

    public function finalize(Overtime $overtime, User $actor): void
    {
        DB::transaction(function () use ($overtime): void {
            $overtime = $this->lock($overtime);
            $this->ensureApprovedL1($overtime);

            $overtime->update([
                'status' => RequestStatus::APPROVED,
                'approved_at' => now(),
            ]);
        });

        $this->notifyStatusUpdated($overtime);
    }

    public function reject(Overtime $overtime, User $actor, ?string $rejectionReason = null): void
    {
        DB::transaction(function () use ($overtime, $actor, $rejectionReason): void {
            $overtime = $this->lock($overtime);
            $this->ensurePending($overtime);

            $overtime->update([
                'status' => RequestStatus::REJECTED,
                'approved_by' => $actor->employee?->id,
                'rejection_reason' => $rejectionReason,
            ]);
        });

        $this->notifyStatusUpdated($overtime);
    }

    protected function notifyStatusUpdated(Overtime $overtime): void
    {
        if (! class_exists(OvertimeStatusUpdated::class)) {
            return;
        }

        $overtime->loadMissing('employee.user');
        $overtime->employee?->user?->notify(new OvertimeStatusUpdated($overtime));
    }

    private function lock(Overtime $overtime): Overtime
    {
        return Overtime::query()
            ->whereKey($overtime->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function ensurePending(Overtime $overtime): void
    {
        if ($overtime->status !== RequestStatus::PENDING) {
            throw new AuthorizationException(__('This overtime request has already been reviewed.'));
        }
    }

    private function ensureApprovedL1(Overtime $overtime): void
    {
        if ($overtime->status !== RequestStatus::APPROVED_L1) {
            throw new AuthorizationException(__('This overtime request is not waiting for final approval.'));
        }
    }
}
