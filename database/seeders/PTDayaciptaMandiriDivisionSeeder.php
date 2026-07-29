<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Division;
use Illuminate\Database\Seeder;

class PTDayaciptaMandiriDivisionSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('code', 'DKMS-2025')->firstOrFail();
        $branch = $company->branches()->first();

        $divisions = [
            [
                'branch_id' => $branch->id,
                'name' => 'Sales & Marketing',
                'code' => 'SALES-MKT',
                'description' => 'Sales, Marketing, Business Development, Account Management',
                'is_active' => true,
            ],
            [
                'branch_id' => $branch->id,
                'name' => 'Finance & Administration',
                'code' => 'FIN-ADMIN',
                'description' => 'Accounting, Treasury, General Affairs, Admin',
                'is_active' => true,
            ],
            [
                'branch_id' => $branch->id,
                'name' => 'Engineering & Technology',
                'code' => 'ENG-TECH',
                'description' => 'Development, IT, Maintenance, Pre Sales',
                'is_active' => true,
            ],
        ];

        foreach ($divisions as $division) {
            Division::firstOrCreate(
                ['code' => $division['code']],
                $division
            );
        }
    }
}
