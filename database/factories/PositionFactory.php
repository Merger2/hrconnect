<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

class PositionFactory extends Factory
{
    protected $model = Position::class;

    public function definition(): array
    {
        $role = fake()->randomElement(['Staff', 'Senior Staff', 'Supervisor', 'Manager', 'Senior Manager', 'Head']);

        return [
            'department_id' => Department::factory(),
            'name' => $role.' '.fake()->randomElement(['IT', 'HR', 'Finance', 'Operational']),
            'code' => strtoupper(fake()->unique()->lexify('???').'-'.fake()->numerify('##')),
            'grade' => fake()->numberBetween(1, 5),
            'basic_salary' => fake()->numberBetween(4_000_000, 15_000_000),
            'allowance_jabatan' => fake()->numberBetween(250_000, 2_000_000),
            'is_active' => true,
        ];
    }
}
