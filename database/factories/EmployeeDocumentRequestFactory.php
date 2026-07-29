<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeDocumentRequest;
use App\Models\EmployeeDocumentType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeDocumentRequest>
 */
class EmployeeDocumentRequestFactory extends Factory
{
    protected $model = EmployeeDocumentRequest::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'document_type_id' => EmployeeDocumentType::factory(),
            'requested_by' => User::factory(),
            'request_source' => 'employee',
            'purpose' => fake()->sentence(6),
            'details' => fake()->paragraph(2),
            'due_date' => fake()->dateTimeBetween('+1 week', '+1 month')->format('Y-m-d'),
            'status' => EmployeeDocumentRequest::STATUS_PENDING,
        ];
    }

    public function requested(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EmployeeDocumentRequest::STATUS_REQUESTED,
        ]);
    }

    public function uploaded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EmployeeDocumentRequest::STATUS_UPLOADED,
            'uploaded_path' => 'documents/uploads/test.pdf',
            'uploaded_original_name' => 'test.pdf',
            'uploaded_at' => now(),
        ]);
    }

    public function generated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EmployeeDocumentRequest::STATUS_GENERATED,
            'generated_path' => 'documents/generated/test.pdf',
            'generated_at' => now(),
        ]);
    }

    public function ready(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EmployeeDocumentRequest::STATUS_READY,
            'reviewed_by' => User::factory(),
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EmployeeDocumentRequest::STATUS_REJECTED,
            'rejection_note' => fake()->sentence(4),
        ]);
    }
}
