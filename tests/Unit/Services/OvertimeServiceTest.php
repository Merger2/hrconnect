<?php

use App\Enums\RequestStatus;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Overtime;
use App\Models\Position;
use App\Models\User;
use App\Services\ApprovalService;
use App\Services\OvertimeService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function overtimeServiceEmployee(): Employee
{
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $department = Department::factory()->for($branch)->create();
    $position = Position::factory()->for($department)->create();
    $user = User::factory()->create();

    return Employee::factory()->create([
        'user_id' => $user->id,
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'department_id' => $department->id,
        'position_id' => $position->id,
    ]);
}

test('createOvertime creates pending overtime and approval workflow', function () {
    $employee = overtimeServiceEmployee();
    $approvalService = Mockery::mock(ApprovalService::class);
    $approvalService->shouldReceive('createApprovalWorkflow')
        ->once()
        ->with(Mockery::type(Overtime::class));

    $service = new OvertimeService($approvalService);

    $overtime = $service->createOvertime($employee, [
        'date' => '2026-06-17',
        'start_time' => '18:00',
        'end_time' => '20:30',
        'description' => 'Menutup pekerjaan rilis payroll bulanan.',
    ]);

    expect($overtime)->toBeInstanceOf(Overtime::class)
        ->and($overtime->employee_id)->toBe($employee->id)
        ->and($overtime->status)->toBe(RequestStatus::PENDING)
        ->and((float) $overtime->total_hours)->toBe(2.5);

    $this->assertModelExists($overtime);
});

test('createOvertime supports overnight overtime duration', function () {
    $employee = overtimeServiceEmployee();
    $approvalService = Mockery::mock(ApprovalService::class);
    $approvalService->shouldReceive('createApprovalWorkflow')->once();

    $service = new OvertimeService($approvalService);

    $overtime = $service->createOvertime($employee, [
        'date' => '2026-06-17',
        'start_time' => '22:00',
        'end_time' => '01:30',
        'description' => 'Menangani insiden produksi di luar jam kerja.',
    ]);

    expect((float) $overtime->total_hours)->toBe(3.5)
        ->and($overtime->end_time->greaterThan($overtime->start_time))->toBeTrue();
});

test('cancelOvertime cancels and soft deletes overtime', function () {
    $employee = overtimeServiceEmployee();
    $overtime = Overtime::factory()->create([
        'employee_id' => $employee->id,
        'status' => RequestStatus::PENDING,
    ]);

    $service = new OvertimeService(Mockery::mock(ApprovalService::class));

    $service->cancelOvertime($overtime);

    $cancelled = Overtime::withTrashed()->findOrFail($overtime->id);

    expect($cancelled->trashed())->toBeTrue()
        ->and($cancelled->status)->toBe(RequestStatus::CANCELLED);
});
