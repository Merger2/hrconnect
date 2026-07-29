<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function before(User $user): ?bool
    {
        return $user->isSuperadmin ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view_employees');
    }

    public function view(User $user, Employee $employee): bool
    {
        return $user->can('view_employees') || $user->employee?->id === $employee->id;
    }

    public function create(User $user): bool
    {
        return $user->can('manage_user_record') || $user->can('manage_employees');
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->can('manage_user_record') || $user->can('manage_employees');
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->can('manage_user_record') || $user->can('manage_employees');
    }

    public function viewPii(User $user, Employee $employee): bool
    {
        return $user->can('manage_employees') || $user->employee?->id === $employee->id;
    }
}
