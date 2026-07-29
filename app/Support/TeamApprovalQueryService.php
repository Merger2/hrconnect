<?php

namespace App\Support;

use App\Models\Approval;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\CashAdvance;
use App\Models\Employee;
use App\Models\Overtime;
use App\Models\Reimbursement;
use App\Models\ShiftSwapRequest;
use App\Models\User;
use App\Models\WorkFromHomeRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class TeamApprovalQueryService
{
    public function getPendingApprovalsForUser(Employee $user): Collection
    {
        return Approval::query()
            ->where('approver_id', $user->id)
            ->where('status', 'pending')
            ->with(['approvable'])
            ->get();
    }

    public function getApprovalsByActor(Employee $actor, ?string $status = null): Collection
    {
        $query = Approval::where('approver_id', $actor->id);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->with(['approvable'])->get();
    }

    public function getTeamApprovalsForManager(Employee $manager): Collection
    {
        $subordinateIds = $manager->subordinates()->pluck('id');

        return Approval::whereHas('approvable', function ($query) use ($subordinateIds) {
            $query->whereIn('employee_id', $subordinateIds);
        })->with(['approvable', 'approver'])->get();
    }

    public function pending(User $user, string $activeTab, string $search = ''): LengthAwarePaginator
    {
        $subordinateIds = $this->subordinateIds($user);

        $query = $this->buildQuery($activeTab, $subordinateIds, $search, false);

        return $query->latest()->paginate(15);
    }

    public function history(User $user, string $activeTab, string $search = ''): LengthAwarePaginator
    {
        $subordinateIds = $this->subordinateIds($user);

        $query = $this->buildQuery($activeTab, $subordinateIds, $search, true);

        return $query->latest()->paginate(15);
    }

    protected function buildQuery(string $tab, array $subordinateIds, string $search, bool $history): mixed
    {
        return match ($tab) {
            'attendance-corrections' => $this->attendanceCorrectionQuery($subordinateIds, $search, $history),
            'shift-swaps' => $this->shiftSwapQuery($subordinateIds, $search, $history),
            'leaves' => $this->leaveQuery($subordinateIds, $search, $history),
            'reimbursements' => $this->reimbursementQuery($subordinateIds, $search, $history),
            'overtimes' => $this->overtimeQuery($subordinateIds, $search, $history),
            'wfh' => $this->wfhQuery($subordinateIds, $search),
            'kasbons' => $this->cashAdvanceQuery($subordinateIds, $search, $history),
            default => AttendanceCorrection::query()->whereRaw('1 = 0'),
        };
    }

    protected function attendanceCorrectionQuery(array $subordinateIds, string $search, bool $history): mixed
    {
        $query = AttendanceCorrection::query()->with(['user', 'requestedShift', 'headApprover', 'reviewer'])
            ->whereHas('user', fn ($q) => $q->whereIn('id', $subordinateIds));

        if ($history) {
            $query->whereNotIn('status', [AttendanceCorrection::STATUS_PENDING, AttendanceCorrection::STATUS_PENDING_ADMIN]);
        } else {
            $query->whereIn('status', [AttendanceCorrection::STATUS_PENDING, AttendanceCorrection::STATUS_PENDING_ADMIN]);
        }

        if ($search) {
            $query->where(function ($q) use ($search): void {
                $q->where('reason', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    protected function shiftSwapQuery(array $subordinateIds, string $search, bool $history): mixed
    {
        $query = ShiftSwapRequest::query()->with(['user', 'currentShift', 'requestedShift', 'replacementUser', 'reviewer'])
            ->whereHas('user', fn ($q) => $q->whereIn('id', $subordinateIds));

        if ($history) {
            $query->whereNotIn('status', [ShiftSwapRequest::STATUS_PENDING]);
        } else {
            $query->where('status', ShiftSwapRequest::STATUS_PENDING);
        }

        if ($search) {
            $query->where(function ($q) use ($search): void {
                $q->where('reason', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    protected function leaveQuery(array $subordinateIds, string $search, bool $history): mixed
    {
        $query = Attendance::query()->with(['user', 'shift'])
            ->whereIn('user_id', $subordinateIds)
            ->whereNotNull('leave_type_id');

        if ($history) {
            $query->whereIn('approval_status', ['approved', 'rejected']);
        } else {
            $query->where('approval_status', 'pending');
        }

        if ($search) {
            $query->whereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"));
        }

        return $query;
    }

    protected function reimbursementQuery(array $subordinateIds, string $search, bool $history): mixed
    {
        $query = Reimbursement::query()->with(['user'])
            ->whereHas('user', fn ($q) => $q->whereIn('id', $subordinateIds));

        if ($history) {
            $query->whereIn('status', ['approved', 'rejected', 'paid']);
        } else {
            $query->where('status', 'pending');
        }

        if ($search) {
            $query->where(function ($q) use ($search): void {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    protected function overtimeQuery(array $subordinateIds, string $search, bool $history): mixed
    {
        $query = Overtime::query()->with(['user'])
            ->whereHas('user', fn ($q) => $q->whereIn('id', $subordinateIds));

        if ($history) {
            $query->whereIn('status', ['approved', 'rejected', 'paid']);
        } else {
            $query->where('status', 'pending');
        }

        if ($search) {
            $query->where(function ($q) use ($search): void {
                $q->where('reason', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    protected function wfhQuery(array $subordinateIds, string $search): mixed
    {
        $query = WorkFromHomeRequest::query()->with(['user'])
            ->whereHas('user', fn ($q) => $q->whereIn('id', $subordinateIds))
            ->where('status', 'pending');

        if ($search) {
            $query->where(function ($q) use ($search): void {
                $q->where('reason', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    protected function cashAdvanceQuery(array $subordinateIds, string $search, bool $history): mixed
    {
        $query = CashAdvance::query()->with(['user'])
            ->whereHas('user', fn ($q) => $q->whereIn('id', $subordinateIds));

        if ($history) {
            $query->whereIn('status', ['approved', 'rejected', 'paid']);
        } else {
            $query->where('status', 'pending');
        }

        if ($search) {
            $query->where(function ($q) use ($search): void {
                $q->where('purpose', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    protected function subordinateIds(User $user): array
    {
        return $user->employee?->subordinates()->pluck('users.id')->toArray() ?? [];
    }
}
