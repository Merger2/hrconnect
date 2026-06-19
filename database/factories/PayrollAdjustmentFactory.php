<?php

namespace Database\Factories;

use App\Models\Payroll;
use App\Models\PayrollAdjustment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollAdjustment>
 */
class PayrollAdjustmentFactory extends Factory
{
    #[UseModel(PayrollAdjustment::class)]
    public function definition(): array
    {
        return [
            'payroll_id' => Payroll::factory(),
            'amount' => $this->faker->randomFloat(2, -500000, 500000),
            'reason' => $this->faker->sentence(),
            'created_by' => User::factory(),
            'applied_to_period' => $this->faker->date(),
        ];
    }

    public function positive(): static
    {
        return $this->state(fn () => [
            'amount' => $this->faker->randomFloat(2, 10000, 500000),
        ]);
    }

    public function negative(): static
    {
        return $this->state(fn () => [
            'amount' => $this->faker->randomFloat(2, -500000, -10000),
        ]);
    }
}
