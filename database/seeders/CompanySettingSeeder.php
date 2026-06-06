<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanySetting;
use Illuminate\Database\Seeder;

class CompanySettingSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::firstOrCreate(
            ['code' => 'HRCONNECT'],
            ['name' => 'HRConnect Indonesia', 'is_active' => true]
        );

        $settings = [
            // Password expiry
            ['key' => 'password_expiry_days', 'value' => '90', 'description' => 'Masa berlaku password dalam hari'],
            // Chronic late threshold
            ['key' => 'chronic_late_threshold', 'value' => '3', 'description' => 'Batas keterlambatan kronis per bulan'],
            // Leave carry forward
            ['key' => 'leave_carry_forward_deadline', 'value' => '03-31', 'description' => 'Batas waktu penggunaan cuti carry forward'],
            // Face recognition threshold (PRD §2.1)
            ['key' => 'face_distance_threshold', 'value' => '0.15', 'description' => 'Ambang jarak cosinus face recognition (default 0.15 ≈ 85% similaritas)'],
            // WFA auto-approve
            ['key' => 'wfa_auto_approve_days', 'value' => '3', 'description' => 'Hari kerja sebelum WFA di-auto-approve'],
            // PTKP values (sesuai PP terbaru, configurable)
            ['key' => 'ptkp_base_single', 'value' => '54000000', 'description' => 'PTKP TK/0 (single/tanpa tanggungan)'],
            ['key' => 'ptkp_base_married', 'value' => '58500000', 'description' => 'PTKP K/0 (married/tanpa tanggungan)'],
            ['key' => 'ptkp_per_dependent', 'value' => '4500000', 'description' => 'PTKP tambahan per tanggungan (max 3)'],
            ['key' => 'ptkp_max_dependents', 'value' => '3', 'description' => 'Max jumlah tanggungan untuk PTKP'],
            // Overtime tiers (sesuai UU Cipta Kerja)
            [
                'key' => 'overtime_tiers_weekday',
                'value' => json_encode([
                    ['from' => 1, 'to' => 1, 'multiplier' => 1.5],
                    ['from' => 2, 'to' => null, 'multiplier' => 2.0],
                ]),
                'description' => 'Tier lembur hari kerja (UU Cipta Kerja)',
            ],
            [
                'key' => 'overtime_tiers_holiday',
                'value' => json_encode([
                    ['from' => 1, 'to' => 8, 'multiplier' => 2.0],
                    ['from' => 9, 'to' => 9, 'multiplier' => 3.0],
                    ['from' => 10, 'to' => null, 'multiplier' => 4.0],
                ]),
                'description' => 'Tier lembur hari libur (UU Cipta Kerja)',
            ],
        ];

        foreach ($settings as $setting) {
            CompanySetting::firstOrCreate(
                ['key' => $setting['key'], 'company_id' => $company->id],
                [
                    'value' => $setting['value'],
                    'description' => $setting['description'],
                ]
            );
        }

        $this->command?->info('CompanySettings seeded: '.count($settings).' settings');
    }
}
