<?php

namespace Database\Factories;

use App\Enums\HandoverCategory;
use App\Models\Asset;
use App\Models\AssetHandover;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetHandoverFactory extends Factory
{
    protected $model = AssetHandover::class;

    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'employee_id' => Employee::factory(),
            'handover_date' => $this->faker->date(),
            'condition' => $this->faker->randomElement(['baik', 'baik', 'baik', 'rusak ringan']),
            'category' => HandoverCategory::ASSET,
        ];
    }

    public function returned(): static
    {
        return $this->state(fn () => [
            'return_date' => $this->faker->date(),
            'condition' => 'baik',
        ]);
    }
}
