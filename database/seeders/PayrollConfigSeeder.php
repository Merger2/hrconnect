<?php

namespace Database\Seeders;

use App\Enums\BpjsType;
use App\Enums\TerCategory;
use App\Models\BpjsConfig;
use App\Models\TaxConfig;
use Illuminate\Database\Seeder;

class PayrollConfigSeeder extends Seeder
{
    public function run(): void
    {
        // Tax configs — Tabel PPh21 2024/2025
        $taxConfigs = [
            ['ter_category' => TerCategory::A, 'min_income' => 0, 'max_income' => 54000000, 'rate' => 0.05, 'effective_rate' => 0.00],
            ['ter_category' => TerCategory::A, 'min_income' => 54000000, 'max_income' => 56500000, 'rate' => 0.15, 'effective_rate' => 0.00],
            ['ter_category' => TerCategory::A, 'min_income' => 56500000, 'max_income' => 63000000, 'rate' => 0.15, 'effective_rate' => 0.00],
            ['ter_category' => TerCategory::A, 'min_income' => 63000000, 'max_income' => 69500000, 'rate' => 0.15, 'effective_rate' => 0.00],
            ['ter_category' => TerCategory::A, 'min_income' => 69500000, 'max_income' => 93000000, 'rate' => 0.25, 'effective_rate' => 0.00],
            ['ter_category' => TerCategory::A, 'min_income' => 93000000, 'max_income' => 160000000, 'rate' => 0.30, 'effective_rate' => 0.00],
            ['ter_category' => TerCategory::A, 'min_income' => 160000000, 'max_income' => 189000000, 'rate' => 0.35, 'effective_rate' => 0.00],
            ['ter_category' => TerCategory::A, 'min_income' => 189000000, 'max_income' => 250000000, 'rate' => 0.35, 'effective_rate' => 0.00],
            ['ter_category' => TerCategory::A, 'min_income' => 250000000, 'max_income' => 400000000, 'rate' => 0.35, 'effective_rate' => 0.00],
            ['ter_category' => TerCategory::A, 'min_income' => 400000000, 'max_income' => 500000000, 'rate' => 0.35, 'effective_rate' => 0.00],
            ['ter_category' => TerCategory::A, 'min_income' => 500000000, 'max_income' => 600000000, 'rate' => 0.35, 'effective_rate' => 0.00],
            ['ter_category' => TerCategory::A, 'min_income' => 600000000, 'max_income' => 1000000000, 'rate' => 0.35, 'effective_rate' => 0.00],
            ['ter_category' => TerCategory::A, 'min_income' => 1000000000, 'max_income' => 5000000000, 'rate' => 0.35, 'effective_rate' => 0.00],
            ['ter_category' => TerCategory::A, 'min_income' => 5000000000, 'max_income' => 9999999999, 'rate' => 0.35, 'effective_rate' => 0.00],

            ['ter_category' => TerCategory::B, 'min_income' => 0, 'max_income' => 58500000, 'rate' => 0.05, 'effective_rate' => 0.00],
            ['ter_category' => TerCategory::B, 'min_income' => 58500000, 'max_income' => 61000000, 'rate' => 0.15, 'effective_rate' => 0.00],
            ['ter_category' => TerCategory::B, 'min_income' => 61000000, 'max_income' => 67500000, 'rate' => 0.15, 'effective_rate' => 0.00],
            ['ter_category' => TerCategory::B, 'min_income' => 67500000, 'max_income' => 74000000, 'rate' => 0.15, 'effective_rate' => 0.00],
            ['ter_category' => TerCategory::B, 'min_income' => 74000000, 'max_income' => 97500000, 'rate' => 0.25, 'effective_rate' => 0.00],
            ['ter_category' => TerCategory::B, 'min_income' => 97500000, 'max_income' => 164500000, 'rate' => 0.30, 'effective_rate' => 0.00],
            ['ter_category' => TerCategory::B, 'min_income' => 164500000, 'max_income' => 193500000, 'rate' => 0.35, 'effective_rate' => 0.00],
            ['ter_category' => TerCategory::B, 'min_income' => 193500000, 'max_income' => 254500000, 'rate' => 0.35, 'effective_rate' => 0.00],
            ['ter_category' => TerCategory::B, 'min_income' => 254500000, 'max_income' => 404500000, 'rate' => 0.35, 'effective_rate' => 0.00],
            ['ter_category' => TerCategory::B, 'min_income' => 404500000, 'max_income' => 504500000, 'rate' => 0.35, 'effective_rate' => 0.00],
            ['ter_category' => TerCategory::B, 'min_income' => 504500000, 'max_income' => 604500000, 'rate' => 0.35, 'effective_rate' => 0.00],
            ['ter_category' => TerCategory::B, 'min_income' => 604500000, 'max_income' => 1004500000, 'rate' => 0.35, 'effective_rate' => 0.00],
            ['ter_category' => TerCategory::B, 'min_income' => 1004500000, 'max_income' => 5004500000, 'rate' => 0.35, 'effective_rate' => 0.00],
            ['ter_category' => TerCategory::B, 'min_income' => 5004500000, 'max_income' => 9999999999, 'rate' => 0.35, 'effective_rate' => 0.00],

            ['ter_category' => TerCategory::C, 'min_income' => 0, 'max_income' => 63000000, 'rate' => 0.05, 'effective_rate' => 0.00],
            ['ter_category' => TerCategory::C, 'min_income' => 63000000, 'max_income' => 65500000, 'rate' => 0.15, 'effective_rate' => 0.00],
            ['ter_category' => TerCategory::C, 'min_income' => 65500000, 'max_income' => 72000000, 'rate' => 0.15, 'effective_rate' => 0.00],
            ['ter_category' => TerCategory::C, 'min_income' => 72000000, 'max_income' => 78500000, 'rate' => 0.15, 'effective_rate' => 0.00],
            ['ter_category' => TerCategory::C, 'min_income' => 78500000, 'max_income' => 102000000, 'rate' => 0.25, 'effective_rate' => 0.00],
            ['ter_category' => TerCategory::C, 'min_income' => 102000000, 'max_income' => 169000000, 'rate' => 0.30, 'effective_rate' => 0.00],
            ['ter_category' => TerCategory::C, 'min_income' => 169000000, 'max_income' => 198000000, 'rate' => 0.35, 'effective_rate' => 0.00],
            ['ter_category' => TerCategory::C, 'min_income' => 198000000, 'max_income' => 259000000, 'rate' => 0.35, 'effective_rate' => 0.00],
            ['ter_category' => TerCategory::C, 'min_income' => 259000000, 'max_income' => 409000000, 'rate' => 0.35, 'effective_rate' => 0.00],
            ['ter_category' => TerCategory::C, 'min_income' => 409000000, 'max_income' => 509000000, 'rate' => 0.35, 'effective_rate' => 0.00],
            ['ter_category' => TerCategory::C, 'min_income' => 509000000, 'max_income' => 609000000, 'rate' => 0.35, 'effective_rate' => 0.00],
            ['ter_category' => TerCategory::C, 'min_income' => 609000000, 'max_income' => 1009000000, 'rate' => 0.35, 'effective_rate' => 0.00],
            ['ter_category' => TerCategory::C, 'min_income' => 1009000000, 'max_income' => 5009000000, 'rate' => 0.35, 'effective_rate' => 0.00],
            ['ter_category' => TerCategory::C, 'min_income' => 5009000000, 'max_income' => 9999999999, 'rate' => 0.35, 'effective_rate' => 0.00],
        ];

        foreach ($taxConfigs as $tax) {
            TaxConfig::firstOrCreate(
                ['ter_category' => $tax['ter_category'], 'min_income' => $tax['min_income']],
                $tax
            );
        }

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

        $this->command?->info('PayrollConfig seeded: '.count($taxConfigs).' tax brackets, '.count($bpjsConfigs).' BPJS configs');
    }
}
