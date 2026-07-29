<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class UserImport implements SkipsEmptyRows, ToCollection, WithHeadingRow, WithValidation
{
    public int $rowCount = 0;

    public array $errors = [];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            try {
                $email = $row['email'] ?? null;
                if (! $email) {
                    $this->errors[] = 'Email is required';

                    continue;
                }

                // Check if user already exists
                $user = User::firstOrNew(['email' => $email]);

                $user->name = $row['name'] ?? $email;
                $user->password = Hash::make($row['password'] ?? 'password123');
                $user->password_changed_at = now();
                $user->save();

                // Sync roles
                if (! empty($row['role'])) {
                    $role = Role::where('name', $row['role'])->first();
                    if ($role) {
                        $user->syncRoles([$role->name]);
                    }
                }

                // Link to employee if employee_number provided
                if (! empty($row['employee_number'])) {
                    $employee = Employee::where('employee_number', $row['employee_number'])->first();
                    if ($employee) {
                        $employee->update(['user_id' => $user->id]);
                    }
                }

                $this->rowCount++;
            } catch (\Throwable $e) {
                Log::error('User import row error', [
                    'row' => $row->toArray(),
                    'error' => $e->getMessage(),
                ]);
                $this->errors[] = $e->getMessage();
            }
        }
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email|unique:users,email',
            'name' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:8',
            'role' => 'nullable|exists:roles,name',
            'employee_number' => 'nullable|string|exists:employees,employee_number',
        ];
    }

    public function getRowCount(): int
    {
        return $this->rowCount;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
