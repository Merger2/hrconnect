<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Division;
use App\Models\JobLevel;
use App\Models\JobTitle;
use App\Models\Position;
use Illuminate\Database\Seeder;

class CompanyAndDivisionSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::firstOrCreate(
            ['code' => 'DKMS-2025'],
            [
                'name' => 'PT Daya Cipta Mandiri Solusi',
                'phone' => '+62 21 38859238',
                'email' => 'contact@dayaciptamandiri.com',
                'website' => 'https://dayaciptamandiri.com',
                'npwp' => '12.345.678.9-012.000',
                'logo' => 'logos/hrconnect.png',
                'is_active' => true,
            ]
        );

        $branch = Branch::firstOrCreate(
            ['name' => 'Kantor Pusat', 'company_id' => $company->id],
            [
                'address' => 'Jl. Pegambiran No.292 B, RT.15/RW.8, Rawamangun, Kec. Pulo Gadung, Kota Jakarta Timur, DKI Jakarta 13220',
                'latitude' => -6.2069,
                'longitude' => 106.8775,
                'radius' => 200,
                'is_main' => true,
                'is_active' => true,
            ]
        );

        $divisions = [
            ['branch_id' => $branch->id, 'name' => 'Teknologi Informasi', 'code' => 'IT', 'description' => 'Divisi Teknologi Informasi', 'is_active' => true],
            ['branch_id' => $branch->id, 'name' => 'Sumber Daya Manusia', 'code' => 'HR', 'description' => 'Divisi Sumber Daya Manusia', 'is_active' => true],
            ['branch_id' => $branch->id, 'name' => 'Keuangan', 'code' => 'FIN', 'description' => 'Divisi Keuangan', 'is_active' => true],
            ['branch_id' => $branch->id, 'name' => 'Operasional', 'code' => 'OPS', 'description' => 'Divisi Operasional', 'is_active' => true],
        ];

        foreach ($divisions as $div) {
            Division::firstOrCreate(
                ['code' => $div['code']],
                $div
            );
        }

        $itDiv = Division::where('code', 'IT')->first();
        $hrDiv = Division::where('code', 'HR')->first();
        $finDiv = Division::where('code', 'FIN')->first();
        $opsDiv = Division::where('code', 'OPS')->first();

        $positions = [
            ['division_id' => $itDiv->id, 'name' => 'Staff IT', 'code' => 'IT-STAFF', 'grade' => 1, 'basic_salary' => 5_000_000, 'allowance_jabatan' => 500_000, 'is_active' => true],
            ['division_id' => $itDiv->id, 'name' => 'IT Manager', 'code' => 'IT-MGR', 'grade' => 3, 'basic_salary' => 12_000_000, 'allowance_jabatan' => 1_500_000, 'is_active' => true],
            ['division_id' => $hrDiv->id, 'name' => 'Staff HR', 'code' => 'HR-STAFF', 'grade' => 1, 'basic_salary' => 4_500_000, 'allowance_jabatan' => 500_000, 'is_active' => true],
            ['division_id' => $hrDiv->id, 'name' => 'HR Manager', 'code' => 'HR-MGR', 'grade' => 3, 'basic_salary' => 10_000_000, 'allowance_jabatan' => 1_500_000, 'is_active' => true],
            ['division_id' => $finDiv->id, 'name' => 'Staff Finance', 'code' => 'FIN-STAFF', 'grade' => 1, 'basic_salary' => 4_500_000, 'allowance_jabatan' => 500_000, 'is_active' => true],
            ['division_id' => $finDiv->id, 'name' => 'Finance Manager', 'code' => 'FIN-MGR', 'grade' => 3, 'basic_salary' => 10_000_000, 'allowance_jabatan' => 1_500_000, 'is_active' => true],
            ['division_id' => $opsDiv?->id ?? $itDiv->id, 'name' => 'Staff Operasional', 'code' => 'OPS-STAFF', 'grade' => 1, 'basic_salary' => 4_200_000, 'allowance_jabatan' => 400_000, 'is_active' => true],
            ['division_id' => $opsDiv?->id ?? $itDiv->id, 'name' => 'Operational Manager', 'code' => 'OPS-MGR', 'grade' => 3, 'basic_salary' => 9_000_000, 'allowance_jabatan' => 1_200_000, 'is_active' => true],
        ];

        foreach ($positions as $pos) {
            Position::firstOrCreate(
                ['code' => $pos['code']],
                $pos
            );
        }

        $jobLevels = [
            ['name' => 'Top Management', 'rank' => 1],
            ['name' => 'Managerial', 'rank' => 2],
            ['name' => 'Senior', 'rank' => 3],
            ['name' => 'Officer', 'rank' => 4],
            ['name' => 'Staff', 'rank' => 5],
        ];

        foreach ($jobLevels as $level) {
            JobLevel::firstOrCreate(
                ['name' => $level['name']],
                $level
            );
        }

        $jobTitles = [
            ['name' => 'Head', 'job_level' => 'Top Management'],
            ['name' => 'Manager', 'job_level' => 'Managerial'],
            ['name' => 'Senior', 'job_level' => 'Senior'],
            ['name' => 'Officer', 'job_level' => 'Officer'],
            ['name' => 'Staff', 'job_level' => 'Staff'],
        ];

        foreach ($jobTitles as $title) {
            $jobLevel = JobLevel::where('name', $title['job_level'])->firstOrFail();

            JobTitle::firstOrCreate(
                ['name' => $title['name']],
                [
                    'job_level_id' => $jobLevel->id,
                    'is_active' => true,
                ]
            );
        }

        $this->command?->info('Company seeded: '.$company->name.' with '.count($divisions).' divisions, '.count($positions).' positions and '.count($jobTitles).' job titles');
    }
}
