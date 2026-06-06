<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->city().' Branch',
            'address' => fake()->address(),
            'latitude' => fake()->latitude(-10, 6),
            'longitude' => fake()->longitude(95, 141),
            'radius' => fake()->numberBetween(50, 200),
            'is_main' => false,
            'is_active' => true,
        ];
    }

    public function main(): static
    {
        return $this->state(fn (array $attrs) => [
            'name' => 'Kantor Pusat',
            'is_main' => true,
            'latitude' => -6.2088,
            'longitude' => 106.8456,
        ]);
    }
}
