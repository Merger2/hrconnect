<?php

use App\Enums\PayrollStatus;
use App\Enums\ReimbursementStatus;
use App\Enums\RequestStatus;
use App\Models\Asset;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\KnowledgeBase;
use App\Models\Leave;
use App\Models\Overtime;
use App\Models\Payroll;
use App\Models\Reimbursement;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

/**
 * Helper: create User dengan role + Employee model in-memory (no save).
 * Hindari factory chain Company/Branch/Department/Position yang berat.
 */
function userWithRole(string $role, ?int $employeeId = null, ?int $parentId = null): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    if ($employeeId !== null) {
        $employee = new Employee;
        $employee->id = $employeeId;
        $employee->user_id = $user->id;
        $employee->parent_id = $parentId;
        $user->setRelation('employee', $employee);
    }

    return $user;
}

function makeLeave(int $employeeId, int $parentId = 0, RequestStatus $status = RequestStatus::PENDING): Leave
{
    $employeeStub = new Employee;
    $employeeStub->id = $employeeId;
    $employeeStub->parent_id = $parentId ?: null;

    $leave = new Leave;
    $leave->id = $employeeId * 10;
    $leave->employee_id = $employeeId;
    $leave->status = $status;
    $leave->setRelation('employee', $employeeStub);

    return $leave;
}

// ─── EmployeePolicy ───────────────────────────────────────────────────

test('EmployeePolicy: HR Manager bisa view semua employee', function () {
    $hr = userWithRole('hr-manager', employeeId: 1);
    $other = new Employee;
    $other->id = 99;

    expect($hr->can('view', $other))->toBeTrue();
});

test('EmployeePolicy: Employee tidak bisa view employee lain (IDOR fix)', function () {
    $emp = userWithRole('employee', employeeId: 5);
    $other = new Employee;
    $other->id = 99;

    expect($emp->can('view', $other))->toBeFalse();
});

test('EmployeePolicy: Employee bisa view diri sendiri', function () {
    $emp = userWithRole('employee', employeeId: 5);
    $self = new Employee;
    $self->id = 5;

    expect($emp->can('view', $self))->toBeTrue();
});

test('EmployeePolicy: HR Manager bisa create employee, Employee tidak', function () {
    $hr = userWithRole('hr-manager', employeeId: 1);
    $emp = userWithRole('employee', employeeId: 2);

    expect($hr->can('create', Employee::class))->toBeTrue();
    expect($emp->can('create', Employee::class))->toBeFalse();
});

test('EmployeePolicy: hanya super-admin bisa forceDelete', function () {
    $sa = userWithRole('super-admin', employeeId: 1);
    $hr = userWithRole('hr-manager', employeeId: 2);
    $target = new Employee;
    $target->id = 99;

    expect($sa->can('forceDelete', $target))->toBeTrue();
    expect($hr->can('forceDelete', $target))->toBeFalse();
});

// ─── LeavePolicy ──────────────────────────────────────────────────────

test('LeavePolicy: Employee bisa view leave-nya sendiri', function () {
    $emp = userWithRole('employee', employeeId: 5);
    $leave = makeLeave(employeeId: 5);

    expect($emp->can('view', $leave))->toBeTrue();
});

test('LeavePolicy: Employee TIDAK bisa view leave employee lain (IDOR)', function () {
    $emp = userWithRole('employee', employeeId: 5);
    $leaveOther = makeLeave(employeeId: 99);

    expect($emp->can('view', $leaveOther))->toBeFalse();
});

test('LeavePolicy: Manager bisa view leave timnya (parent_id match)', function () {
    $manager = userWithRole('manager', employeeId: 10);
    $teamLeave = makeLeave(employeeId: 5, parentId: 10);

    expect($manager->can('view', $teamLeave))->toBeTrue();
});

test('LeavePolicy: Manager TIDAK bisa view leave karyawan luar tim', function () {
    $manager = userWithRole('manager', employeeId: 10);
    $outsideLeave = makeLeave(employeeId: 5, parentId: 999); // parent berbeda

    expect($manager->can('view', $outsideLeave))->toBeFalse();
});

