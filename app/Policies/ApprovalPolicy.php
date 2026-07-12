<?php

namespace App\Policies;

use App\Enums\ApprovalLevel;
use App\Enums\Permission;
use App\Models\Approval;
use App\Models\User;

class ApprovalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::APPROVE_LEAVES_L1->value)
            || $user->can(Permission::APPROVE_LEAVES_L2->value)
            || $user->can(Permission::APPROVE_OVERTIMES_L1->value)
            || $user->can(Permission::APPROVE_OVERTIMES_L2->value)
            || $user->can(Permission::APPROVE_REIMBURSEMENTS_L1->value)
            || $user->can(Permission::APPROVE_REIMBURSEMENTS_L2->value)
            || $user->can(Permission::APPROVE_WFA->value);
    }

    public function view(User $user, Approval $approval): bool
    {
        if ($user->hasRole(['super-admin', 'hr-manager'])) {
            return true;
        }

        if ($this->canApproveLevel($user, $approval, Permission::APPROVE_LEAVES_L1)
            || $this->canApproveLevel($user, $approval, Permission::APPROVE_LEAVES_L2)
            || $this->canApproveLevel($user, $approval, Permission::APPROVE_OVERTIMES_L1)
            || $this->canApproveLevel($user, $approval, Permission::APPROVE_OVERTIMES_L2)
            || $this->canApproveLevel($user, $approval, Permission::APPROVE_REIMBURSEMENTS_L1)
            || $this->canApproveLevel($user, $approval, Permission::APPROVE_REIMBURSEMENTS_L2)) {
            return true;
        }

        return false;
    }

    public function approve(User $user, Approval $approval): bool
    {
        return $this->canApproveLevel($user, $approval, $this->permissionForLevel($approval));
    }

    public function reject(User $user, Approval $approval): bool
    {
        return $this->canApproveLevel($user, $approval, $this->permissionForLevel($approval));
    }

    protected function canApproveLevel(User $user, Approval $approval, Permission $permission): bool
    {
        if (! $user->can($permission->value)) {
            return false;
        }

        if ($user->hasRole(['super-admin', 'hr-manager'])) {
            return true;
        }

        if ($approval->level === ApprovalLevel::L1_SUPERVISOR
            && $approval->approvable?->employee?->parent_id === $user->employee?->id) {
            return true;
        }

        return false;
    }

    protected function permissionForLevel(Approval $approval): Permission
    {
        $mapping = [
            'leave' => [1 => Permission::APPROVE_LEAVES_L1, 2 => Permission::APPROVE_LEAVES_L2],
            'overtime' => [1 => Permission::APPROVE_OVERTIMES_L1, 2 => Permission::APPROVE_OVERTIMES_L2],
            'reimbursement' => [1 => Permission::APPROVE_REIMBURSEMENTS_L1, 2 => Permission::APPROVE_REIMBURSEMENTS_L2],
        ];

        $type = $approval->approvable_type ? class_basename($approval->approvable_type) : '';
        $type = strtolower($type);
        $level = $approval->level?->value ?? 1;

        return $mapping[$type][$level] ?? Permission::APPROVE_LEAVES_L1;
    }
}
