<?php

namespace Database\Factories;

use App\Models\Barcode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BarcodeFactory extends Factory
{
    protected $model = Barcode::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word().' QR',
            'value' => Str::random(16),
            'secret_key' => Str::random(64),
            'latitude' => fake()->latitude(-10, 5),
            'longitude' => fake()->longitude(95, 140),
            'radius' => fake()->randomElement([50, 75, 100, 150]),
            'dynamic_enabled' => false,
            'dynamic_ttl_seconds' => 60,
        ];
    }

    public function dynamic(): static
    {
        return $this->state(fn (array $attrs) => [
            'dynamic_enabled' => true,
            'dynamic_ttl_seconds' => 60,
        ]);
    }
}
