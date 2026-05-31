<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\RequestStatus;
use App\Models\Leave;
use App\Models\User;

/**
 * LeavePolicy — authorization untuk Leave (cuti) model.
 *
 * Matrix approval (sesuai SRS §3.2.7 Approval Workflow):
 * - L1 (approve_leaves_l1)  → Manager (cek parent_id == user->employee->id)
 * - L2 (approve_leaves_l2)  → HR Manager (semua)
 *
 * Owner dapat:
 * - View: selalu
 * - Withdraw (delete): hanya saat status = PENDING
 * - Update: hanya saat status = PENDING (sebelum di-approve siapapun)
 *
 * Cegah IDOR pada cross-employee leave records.
 */
class LeavePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::VIEW_LEAVES->value);
    }

    public function view(User $user, Leave $leave): bool
    {
        // HR/Super dengan view permission → all
        if ($user->hasRole(['super-admin', 'hr-manager'])) {
            return true;
        }

        // Self-ownership
        if ($user->employee?->id === $leave->employee_id) {
            return true;
        }

        // Manager → tim
        if ($user->can(Permission::APPROVE_LEAVES_L1->value)
            && $leave->employee?->parent_id === $user->employee?->id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        // Semua employee bisa apply leave (default behavior).
        // Validasi probation/quota di LeaveService.
        return $user->employee !== null;
    }

    public function update(User $user, Leave $leave): bool
    {
        // Hanya owner, dan hanya saat masih PENDING (belum di-approve siapapun).
        return $user->employee?->id === $leave->employee_id
            && $leave->status === RequestStatus::PENDING;
    }

    public function delete(User $user, Leave $leave): bool
    {
        // Withdraw — sama seperti update: owner + status PENDING
        return $user->employee?->id === $leave->employee_id
            && $leave->status === RequestStatus::PENDING;
    }

    /**
     * Approve Level 1 — hanya direct manager (parent_id = approver->employee->id).
     */
    public function approveLevel1(User $user, Leave $leave): bool
    {
        if (! $user->can(Permission::APPROVE_LEAVES_L1->value)) {
            return false;
        }

        return $leave->employee?->parent_id === $user->employee?->id;
    }

    /**
     * Approve Level 2 — HR Manager / Super Admin (semua employee).
     */
    public function approveLevel2(User $user, Leave $leave): bool
    {
        return $user->can(Permission::APPROVE_LEAVES_L2->value);
    }
}
