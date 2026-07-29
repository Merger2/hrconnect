<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\FaceDescriptor;
use Illuminate\Database\Eloquent\Factories\Factory;

class FaceDescriptorFactory extends Factory
{
    protected $model = FaceDescriptor::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'embedding' => array_fill(0, 128, 0.01),
            'is_active' => true,
            'metadata' => null,
        ];
    }

    public function withEmbedding(): static
    {
        return $this->state(fn (array $attrs) => [
            'embedding' => array_fill(0, 128, 0.01),
            'is_active' => true,
        ]);
    }
}
