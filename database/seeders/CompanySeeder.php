<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
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

        Branch::firstOrCreate(
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

        $this->command?->info("Company seeded: {$company->name}");
    }
}
