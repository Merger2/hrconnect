<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * E2eTestSeeder — seed test users untuk Playwright E2E tests.
 *
 * Users yang dibutuhkan:
 * - test@hrconnect.test        → auth.spec.js
 * - employee@hrconnect.test    → clock-in, face-enrollment, rag-chat
 * - hr@hrconnect.test          → face-enrollment.spec.ts
 *
 * Semua password: 'password'
 * Role: employee (default)
 *
 * Idempotent: aman jalan berkali-kali.
 */
class E2eTestSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $companyId = Company::first()->id ?? 1;
        $branchId = Branch::first()->id ?? 1;
        $departmentId = Department::first()->id ?? 1;
        $positionId = Position::first()->id ?? 1;
        $shiftId = Shift::first()->id ?? 1;

        $employeeRole = Role::where('name', 'employee')->first();

        $testUsers = [
            ['email' => 'employee@hrconnect.test',  'name' => 'Test Employee', 'role' => 'employee',   'password' => 'Employee1234'],
            ['email' => 'hr@hrconnect.test',        'name' => 'Test HR',       'role' => 'hr-manager', 'password' => 'HRmanager1234'],
            ['email' => 'test@hrconnect.test',      'name' => 'Test User',     'role' => 'employee',   'password' => 'Employee1234'],
            ['email' => 'admin@hrconnect.local',    'name' => 'Super Admin',   'role' => 'super-admin', 'password' => 'ChangeMe!2026'],
            ['email' => 'manager@hrconnect.test',   'name' => 'Test Manager',  'role' => 'manager',    'password' => 'Manager1234!!'],
            ['email' => 'finance@hrconnect.test',   'name' => 'Test Finance',  'role' => 'finance',    'password' => 'Finance1234!!'],
            ['email' => 'it-support@hrconnect.test', 'name' => 'IT Support',    'role' => 'it-support', 'password' => 'ITsupport1234'],
        ];

        foreach ($testUsers as $data) {
            /** @var User $user */
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make($data['password'] ?? 'password'),
                    'email_verified_at' => now(),
                    'password_changed_at' => now(),
                ]
            );

            // Ensure password is always correct (firstOrCreate tidak update existing)
            if (! Hash::check($data['password'] ?? 'password', $user->password)) {
                $user->password = Hash::make($data['password'] ?? 'password');
                $user->password_changed_at = now();
                $user->save();
            }

            $roleName = $data['role'];
            if (! $user->hasRole($roleName)) {
                $user->assignRole($roleName);
                $user->refresh();
            }

            // FIXED: ensure every E2E user (incl. manager/finance) has an Employee record
            // so approval/employee APIs that rely on $user->employee work in production parity.
            if (! $user->employee) {
                Employee::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'employee_number' => 'EMP-'.str_pad((string) $user->id, 4, '0', STR_PAD_LEFT),
                        'full_name' => $user->name,
                        'company_id' => $companyId,
                        'branch_id' => $branchId,
                        'department_id' => $departmentId,
                        'position_id' => $positionId,
                        'gender' => 'L',
                        'marital_status' => 'single',
                        'employment_type' => 'permanent',
                        'birth_date' => '1990-01-01',
                        'join_date' => '2024-01-01',
                        'salary_type' => 'monthly',
                        'status' => 'active',
                        'phone' => '081234567890',
                        'nik' => '1234567890123456',
                        'npwp' => '123456789012345',
                        'bank_account_number' => '1234567890',
                        'bank_name' => 'BANK BCA',
                        'shift_id' => $shiftId,
                        'education_level' => 'sma',
                        'institution_name' => 'SMA Negeri 1',
                        'major' => 'IPA',
                        'graduation_year' => 2008,
                    ]
                );
            }
        }
    }
}
