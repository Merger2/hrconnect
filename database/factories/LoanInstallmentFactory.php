<?php

namespace Database\Factories;

use App\Enums\LoanInstallmentStatus;
use App\Models\Loan;
use App\Models\LoanInstallment;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoanInstallmentFactory extends Factory
{
    protected $model = LoanInstallment::class;

    public function definition(): array
    {
        return [
            'loan_id' => Loan::factory(),
            'amount_paid' => $this->faker->numberBetween(50000, 5000000),
            'installment_number' => $this->faker->numberBetween(1, 24),
            'status' => LoanInstallmentStatus::PENDING,
            'due_date' => $this->faker->date(),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => LoanInstallmentStatus::PAID,
            'paid_at' => now(),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => ['status' => LoanInstallmentStatus::OVERDUE]);
    }
}
