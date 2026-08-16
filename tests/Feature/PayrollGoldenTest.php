<?php

declare(strict_types=1);

use App\Enums\AttendanceStatus;
use App\Enums\EmploymentType;
use App\Enums\MaritalStatus;
use App\Enums\PayrollStatus;
use App\Enums\ReimbursementStatus;
use App\Enums\RequestStatus;
use App\Models\Attendance;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\Overtime;
use App\Models\Payroll;
use App\Models\Position;
use App\Models\Reimbursement;
use App\Services\Payroll\PayrollCalculatorService;
use Carbon\Carbon;
use Database\Seeders\PayrollConfigSeeder;
use Database\Seeders\TarifTerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

$goldenCases = json_decode(file_get_contents(__DIR__.'/../Fixtures/payroll-golden-cases.json'), true);

beforeEach(function () {
    // Freeze waktu sesuai angka referensi (pesangon/PMK/terminasi sensitif now()).
    $this->travelTo(Carbon::parse('2026-08-04 12:00:00'));

    // kategori_ter di-seed oleh migration (A/B/C/D); di sini hanya tarif + BPJS.
    (new TarifTerSeeder)->run();
    (new PayrollConfigSeeder)->run();
});

function goldenEmployee(array $spec): Employee
{
    $position = Position::factory()->create([
        'basic_salary' => $spec['position']['basic_salary'],
        'allowance_jabatan' => $spec['position']['allowance_jabatan'],
    ]);

    $employee = Employee::factory()->create([
        'position_id' => $position->id,
        'employment_type' => EmploymentType::from(strtolower($spec['employment_type'])),
        'marital_status' => MaritalStatus::from(strtolower($spec['marital_status'])),
        'join_date' => $spec['join_date'] ?: null,
        'resign_date' => $spec['resign_date'] ?: null,
        'phk_variant' => $spec['phk_variant'] ?: null,
    ]);

    // children_count dipakai calculator via atribut (fallback ke relasi families).
    $employee->children_count = $spec['children_count'];
    $employee->setRelation('position', $position);

    return $employee;
}

function goldenOvertime(Employee $employee, array $spec): Overtime
{
    $start = Carbon::parse($spec['date'].' 17:00:00');
    $end = $start->copy()->addHours((float) $spec['hours']);

    return Overtime::factory()->create([
        'employee_id' => $employee->id,
        'date' => $spec['date'],
        'start_time' => $start->format('Y-m-d H:i:s'),
        'end_time' => $end->format('Y-m-d H:i:s'),
        'status' => RequestStatus::APPROVED,
    ]);
}

function goldenAttendance(Employee $employee, string $period, array $spec): void
{
    $month = Carbon::createFromFormat('Y-m', $period);
    $day = 1;

    // CATATAN: jangan pakai range(1, $n) — range(1, 0) menghasilkan [1, 0] (2 elemen!)
    for ($i = 0; $i < ($spec['late_days'] ?? 0); $i++) {
        Attendance::factory()->create([
            'employee_id' => $employee->id,
            'date' => $month->copy()->setDay($day++)->format('Y-m-d'),
            'status' => AttendanceStatus::LATE,
            'late_minutes' => 15,
            'is_wfa' => false,
            'exception_type' => null,
        ]);
    }

    for ($i = 0; $i < ($spec['absent_days'] ?? 0); $i++) {
        Attendance::factory()->create([
            'employee_id' => $employee->id,
            'date' => $month->copy()->setDay($day++)->format('Y-m-d'),
            'status' => AttendanceStatus::ABSENT,
            'is_wfa' => false,
            'exception_type' => null,
        ]);
    }
}

function goldenReimbursement(Employee $employee, string $period, array $spec): void
{
    Reimbursement::factory()->create([
        'employee_id' => $employee->id,
        'expense_date' => Carbon::createFromFormat('Y-m', $period)->setDay(10)->format('Y-m-d'),
        'amount' => $spec['amount'],
        'status' => ReimbursementStatus::APPROVED,
    ]);
}

function goldenYtdPayroll(Employee $employee, array $spec): void
{
    Payroll::factory()->create([
        'employee_id' => $employee->id,
        'period' => $spec['period'],
        'gross_salary' => $spec['gross_salary'],
        'pph21' => $spec['pph21'],
        'status' => PayrollStatus::DRAFT,
    ]);
}

it('fixture payroll golden cases valid', function () use ($goldenCases) {
    expect($goldenCases)->toHaveKey('_meta')
        ->and($goldenCases['payroll_cases'])->not->toBeEmpty()
        ->and($goldenCases['component_cases'])->not->toBeEmpty();
});

