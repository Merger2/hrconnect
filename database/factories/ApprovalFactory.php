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
            // M14 AUDIT: approvable_id sebelumnya random (1–1000) → dangling morph
            // (id bisa tidak ada di tabel target). Default: Leave nyata via factory;
            // state forOvertime()/forReimbursement() tersedia untuk tipe lain.
            'approvable_type' => Leave::class,
            'approvable_id' => Leave::factory(),
            'approver_id' => Employee::factory(),
            'level' => $this->faker->randomElement(ApprovalLevel::cases()),
            'status' => ApprovalStatus::PENDING,
            'notes' => null,
            'approved_at' => null,
        ];
    }

    public function forOvertime(): static
    {
        return $this->state(fn () => [
            'approvable_type' => Overtime::class,
            'approvable_id' => Overtime::factory(),
        ]);
    }

    public function forReimbursement(): static
    {
        return $this->state(fn () => [
            'approvable_type' => Reimbursement::class,
            'approvable_id' => Reimbursement::factory(),
        ]);
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
