<?php

namespace Database\Factories;

use App\Models\EmployeeDocumentTemplate;
use App\Models\EmployeeDocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeDocumentTemplate>
 */
class EmployeeDocumentTemplateFactory extends Factory
{
    protected $model = EmployeeDocumentTemplate::class;

    public function definition(): array
    {
        return [
            'document_type_id' => EmployeeDocumentType::factory(),
            'name' => fake()->sentence(3),
            'content' => '{{ $employee_name }} - {{ $request_purpose }}',
            'is_active' => true,
        ];
    }
}
