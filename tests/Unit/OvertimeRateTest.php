<?php

use App\Models\Employee;
use App\Models\Overtime;
use App\Models\Position;
use App\Services\PayrollCalculatorService;
use App\Services\ApprovalService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * fix verification — calculateOvertimePay() pakai tiered rate.
 *
 * Tarif sesuai UU Cipta Kerja PP 35/2021 Pasal 31:
 * - Weekday (Senin-Jumat, bukan holiday):
 *     • Jam ke-1     : 1.5x hourlyRate
 *     • Jam ke-2 dst : 2.0x hourlyRate
 * - Holiday/Weekend (multiplier identik per ERR-007):
 *     • Jam 1-8   : 2.0x hourlyRate
 *     • Jam 9-10  : 3.0x hourlyRate
 *     • Jam 11+   : 4.0x hourlyRate
 *
 * MONTHLY_WORKING_HOURS = 173 (UU Ketenagakerjaan).
 * hourlyRate = (basicSalary + fixedAllowance) / 173.
 */

/**
 * Helper untuk bikin Overtime stub tanpa DB factory.
 * Kita pakai unsaved instance + relation set manual.
 */
function makeOvertime(Carbon $date, float $hours, int $basicSalary = 1730000, int $allowance = 0): Overtime
{
    $position = new Position();
    $position->basic_salary = $basicSalary;
    $position->allowance_jabatan = $allowance;

    $employee = new Employee();
    $employee->setRelation('position', $position);

    $start = $date->copy()->setTime(17, 0);
    $end = $start->copy()->addMinutes((int) round($hours * 60));

    $overtime = new Overtime();
    $overtime->date = $date;
    $overtime->start_time = $start;
    $overtime->end_time = $end;
    $overtime->setRelation('employee', $employee);

    return $overtime;
}

test('weekday overtime jam ke-1 dapat 1.5x rate', function () {
    // basicSalary=1.730.000, allowance=0 → hourlyRate=10.000/jam
    $tuesday = Carbon::create(2026, 6, 2); // Selasa, bukan weekend, asumsi bukan holiday
    $overtime = makeOvertime($tuesday, hours: 1.0);

    $svc = app(PayrollCalculatorService::class);
    $pay = $svc->calculateOvertimePay($overtime);

    // 1 jam × 10.000 × 1.5 = 15.000
    expect($pay)->toBe(15000.00);
});

test('weekday overtime jam ke-2 dapat 2x rate (jam-1=1.5x, jam-2=2x)', function () {
    $tuesday = Carbon::create(2026, 6, 2);
    $overtime = makeOvertime($tuesday, hours: 2.0);

    $svc = app(PayrollCalculatorService::class);
    $pay = $svc->calculateOvertimePay($overtime);

    // (1 × 10.000 × 1.5) + (1 × 10.000 × 2.0) = 15.000 + 20.000 = 35.000
    expect($pay)->toBe(35000.00);
});

test('weekday overtime 3 jam: jam-1=1.5x, jam-2 dan jam-3=2x', function () {
    $tuesday = Carbon::create(2026, 6, 2);
    $overtime = makeOvertime($tuesday, hours: 3.0);

    $svc = app(PayrollCalculatorService::class);
    $pay = $svc->calculateOvertimePay($overtime);

    // (1 × 10.000 × 1.5) + (2 × 10.000 × 2.0) = 15.000 + 40.000 = 55.000
    expect($pay)->toBe(55000.00);
});

test('weekend (Sabtu) overtime 8 jam dapat 2x rate (holiday tier-1)', function () {
    $saturday = Carbon::create(2026, 6, 6); // Sabtu
    $overtime = makeOvertime($saturday, hours: 8.0);

    $svc = app(PayrollCalculatorService::class);
    $pay = $svc->calculateOvertimePay($overtime);

    // 8 × 10.000 × 2.0 = 160.000
    expect($pay)->toBe(160000.00);
});

test('weekend overtime 10 jam: 1-8=2x, 9-10=3x', function () {
    $saturday = Carbon::create(2026, 6, 6);
    $overtime = makeOvertime($saturday, hours: 10.0);

    $svc = app(PayrollCalculatorService::class);
    $pay = $svc->calculateOvertimePay($overtime);

    // (8 × 10.000 × 2.0) + (2 × 10.000 × 3.0) = 160.000 + 60.000 = 220.000
    expect($pay)->toBe(220000.00);
});

test('weekend overtime 12 jam: 1-8=2x, 9-10=3x, 11-12=4x', function () {
    $saturday = Carbon::create(2026, 6, 6);
    $overtime = makeOvertime($saturday, hours: 12.0);

    $svc = app(PayrollCalculatorService::class);
    $pay = $svc->calculateOvertimePay($overtime);

    // (8 × 10.000 × 2.0) + (2 × 10.000 × 3.0) + (2 × 10.000 × 4.0)
    // = 160.000 + 60.000 + 80.000 = 300.000
    expect($pay)->toBe(300000.00);
});

test('overtime 0 jam return 0', function () {
    $tuesday = Carbon::create(2026, 6, 2);
    $overtime = makeOvertime($tuesday, hours: 0.0);

    $svc = app(PayrollCalculatorService::class);
    expect($svc->calculateOvertimePay($overtime))->toBe(0.0);
});
