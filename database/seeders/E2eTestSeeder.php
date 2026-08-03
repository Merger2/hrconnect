<?php

namespace Database\Seeders;

use App\Enums\BloodType;
use App\Enums\EducationLevel;
use App\Enums\EmployeeStatus;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\SalaryType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Division;
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

        $employeeRole = Role::where('name', 'employee')->first();

        $testUsers = [
            ['email' => 'employee@hrconnect.test',  'name' => 'Test Employee', 'role' => 'employee',   'password' => 'password'],
            ['email' => 'hr@hrconnect.test',        'name' => 'Test HR',       'role' => 'hr-manager', 'password' => 'password'],
            ['email' => 'test@hrconnect.test',      'name' => 'Test User',     'role' => 'employee',   'password' => 'password'],
            ['email' => 'admin@hrconnect.local',    'name' => 'Super Admin',   'role' => 'super-admin', 'password' => 'ChangeMe!2026'],
            ['email' => 'manager@hrconnect.test',   'name' => 'Test Manager',  'role' => 'manager',    'password' => 'password'],
            ['email' => 'finance@hrconnect.test',   'name' => 'Test Finance',  'role' => 'finance',    'password' => 'password'],
        ];

        $company = Company::where('code', 'HRCONNECT')->firstOrFail();
        $branch = Branch::where('company_id', $company->id)->where('is_main', true)->firstOrFail();

        $divMap = [
            'it' => Division::where('code', 'IT')->firstOrFail(),
            'hr' => Division::where('code', 'HR')->firstOrFail(),
            'fin' => Division::where('code', 'FIN')->firstOrFail(),
            'ops' => Division::where('code', 'OPS')->firstOrFail(),
        ];

        $posMap = Position::whereIn('code', ['IT-STAFF', 'HR-MGR', 'HR-STAFF', 'FIN-STAFF', 'OPS-STAFF'])->get()->keyBy('code');

        $employeeData = [
            'employee@hrconnect.test' => ['div' => 'it',  'pos' => 'IT-STAFF',   'emp_no' => 'EMP-E2E-001', 'full_name' => 'Test Employee', 'nik' => '3276010000000001', 'npwp' => '99.999.999.9-999.001', 'phone' => '081900000001'],
            'hr@hrconnect.test' => ['div' => 'hr',  'pos' => 'HR-MGR',     'emp_no' => 'EMP-E2E-002', 'full_name' => 'Test HR',       'nik' => '3276010000000002', 'npwp' => '99.999.999.9-999.002', 'phone' => '081900000002'],
            'test@hrconnect.test' => ['div' => 'it',  'pos' => 'IT-STAFF',   'emp_no' => 'EMP-E2E-003', 'full_name' => 'Test User',     'nik' => '3276010000000003', 'npwp' => '99.999.999.9-999.003', 'phone' => '081900000003'],
            'manager@hrconnect.test' => ['div' => 'ops', 'pos' => 'OPS-STAFF',  'emp_no' => 'EMP-E2E-004', 'full_name' => 'Test Manager',  'nik' => '3276010000000004', 'npwp' => '99.999.999.9-999.004', 'phone' => '081900000004'],
            'finance@hrconnect.test' => ['div' => 'fin', 'pos' => 'FIN-STAFF',  'emp_no' => 'EMP-E2E-005', 'full_name' => 'Test Finance',  'nik' => '3276010000000005', 'npwp' => '99.999.999.9-999.005', 'phone' => '081900000005'],
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

            if ($roleName === 'super-admin') {
                continue;
            }

            $emp = $employeeData[$data['email']] ?? null;
            if ($emp === null) {
                continue;
            }

            Employee::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                    'division_id' => $divMap[$emp['div']]->id,
                    'position_id' => $posMap[$emp['pos']]->id,
                    'employee_number' => $emp['emp_no'],
                    'full_name' => $emp['full_name'],
                    'nik' => $emp['nik'],
                    'npwp' => $emp['npwp'],
                    'phone' => $emp['phone'],
                    'gender' => Gender::LAKI_LAKI,
                    'marital_status' => MaritalStatus::SINGLE,
                    'blood_type' => BloodType::O_PLUS,
                    'status' => EmployeeStatus::ACTIVE,
                    'birth_date' => '1995-06-15',
                    'join_date' => '2024-01-01',
                    'education_level' => EducationLevel::BACHELOR,
                    'institution_name' => 'Universitas Indonesia',
                    'major' => 'Teknik Informatika',
                    'graduation_year' => 2018,
                    'salary_type' => SalaryType::MONTHLY,
                    'shift_id' => Shift::where('name', 'Office Hour')->first()?->id,
                    'address_detail' => 'Jl. Test No. 1, Jakarta',
                    'pin' => Hash::make('123456'),
                ]
            );
        }
    }
}
