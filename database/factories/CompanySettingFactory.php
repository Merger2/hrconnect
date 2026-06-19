<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanySetting;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanySetting>
 */
class CompanySettingFactory extends Factory
{
    #[UseModel(CompanySetting::class)]
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'key' => $this->faker->unique()->word(),
            'value' => ['value' => $this->faker->word()],
            'description' => $this->faker->sentence(),
        ];
    }

    public function withKey(string $key, mixed $value = null): static
    {
        return $this->state(fn () => [
            'key' => $key,
            'value' => ['value' => $value ?? $this->faker->word()],
        ]);
    }
}
