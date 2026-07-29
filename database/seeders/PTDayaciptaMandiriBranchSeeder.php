<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Database\Seeder;

class PTDayaciptaMandiriBranchSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('code', 'DKMS-2025')->firstOrFail();

        Branch::firstOrCreate(
            [
                'company_id' => $company->id,
                'name' => 'Jakarta Head Office',
            ],
            [
                'address' => 'Jl. Pegambiran No.292B, RT.015/RW.008, Rawamangun, Kec. Pulogadung, Kota Jakarta Timur, DKI Jakarta 13220',
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'radius' => 100,
                'is_main' => true,
                'is_active' => true,
            ]
        );
    }
}
