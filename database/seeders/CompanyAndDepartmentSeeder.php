<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Seeder;

class CompanyAndDepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::firstOrCreate(
            ['code' => 'HRCONNECT'],
            [
                'name' => 'HRConnect Indonesia',
                'phone' => '021-5551234',
                'email' => 'info@hrconnect.local',
                'website' => 'https://hrconnect.local',
                'npwp' => '12.345.678.9-012.000',
                'logo' => 'logos/hrconnect.png',
                'is_active' => true,
            ]
        );

        $branch = Branch::firstOrCreate(
            ['name' => 'Kantor Pusat', 'company_id' => $company->id],
            [
                'address' => 'Jl. Sudirman No. 123, Jakarta Selatan',
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'radius' => 100,
                'is_main' => true,
                'is_active' => true,
            ]
        );

        $departments = [
            ['branch_id' => $branch->id, 'name' => 'Teknologi Informasi', 'code' => 'IT', 'description' => 'Divisi Teknologi Informasi', 'is_active' => true],
            ['branch_id' => $branch->id, 'name' => 'Sumber Daya Manusia', 'code' => 'HR', 'description' => 'Divisi Sumber Daya Manusia', 'is_active' => true],
            ['branch_id' => $branch->id, 'name' => 'Keuangan', 'code' => 'FIN', 'description' => 'Divisi Keuangan', 'is_active' => true],
            ['branch_id' => $branch->id, 'name' => 'Operasional', 'code' => 'OPS', 'description' => 'Divisi Operasional', 'is_active' => true],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(
                ['code' => $dept['code']],
                $dept
            );
        }

        $itDept = Department::where('code', 'IT')->first();
        $hrDept = Department::where('code', 'HR')->first();
        $finDept = Department::where('code', 'FIN')->first();

        $positions = [
            ['department_id' => $itDept->id, 'name' => 'Staff IT', 'code' => 'IT-STAFF', 'grade' => 1, 'basic_salary' => 5_000_000, 'allowance_jabatan' => 500_000, 'is_active' => true],
            ['department_id' => $itDept->id, 'name' => 'IT Manager', 'code' => 'IT-MGR', 'grade' => 3, 'basic_salary' => 12_000_000, 'allowance_jabatan' => 1_500_000, 'is_active' => true],
            ['department_id' => $hrDept->id, 'name' => 'Staff HR', 'code' => 'HR-STAFF', 'grade' => 1, 'basic_salary' => 4_500_000, 'allowance_jabatan' => 500_000, 'is_active' => true],
            ['department_id' => $hrDept->id, 'name' => 'HR Manager', 'code' => 'HR-MGR', 'grade' => 3, 'basic_salary' => 10_000_000, 'allowance_jabatan' => 1_500_000, 'is_active' => true],
            ['department_id' => $finDept->id, 'name' => 'Staff Finance', 'code' => 'FIN-STAFF', 'grade' => 1, 'basic_salary' => 4_500_000, 'allowance_jabatan' => 500_000, 'is_active' => true],
            ['department_id' => $finDept->id, 'name' => 'Finance Manager', 'code' => 'FIN-MGR', 'grade' => 3, 'basic_salary' => 10_000_000, 'allowance_jabatan' => 1_500_000, 'is_active' => true],
        ];

        foreach ($positions as $pos) {
            Position::firstOrCreate(
                ['code' => $pos['code']],
                $pos
            );
        }

        $this->command?->info("Company seeded: {$company->name} with ".count($departments).' departments and '.count($positions).' positions');
    }
}
