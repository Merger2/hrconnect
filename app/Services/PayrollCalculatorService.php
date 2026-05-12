<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\EmploymentType;
use App\Enums\FamilyRelationship;
use App\Enums\MaritalStatus;
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
use Illuminate\Support\Facades\Cache;
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
     * PRD 11.3 & 11.10.
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
     * Menghitung upah lembur berdasarkan UU Cipta Kerja.
     * PRD 8.3 & 8.4. Cache Holiday lookup untuk cegah N+1.
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

        $isHoliday = Cache::remember("holiday_{$date->toDateString()}", now()->addMonth(), function () use ($date) {
            return Holiday::isHoliday($date);
        }) || $date->isWeekend();

        if ($isHoliday) {
            $firstEightHours = min($hours, 8);
            $extraHours = max($hours - 8, 0);

            return round(($firstEightHours * $hourlyRate * 2.0) + ($extraHours * $hourlyRate * 3.0), 2);
        }

        return round($hours * $hourlyRate * 1.5, 2);
    }

    /**
     * Menentukan Kategori TER (A/B/C).
     * Fix N+1: pakai Collection (tanpa kurung) agar tidak query ulang per karyawan.
     */
    public function getTERCategory(Employee $employee): TerCategory
    {
        $maritalStatus = $employee->marital_status;

        $dependentsCount = $employee->family_details_count
            ?? $employee->families
                ->where('relationship', FamilyRelationship::CHILD)
                ->count();
        $dependents = min($dependentsCount, 3);

        if ($maritalStatus === MaritalStatus::SINGLE) {
            return match (true) {
                $dependents <= 1 => TerCategory::A,
                default => TerCategory::B,
            };
        }

        return match (true) {
            $dependents <= 1 => TerCategory::B,
            default => TerCategory::C,
        };
    }

    /**
     * Menghitung PPh21 TER per bulan.
     * Cache TaxConfig untuk cegah N+1.
     */
    public function calculatePPh21(Employee $employee, float $grossIncome, TerCategory $category): float
    {
        if ($employee->employment_type === EmploymentType::INTERN) {
            return 0.0;
        }

        $taxConfigs = Cache::remember('tax_configs', now()->addDay(), fn () => TaxConfig::all());

        $taxRate = $taxConfigs
            ->where('ter_category', $category)
            ->where('min_income', '<=', $grossIncome)
            ->where('max_income', '>=', $grossIncome)
            ->first();

        if (! $taxRate) {
            return 0.0;
        }

        $rateToUse = $taxRate->effective_rate ?? $taxRate->rate;

        return round($grossIncome * $rateToUse, 2);
    }

    /**
     * Menghitung 5 komponen BPJS.
     * Cache mencegah N+1 saat bulk generate payroll.
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

        $bpjsConfigs = Cache::remember('bpjs_configs', now()->addDay(), fn () => BpjsConfig::all());

        foreach ($bpjsConfigs as $config) {
            $baseIncome = $config->ceiling ? min($grossIncome, $config->ceiling) : $grossIncome;

            $result["bpjs_{$config->name->value}"] = [
                'employer' => round($baseIncome * $config->employer_rate, 2),
                'employee' => round($baseIncome * $config->employee_rate, 2),
            ];
        }

        return $result;
    }

    /**
     * Menghitung THR Pro-Rated (V1).
     * PRD §11.11.
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
     */
    public function generatePayroll(Employee $employee, string $period): Payroll
    {
        // Fix: Parse period untuk whereYear/whereMonth (PostgreSQL safe)
        $parsedPeriod = Carbon::createFromFormat('Y-m', $period);
        $targetYear = $parsedPeriod->year;
        $targetMonth = $parsedPeriod->month;

        // Cek existing — JANGAN delete di sini!
        $existingPayroll = Payroll::where('employee_id', $employee->id)
            ->where('period', $period)
            ->first();

        if ($existingPayroll && $existingPayroll->status === PayrollStatus::PUBLISHED) {
            throw new DomainException("Payroll untuk periode {$period} sudah dikunci permanen.");
        }

        // Pendapatan kena pajak
        $grossSalary = $this->calculateProratedSalary($employee, $period);

        // Fix: whereYear/whereMonth ganti LIKE (PostgreSQL safe)
        $overtimes = Overtime::where('employee_id', $employee->id)
            ->where('status', RequestStatus::APPROVED)
            ->whereYear('date', $targetYear)
            ->whereMonth('date', $targetMonth)
            ->get();

        // Fix: map()->sum() — tidak bikin stdClass
        $totalOvertimePay = $overtimes
            ->map(fn (Overtime $ot) => $this->calculateOvertimePay($ot))
            ->sum();

        $taxableIncome = $grossSalary + $totalOvertimePay;

        // Pendapatan bebas pajak — Reimbursement
        $reimbursements = Reimbursement::where('employee_id', $employee->id)
            ->where('status', ReimbursementStatus::APPROVED)
            ->get();
        $totalReimbursement = $reimbursements->sum('amount');
        $totalGross = $taxableIncome + $totalReimbursement;

        // Fix: Flat per hari (PRD §11.6), bukan per menit
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
        $alphaPenalty = $alphaCount * ($dailyRate > 0 ? $dailyRate / 22 : 0);

        $attendancePenalty = $latePenalty + $alphaPenalty;

        // BPJS & PPh21 dari taxable income (Reimbursement tidak dipajaki)
        $bpjsComponents = $this->calculateBPJS($employee, $taxableIncome);
        $bpjsKesehatanDeduction = $bpjsComponents['bpjs_kesehatan']['employee'];
        $bpjsEmploymentDeduction = $bpjsComponents['bpjs_jht']['employee'] + $bpjsComponents['bpjs_jp']['employee'];

        $terCategory = $this->getTERCategory($employee);
        $pph21Deduction = $this->calculatePPh21($employee, ($taxableIncome - $attendancePenalty), $terCategory);

        // Fix: Hitung kolom NOT NULL sebelum transaksi
        $totalDeduction = $attendancePenalty + $bpjsKesehatanDeduction + $bpjsEmploymentDeduction + $pph21Deduction;
        $basicSalary = $employee->position?->basic_salary ?? 0;
        $totalAllowance = $employee->position?->allowance_jabatan ?? 0;
        $netSalary = $totalGross - $totalDeduction;

        // Fix: delete existingPayroll DI DALAM transaksi (cegah data loss)
        return DB::transaction(function () use (
            $existingPayroll, $employee, $period, $basicSalary, $totalAllowance,
            $totalGross, $totalOvertimePay, $reimbursements,
            $bpjsKesehatanDeduction, $bpjsEmploymentDeduction,
            $pph21Deduction, $attendancePenalty, $totalDeduction, $netSalary
        ) {
            // Fix: Delete DI DALAM transaksi
            if ($existingPayroll) {
                $existingPayroll->delete();
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

            // Bulk update reimbursement — 1 query, bukan N query
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
