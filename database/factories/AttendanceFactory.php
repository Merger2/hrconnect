<?php

namespace Database\Factories;

use App\Enums\ApprovalStatus;
use App\Enums\AttendanceStatus;
use App\Enums\VerificationMethod;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'shift_id' => Shift::factory(),
            'date' => fake()->dateTimeThisMonth()->format('Y-m-d'),
            'clock_in' => now()->setTime(8, fake()->numberBetween(0, 30)),
            'clock_out' => now()->setTime(17, fake()->numberBetween(0, 30)),
            'lat_in' => fake()->latitude(-10, 6),
            'long_in' => fake()->longitude(95, 141),
            'lat_out' => fake()->latitude(-10, 6),
            'long_out' => fake()->longitude(95, 141),
            'status' => AttendanceStatus::ON_TIME,
            'is_wfa' => false,
            'status_wfa' => null,
            'verification_method' => VerificationMethod::FACE_VERIFIED->value,
            'face_similarity_score' => fake()->randomFloat(2, 85, 99),
        ];
    }

    public function wfa(): static
    {
        return $this->state(fn (array $attrs) => [
            'is_wfa' => true,
            'status_wfa' => ApprovalStatus::PENDING->value,
            'lat_in' => null,
            'long_in' => null,
            'wfa_note' => fake()->sentence(10),
        ]);
    }

    public function late(): static
    {
        return $this->state(fn (array $attrs) => [
            'clock_in' => now()->setTime(8, fake()->numberBetween(31, 120)),
            'status' => AttendanceStatus::LATE,
            'late_minutes' => fake()->numberBetween(1, 90),
        ]);
    }
}
