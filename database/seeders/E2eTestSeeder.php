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
        // Demo VPS (skripsi): SEED_DEMO=true mengizinkan di production — default off.
        if (app()->isProduction() && ! filter_var(env('SEED_DEMO', false), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $employeeRole = Role::where('name', 'employee')->first();

        $testUsers = [
            ['email' => 'employee@hrconnect.test',  'name' => 'Test Employee', 'role' => 'employee',   'password' => 'password'],
            ['email' => 'hr@hrconnect.test',        'name' => 'Test HR',       'role' => 'admin',      'password' => 'password'],
            ['email' => 'test@hrconnect.test',      'name' => 'Test User',     'role' => 'employee',   'password' => 'password'],
            ['email' => 'admin@hrconnect.local',    'name' => 'Super Admin',   'role' => 'super-admin', 'password' => 'ChangeMe!2026'],
            ['email' => 'manager@hrconnect.test',   'name' => 'Test Manager',  'role' => 'manager',    'password' => 'Manager1234!!'],
            ['email' => 'finance@hrconnect.test',   'name' => 'Test Finance',  'role' => 'finance',    'password' => 'Finance1234!!'],
            // Role 'it-support' TIDAK ADA di RoleAndPermissionSeeder (modul ItSupport
            // dihapus) — entry lama di bawah membuat assignRole() throw ModelNotFound
            // dan menggagalkan seluruh db:seed. User it-support dihapus 2026-08-16.
            // ['email' => 'it-support@hrconnect.test', 'name' => 'IT Support', 'role' => 'it-support', 'password' => 'ITsupport1234'],
        ];

        $company = Company::where('code', 'DKMS-2025')->firstOrFail();
        $branch = Branch::where('company_id', $company->id)->where('is_main', true)->firstOrFail();

        $divMap = [
            'it' => Division::where('code', 'IT')->firstOrFail(),
            'hr' => Division::where('code', 'HR')->firstOrFail(),
            'fin' => Division::where('code', 'FIN')->firstOrFail(),
            'ops' => Division::where('code', 'OPS')->firstOrFail(),
        ];

        $posMap = Position::whereIn('code', ['IT-STAFF', 'HR-MGR', 'IT-MGR', 'FIN-STAFF', 'OPS-MGR'])->get()->keyBy('code');

        $employeeData = [
            'employee@hrconnect.test' => ['div' => 'it',  'pos' => 'IT-STAFF',   'emp_no' => 'EMP-E2E-001', 'full_name' => 'Test Employee', 'nik' => '3276010000000001', 'npwp' => '99.999.999.9-999.001', 'phone' => '081900000001'],
            'hr@hrconnect.test' => ['div' => 'hr',  'pos' => 'HR-MGR',     'emp_no' => 'EMP-E2E-002', 'full_name' => 'Test HR',       'nik' => '3276010000000002', 'npwp' => '99.999.999.9-999.002', 'phone' => '081900000002'],
            'test@hrconnect.test' => ['div' => 'it',  'pos' => 'IT-STAFF',   'emp_no' => 'EMP-E2E-003', 'full_name' => 'Test User',     'nik' => '3276010000000003', 'npwp' => '99.999.999.9-999.003', 'phone' => '081900000003'],
            'manager@hrconnect.test' => ['div' => 'ops', 'pos' => 'OPS-MGR',   'emp_no' => 'EMP-E2E-004', 'full_name' => 'Test Manager',  'nik' => '3276010000000004', 'npwp' => '99.999.999.9-999.004', 'phone' => '081900000004'],
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
                // Aksesor isSuperadmin/isAdmin berbasis kolom `group` (legacy
                // PasPapan), bukan role. Tanpa group='superadmin', akun ini
                // kehilangan global admin scope (managedBy -> kosong) dan
                // dashboard admin tampil tanpa data. Konsisten dengan
                // SuperAdminSeeder. (fix 2026-08-06)
                if ($user->group !== 'superadmin') {
                    $user->update(['group' => 'superadmin']);
                }

                continue;
            }

            $emp = $employeeData[$data['email']] ?? null;
            if ($emp === null) {
                continue;
            }

            // Cari parent_id dari IT Manager (untuk approval flow E2E)
            $itManager = User::where('email', 'employee1@hrconnect.local')->first();
            $parentId = $itManager?->employee?->id;

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
                    'marital_status' => $emp['pos'] === 'OPS-MGR' ? MaritalStatus::MARRIED : MaritalStatus::SINGLE,
                    'blood_type' => BloodType::O_PLUS,
                    'status' => EmployeeStatus::ACTIVE,
                    'birth_date' => '1995-06-15',
                    'join_date' => '2024-01-01',
                    'education_level' => EducationLevel::BACHELOR,
                    'institution_name' => ['Universitas Indonesia', 'Institut Teknologi Bandung', 'BINUS University'][array_rand(['Universitas Indonesia', 'Institut Teknologi Bandung', 'BINUS University'])],
                    'major' => $emp['div'] === 'fin' ? 'Akuntansi' : ($emp['div'] === 'hr' ? 'Psikologi' : 'Teknik Informatika'),
                    'graduation_year' => 2018,
                    'salary_type' => SalaryType::MONTHLY,
                    'shift_id' => Shift::where('name', 'Office Hour')->first()?->id,
                    'address_detail' => 'Jl. Test No. '.rand(1, 50).', Jakarta',
                    'pin' => Hash::make('123456'),
                    'parent_id' => $emp['pos'] === 'OPS-MGR' || $emp['pos'] === 'HR-MGR' ? null : $parentId,
                ]
            );
        }
    }
}
