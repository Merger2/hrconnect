<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * ProfileService — business logic for user profile management.
 */
class ProfileService
{
    public function getProfile(User $user): ?Employee
    {
        return $user->employee()->with([
            'branch:id,name',
            'department:id,name',
            'position:id,name,grade',
            'shift:id,name',
            'manager:id,full_name',
        ])->first();
    }

    public function updateProfile(Employee $employee, array $data): Employee
    {
        $employee->update($data);

        return $employee->fresh()->load([
            'branch:id,name',
            'department:id,name',
            'position:id,name,grade',
            'shift:id,name',
            'manager:id,full_name',
        ]);
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Password saat ini salah.'],
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($newPassword),
            'password_changed_at' => now(),
        ])->save();
    }
}
