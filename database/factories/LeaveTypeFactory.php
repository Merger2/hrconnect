<?php

namespace Database\Factories;

use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveTypeFactory extends Factory
{
    protected $model = LeaveType::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'Cuti Tahunan', 'Cuti Sakit', 'Cuti Melahirkan',
                'Cuti Besar', 'Cuti Penting', 'Cuti Menstruasi',
            ]),
            'code' => strtoupper(fake()->unique()->lexify('CUTI_???')),
            'quota' => fake()->numberBetween(0, 12),
            'is_paid' => true,
            'deducts_from_quota' => true,
            'is_active' => true,
        ];
    }
}
