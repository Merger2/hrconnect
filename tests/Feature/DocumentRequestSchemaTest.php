<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeDocumentRequest;
use App\Models\EmployeeDocumentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentRequestSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_persists_with_required_fields(): void
    {
        $employee = Employee::factory()->create();
        $type = EmployeeDocumentType::factory()->create();
        $user = User::factory()->create();

        $request = EmployeeDocumentRequest::create([
            'employee_id' => $employee->id,
            'document_type_id' => $type->id,
            'requested_by' => $user->id,
            'request_source' => 'employee',
            'purpose' => 'Bank account opening',
            'status' => EmployeeDocumentRequest::STATUS_PENDING,
        ]);

        $this->assertDatabaseHas('employee_document_requests', [
            'id' => $request->id,
            'employee_id' => $employee->id,
            'document_type_id' => $type->id,
            'requested_by' => $user->id,
            'request_source' => 'employee',
            'purpose' => 'Bank account opening',
            'status' => 'pending',
        ]);
    }

    public function test_factory_produces_valid_record(): void
    {
        $request = EmployeeDocumentRequest::factory()->create();

        $this->assertNotNull($request->employee_id);
        $this->assertNotNull($request->document_type_id);
        $this->assertNotNull($request->requested_by);
        $this->assertDatabaseHas('employee_document_requests', [
            'id' => $request->id,
        ]);
    }
}
