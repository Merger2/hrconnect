<?php

namespace Database\Seeders;

use App\Models\Shift;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        $shifts = [
            [
                'name' => 'Regular',
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'late_tolerance_minutes' => 15,
                'is_active' => true,
            ],
            [
                'name' => 'Flexible',
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'late_tolerance_minutes' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Morning',
                'start_time' => '06:00:00',
                'end_time' => '14:00:00',
                'late_tolerance_minutes' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Afternoon',
                'start_time' => '14:00:00',
                'end_time' => '22:00:00',
                'late_tolerance_minutes' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Night',
                'start_time' => '22:00:00',
                'end_time' => '06:00:00',
                'late_tolerance_minutes' => 10,
                'is_active' => true,
            ],
        ];

        foreach ($shifts as $shift) {
            Shift::firstOrCreate(
                ['name' => $shift['name']],
                $shift
            );
        }

        $this->command?->info('Shifts seeded: '.count($shifts).' shifts');
    }
}
