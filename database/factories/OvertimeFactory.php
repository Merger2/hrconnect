<?php

namespace Database\Factories;

use App\Enums\RequestStatus;
use App\Models\Employee;
use App\Models\Overtime;
use Illuminate\Database\Eloquent\Factories\Factory;

class OvertimeFactory extends Factory
{
    protected $model = Overtime::class;

    public function definition(): array
    {
        $hours = fake()->randomFloat(1, 1, 4);

        return [
            'employee_id' => Employee::factory(),
            'date' => fake()->dateTimeThisMonth()->format('Y-m-d'),
            'start_time' => now()->setTime(17, 0),
            'end_time' => now()->setTime(17 + floor($hours), (int) (($hours - floor($hours)) * 60)),
            'description' => fake()->sentence(6),
            'total_hours' => $hours,
            'amount' => $hours * 25000,
            'status' => RequestStatus::PENDING,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attrs) => [
            'status' => RequestStatus::APPROVED,
        ]);
    }
}
