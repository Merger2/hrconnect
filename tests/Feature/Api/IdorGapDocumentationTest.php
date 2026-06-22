<?php

use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $companyId = DB::table('companies')->insertGetId([
        'name' => 'PT Test',
        'code' => 'TST',
        'phone' => '021',
        'email' => 't@t.com',
        'npwp' => '01.234.567.8-901.000',
        'is_active' => true,
    ]);

    $branchId = DB::table('branches')->insertGetId([
        'company_id' => $companyId,
        'name' => 'HQ',
        'address' => 'JKT',
        'is_main' => true,
        'is_active' => true,
        'latitude' => -6.2,
        'longitude' => 106.8,
        'radius' => 100,
    ]);

    $deptId = DB::table('departments')->insertGetId([
        'branch_id' => $branchId,
        'name' => 'Engineering',
        'code' => 'ENG',
        'is_active' => true,
    ]);

    $positionId = DB::table('positions')->insertGetId([
        'department_id' => $deptId,
        'name' => 'Staff',
        'code' => 'STF',
        'grade' => 1,
        'basic_salary' => 5_000_000,
        'is_active' => true,
    ]);

    DB::table('leave_types')->insert([
        'name' => 'Cuti Tahunan',
        'code' => 'ANN',
        'quota' => 12,
        'is_paid' => true,
        'deducts_from_quota' => true,
        'is_active' => true,
    ]);

    // Manager user
    $this->managerUser = User::factory()->create();
    $this->managerUser->assignRole('manager');

    // Employee A (direct report of manager)
    $this->managerEmployee = Employee::create([
        'user_id' => $this->managerUser->id,
        'company_id' => $companyId,
        'branch_id' => $branchId,
        'department_id' => $deptId,
        'position_id' => $positionId,
        'full_name' => 'Manager User',
        'employee_number' => 'MGR001',
        'employment_type' => 'permanent',
        'status' => 'active',
        'gender' => 'L',
        'salary_type' => 'monthly',
        'phone' => '081111111111',
        'nik' => '3276010101900001',
        'join_date' => '2020-01-01',
        'birth_date' => '1990-01-01',
        'marital_status' => 'single',
        'education_level' => 'bachelor',
        'institution_name' => 'Univ A',
        'graduation_year' => 2015,
    ]);

    // Employee B (direct report of manager)
    $this->empB = Employee::create([
        'user_id' => User::factory()->create()->id,
        'company_id' => $companyId,
        'branch_id' => $branchId,
        'department_id' => $deptId,
        'position_id' => $positionId,
        'full_name' => 'Employee B',
        'employee_number' => 'EMP002',
        'employment_type' => 'permanent',
        'status' => 'active',
        'gender' => 'L',
        'salary_type' => 'monthly',
        'phone' => '081222222222',
        'nik' => '3276010101900002',
        'join_date' => '2020-06-01',
        'birth_date' => '1992-01-01',
        'marital_status' => 'single',
        'education_level' => 'bachelor',
        'institution_name' => 'Univ B',
        'graduation_year' => 2016,
        'parent_id' => $this->managerEmployee->id,
    ]);

    // Employee C (NOT a direct report of manager — different department)
    $otherDeptId = DB::table('departments')->insertGetId([
        'branch_id' => $branchId,
        'name' => 'Finance',
        'code' => 'FIN',
        'is_active' => true,
    ]);

    $this->empC = Employee::create([
        'user_id' => User::factory()->create()->id,
        'company_id' => $companyId,
        'branch_id' => $branchId,
        'department_id' => $otherDeptId,
        'position_id' => $positionId,
        'full_name' => 'Employee C (Finance)',
        'employee_number' => 'EMP003',
        'employment_type' => 'permanent',
        'status' => 'active',
        'gender' => 'P',
        'salary_type' => 'monthly',
        'phone' => '081333333333',
        'nik' => '3276010101900003',
        'join_date' => '2021-01-01',
        'birth_date' => '1993-01-01',
        'marital_status' => 'single',
        'education_level' => 'bachelor',
        'institution_name' => 'Univ C',
        'graduation_year' => 2017,
    ]);

    // Finance user
    $this->financeUser = User::factory()->create();
    $this->financeUser->assignRole('finance');
});

// ─── IDOR GAP 1: Employee API — no team scoping in API ──────────────
// Known gap: Manager can list ALL employees (not just direct reports).
// Defense: EmployeePolicy::viewAny() gates by permission only.
// Team scoping is handled via Livewire query scope, not API policy.

test('manager with view_employees can list ALL employees (no team scoping — by design)', function () {
    expect(Employee::query()->count())->toBe(3);

    $this->actingAs($this->managerUser);

    $response = $this->getJson('/api/v1/employees');

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(3);
});

test('manager can view employee from different department (no team scoping — by design)', function () {
    $this->actingAs($this->managerUser);

    $response = $this->getJson("/api/v1/employees/{$this->empC->id}");

    $response->assertOk();
    expect($response->json('data.id'))->toBe($this->empC->id);
});

// ─── IDOR GAP 2: Payroll generate accepts arbitrary employee_ids ────
// Known gap: employee_ids[] has no relationship-to-user verification.
// Defense: Endpoint is gated by process_payroll permission (Finance-only).
// Finance user can generate payroll for ANY employee.

test('finance user dapat generate payroll untuk employee dari department manapun', function () {
    $this->actingAs($this->financeUser);

    $response = $this->postJson('/api/v1/payroll/generate', [
        'period' => '2026-07',
        'employee_ids' => [$this->empB->id, $this->empC->id],
    ]);

    $response->assertStatus(202);
    expect($response->json('data.queued_jobs'))->toBe(2);
});

test('non-finance user TIDAK bisa generate payroll — gated by process_payroll', function () {
    $this->actingAs($this->managerUser);

    $response = $this->postJson('/api/v1/payroll/generate', [
        'period' => '2026-07',
    ]);

    $response->assertStatus(403);
});

// ─── IDOR GAP 3: approveWfa uses string permission ─────────────────
// Known gap: Uses $user->can('approve_wfa') instead of $this->authorize().
// Defense: Hierarchy check (parent_id + super-admin/hr-manager override)
// still prevents managers from approving WFA for non-direct-reports.

test('approveWfa hierarchy check prevents approving non-direct-report WFA', function () {
    $this->actingAs($this->managerUser);

    $empBAttendanceId = DB::table('attendances')->insertGetId([
        'employee_id' => $this->empB->id,
        'date' => now()->toDateString(),
        'clock_in' => '09:00:00',
        'status' => 'on_time',
        'is_wfa' => true,
        'status_wfa' => 'pending',
        'late_minutes' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Can approve own team (empB is direct report)
    $response = $this->postJson("/api/v1/attendance/{$empBAttendanceId}/approve-wfa", [
        'decision' => 'approve',
    ]);
    $response->assertOk();
});

test('approveWfa hierarchy check rejects approving WFA for non-team employee', function () {
    $this->actingAs($this->managerUser);

    $empCAttendanceId = DB::table('attendances')->insertGetId([
        'employee_id' => $this->empC->id,
        'date' => now()->toDateString(),
        'clock_in' => '09:00:00',
        'status' => 'on_time',
        'is_wfa' => true,
        'status_wfa' => 'pending',
        'late_minutes' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Cannot approve non-team (empC has no parent_id = manager)
    $response = $this->postJson("/api/v1/attendance/{$empCAttendanceId}/approve-wfa", [
        'decision' => 'approve',
    ]);
    $response->assertStatus(403);
    expect($response->json('message'))->toContain('hanya bisa approve');
});
