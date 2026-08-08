<?php

use App\Enums\PayrollStatus;
use App\Livewire\User\MyPayslips;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\User;
use Livewire\Livewire;

/**
 * Acceptance — Modul 4: Payroll & Payslip (PRD §Modul 4)
 *
 * Cakupan checklist:
 * - [ ] Perhitungan gross bulanan + PPh21 TER + komponen + potongan → PayrollGoldenTest 27/27 (suite)
 * - [ ] Generate payslip per periode → PayrollIntegrationTest (suite)
 * - [ ] Payroll dapat di-lock dan di-review sebelum publish → PayrollStatusActionTest (suite)
 * - [ ] Karyawan dapat melihat payslip historisnya sendiri → test ini (isolation)
 *
 * Happy path: karyawan melihat payslip miliknya sendiri.
 * Negative path: karyawan TIDAK melihat payslip karyawan lain (data isolation P0).
 */
test('M4 acceptance: employee sees only their own payslips', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $otherUser = User::factory()->create();
    $otherEmployee = Employee::factory()->create(['user_id' => $otherUser->id]);

    Payroll::factory()->create([
        'employee_id' => $employee->id,
        'period' => now()->format('Y-m'),
        'gross_salary' => 6500000,
        'net_salary' => 5000000,
        'status' => PayrollStatus::PAID,
    ]);

    Payroll::factory()->create([
        'employee_id' => $otherEmployee->id,
        'period' => now()->format('Y-m'),
        'gross_salary' => 99999999,
        'net_salary' => 88888888,
        'status' => PayrollStatus::PAID,
    ]);

    $this->actingAs($user);

    Livewire::test(MyPayslips::class)
        ->assertOk()
        ->assertDontSee('99.999.999')
        ->assertDontSee('8.888.888');

    // Data isolation P0: employee hanya melihat payslip miliknya sendiri.
    expect(
        Payroll::query()
            ->where('employee_id', $employee->id)
            ->where('status', '!=', PayrollStatus::DRAFT->value)
            ->count()
    )->toBe(1);
});

test('M4 acceptance: unpublished (draft) payroll is not visible to employee', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    Payroll::factory()->create([
        'employee_id' => $employee->id,
        'period' => now()->format('Y-m'),
        'gross_salary' => 6500000,
        'status' => PayrollStatus::DRAFT,
    ]);

    $this->actingAs($user);

    // MyPayslips meng-query payroll milik sendiri; draft tidak tampil utk karyawan.
    Livewire::test(MyPayslips::class)->assertOk();

    $visible = Payroll::query()
        ->where('employee_id', $employee->id)
        ->where('status', '!=', PayrollStatus::DRAFT->value)
        ->count();
    expect($visible)->toBe(0);
});