it('ytd gross income does not double count overtime and excludes reimbursements', function () {
    $service = app(PayrollCalculatorService::class);

    $employee = goldenEmployee([
        'employment_type' => 'PERMANENT',
        'marital_status' => 'single',
        'children_count' => 0,
        'join_date' => '2024-01-15',
        'resign_date' => null,
        'phk_variant' => null,
        'position' => ['basic_salary' => 10000000, 'allowance_jabatan' => 1000000],
    ]);

    // Bulan lalu: gross_salary tersimpan SUDAH mencakup lembur (audit M1).
    // Reimbursement 500.000 ikut masuk gross_salary tapi TIDAK dipajaki.
    $previous = Payroll::factory()->create([
        'employee_id' => $employee->id,
        'period' => '2026-11-01',
        'gross_salary' => 12_500_000, // = prorata 11.000.000 + lembur 1.000.000 + reimburse 500.000
        'overtime_pay' => 1_000_000,
        'status' => PayrollStatus::DRAFT,
    ]);

    Reimbursement::factory()->create([
        'employee_id' => $employee->id,
        'expense_date' => '2026-11-10',
        'amount' => 500_000,
        'status' => ReimbursementStatus::PAID,
        'payroll_id' => $previous->id,
    ]);

    $method = new ReflectionMethod(PayrollCalculatorService::class, 'getYtdGrossIncome');
    $method->setAccessible(true);

    // Sebelum fix: 12.500.000 + 1.000.000 (lembur dobel) = 13.500.000.
    // Setelah fix: gross_salary − reimburse = 12.000.000 (lembur tidak dihitung 2×).
    expect((float) $method->invoke($service, $employee, '2026-12'))->toBe(12_000_000.0);
});

foreach ($goldenCases['payroll_cases'] as $case) {
    it("payroll golden case {$case['id']}: {$case['title']}", function () use ($case) {
        $service = app(PayrollCalculatorService::class);
        $employee = goldenEmployee($case['input']['employee']);

        foreach ($case['input']['overtimes'] as $otSpec) {
            goldenOvertime($employee, $otSpec);
        }
        goldenAttendance($employee, $case['input']['period'], $case['input']['attendance']);
        foreach ($case['input']['reimbursements'] as $reimbSpec) {
            goldenReimbursement($employee, $case['input']['period'], $reimbSpec);
        }
        foreach ($case['input']['ytd_payrolls'] ?? [] as $ytdSpec) {
            goldenYtdPayroll($employee, $ytdSpec);
        }

        CompanySetting::set('attendance_penalty_per_day', (string) $case['input']['penalty_per_day']);

        $payroll = $service->generatePayroll($employee, $case['input']['period']);
        $expected = $case['expected'];

        if ($expected['ter_category'] !== null) {
            expect($service->getTERCategory($employee)->value)->toBe($expected['ter_category']);
        }
        $this->assertEqualsWithDelta((float) $expected['gross_salary'], $payroll->gross_salary, 0.01);
        $this->assertEqualsWithDelta((float) $expected['overtime_pay'], $payroll->overtime_pay, 0.01);
        $this->assertEqualsWithDelta((float) $expected['pph21'], $payroll->pph21, 0.01);
        $this->assertEqualsWithDelta((float) $expected['bpjs']['kesehatan']['employee'], $payroll->bpjs_health, 0.01);
        $this->assertEqualsWithDelta(
            (float) ($expected['bpjs']['jht']['employee'] + $expected['bpjs']['jp']['employee']),
            $payroll->bpjs_employment,
            0.01
        );
        $this->assertEqualsWithDelta(
            (float) ($expected['late_penalty'] + $expected['alpha_penalty']),
            $payroll->attendance_penalty,
            0.01
        );
        $this->assertEqualsWithDelta((float) $expected['total_deduction'], $payroll->total_deduction, 0.01);
        $this->assertEqualsWithDelta((float) $expected['net_salary'], $payroll->net_salary, 0.01);

        // Employer shares tidak tersimpan di Payroll — hitung ulang via service,
        // basis taxable_income dari fixture (prorata + lembur, tanpa reimburse).
        $bpjs = $service->calculateBPJS($employee, (float) $expected['taxable_income']);

        $this->assertEqualsWithDelta((float) $expected['bpjs']['kesehatan']['employer'], $bpjs['bpjs_kesehatan']['employer'], 0.01);
        $this->assertEqualsWithDelta((float) $expected['bpjs']['jht']['employer'], $bpjs['bpjs_jht']['employer'], 0.01);
        $this->assertEqualsWithDelta((float) $expected['bpjs']['jp']['employer'], $bpjs['bpjs_jp']['employer'], 0.01);
        $this->assertEqualsWithDelta((float) $expected['bpjs']['jkk']['employer'], $bpjs['bpjs_jkk']['employer'], 0.01);
        $this->assertEqualsWithDelta((float) $expected['bpjs']['jkm']['employer'], $bpjs['bpjs_jkm']['employer'], 0.01);
    })->skip(! $case['active'], 'Menunggu angka referensi dari Fikih');
}

