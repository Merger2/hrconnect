<?php

namespace Database\Factories;

use App\Models\Division;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PositionFactory extends Factory
{
    protected $model = Position::class;

    public function definition(): array
    {
        $role = fake()->randomElement(['Staff', 'Senior Staff', 'Supervisor', 'Manager', 'Senior Manager', 'Head']);

        return [
            'division_id' => Division::factory(),
            'name' => $role.' '.fake()->randomElement(['IT', 'HR', 'Finance', 'Operational']),
            'code' => 'POS'.strtoupper(Str::random(8)),
            'grade' => fake()->numberBetween(1, 5),
            'basic_salary' => fake()->numberBetween(4_000_000, 15_000_000),
            'allowance_jabatan' => fake()->numberBetween(250_000, 2_000_000),
            'is_active' => true,
        ];
    }
}
