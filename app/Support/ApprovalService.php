<?php

namespace App\Support;

use App\Enums\ApprovalLevel;
use App\Enums\ApprovalStatus;
use App\Enums\RequestStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Approval;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ApprovalService
{
    public function createApprovalWorkflow(Model $approvable): void
    {
        $employee = $approvable->employee;

        if (! $employee) {
            throw new \LogicException('Approvaable must have an employee relationship');
        }

        $approvers = $this->getApprovers($employee);

        if (empty($approvers)) {
            throw new \LogicException('Tidak ada Approver');
        }

        // Deduplicate approvers by employee to ensure unique levels
        $uniqueApprovers = [];
        $addedEmployees = [];

        foreach ($approvers as $approver) {
            $emp = $approver['employee'];
            $levelEnum = $approver['level'];

            if (! isset($addedEmployees[$emp->id])) {
                $addedEmployees[$emp->id] = true;
                $uniqueApprovers[] = ['employee' => $emp, 'level' => $levelEnum];
            }
        }

        $level = 0;
        foreach ($uniqueApprovers as $approver) {
            $level++;
            $approvalLevel = $approver['level'];

            $approvable->approvals()->create([
                'approver_id' => $approver['employee']->id,
                'level' => $approvalLevel,
                'status' => ApprovalStatus::PENDING,
            ]);
        }
    }

    public function approve(Approval $approval, string $notes = ''): void
    {
        $approval->load('approvable');

        if ($approval->status !== ApprovalStatus::PENDING) {
            throw new BusinessRuleException('Approval sudah diproses');
        }

        if ($notes !== '') {
            $approval->notes = $notes;
        }

        // Check if previous levels are approved
        if ($approval->level === ApprovalLevel::L2_MANAGER) {
            $l1Approval = $approval->approvable->approvals()
                ->where('level', ApprovalLevel::L1_SUPERVISOR)
                ->first();

            if ($l1Approval && $l1Approval->status !== ApprovalStatus::APPROVED) {
                throw new BusinessRuleException('Approval level sebelumnya belum disetujui');
            }
        }

        $approval->status = ApprovalStatus::APPROVED;
        $approval->approved_at = now();
        $approval->save();

        $this->updateApprovableStatus($approval->approvable);
    }

    public function reject(Approval $approval, string $reason): void
    {
        $approval->load('approvable');

        if ($approval->status !== ApprovalStatus::PENDING) {
            throw new BusinessRuleException('Approval sudah diproses');
        }

        $approval->status = ApprovalStatus::REJECTED;
        $approval->notes = $reason;
        $approval->save();

        $approval->approvable->status = RequestStatus::REJECTED;
        $approval->approvable->rejection_reason = $reason;
        $approval->approvable->save();
    }

    private function getApprovers(Employee $employee): array
    {
        $approvers = [];

        // L1: Direct supervisor
        if ($employee->parent_id) {
            $supervisor = Employee::find($employee->parent_id);
            if ($supervisor && $supervisor->status->value === 'active') {
                $approvers[] = ['employee' => $supervisor, 'level' => ApprovalLevel::L1_SUPERVISOR];
            }
        }

        // L2: HR (role admin — HRD; role hr-manager dihapus)
        $hrUsers = User::role('admin')->get();
        foreach ($hrUsers as $hrUser) {
            if ($hrUser->employee) {
                $approvers[] = ['employee' => $hrUser->employee, 'level' => ApprovalLevel::L2_MANAGER];
            }
        }

        return $approvers;
    }

    private function updateApprovableStatus(Model $approvable): void
    {
        $pendingApprovals = $approvable->approvals()
            ->where('status', ApprovalStatus::PENDING)
            ->count();

        if ($pendingApprovals === 0) {
            $hasRejected = $approvable->approvals()
                ->where('status', ApprovalStatus::REJECTED)
                ->exists();

            if ($hasRejected) {
                $approvable->status = RequestStatus::REJECTED;
            } else {
                // Check if L1 exists and is approved
                $l1Approved = $approvable->approvals()
                    ->where('level', ApprovalLevel::L1_SUPERVISOR)
                    ->where('status', ApprovalStatus::APPROVED)
                    ->exists();

                $hasL1 = $approvable->approvals()
                    ->where('level', ApprovalLevel::L1_SUPERVISOR)
                    ->exists();

                if ($hasL1 && ! $l1Approved) {
                    $approvable->status = RequestStatus::APPROVED_L1;
                } else {
                    $approvable->status = RequestStatus::APPROVED;
                }
            }

            // Handle leave quota deduction
            if ($approvable instanceof Leave && $approvable->status === RequestStatus::APPROVED) {
                $this->deductLeaveQuota($approvable);
            }

            $approvable->save();
        } else {
            // Still has pending approvals - check if L1 is approved
            $l1Approved = $approvable->approvals()
                ->where('level', ApprovalLevel::L1_SUPERVISOR)
                ->where('status', ApprovalStatus::APPROVED)
                ->exists();

            $hasL1 = $approvable->approvals()
                ->where('level', ApprovalLevel::L1_SUPERVISOR)
                ->exists();

            if ($hasL1 && $l1Approved) {
                $approvable->status = RequestStatus::APPROVED_L1;
                $approvable->save();
            }
        }
    }

    private function deductLeaveQuota(Leave $leave): void
    {
        if (! $leave->leaveType->deducts_from_quota) {
            return;
        }

        $balance = LeaveBalance::where('employee_id', $leave->employee_id)
            ->where('leave_type_id', $leave->leave_type_id)
            ->where('year', $leave->start_date->year)
            ->first();

        if ($balance) {
            $balance->used += $leave->total_days;
            $balance->save();
        }
    }
}
