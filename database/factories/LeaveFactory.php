<?php

namespace Database\Factories;

use App\Enums\DayType;
use App\Enums\RequestStatus;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveFactory extends Factory
{
    protected $model = Leave::class;

    public function definition(): array
    {
        $start = Carbon::parse(fake()->dateTimeBetween('-1 month', '+1 month'));

        return [
            'employee_id' => Employee::factory(),
            'leave_type_id' => LeaveType::factory(),
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addDays(fake()->numberBetween(0, 3))->toDateString(),
            'day_type' => DayType::FULL_DAY,
            'total_days' => fake()->randomFloat(1, 0.5, 3),
            'reason' => fake()->sentence(8),
            'status' => RequestStatus::PENDING,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attrs) => [
            'status' => RequestStatus::APPROVED,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attrs) => [
            'status' => RequestStatus::REJECTED,
            'rejection_reason' => fake()->sentence(5),
        ]);
    }
}
