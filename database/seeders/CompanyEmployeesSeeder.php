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
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CompanyEmployeesSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Company & Branch (menggunakan yang sudah ada dari CompanyAndDivisionSeeder)
        $company = Company::where('code', 'DKMS-2025')->firstOrFail();
        $branch = Branch::where('company_id', $company->id)->where('is_main', true)->firstOrFail();

        // 2. Divisions & Positions (menggunakan yang sudah ada)
        $itDiv = Division::where('code', 'IT')->firstOrFail();
        $hrDiv = Division::where('code', 'HR')->firstOrFail();
        $finDiv = Division::where('code', 'FIN')->firstOrFail();
        $opsDiv = Division::where('code', 'OPS')->firstOrFail();

        // Ambil semua positions
        $positions = Position::whereIn('division_id', [$itDiv->id, $hrDiv->id, $finDiv->id, $opsDiv->id])->get()->keyBy('code');

        // 3. Create OWNER (super-admin user + employee record)
        $ownerUser = User::firstOrCreate(
            ['email' => 'owner@hrconnect.local'],
            [
                'name' => 'Owner PT DCM',
                'password' => Hash::make('owner12345'),
                'email_verified_at' => now(),
            ]
        );

        if (! $ownerUser->hasRole('super-admin')) {
            $ownerUser->assignRole('super-admin');
        }

        $ownerEmployee = Employee::firstOrCreate(
            ['user_id' => $ownerUser->id],
            [
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'division_id' => $hrDiv->id,
                'position_id' => $positions['HR-MGR']->id,
                'employee_number' => 'EMP-0001',
                'full_name' => 'Owner PT DCM',
                'nik' => '3276012345678901',
                'npwp' => '12.345.678.9-012.000',
                'phone' => '08111222333',
                'gender' => Gender::LAKI_LAKI,
                'marital_status' => MaritalStatus::MARRIED,
                'blood_type' => BloodType::O_PLUS,
                'status' => EmployeeStatus::ACTIVE,
                'birth_date' => '1980-01-01',
                'join_date' => '2020-01-01',
                'education_level' => EducationLevel::BACHELOR,
                'institution_name' => 'Universitas Indonesia',
                'major' => 'Manajemen',
                'graduation_year' => 2002,
                'salary_type' => SalaryType::MONTHLY,
                'address_detail' => 'Jl. Sudirman No. 123, Jakarta Selatan',
            ]
        );

        // 4. Create 50 employees using factories (dengan distribusi ke 4 divisi)
        $divisions = [$itDiv, $hrDiv, $finDiv, $opsDiv];

        // Mapping posisi per divisi
        $posByDiv = [
            'IT' => ['IT-STAFF', 'IT-MGR'],
            'HR' => ['HR-STAFF', 'HR-MGR'],
            'FIN' => ['FIN-STAFF', 'FIN-MGR'],
            'OPS' => ['OPS-STAFF'], // akan dibuat jika belum ada
        ];

        // Ensure OPS position exists
        if (! isset($positions['OPS-STAFF'])) {
            $opsStaffPos = Position::firstOrCreate(
                ['code' => 'OPS-STAFF'],
                ['division_id' => $opsDiv->id, 'name' => 'Staff Operasional', 'grade' => 1, 'basic_salary' => 4_500_000, 'allowance_jabatan' => 500_000, 'is_active' => true]
            );
            $positions['OPS-STAFF'] = $opsStaffPos;
        }

        $created = 0;
        foreach (range(1, 50) as $i) {
            $div = $divisions[$i % 4];
            $divCode = $div->code;

            // Pilih posisi random di divisi tsb
            $posCodes = $posByDiv[$divCode] ?? $posByDiv['IT'];
            $posCode = $posCodes[array_rand($posCodes)];
            $position = $positions[$posCode];

            $user = User::firstOrCreate(
                ['email' => "employee{$i}@hrconnect.local"],
                [
                    'name' => "Employee {$i}",
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            if (! $user->hasRole('employee')) {
                $user->assignRole('employee');
            }

            $employee = Employee::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                    'division_id' => $div->id,
                    'position_id' => $position->id,
                    'employee_number' => sprintf('EMP-%04d', $i + 1),
                    'nik' => '32760123456789'.str_pad($i, 2, '0', STR_PAD_LEFT),
                    'npwp' => $this->generateNpwp($i),
                    'phone' => '081'.str_pad($i, 9, '0', STR_PAD_LEFT),
                    'full_name' => "Employee {$i}",
                    'gender' => Gender::LAKI_LAKI,
                    'marital_status' => MaritalStatus::SINGLE,
                    'status' => EmployeeStatus::ACTIVE,
                    'birth_date' => '1995-01-01',
                    'join_date' => '2024-01-01',
                    'education_level' => EducationLevel::BACHELOR,
                    'institution_name' => 'Universitas Indonesia',
                    'major' => 'Manajemen',
                    'graduation_year' => 2018,
                    'salary_type' => SalaryType::MONTHLY,
                ]
            );

            if ($employee->wasRecentlyCreated) {
                $created++;
            }
        }

        $this->command?->info("Seeded: 1 Owner + {$created} Employees = ".($created + 1).' total people');
    }

    private function generateNpwp(int $i): string
    {
        $base = 1234567890 + $i * 11;

        return substr_replace(substr_replace(substr_replace((string) $base, '.', 2, 0), '.', 6, 0), '-', 10, 0).'.000';
    }
}
