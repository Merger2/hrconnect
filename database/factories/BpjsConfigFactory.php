<?php

namespace Database\Factories;

use App\Models\BpjsConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

class BpjsConfigFactory extends Factory
{
    protected $model = BpjsConfig::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['kesehatan', 'jht', 'jp', 'jkk', 'jkm']),
            'employer_rate' => 0.0400,
            'employee_rate' => 0.0100,
            'ceiling' => 10000000,
        ];
    }
}
