<?php

namespace Database\Factories;

use App\Enums\ReimbursementStatus;
use App\Models\Employee;
use App\Models\Reimbursement;
use App\Models\ReimbursementCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReimbursementFactory extends Factory
{
    protected $model = Reimbursement::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'category_id' => ReimbursementCategory::factory(),
            'title' => fake()->sentence(3),
            'expense_date' => fake()->dateTimeThisMonth()->format('Y-m-d'),
            'amount' => fake()->numberBetween(50000, 5000000),
            'description' => fake()->sentence(8),
            'status' => ReimbursementStatus::PENDING,
        ];
    }
}
