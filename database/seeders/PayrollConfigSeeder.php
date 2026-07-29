<?php

namespace Database\Seeders;

use App\Enums\BpjsType;
use App\Models\BpjsConfig;
use Illuminate\Database\Seeder;

class PayrollConfigSeeder extends Seeder
{
    public function run(): void
    {
        // BPJS configs — rates 2025
        $bpjsConfigs = [
            [
                'name' => BpjsType::KESEHATAN,
                'employer_rate' => 0.04,
                'employee_rate' => 0.01,
                'ceiling' => 12000000,
            ],
            [
                'name' => BpjsType::JHT,
                'employer_rate' => 0.037,
                'employee_rate' => 0.02,
                'ceiling' => null,
            ],
            [
                'name' => BpjsType::JP,
                'employer_rate' => 0.02,
                'employee_rate' => 0.01,
                'ceiling' => 9559600,
            ],
            [
                'name' => BpjsType::JKK,
                'employer_rate' => 0.0024,
                'employee_rate' => 0.0,
                'ceiling' => null,
            ],
            [
                'name' => BpjsType::JKM,
                'employer_rate' => 0.003,
                'employee_rate' => 0.0,
                'ceiling' => null,
            ],
        ];

        foreach ($bpjsConfigs as $bpjs) {
            BpjsConfig::firstOrCreate(
                ['name' => $bpjs['name']],
                $bpjs
            );
        }

        $this->command?->info('PayrollConfig seeded: '.count($bpjsConfigs).' BPJS configs');
    }
}
