<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\EmploymentType;
use App\Enums\FamilyRelationship;
use App\Enums\PayrollStatus;
use App\Enums\ReimbursementStatus;
use App\Enums\RequestStatus;
use App\Enums\TerCategory;
use App\Models\Attendance;
use App\Models\BpjsConfig;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Overtime;
use App\Models\Payroll;
use App\Models\Reimbursement;
use App\Models\TaxConfig;
use App\Traits\ManagesWorkDays;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

class PayrollCalculatorService
{
    use ManagesWorkDays;

    const MONTHLY_WORKING_HOURS = 173;

    public function __construct(
        protected ApprovalService $approvalService,
    ) {}

    /**
     * Menghitung Gaji Tetap (Basic + Allowance) secara Pro-Rate.
     */
    public function calculateProratedSalary(Employee $employee, string $period): float
    {
        $date = Carbon::createFromFormat('Y-m', $period);
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();
        $actualStart = $startOfMonth;
        $actualEnd = $endOfMonth;

        if ($employee->join_date && $employee->join_date->between($startOfMonth, $endOfMonth)) {
            $actualStart = $employee->join_date;
        }
        if ($employee->resign_date && $employee->resign_date->between($startOfMonth, $endOfMonth)) {
            $actualEnd = $employee->resign_date;
        }

        $workingDays = $this->countWorkingDays($startOfMonth, $endOfMonth);
        $actualDays = $this->countWorkingDays($actualStart, $actualEnd);

        if ($workingDays === 0) {
            return 0.0;
        }

        $basicSalary = $employee->position?->basic_salary ?? 0;
        $allowance = $employee->position?->allowance_jabatan ?? 0;

        return round(($actualDays / $workingDays) * ($basicSalary + $allowance), 2);
    }

    /**
     * Menghitung upah lembur berdasarkan UU Cipta Kerja PP 35/2021 Pasal 31.
     * PRD §8.3. SSOT cache holiday di Holiday::cachedYear() (B11 — 1 entry per tahun).
     *
     * Tarif tiered:
     * - Weekday (Senin-Jumat, bukan holiday):
     *     • Jam ke-1     : 1.5x hourlyRate
     *     • Jam ke-2 dst : 2.0x hourlyRate
     * - Hari libur / weekend (multiplier identik per ERR-007):
     *     • Jam 1-8   : 2.0x hourlyRate (8 jam pertama)
     *     • Jam 9-10  : 3.0x hourlyRate (jam ke-9 dan 10)
     *     • Jam 11+   : 4.0x hourlyRate (jam ke-11 dst)
     *
     * Catatan: implementasi simplifikasi mengacu pola 5 hari kerja/minggu.
     * Konversi 6 hari kerja ditangani saat config dinaikkan (TODO config-driven).
     */
    public function calculateOvertimePay(Overtime $overtime): float
    {
        $hours = $overtime->durationHours();

        if ($hours <= 0) {
            return 0.0;
        }

        $employee = $overtime->employee;
        $basicSalary = $employee->position?->basic_salary ?? 0;
        $fixedAllowance = $employee->position?->allowance_jabatan ?? 0;
        $hourlyRate = ($basicSalary + $fixedAllowance) / self::MONTHLY_WORKING_HOURS;

        $date = Carbon::parse($overtime->date);

        // B11: yearly cache (1 entry per year) + in-memory in_array() check
        $isHoliday = Holiday::isHoliday($date) || $date->isWeekend();

        if ($isHoliday) {
            // Holiday/Weekend tiered: 1-8 = 2x, 9-10 = 3x, 11+ = 4x
            $tier1Hours = min($hours, 8);
            $tier2Hours = max(0, min($hours - 8, 2));
            $tier3Hours = max(0, $hours - 10);

            return round(
                ($tier1Hours * $hourlyRate * 2.0) +
                ($tier2Hours * $hourlyRate * 3.0) +
                ($tier3Hours * $hourlyRate * 4.0),
                2
            );
        }

        // B7 fix: Weekday tiered — jam-1 = 1.5x, jam-2 dst = 2x (UU Cipta Kerja).
        $firstHour = min($hours, 1);
        $extraHours = max(0, $hours - 1);

        return round(
            ($firstHour * $hourlyRate * 1.5) +
            ($extraHours * $hourlyRate * 2.0),
            2
        );
    }

