<?php

namespace Database\Factories;

use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShiftFactory extends Factory
{
    protected $model = Shift::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Office Hour', 'Flexible', 'Morning', 'Night 14-22']),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'late_tolerance_minutes' => 15,
            'is_active' => true,
        ];
    }
}
