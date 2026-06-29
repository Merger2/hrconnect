<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Loan;
use App\Models\User;

class LoanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::VIEW_LOANS->value);
    }

    public function view(User $user, Loan $loan): bool
    {
        if ($user->can(Permission::MANAGE_LOANS->value)) {
            return true;
        }

        return $user->can(Permission::VIEW_LOANS->value)
            && $user->employee?->id === $loan->employee_id;
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::MANAGE_LOANS->value) || $user->employee;
    }

    public function update(User $user, Loan $loan): bool
    {
        if ($user->can(Permission::MANAGE_LOANS->value)) {
            return true;
        }

        return $user->employee?->id === $loan->employee_id;
    }

    public function delete(User $user, Loan $loan): bool
    {
        if ($user->can(Permission::MANAGE_LOANS->value)) {
            return true;
        }

        return $user->employee?->id === $loan->employee_id
            && in_array($loan->status->value, ['pending', 'approved']);
    }
}
