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

/**
 * K6PerformanceTestSeeder — akun khusus performance testing dengan K6.
 *
 * 5 akun (satu per role), semua password: 'password'.
 *
 * | Role       | Email                          | Employee #  |
 * |------------|--------------------------------|-------------|
 * | employee   | k6.employee@hrconnect.test     | K6-EMP-001  |
 * | admin/HR   | k6.hr@hrconnect.test           | K6-EMP-002  |
 * | manager    | k6.manager@hrconnect.test      | K6-EMP-003  |
 * | finance    | k6.fin@hrconnect.test          | K6-EMP-004  |
 * | super-admin| k6.admin@hrconnect.test        | —           |
 *
 * Password seragam supaya K6 env var cukup satu: K6_USER_PASSWORD=password.
 *
 * Idempotent: aman dijalankan berkali-kali (firstOrCreate).
 *
 * Jalankan: php artisan db:seed --class=K6PerformanceTestSeeder
 */
class K6PerformanceTestSeeder extends Seeder
{
    private const PASSWORD = 'password';

    public function run(): void
    {
        if (app()->isProduction() && ! filter_var(env('SEED_DEMO', false), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $company = Company::where('code', 'DKMS-2025')->firstOrFail();
        $branch = Branch::where('company_id', $company->id)->where('is_main', true)->firstOrFail();

        $divMap = [
            'it' => Division::where('code', 'IT')->firstOrFail(),
            'hr' => Division::where('code', 'HR')->firstOrFail(),
            'fin' => Division::where('code', 'FIN')->firstOrFail(),
            'ops' => Division::where('code', 'OPS')->firstOrFail(),
        ];

        $posMap = Position::whereIn('code', ['IT-STAFF', 'HR-MGR', 'FIN-STAFF', 'OPS-MGR'])
            ->get()
            ->keyBy('code');

        $users = [
            [
                'email' => 'k6.employee@hrconnect.test',
                'name' => 'K6 Test Employee',
                'role' => 'employee',
            ],
            [
                'email' => 'k6.hr@hrconnect.test',
                'name' => 'K6 Test HR',
                'role' => 'admin',
            ],
            [
                'email' => 'k6.manager@hrconnect.test',
                'name' => 'K6 Test Manager',
                'role' => 'manager',
            ],
            [
                'email' => 'k6.fin@hrconnect.test',
                'name' => 'K6 Test Finance',
                'role' => 'finance',
            ],
            [
                'email' => 'k6.admin@hrconnect.test',
                'name' => 'K6 Test SuperAdmin',
                'role' => 'super-admin',
            ],
        ];

        $employeeData = [
            'k6.employee@hrconnect.test' => [
                'div' => 'it',
                'pos' => 'IT-STAFF',
                'emp_no' => 'K6-EMP-001',
                'full_name' => 'K6 Test Employee',
                'nik' => '3276010000000101',
                'npwp' => '99.999.999.9-999.101',
                'phone' => '081900000101',
            ],
            'k6.hr@hrconnect.test' => [
                'div' => 'hr',
                'pos' => 'HR-MGR',
                'emp_no' => 'K6-EMP-002',
                'full_name' => 'K6 Test HR',
                'nik' => '3276010000000102',
                'npwp' => '99.999.999.9-999.102',
                'phone' => '081900000102',
            ],
            'k6.manager@hrconnect.test' => [
                'div' => 'ops',
                'pos' => 'OPS-MGR',
                'emp_no' => 'K6-EMP-003',
                'full_name' => 'K6 Test Manager',
                'nik' => '3276010000000103',
                'npwp' => '99.999.999.9-999.103',
                'phone' => '081900000103',
            ],
            'k6.fin@hrconnect.test' => [
                'div' => 'fin',
                'pos' => 'FIN-STAFF',
                'emp_no' => 'K6-EMP-004',
                'full_name' => 'K6 Test Finance',
                'nik' => '3276010000000104',
                'npwp' => '99.999.999.9-999.104',
                'phone' => '081900000104',
            ],
        ];

        // OPS-MGR position (fallback kalau gak ada di posMap)
        $opsMgr = $posMap->get('OPS-MGR') ?? Position::where('code', 'OPS-MGR')->firstOrFail();

        foreach ($users as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make(self::PASSWORD),
                    'email_verified_at' => now(),
                    'password_changed_at' => now(),
                ]
            );

            // Sync password kalau hash gak match (idempotent)
            if (! Hash::check(self::PASSWORD, $user->password)) {
                $user->password = Hash::make(self::PASSWORD);
                $user->password_changed_at = now();
                $user->save();
            }

            $roleName = $data['role'];
            if (! $user->hasRole($roleName)) {
                $user->assignRole($roleName);
                $user->refresh();
            }

            if ($roleName === 'super-admin') {
                if ($user->group !== 'superadmin') {
                    $user->update(['group' => 'superadmin']);
                }

                continue;
            }

            $emp = $employeeData[$data['email']] ?? null;
            if ($emp === null) {
                continue;
            }

            // Parent: IT Manager dari CompanyEmployeesSeeder
            $parentId = User::where('email', 'employee1@hrconnect.local')
                ->first()?->employee?->id;

            $divCode = $emp['div'];
            $divId = match ($divCode) {
                'it' => $divMap['it']->id,
                'hr' => $divMap['hr']->id,
                'fin' => $divMap['fin']->id,
                'ops' => $divMap['ops']->id,
                default => $divMap['it']->id,
            };

            Employee::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                    'division_id' => $divId,
                    'position_id' => $emp['pos'] === 'OPS-MGR'
                        ? $opsMgr->id
                        : ($posMap->get($emp['pos']) ?? $posMap['IT-STAFF'])->id,
                    'employee_number' => $emp['emp_no'],
                    'full_name' => $emp['full_name'],
                    'nik' => $emp['nik'],
                    'npwp' => $emp['npwp'],
                    'phone' => $emp['phone'],
                    'gender' => Gender::LAKI_LAKI,
                    'marital_status' => $emp['pos'] === 'OPS-MGR'
                        ? MaritalStatus::MARRIED
                        : MaritalStatus::SINGLE,
                    'blood_type' => BloodType::O_PLUS,
                    'status' => EmployeeStatus::ACTIVE,
                    'birth_date' => '1995-06-15',
                    'join_date' => '2024-01-01',
                    'education_level' => EducationLevel::BACHELOR,
                    'institution_name' => 'Universitas Indonesia',
                    'major' => match ($divCode) {
                        'fin' => 'Akuntansi',
                        'hr' => 'Psikologi',
                        default => 'Teknik Informatika',
                    },
                    'graduation_year' => 2018,
                    'salary_type' => SalaryType::MONTHLY,
                    'shift_id' => Shift::where('name', 'Office Hour')->first()?->id,
                    'address_detail' => 'Jl. K6 Test No. 1, Jakarta',
                    'pin' => Hash::make('123456'),
                    'parent_id' => $emp['pos'] === 'OPS-MGR' || $emp['pos'] === 'HR-MGR'
                        ? null
                        : $parentId,
                ]
            );
        }

        // Create Sanctum personal access tokens for API load testing.
        // Tokens printed to console — copy to .env as K6_TOKEN_* vars.
        $tokenUsers = [
            'k6.employee@hrconnect.test' => 'K6_TOKEN_EMPLOYEE',
            'k6.hr@hrconnect.test' => 'K6_TOKEN_ADMIN',
            'k6.manager@hrconnect.test' => 'K6_TOKEN_MANAGER',
        ];

        $tokens = [];
        foreach ($tokenUsers as $email => $envKey) {
            $user = User::where('email', $email)->first();
            if (! $user) {
                continue;
            }

            // Revoke old K6 tokens (idempotent)
            $user->tokens()->where('name', 'k6-load-test')->delete();
            $plainText = $user->createToken('k6-load-test')->plainTextToken;
            $tokens[$envKey] = $plainText;
        }

        $this->command?->info('K6 performance test accounts seeded: 5 users + API tokens created.');
        $this->command?->info('');
        $this->command?->info('Add these to your .env for API load testing:');
        $this->command?->info('');
        foreach ($tokens as $envKey => $token) {
            $this->command?->info("{$envKey}={$token}");
        }
    }
}