test('LeavePolicy: Manager bisa approveLevel1 untuk timnya saja', function () {
    $manager = userWithRole('manager', employeeId: 10);
    $teamLeave = makeLeave(employeeId: 5, parentId: 10);
    $outsideLeave = makeLeave(employeeId: 6, parentId: 999);

    expect($manager->can('approveLevel1', $teamLeave))->toBeTrue();
    expect($manager->can('approveLevel1', $outsideLeave))->toBeFalse();
});

test('LeavePolicy: HR Manager bisa approveLevel2 semua leave', function () {
    $hr = userWithRole('hr-manager', employeeId: 1);
    $anyLeave = makeLeave(employeeId: 99);

    expect($hr->can('approveLevel2', $anyLeave))->toBeTrue();
});

test('LeavePolicy: Manager TIDAK bisa approveLevel2', function () {
    $manager = userWithRole('manager', employeeId: 10);
    $anyLeave = makeLeave(employeeId: 5, parentId: 10);

    expect($manager->can('approveLevel2', $anyLeave))->toBeFalse();
});

test('LeavePolicy: Owner bisa update leave saat PENDING saja', function () {
    $emp = userWithRole('employee', employeeId: 5);
    $pending = makeLeave(employeeId: 5, status: RequestStatus::PENDING);
    $approved = makeLeave(employeeId: 5, status: RequestStatus::APPROVED);

    expect($emp->can('update', $pending))->toBeTrue();
    expect($emp->can('update', $approved))->toBeFalse();
});

// ─── PayrollPolicy ────────────────────────────────────────────────────

test('PayrollPolicy: Finance bisa create + update DRAFT payroll', function () {
    $fin = userWithRole('finance', employeeId: 1);

    $draft = new Payroll;
    $draft->status = PayrollStatus::DRAFT;

    $published = new Payroll;
    $published->status = PayrollStatus::PUBLISHED;

    expect($fin->can('create', Payroll::class))->toBeTrue();
    expect($fin->can('update', $draft))->toBeTrue();
    expect($fin->can('update', $published))->toBeFalse(); // LOCKED
});

test('PayrollPolicy: Finance TIDAK bisa delete published payroll (lock)', function () {
    $fin = userWithRole('finance', employeeId: 1);

    $draft = new Payroll;
    $draft->status = PayrollStatus::DRAFT;

    $paid = new Payroll;
    $paid->status = PayrollStatus::PAID;

    expect($fin->can('delete', $draft))->toBeTrue();
    expect($fin->can('delete', $paid))->toBeFalse();
});

test('PayrollPolicy: Employee TIDAK bisa lihat payroll employee lain', function () {
    $emp = userWithRole('employee', employeeId: 5);

    $ownPayroll = new Payroll;
    $ownPayroll->employee_id = 5;
    $ownPayroll->status = PayrollStatus::PUBLISHED;

    $otherPayroll = new Payroll;
    $otherPayroll->employee_id = 99;
    $otherPayroll->status = PayrollStatus::PUBLISHED;

    expect($emp->can('view', $ownPayroll))->toBeTrue();
    expect($emp->can('view', $otherPayroll))->toBeFalse();
});

test('PayrollPolicy: Employee bisa download payslip diri sendiri (PUBLISHED only)', function () {
    $emp = userWithRole('employee', employeeId: 5);

    $draft = new Payroll;
    $draft->employee_id = 5;
    $draft->status = PayrollStatus::DRAFT;

    $published = new Payroll;
    $published->employee_id = 5;
    $published->status = PayrollStatus::PUBLISHED;

    // DRAFT tidak boleh di-download (belum final)
    expect($emp->can('downloadPayslip', $draft))->toBeFalse();
    expect($emp->can('downloadPayslip', $published))->toBeTrue();
});

