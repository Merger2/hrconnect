<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\RequestStatus;
use App\Models\Overtime;
use App\Models\User;

/**
 * OvertimePolicy — authorization untuk Overtime (lembur) model.
 *
 * Matrix sama dengan LeavePolicy:
 * - L1 → Manager (parent_id check)
 * - L2 → HR Manager
 * - Owner → view/update/delete saat PENDING
 */
class OvertimePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::VIEW_OVERTIMES->value);
    }

    public function view(User $user, Overtime $overtime): bool
    {
        if ($user->hasRole(['super-admin', 'hr-manager'])) {
            return true;
        }

        if ($user->employee?->id === $overtime->employee_id) {
            return true;
        }

        if ($user->can(Permission::APPROVE_OVERTIMES_L1->value)
            && $overtime->employee?->parent_id === $user->employee?->id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->employee !== null;
    }

    public function update(User $user, Overtime $overtime): bool
    {
        return $user->employee?->id === $overtime->employee_id
            && $overtime->status === RequestStatus::PENDING;
    }

    public function delete(User $user, Overtime $overtime): bool
    {
        return $user->employee?->id === $overtime->employee_id
            && $overtime->status === RequestStatus::PENDING;
    }

    public function approveLevel1(User $user, Overtime $overtime): bool
    {
        if (! $user->can(Permission::APPROVE_OVERTIMES_L1->value)) {
            return false;
        }

        return $overtime->employee?->parent_id === $user->employee?->id;
    }

    public function approveLevel2(User $user, Overtime $overtime): bool
    {
        return $user->can(Permission::APPROVE_OVERTIMES_L2->value);
    }
}
