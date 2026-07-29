<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class UserComponentImport implements SkipsEmptyRows, ToCollection, WithHeadingRow, WithValidation
{
    public int $rowCount = 0;

    public array $errors = [];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            try {
                $email = $row['email'] ?? null;
                $employeeNumber = $row['employee_number'] ?? null;

                // Find or create user
                $user = User::where('email', $email)->first();
                if (! $user) {
                    $password = Str::random(12);
                    $user = User::create([
                        'name' => $row['name'] ?? $row['full_name'] ?? 'Unknown',
                        'email' => $email,
                        'password' => Hash::make($password),
                    ]);
                }

                // Find or create employee record
                $employee = Employee::where('employee_number', $employeeNumber)->first();
                if (! $employee) {
                    Employee::create([
                        'user_id' => $user->id,
                        'employee_number' => $employeeNumber,
                        'full_name' => $row['name'] ?? $row['full_name'] ?? $user->name,
                        'status' => 'active',
                    ]);
                }

                $this->rowCount++;
            } catch (\Throwable $e) {
                Log::error('UserComponentImport row error', [
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
            'email' => 'required|email',
            'employee_number' => 'required|string',
            'name' => 'required|string|max:255',
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
