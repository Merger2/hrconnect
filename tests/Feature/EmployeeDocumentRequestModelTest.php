<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeDocumentRequest;
use App\Models\EmployeeDocumentTemplate;
use App\Models\EmployeeDocumentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeDocumentRequestModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_relations_resolve(): void
    {
        $employee = Employee::factory()->create();
        $type = EmployeeDocumentType::factory()->create();
        $template = EmployeeDocumentTemplate::factory()->create(['document_type_id' => $type->id]);
        $requester = User::factory()->create();
        $reviewer = User::factory()->create();

        $request = EmployeeDocumentRequest::factory()->create([
            'employee_id' => $employee->id,
            'document_type_id' => $type->id,
            'requested_by' => $requester->id,
            'reviewed_by' => $reviewer->id,
            'generated_template_id' => $template->id,
        ]);

        $this->assertInstanceOf(Employee::class, $request->employee);
        $this->assertInstanceOf(EmployeeDocumentType::class, $request->documentType);
        $this->assertInstanceOf(User::class, $request->requester);
        $this->assertInstanceOf(User::class, $request->reviewer);
        $this->assertInstanceOf(EmployeeDocumentTemplate::class, $request->generatedTemplate);
    }

    public function test_user_accessor_returns_employee_user(): void
    {
        $employee = Employee::factory()->create();
        $request = EmployeeDocumentRequest::factory()->create([
            'employee_id' => $employee->id,
        ]);

        $this->assertInstanceOf(User::class, $request->user);
        $this->assertEquals($employee->user_id, $request->user->id);
    }

    public function test_status_label_supports_all_declared_statuses(): void
    {
        $statuses = [
            EmployeeDocumentRequest::STATUS_PENDING,
            EmployeeDocumentRequest::STATUS_REQUESTED,
            EmployeeDocumentRequest::STATUS_UPLOADED,
            EmployeeDocumentRequest::STATUS_GENERATED,
            EmployeeDocumentRequest::STATUS_READY,
            EmployeeDocumentRequest::STATUS_REJECTED,
            EmployeeDocumentRequest::STATUS_EXPIRED,
        ];

        foreach ($statuses as $status) {
            $request = EmployeeDocumentRequest::factory()->make(['status' => $status]);
            $label = $request->statusLabel();
            $this->assertNotEmpty($label, "Status {$status} should have a label");
        }
    }

    public function test_document_type_label_falls_back_to_dash(): void
    {
        $request = EmployeeDocumentRequest::factory()->make(['document_type_id' => null]);
        $this->assertEquals('—', $request->documentTypeLabel());
    }
}
