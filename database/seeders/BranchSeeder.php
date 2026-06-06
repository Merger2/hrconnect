<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::firstOrCreate(
            ['code' => 'HRCONNECT'],
            ['name' => 'HRConnect Indonesia', 'is_active' => true]
        );

        $branches = [
            ['name' => 'Cabang Bandung', 'address' => 'Jl. Asia Afrika No. 45, Bandung', 'latitude' => -6.9175, 'longitude' => 107.6191, 'radius' => 100, 'is_main' => false, 'is_active' => true],
            ['name' => 'Cabang Surabaya', 'address' => 'Jl. Tunjungan No. 21, Surabaya', 'latitude' => -7.2575, 'longitude' => 112.7521, 'radius' => 100, 'is_main' => false, 'is_active' => true],
            ['name' => 'Cabang Yogyakarta', 'address' => 'Jl. Malioboro No. 10, Yogyakarta', 'latitude' => -7.7956, 'longitude' => 110.3695, 'radius' => 80, 'is_main' => false, 'is_active' => true],
        ];

        foreach ($branches as $branch) {
            Branch::firstOrCreate(
                ['name' => $branch['name'], 'company_id' => $company->id],
                array_merge($branch, ['company_id' => $company->id])
            );
        }

        $this->command?->info('BranchSeeder: '.count($branches).' cabang tambahan dibuat.');
    }
}