foreach ($goldenCases['component_cases'] as $case) {
    it("payroll component case {$case['id']}: {$case['title']}", function () use ($case) {
        $service = app(PayrollCalculatorService::class);
        $input = $case['input'];

        $actual = match ($case['method']) {
            'calculateThrProrated' => $service->calculateThrProrated(
                Employee::factory()->create(),
                (float) $input['monthly_salary'],
                (int) $input['months_worked'],
            ),
            'calculatePesangon' => $service->calculatePesangon(goldenEmployee([
                'employment_type' => 'PERMANENT',
                'marital_status' => 'single',
                'children_count' => 0,
                'join_date' => $input['join_date'],
                'resign_date' => null,
                'phk_variant' => $input['phk_variant'] ?? null,
                'position' => ['basic_salary' => $input['monthly_salary'], 'allowance_jabatan' => 0],
            ])),
            'calculateUangPenghargaanMasaKerja' => $service->calculateUangPenghargaanMasaKerja(goldenEmployee([
                'employment_type' => 'PERMANENT',
                'marital_status' => 'single',
                'children_count' => 0,
                'join_date' => $input['join_date'],
                'resign_date' => null,
                'phk_variant' => $input['phk_variant'] ?? null,
                'position' => ['basic_salary' => $input['monthly_salary'], 'allowance_jabatan' => 0],
            ])),
            'calculateUangKompensasi' => $service->calculateUangKompensasi(goldenEmployee([
                'employment_type' => 'CONTRACT',
                'marital_status' => 'single',
                'children_count' => 0,
                'join_date' => $input['join_date'],
                'resign_date' => null,
                'phk_variant' => null,
                'position' => ['basic_salary' => $input['monthly_salary'], 'allowance_jabatan' => 0],
            ])->forceFill(['contract_end_date' => $input['contract_end_date'] ?? null])),
            'calculateLeaveCashOut' => function () use ($service, $input) {
                $employee = goldenEmployee([
                    'employment_type' => 'PERMANENT',
                    'marital_status' => 'single',
                    'children_count' => 0,
                    'join_date' => '2024-01-15',
                    'resign_date' => null,
                    'phk_variant' => null,
                    'position' => ['basic_salary' => $input['monthly_salary'], 'allowance_jabatan' => 0],
                ]);

                LeaveBalance::factory()->create([
                    'employee_id' => $employee->id,
                    'year' => now()->year,
                    'quota' => $input['leave_quota'],
                    'used' => $input['leave_used'],
                ]);

                return $service->calculateLeaveCashOut($employee);
            },
            'calculateAnnualPPh21Progressive' => $service->calculateAnnualPPh21Progressive(
                goldenEmployee([
                    'employment_type' => 'PERMANENT',
                    'marital_status' => $input['marital_status'],
                    'children_count' => $input['children_count'],
                    'join_date' => '2024-01-15',
                    'resign_date' => null,
                    'phk_variant' => null,
                    'position' => ['basic_salary' => 1000000, 'allowance_jabatan' => 0],
                ]),
                (float) $input['annual_gross_income'],
            ),
            'calculateOvertimePay' => $service->calculateOvertimePay(
                goldenOvertime(
                    goldenEmployee([
                        'employment_type' => 'PERMANENT',
                        'marital_status' => 'single',
                        'children_count' => 0,
                        'join_date' => '2024-01-15',
                        'resign_date' => null,
                        'phk_variant' => null,
                        'position' => ['basic_salary' => $input['monthly_salary'], 'allowance_jabatan' => 0],
                    ]),
                    ['date' => $input['overtime_date'], 'hours' => $input['overtime_hours']],
                )
            ),
        };

        $result = $actual instanceof Closure ? $actual() : $actual;

        $this->assertEqualsWithDelta((float) $case['expected'], $result, 0.01);
    })->skip(! $case['active'], 'Menunggu angka referensi dari Fikih');
}
