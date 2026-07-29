<?php

namespace Database\Factories;

use App\Models\EmployeeDocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeDocumentType>
 */
class EmployeeDocumentTypeFactory extends Factory
{
    protected $model = EmployeeDocumentType::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'slug' => fake()->unique()->slug(),
            'code' => fake()->unique()->slug(),
            'is_required' => false,
            'is_active' => true,
        ];
    }
}
