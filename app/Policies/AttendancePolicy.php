<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Attendance;
use App\Models\User;

/**
 * AttendancePolicy — authorization untuk Attendance model.
 *
 * Matrix akses (sesuai SRS §3.2.2):
 * - HR Manager  → all (manage_attendances)
 * - Manager     → tim (parent_id = $user->employee->id)
 * - Employee    → diri sendiri saja
 * - Finance     → tidak punya akses langsung
 *
 * Cegah IDOR: cross-employee attendance WAJIB lewat policy ini.
 */
class AttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::VIEW_ATTENDANCES->value);
    }

    public function view(User $user, Attendance $attendance): bool
    {
        // HR Manager dengan permission manage → semua
        if ($user->can(Permission::MANAGE_ATTENDANCES->value)) {
            return true;
        }

        // Self-ownership
        if ($user->employee?->id === $attendance->employee_id) {
            return true;
        }

        // Manager → tim langsung (parent_id)
        if ($user->can(Permission::VIEW_ATTENDANCES->value)
            && $attendance->employee?->parent_id === $user->employee?->id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        // Clock-in/out dilakukan via service AttendanceService, bukan policy.
        // Endpoint Livewire/API langsung pakai $user, jadi self-only.
        return true;
    }

    public function update(User $user, Attendance $attendance): bool
    {
        // Koreksi manual hanya HR Manager
        return $user->can(Permission::MANAGE_ATTENDANCES->value);
    }

    public function delete(User $user, Attendance $attendance): bool
    {
        return $user->can(Permission::MANAGE_ATTENDANCES->value);
    }
}
