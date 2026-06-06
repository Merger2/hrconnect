<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $leaveTypes = [
            [
                'name' => 'Cuti Tahunan',
                'code' => 'ANNUAL',
                'quota' => 12,
                'is_paid' => true,
                'is_active' => true,
                'deducts_from_quota' => true,
            ],
            [
                'name' => 'Cuti Sakit',
                'code' => 'SICK',
                'quota' => 12,
                'is_paid' => true,
                'is_active' => true,
                'deducts_from_quota' => true,
            ],
            [
                'name' => 'Cuti Menstruasi',
                'code' => 'MENSTRUAL',
                'quota' => 2,
                'is_paid' => true,
                'is_active' => true,
                'deducts_from_quota' => false,
            ],
            [
                'name' => 'Cuti Besar',
                'code' => 'COMPASSIONATE',
                'quota' => 3,
                'is_paid' => true,
                'is_active' => true,
                'deducts_from_quota' => true,
            ],
            [
                'name' => 'Cuti Melahirkan',
                'code' => 'MATERNITY',
                'quota' => 90,
                'is_paid' => true,
                'is_active' => true,
                'deducts_from_quota' => false,
            ],
            [
                'name' => 'Cuti Tanpa Gaji',
                'code' => 'UNPAID',
                'quota' => 30,
                'is_paid' => false,
                'is_active' => true,
                'deducts_from_quota' => false,
            ],
            [
                'name' => 'Cuti Alasan Penting',
                'code' => 'IMPORTANT',
                'quota' => 3,
                'is_paid' => true,
                'is_active' => true,
                'deducts_from_quota' => true,
            ],
        ];

        foreach ($leaveTypes as $lt) {
            LeaveType::firstOrCreate(
                ['code' => $lt['code']],
                $lt
            );
        }

        $this->command?->info('LeaveTypes seeded: '.count($leaveTypes).' types');
    }
}
