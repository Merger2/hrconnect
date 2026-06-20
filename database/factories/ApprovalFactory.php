<?php

namespace Database\Factories;

use App\Enums\ApprovalLevel;
use App\Enums\ApprovalStatus;
use App\Models\Approval;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Overtime;
use App\Models\Reimbursement;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Approval>
 */
class ApprovalFactory extends Factory
{
    #[UseModel(Approval::class)]
    public function definition(): array
    {
        return [
            'approvable_type' => $this->faker->randomElement([
                Leave::class,
                Overtime::class,
                Reimbursement::class,
            ]),
            'approvable_id' => $this->faker->numberBetween(1, 1000),
            'approver_id' => Employee::factory(),
            'level' => $this->faker->randomElement(ApprovalLevel::cases()),
            'status' => ApprovalStatus::PENDING,
            'notes' => null,
            'approved_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => ApprovalStatus::APPROVED,
            'notes' => $this->faker->sentence(),
            'approved_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => ApprovalStatus::REJECTED,
            'notes' => $this->faker->sentence(),
            'approved_at' => now(),
        ]);
    }
}
