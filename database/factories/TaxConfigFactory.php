<?php

namespace Database\Factories;

use App\Models\TaxConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxConfigFactory extends Factory
{
    protected $model = TaxConfig::class;

    public function definition(): array
    {
        return [
            'ter_category' => 'A',
            'min_income' => 0,
            'max_income' => 100000000,
            'rate' => 0.0500,
            'effective_rate' => 0.0300,
        ];
    }
}
