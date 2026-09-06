<?php

namespace App\Support;

use App\Enums\RequestStatus;
use App\Models\AttendanceCorrection;
use App\Models\CashAdvance;
use App\Models\CustomFormSubmission;
use App\Models\EmployeeDocumentRequest;
use App\Models\HrChecklistTask;
use App\Models\Leave;
use App\Models\Overtime;
use App\Models\Reimbursement;
use App\Models\ShiftSwapRequest;
use App\Models\User;
use App\Models\WorkFromHomeRequest;
use Illuminate\Database\Eloquent\Builder;

class ManagerInboxService
{
    public const OVERDUE_AFTER_DAYS = 2;

    /**
     * @return list<string>
     */
    public function accessibleTabs(User $admin): array
    {
        $tabs = [];

        if ($admin->can('manageLeaveApprovals')) {
            $tabs[] = 'leaves';
        }

        if ($admin->can('manageOvertime')) {
            $tabs[] = 'overtime';
        }

        if ($admin->can('viewAdminAny', AttendanceCorrection::class)) {
            $tabs[] = 'attendance_corrections';
        }

        if ($admin->can('viewAdminAny', Reimbursement::class)) {
            $tabs[] = 'reimbursements';
        }

        if ($admin->can('manageCashAdvances')) {
            $tabs[] = 'cash_advances';
        }

        if ($admin->can('manageShiftSwapApprovals')) {
            $tabs[] = 'shift_swaps';
        }

        if ($admin->can('manageWfhRequests')) {
            $tabs[] = 'wfh_requests';
        }

        if ($admin->can('viewAdminAny', EmployeeDocumentRequest::class)) {
            $tabs[] = 'document_requests';
        }

        if ($admin->can('viewHrChecklists')) {
            $tabs[] = 'hr_tasks';
        }

        if ($admin->can('viewCustomForms')) {
            $tabs[] = 'custom_forms';
        }

        return $tabs;
    }

    /**
     * Get counts of pending requests for the admin inbox.
     * Respects the admin's managed scope.
     */
    public function getPendingCounts(User $admin): array
    {
        return $this->getScopedCounts($admin);
    }

    /**
     * @return array<string, int>
     */
    public function getOverdueCounts(User $admin): array
    {
        return $this->getScopedCounts($admin, overdueOnly: true);
    }

    /**
     * @return array{pending:int,overdue:int,workflows:int}
     */
    public function getSummary(User $admin): array
    {
        $pending = $this->getPendingCounts($admin);
        $overdue = $this->getOverdueCounts($admin);

        return [
            'pending' => array_sum($pending),
            'overdue' => array_sum($overdue),
            'workflows' => count($pending),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function getScopedCounts(User $admin, bool $overdueOnly = false): array
    {
        $allowedTabs = array_flip($this->accessibleTabs($admin));
        $overdueAt = now()->subDays(self::OVERDUE_AFTER_DAYS);

        $counts = [
            'leaves' => Leave::query()
                ->whereHas('employee.user', fn (Builder $q) => $q->managedBy($admin))
                ->whereIn('status', [RequestStatus::PENDING->value, RequestStatus::APPROVED_L1->value])
                ->when($overdueOnly, fn (Builder $query) => $query->where('created_at', '<=', $overdueAt))
                ->count(),

            'overtime' => Overtime::query()
                ->whereHas('employee.user', fn (Builder $q) => $q->managedBy($admin))
                ->where('status', 'pending')
                ->when($overdueOnly, fn (Builder $query) => $query->where('created_at', '<=', $overdueAt))
                ->count(),

            'attendance_corrections' => AttendanceCorrection::query()
                ->whereHas('user', fn (Builder $q) => $q->managedBy($admin))
                ->whereIn('status', [AttendanceCorrection::STATUS_PENDING, AttendanceCorrection::STATUS_PENDING_ADMIN])
                ->when($overdueOnly, fn (Builder $query) => $query->where('created_at', '<=', $overdueAt))
                ->count(),

            'reimbursements' => Reimbursement::query()
                ->whereHas('employee.user', fn (Builder $q) => $q->managedBy($admin))
                ->where('status', 'pending')
                ->when($overdueOnly, fn (Builder $query) => $query->where('created_at', '<=', $overdueAt))
                ->count(),

            'cash_advances' => CashAdvance::query()
                ->whereHas('user', fn (Builder $q) => $q->managedBy($admin))
                ->where('status', 'pending')
                ->when($overdueOnly, fn (Builder $query) => $query->where('created_at', '<=', $overdueAt))
                ->count(),

            'shift_swaps' => ShiftSwapRequest::query()
                ->whereHas('user', fn (Builder $q) => $q->managedBy($admin))
                ->where('status', ShiftSwapRequest::STATUS_PENDING)
                ->when($overdueOnly, fn (Builder $query) => $query->where('created_at', '<=', $overdueAt))
                ->count(),

            'wfh_requests' => WorkFromHomeRequest::query()
                ->whereHas('user', fn (Builder $q) => $q->managedBy($admin))
                ->where('status', WorkFromHomeRequest::STATUS_PENDING)
                ->when($overdueOnly, fn (Builder $query) => $query->where('created_at', '<=', $overdueAt))
                ->count(),

            'document_requests' => EmployeeDocumentRequest::query()
                ->whereHas('employee.user', fn (Builder $q) => $q->managedBy($admin))
                ->whereIn('status', [EmployeeDocumentRequest::STATUS_PENDING, EmployeeDocumentRequest::STATUS_REQUESTED])
                ->when($overdueOnly, fn (Builder $query) => $query->where('created_at', '<=', $overdueAt))
                ->count(),

            'hr_tasks' => HrChecklistTask::query()
                ->whereHas('case.user', fn (Builder $q) => $q->managedBy($admin))
                ->whereIn('status', [HrChecklistTask::STATUS_PENDING, HrChecklistTask::STATUS_BLOCKED])
                ->when($overdueOnly, fn (Builder $query) => $query->where(function (Builder $overdueQuery): void {
                    $overdueQuery->where('status', HrChecklistTask::STATUS_BLOCKED)
                        ->orWhere(fn (Builder $pendingQuery) => $pendingQuery->reminderReady());
                }))
                ->count(),

            'custom_forms' => CustomFormSubmission::query()
                ->whereHas('submitter', fn (Builder $q) => $q->managedBy($admin))
                ->where('status', CustomFormSubmission::STATUS_SUBMITTED)
                ->when($overdueOnly, fn (Builder $query) => $query->where('created_at', '<=', $overdueAt))
                ->count(),
        ];

        return array_intersect_key($counts, $allowedTabs);
    }

    /**
     * Get total pending count for badge.
     */
    public function getTotalPendingCount(User $admin): int
    {
        return array_sum($this->getPendingCounts($admin));
    }
}