    /**
     * Menentukan Kategori TER (A/B/C).
     *
     * B5 fix: gunakan `children_count` (scoped to relationship=CHILD) bukan
     * `family_details_count` yang menghitung SEMUA relasi (orang tua, pasangan, saudara).
     * Pakai `Employee::withCount('children')` di caller untuk avoid N+1.
     * Fallback: count via families relation dengan filter CHILD.
     */
    public function getTERCategory(Employee $employee): TerCategory
    {
        $dependentsCount = $employee->children_count
            ?? $employee->families
                ->where('relationship', FamilyRelationship::CHILD)
                ->count();

        return TerCategory::resolveFromStatus($employee->marital_status, $dependentsCount);
    }

    /**
     * Menghitung PPh21 TER per bulan.
     * SSOT cache di TaxConfig::cachedAll() — service ini fokus orkestrasi kalkulasi.
     */
    public function calculatePPh21(Employee $employee, float $grossIncome, TerCategory $category): float
    {
        if ($employee->employment_type === EmploymentType::INTERN) {
            return 0.0;
        }

        $categoryValue = $category instanceof \BackedEnum ? $category->value : $category;

        $taxRate = collect(TaxConfig::cachedAll())->first(fn (array $t) => $t['ter_category'] === $categoryValue
            && $t['min_income'] <= $grossIncome
            && $t['max_income'] >= $grossIncome
        );

        if (! $taxRate) {
            return 0.0;
        }

        $rateToUse = $taxRate['effective_rate'] ?? $taxRate['rate'];

        return round($grossIncome * $rateToUse, 2);
    }

    /**
     * Menghitung 5 komponen BPJS.
     * SSOT cache di BpjsConfig::cachedAll() — service ini fokus orkestrasi kalkulasi.
     */
    public function calculateBPJS(Employee $employee, float $grossIncome): array
    {
        $result = [
            'bpjs_kesehatan' => ['employer' => 0, 'employee' => 0],
            'bpjs_jht' => ['employer' => 0, 'employee' => 0],
            'bpjs_jp' => ['employer' => 0, 'employee' => 0],
            'bpjs_jkk' => ['employer' => 0, 'employee' => 0],
            'bpjs_jkm' => ['employer' => 0, 'employee' => 0],
        ];

        if ($employee->employment_type === EmploymentType::INTERN) {
            return $result;
        }

        foreach (BpjsConfig::cachedAll() as $config) {
            $baseIncome = $config['ceiling'] !== null
                ? min($grossIncome, $config['ceiling'])
                : $grossIncome;

            $result["bpjs_{$config['name']}"] = [
                'employer' => round($baseIncome * $config['employer_rate'], 2),
                'employee' => round($baseIncome * $config['employee_rate'], 2),
            ];
        }

        return $result;
    }

    /**
     * Menghitung THR Pro-Rated (V1).
     * 
     */
    public function calculateThrProrated(Employee $employee, float $monthlySalary, int $monthsWorked): float
    {
        if ($monthsWorked < 1) {
            return 0.0;
        }

        return round(($monthsWorked / 12) * $monthlySalary, 2);
    }

