<?php

namespace Database\Factories;

use App\Enums\LoanStatus;
use App\Models\Employee;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoanFactory extends Factory
{
    protected $model = Loan::class;

    public function definition(): array
    {
        $amount = $this->faker->numberBetween(100000, 10000000);
        $tenorMonths = $this->faker->numberBetween(3, 24);
        $interestRate = 0;

        return [
            'employee_id' => Employee::factory(),
            'created_by' => User::factory(),
            'amount' => $amount,
            'interest_rate' => $interestRate,
            'tenor_months' => $tenorMonths,
            'monthly_installment' => round($amount / $tenorMonths, 2),
            'status' => LoanStatus::PENDING,
            'is_settled' => false,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => LoanStatus::PENDING]);
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => LoanStatus::APPROVED]);
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => LoanStatus::ACTIVE]);
    }

    public function paidOff(): static
    {
        return $this->state(fn () => ['status' => LoanStatus::PAID_OFF, 'is_settled' => true]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => LoanStatus::REJECTED, 'rejection_reason' => $this->faker->sentence()]);
    }
}