test('PayrollPolicy: HR Manager TIDAK punya akses payroll (separation of duties)', function () {
    $hr = userWithRole('hr-manager', employeeId: 1);

    $payroll = new Payroll;
    $payroll->employee_id = 99;
    $payroll->status = PayrollStatus::PUBLISHED;

    expect($hr->can('view', $payroll))->toBeFalse();
    expect($hr->can('create', Payroll::class))->toBeFalse();
});

// ─── ReimbursementPolicy ──────────────────────────────────────────────

test('ReimbursementPolicy: Finance approveLevel2 (bukan HR)', function () {
    $finance = userWithRole('finance', employeeId: 1);
    $hr = userWithRole('hr-manager', employeeId: 2);

    $reimbursement = new Reimbursement;
    $reimbursement->employee_id = 99;
    $reimbursement->status = ReimbursementStatus::PENDING;

    expect($finance->can('approveLevel2', $reimbursement))->toBeTrue();
    expect($hr->can('approveLevel2', $reimbursement))->toBeFalse();
});

// ─── KnowledgeBasePolicy ──────────────────────────────────────────────

test('KnowledgeBasePolicy: HR Manager bisa manage, Employee tidak', function () {
    $hr = userWithRole('hr-manager', employeeId: 1);
    $emp = userWithRole('employee', employeeId: 2);

    expect($hr->can('create', KnowledgeBase::class))->toBeTrue();
    expect($emp->can('create', KnowledgeBase::class))->toBeFalse();
});

// ─── AssetPolicy (V2 deferred — basic check) ──────────────────────────

// ─── AssetPolicy (V2 deferred — basic check) ──────────────────────────

test('AssetPolicy: hanya HR Manager bisa manage Asset', function () {
    $hr = userWithRole('hr-manager', employeeId: 1);
    $emp = userWithRole('employee', employeeId: 2);

    expect($hr->can('create', Asset::class))->toBeTrue();
    expect($emp->can('create', Asset::class))->toBeFalse();
});

// ─── AttendancePolicy ────────────────────────────────────────────────

function makeAttendance(int $employeeId, int $parentId = 0): Attendance
{
    $employeeStub = new Employee;
    $employeeStub->id = $employeeId;
    $employeeStub->parent_id = $parentId ?: null;

    $attendance = new Attendance;
    $attendance->id = $employeeId * 100;
    $attendance->employee_id = $employeeId;
    $attendance->setRelation('employee', $employeeStub);

    return $attendance;
}

test('AttendancePolicy: HR Manager bisa view semua attendance', function () {
    $hr = userWithRole('hr-manager', employeeId: 1);
    $any = makeAttendance(employeeId: 99);

    expect($hr->can('view', $any))->toBeTrue();
});

test('AttendancePolicy: Employee bisa view attendance sendiri', function () {
    $emp = userWithRole('employee', employeeId: 5);
    $own = makeAttendance(employeeId: 5);

    expect($emp->can('view', $own))->toBeTrue();
});

test('AttendancePolicy: Employee TIDAK bisa view attendance orang lain (IDOR)', function () {
    $emp = userWithRole('employee', employeeId: 5);
    $other = makeAttendance(employeeId: 99);

    expect($emp->can('view', $other))->toBeFalse();
});

test('AttendancePolicy: Manager bisa view attendance timnya (parent_id match)', function () {
    $manager = userWithRole('manager', employeeId: 10);
    $teamAtt = makeAttendance(employeeId: 5, parentId: 10);

    expect($manager->can('view', $teamAtt))->toBeTrue();
});

test('AttendancePolicy: Manager TIDAK bisa view attendance luar tim', function () {
    $manager = userWithRole('manager', employeeId: 10);
    $outsideAtt = makeAttendance(employeeId: 5, parentId: 999);

    expect($manager->can('view', $outsideAtt))->toBeFalse();
});

test('AttendancePolicy: Only HR Manager bisa update/delete attendance', function () {
    $hr = userWithRole('hr-manager', employeeId: 1);
    $emp = userWithRole('employee', employeeId: 5);
    $any = makeAttendance(employeeId: 99);

    expect($hr->can('update', $any))->toBeTrue();
    expect($hr->can('delete', $any))->toBeTrue();
    expect($emp->can('update', $any))->toBeFalse();
    expect($emp->can('delete', $any))->toBeFalse();
});