    /**
     * Orkestrator penggajian akhir bulan.
     * - lockForUpdate() pada cek existing payroll (cegah race condition double-generation).
     * - forceDelete() pada existing payroll (cegah unique constraint violation karena soft delete).
     * - Semua kalkulasi & write berada di dalam SATU transaction.
     */
    public function generatePayroll(Employee $employee, string $period): Payroll
    {
        $parsedPeriod = Carbon::createFromFormat('Y-m', $period);
        $targetYear = $parsedPeriod->year;
        $targetMonth = $parsedPeriod->month;

        return DB::transaction(function () use ($employee, $period, $targetYear, $targetMonth, $parsedPeriod) {
            $existingPayroll = Payroll::where('employee_id', $employee->id)
                ->where('period', $period)
                ->lockForUpdate()
                ->first();
            if ($existingPayroll && $existingPayroll->status === PayrollStatus::PUBLISHED) {
                throw new DomainException("Payroll untuk periode {$period} sudah dikunci permanen.");
            }
            // pendapatan kena pajak
            $grossSalary = $this->calculateProratedSalary($employee, $period);

            $overtimes = Overtime::where('employee_id', $employee->id)
                ->where('status', RequestStatus::APPROVED)
                ->whereYear('date', $targetYear)
                ->whereMonth('date', $targetMonth)
                ->get();

            $totalOvertimePay = $overtimes
                ->map(fn (Overtime $ot) => $this->calculateOvertimePay($ot))
                ->sum();

            $taxableIncome = $grossSalary + $totalOvertimePay;

            // pendapatan bebas pajak - Reimbursment
            $reimbursements = Reimbursement::where('employee_id', $employee->id)
                ->where('status', ReimbursementStatus::APPROVED)
                ->get();
            $totalReimbursment = $reimbursements->sum('amount');
            $totalGross = $taxableIncome + $totalReimbursment;

            // flat per hari
            $penaltyPerDay = (int) CompanySetting::get('attendance_penalty_per_day', 50000);

            $lateCount = Attendance::where('employee_id', $employee->id)
                ->whereYear('date', $targetYear)
                ->whereMonth('date', $targetMonth)
                ->where('late_minutes', '>', 0)
                ->count();
            $latePenalty = $lateCount * $penaltyPerDay;

            $alphaCount = Attendance::where('employee_id', $employee->id)
                ->whereYear('date', $targetYear)
                ->whereMonth('date', $targetMonth)
                ->where('status', AttendanceStatus::ABSENT)
                ->count();
            $dailyRate = $employee->position?->basic_salary ?? 0;
            // B6 fix: hari kerja efektif dihitung dinamis per bulan (exclude weekend + holiday),
            // bukan hardcoded 22. Cegah perhitungan denda alpha tidak akurat di bulan dengan
            // jumlah hari kerja berbeda (Februari 20 hari, Lebaran 16 hari, dll).
            $workingDaysInMonth = $this->countWorkingDays(
                $parsedPeriod->copy()->startOfMonth(),
                $parsedPeriod->copy()->endOfMonth()
            );
            $alphaPenalty = $alphaCount * ($dailyRate > 0 && $workingDaysInMonth > 0
                ? $dailyRate / $workingDaysInMonth
                : 0);

            $attendancePenalty = $latePenalty + $alphaPenalty;

            // BPJS & PPh21 dari taxable income (Reimbursement tidak dipajaki)
            $bpjsComponents = $this->calculateBPJS($employee, $taxableIncome);
            $bpjsKesehatanDeduction = $bpjsComponents['bpjs_kesehatan']['employee'];
            $bpjsEmploymentDeduction = $bpjsComponents['bpjs_jht']['employee'] + $bpjsComponents['bpjs_jp']['employee'];
            $terCategory = $this->getTERCategory($employee);
            $pph21Deduction = $this->calculatePPh21($employee, ($taxableIncome - $attendancePenalty), $terCategory);
            $totalDeduction = $attendancePenalty + $bpjsKesehatanDeduction + $bpjsEmploymentDeduction + $pph21Deduction;
            $basicSalary = $employee->position?->basic_salary ?? 0;
            $totalAllowance = $employee->position?->allowance_jabatan ?? 0;
            $netSalary = $totalGross - $totalDeduction;

            // forceDelete supaya unique (employee_id, period) tidak violation karena soft delete
            if ($existingPayroll) {
                $existingPayroll->forceDelete();
            }
            $payroll = Payroll::create([
                'employee_id' => $employee->id,
                'period' => $period,
                'basic_salary' => $basicSalary,
                'total_allowance' => $totalAllowance,
                'gross_salary' => $totalGross,
                'overtime_pay' => $totalOvertimePay,
                'pph21' => $pph21Deduction,
                'bpjs_health' => $bpjsKesehatanDeduction,
                'bpjs_employment' => $bpjsEmploymentDeduction,
                'loan_deduction' => 0,
                'attendance_penalty' => $attendancePenalty,
                'total_deduction' => $totalDeduction,
                'net_salary' => $netSalary,
                'status' => PayrollStatus::DRAFT,
            ]);

            if ($reimbursements->isNotEmpty()) {
                Reimbursement::whereIn('id', $reimbursements->pluck('id'))
                    ->update([
                        'payroll_id' => $payroll->id,
                        'status' => ReimbursementStatus::PAID,
                    ]);
            }

            return $payroll;
        });
    }
}
