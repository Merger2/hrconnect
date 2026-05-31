<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Employee;
use App\Models\User;

/**
 * EmployeePolicy — authorization untuk Employee model.
 *
 * Pattern:
 * - viewAny / create / update / delete  → cek permission RBAC
 * - view → permission OR self-ownership
 * - Manager limited (team via parent_id) di-handle via permission `view_employees`
 *   yang di seeder hanya HR/Finance/Manager dapat. Filtering "tim only" via
 *   query scope di Livewire component (bukan policy concern).
 *
 * Cegah IDOR: setiap akses cross-employee WAJIB lewat policy ini.
 */
class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::VIEW_EMPLOYEES->value);
    }

    public function view(User $user, Employee $employee): bool
    {
        // HR Manager / Finance / Manager dengan permission boleh lihat
        if ($user->can(Permission::VIEW_EMPLOYEES->value)) {
            return true;
        }

        // Employee hanya bisa lihat data sendiri (self-ownership)
        return $user->employee?->id === $employee->id;
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::MANAGE_EMPLOYEES->value);
    }

    public function update(User $user, Employee $employee): bool
    {
        // Boleh kalau punya permission manage, ATAU edit profil sendiri
        return $user->can(Permission::MANAGE_EMPLOYEES->value)
            || $user->employee?->id === $employee->id;
    }

    public function delete(User $user, Employee $employee): bool
    {
        // Soft delete employee adalah tindakan HR — bukan self-service
        return $user->can(Permission::MANAGE_EMPLOYEES->value);
    }

    public function restore(User $user, Employee $employee): bool
    {
        return $user->can(Permission::MANAGE_EMPLOYEES->value);
    }

    public function forceDelete(User $user, Employee $employee): bool
    {
        // Hard delete hanya Super Admin
        return $user->hasRole('super-admin');
    }
}
