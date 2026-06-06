<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\ReimbursementCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReimbursementCategoryFactory extends Factory
{
    protected $model = ReimbursementCategory::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->randomElement([
                'Transportasi', 'Konsumsi', 'Akomodasi',
                'Komunikasi', 'Pendidikan', 'Kesehatan',
            ]),
            'code' => strtoupper(fake()->unique()->lexify('RB_???')),
            'is_active' => true,
        ];
    }
}
