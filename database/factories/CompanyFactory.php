<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'website' => 'https://'.fake()->domainName(),
            'npwp' => fake()->unique()->numerify('##.###.###.#-###.###'),
            'logo' => null,
            'is_active' => true,
        ];
    }
}
