<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Division;
use Illuminate\Database\Eloquent\Factories\Factory;

class DivisionFactory extends Factory
{
    protected $model = Division::class;

    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'name' => fake()->randomElement([
                'Information Technology',
                'Human Resources',
                'Finance & Accounting',
                'Operations',
                'Marketing & Sales',
                'Research & Development',
                'Legal & Compliance',
                'Administration',
            ]),
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
