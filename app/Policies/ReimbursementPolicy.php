<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\ReimbursementStatus;
use App\Models\Reimbursement;
use App\Models\User;

/**
 * ReimbursementPolicy — authorization untuk Reimbursement model.
 *
 * Matrix berbeda dengan Leave/Overtime:
 * - L1 → Manager (parent_id check)
 * - L2 → FINANCE (bukan HR Manager — sesuai SRS §3.2.2)
 * - HR Manager hanya view all (tidak approve)
 *
 * Status flow: PENDING → APPROVED (L1+L2) → PAID (after payroll generate)
 */
class ReimbursementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::VIEW_REIMBURSEMENTS->value);
    }

    public function view(User $user, Reimbursement $reimbursement): bool
    {
        if ($user->hasRole(['super-admin', 'hr-manager', 'finance'])) {
            return true;
        }

        if ($user->employee?->id === $reimbursement->employee_id) {
            return true;
        }

        if ($user->can(Permission::APPROVE_REIMBURSEMENTS_L1->value)
            && $reimbursement->employee?->parent_id === $user->employee?->id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->employee !== null;
    }

    public function update(User $user, Reimbursement $reimbursement): bool
    {
        return $user->employee?->id === $reimbursement->employee_id
            && $reimbursement->status === ReimbursementStatus::PENDING;
    }

    public function delete(User $user, Reimbursement $reimbursement): bool
    {
        return $user->employee?->id === $reimbursement->employee_id
            && $reimbursement->status === ReimbursementStatus::PENDING;
    }

    public function approveLevel1(User $user, Reimbursement $reimbursement): bool
    {
        if (! $user->can(Permission::APPROVE_REIMBURSEMENTS_L1->value)) {
            return false;
        }

        return $reimbursement->employee?->parent_id === $user->employee?->id;
    }

    /**
     * L2 untuk reimbursement adalah FINANCE, bukan HR Manager.
     */
    public function approveLevel2(User $user, Reimbursement $reimbursement): bool
    {
        return $user->can(Permission::APPROVE_REIMBURSEMENTS_L2->value);
    }
}
