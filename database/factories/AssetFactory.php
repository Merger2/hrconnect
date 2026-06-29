<?php

namespace Database\Factories;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->words(3, true),
            'serial_number' => $this->faker->unique()->bothify('AST-####-????'),
            'code' => $this->faker->unique()->bothify('ACC-####'),
            'category' => $this->faker->randomElement(['elektronik', 'furniture', 'kendaraan', 'peralatan']),
            'is_available' => true,
            'status' => AssetStatus::AVAILABLE,
        ];
    }

    public function assigned(): static
    {
        return $this->state(fn () => [
            'status' => AssetStatus::ASSIGNED,
            'is_available' => false,
        ]);
    }

    public function disposed(): static
    {
        return $this->state(fn () => ['status' => AssetStatus::DISPOSED]);
    }
}
