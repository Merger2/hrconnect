<?php

namespace Database\Factories;

use App\Enums\PayrollItemType;
use App\Models\Payroll;
use App\Models\PayrollItem;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollItem>
 */
class PayrollItemFactory extends Factory
{
    #[UseModel(PayrollItem::class)]
    public function definition(): array
    {
        return [
            'payroll_id' => Payroll::factory(),
            'name' => $this->faker->randomElement([
                'Gaji Pokok',
                'Tunjangan Transportasi',
                'Tunjangan Makan',
                'Tunjangan Kesehatan',
                'BPJS Kesehatan',
                'BPJS Ketenagakerjaan',
                'Potongan PPh 21',
                'Lembur',
            ]),
            'amount' => $this->faker->randomFloat(2, -5000000, 10000000),
            'type' => $this->faker->randomElement(PayrollItemType::cases()),
        ];
    }

    public function allowance(): static
    {
        return $this->state(fn () => [
            'type' => PayrollItemType::ALLOWANCE,
            'amount' => $this->faker->randomFloat(2, 100000, 10000000),
        ]);
    }

    public function deduction(): static
    {
        return $this->state(fn () => [
            'type' => PayrollItemType::DEDUCTION,
            'amount' => $this->faker->randomFloat(2, -5000000, -10000),
        ]);
    }
}