// ─── OvertimePolicy ──────────────────────────────────────────────────

function makeOvertimeStub(int $employeeId, int $parentId = 0, RequestStatus $status = RequestStatus::PENDING): Overtime
{
    $employeeStub = new Employee;
    $employeeStub->id = $employeeId;
    $employeeStub->parent_id = $parentId ?: null;

    $overtime = new Overtime;
    $overtime->id = $employeeId * 1000;
    $overtime->employee_id = $employeeId;
    $overtime->status = $status;
    $overtime->setRelation('employee', $employeeStub);

    return $overtime;
}

test('OvertimePolicy: Employee bisa view overtime sendiri', function () {
    $emp = userWithRole('employee', employeeId: 5);
    $own = makeOvertimeStub(employeeId: 5);

    expect($emp->can('view', $own))->toBeTrue();
});

test('OvertimePolicy: Employee TIDAK bisa view overtime orang lain (IDOR)', function () {
    $emp = userWithRole('employee', employeeId: 5);
    $other = makeOvertimeStub(employeeId: 99);

    expect($emp->can('view', $other))->toBeFalse();
});

test('OvertimePolicy: Manager bisa view overtime timnya (parent_id match)', function () {
    $manager = userWithRole('manager', employeeId: 10);
    $teamOt = makeOvertimeStub(employeeId: 5, parentId: 10);

    expect($manager->can('view', $teamOt))->toBeTrue();
});

test('OvertimePolicy: Manager TIDAK bisa view overtime luar tim', function () {
    $manager = userWithRole('manager', employeeId: 10);
    $outsideOt = makeOvertimeStub(employeeId: 5, parentId: 999);

    expect($manager->can('view', $outsideOt))->toBeFalse();
});

test('OvertimePolicy: Employee bisa create overtime (has employee record)', function () {
    $emp = userWithRole('employee', employeeId: 5);
    $noEmp = User::factory()->create();
    $noEmp->assignRole('employee');

    expect($emp->can('create', Overtime::class))->toBeTrue();
    expect($noEmp->can('create', Overtime::class))->toBeFalse();
});

test('OvertimePolicy: Owner bisa update/delete overtime saat PENDING saja', function () {
    $emp = userWithRole('employee', employeeId: 5);
    $pending = makeOvertimeStub(employeeId: 5, status: RequestStatus::PENDING);
    $approved = makeOvertimeStub(employeeId: 5, status: RequestStatus::APPROVED);

    expect($emp->can('update', $pending))->toBeTrue();
    expect($emp->can('update', $approved))->toBeFalse();
    expect($emp->can('delete', $pending))->toBeTrue();
    expect($emp->can('delete', $approved))->toBeFalse();
});

test('OvertimePolicy: Manager bisa approveLevel1 untuk timnya saja', function () {
    $manager = userWithRole('manager', employeeId: 10);
    $teamOt = makeOvertimeStub(employeeId: 5, parentId: 10);
    $outsideOt = makeOvertimeStub(employeeId: 6, parentId: 999);

    expect($manager->can('approveLevel1', $teamOt))->toBeTrue();
    expect($manager->can('approveLevel1', $outsideOt))->toBeFalse();
});

test('OvertimePolicy: HR Manager bisa approveLevel2 semua overtime', function () {
    $hr = userWithRole('hr-manager', employeeId: 1);
    $anyOt = makeOvertimeStub(employeeId: 99);

    expect($hr->can('approveLevel2', $anyOt))->toBeTrue();
});

test('OvertimePolicy: Manager TIDAK bisa approveLevel2', function () {
    $manager = userWithRole('manager', employeeId: 10);
    $anyOt = makeOvertimeStub(employeeId: 5, parentId: 10);

    expect($manager->can('approveLevel2', $anyOt))->toBeFalse();
});
