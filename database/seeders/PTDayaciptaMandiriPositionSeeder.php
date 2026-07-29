<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\Position;
use Illuminate\Database\Seeder;

class PTDayaciptaMandiriPositionSeeder extends Seeder
{
    public function run(): void
    {
        // Get divisions
        $salesDiv = Division::where('code', 'SALES-MKT')->first();
        $financeDiv = Division::where('code', 'FIN-ADMIN')->first();
        $engDiv = Division::where('code', 'ENG-TECH')->first();

        $positions = [
            // Company-level Management (no division)
            [
                'division_id' => null,
                'name' => 'Commissioner',
                'code' => 'COMM-001',
                'grade' => 1,
                'basic_salary' => 20_000_000,
                'allowance_jabatan' => 5_000_000,
                'is_active' => true,
            ],
            [
                'division_id' => null,
                'name' => 'Director / COO',
                'code' => 'DIR-COO-001',
                'grade' => 1,
                'basic_salary' => 25_000_000,
                'allowance_jabatan' => 5_000_000,
                'is_active' => true,
            ],
            [
                'division_id' => null,
                'name' => 'Director / CMO',
                'code' => 'DIR-CMO-001',
                'grade' => 1,
                'basic_salary' => 25_000_000,
                'allowance_jabatan' => 5_000_000,
                'is_active' => true,
            ],

            // Sales & Marketing Division
            [
                'division_id' => $salesDiv->id,
                'name' => 'Sales Manager',
                'code' => 'SALES-MGR',
                'grade' => 2,
                'basic_salary' => 12_000_000,
                'allowance_jabatan' => 2_000_000,
                'is_active' => true,
            ],
            [
                'division_id' => $salesDiv->id,
                'name' => 'Account Manager',
                'code' => 'SALES-ACCOUNT-1',
                'grade' => 3,
                'basic_salary' => 7_000_000,
                'allowance_jabatan' => 1_000_000,
                'is_active' => true,
            ],
            [
                'division_id' => $salesDiv->id,
                'name' => 'Account Manager',
                'code' => 'SALES-ACCOUNT-2',
                'grade' => 3,
                'basic_salary' => 7_000_000,
                'allowance_jabatan' => 1_000_000,
                'is_active' => true,
            ],

            // Finance & Administration Division
            [
                'division_id' => $financeDiv->id,
                'name' => 'Finance Admin Manager',
                'code' => 'FIN-ADMIN-MGR',
                'grade' => 2,
                'basic_salary' => 10_000_000,
                'allowance_jabatan' => 1_500_000,
                'is_active' => true,
            ],
            [
                'division_id' => $financeDiv->id,
                'name' => 'Accounting',
                'code' => 'FIN-ACCOUNTING',
                'grade' => 3,
                'basic_salary' => 5_500_000,
                'allowance_jabatan' => 800_000,
                'is_active' => true,
            ],
            [
                'division_id' => $financeDiv->id,
                'name' => 'GA (General Affairs)',
                'code' => 'FIN-GA',
                'grade' => 3,
                'basic_salary' => 4_500_000,
                'allowance_jabatan' => 500_000,
                'is_active' => true,
            ],
            [
                'division_id' => $financeDiv->id,
                'name' => 'Messenger',
                'code' => 'FIN-MESSENGER',
                'grade' => 4,
                'basic_salary' => 3_000_000,
                'allowance_jabatan' => 300_000,
                'is_active' => true,
            ],

            // Engineering & Technology Division
            [
                'division_id' => $engDiv->id,
                'name' => 'Pre Sales',
                'code' => 'ENG-PRE-SALES',
                'grade' => 2,
                'basic_salary' => 8_000_000,
                'allowance_jabatan' => 1_000_000,
                'is_active' => true,
            ],
            [
                'division_id' => $engDiv->id,
                'name' => 'Senior Engineer',
                'code' => 'ENG-SENIOR',
                'grade' => 2,
                'basic_salary' => 9_000_000,
                'allowance_jabatan' => 1_500_000,
                'is_active' => true,
            ],
            [
                'division_id' => $engDiv->id,
                'name' => 'Junior Engineer',
                'code' => 'ENG-JUNIOR-1',
                'grade' => 3,
                'basic_salary' => 4_500_000,
                'allowance_jabatan' => 500_000,
                'is_active' => true,
            ],
            [
                'division_id' => $engDiv->id,
                'name' => 'Junior Engineer',
                'code' => 'ENG-JUNIOR-2',
                'grade' => 3,
                'basic_salary' => 4_500_000,
                'allowance_jabatan' => 500_000,
                'is_active' => true,
            ],
            [
                'division_id' => $engDiv->id,
                'name' => 'Junior Engineer',
                'code' => 'ENG-JUNIOR-3',
                'grade' => 3,
                'basic_salary' => 4_500_000,
                'allowance_jabatan' => 500_000,
                'is_active' => true,
            ],
            [
                'division_id' => $engDiv->id,
                'name' => 'Tehnisi AC',
                'code' => 'ENG-AC-TECH',
                'grade' => 4,
                'basic_salary' => 4_000_000,
                'allowance_jabatan' => 500_000,
                'is_active' => true,
            ],
        ];

        foreach ($positions as $position) {
            Position::firstOrCreate(
                ['code' => $position['code']],
                $position
            );
        }
    }
}
