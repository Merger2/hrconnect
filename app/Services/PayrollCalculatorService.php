<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\EmploymentType;
use App\Enums\FamilyRelationship;
use App\Enums\PayrollStatus;
use App\Enums\ReimbursementStatus;
use App\Enums\RequestStatus;
use App\Enums\TerCategory;
use App\Exceptions\BusinessRuleException;
use App\Models\Attendance;
use App\Models\BpjsConfig;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveBalance;
use App\Models\Overtime;
use App\Models\Payroll;
use App\Models\Reimbursement;
use App\Models\TaxConfig;
use App\Traits\ManagesWorkDays;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PayrollCalculatorService
{
    use ManagesWorkDays;

    public function __construct(
        protected ApprovalService $approvalService,
    ) {}

    /**
     * Menghitung Gaji Tetap (Basic + Allowance) secara Pro-Rate.
     */
    public function calculateProratedSalary(Employee $employee, string $period): float
    {
        $date = CarbonImmutable::createFromFormat('Y-m', $period);
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();
        $actualStart = $startOfMonth;
        $actualEnd = $endOfMonth;

        if ($employee->join_date && $employee->join_date->between($startOfMonth, $endOfMonth)) {
            $actualStart = $employee->join_date;
        }
        if ($employee->resign_date && $employee->resign_date->between($startOfMonth, $endOfMonth)) {
            // B-39: Guard against resign_date before join_date
            if ($employee->join_date && $employee->resign_date->lt($employee->join_date)) {
                throw new BusinessRuleException('Tanggal resign tidak boleh sebelum tanggal join.');
            }
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

        // B-33: Round fractional hours to nearest 0.5 for legal compliance
        $hours = round($hours * 2) / 2;

        $employee = $overtime->employee;

        // B-38: Throw if employee has no position with salary data
        if (! $employee->position || ! $employee->position->basic_salary) {
            throw new BusinessRuleException(
                'Karyawan '.($employee->full_name ?? 'ID: '.$employee->id).' belum memiliki posisi dengan gaji pokok.'
            );
        }

        $basicSalary = $employee->position->basic_salary;
        $fixedAllowance = $employee->position->allowance_jabatan ?? 0;
        $monthlyHours = (int) config('hrconnect.monthly_working_hours', 173);

        // B-21: Guard division by zero
        if ($monthlyHours <= 0) {
            $monthlyHours = 173;
        }

        $hourlyRate = ($basicSalary + $fixedAllowance) / $monthlyHours;

        $date = CarbonImmutable::parse($overtime->date);

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

        // B-28: Array index safety — validate keys exist
        if (! $taxRate) {
            return 0.0;
        }

        if (! array_key_exists('effective_rate', $taxRate) && ! array_key_exists('rate', $taxRate)) {
            throw new BusinessRuleException('Tax rate data invalid: missing rate keys.');
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
     */
    public function calculateThrProrated(Employee $employee, float $monthlySalary, int $monthsWorked): float
    {
        if ($monthsWorked < 1) {
            return 0.0;
        }

        return round(($monthsWorked / 12) * $monthlySalary, 2);
    }

    /**
     * Menghitung pesangon berdasarkan UU Cipta Kerja (PRD Appendix C + §26.3).
     *
     * Tabel pesangon:
     *   < 1 thn = 0, 1 thn = 1, 2 thn = 2, 3 thn = 3, 4 thn = 4,
     *   5 thn = 5, ≥ 6 thn = 6 bulan gaji.
     *
     * Multiplier variant (phk_variant):
     *   dismissed       = 1.0×
     *   dismissed_severe = 2.0×
     *   mutual          = 0.5×
     *   resign          = 1.0× (default)
     */
    public function calculatePesangon(Employee $employee): float
    {
        if (! $employee->join_date) {
            return 0.0;
        }

        $years = (int) ($employee->join_date->diffInMonths(now()) / 12);

        $monthMultiplier = match (true) {
            $years < 1 => 0,
            $years === 1 => 1,
            $years === 2 => 2,
            $years === 3 => 3,
            $years === 4 => 4,
            $years === 5 => 5,
            default => 6,
        };

        if ($monthMultiplier === 0) {
            return 0.0;
        }

        $monthlySalary = $this->getMonthlySalary($employee);
        $variantMultiplier = $this->getPhkVariantMultiplier($employee->phk_variant);

        return round($monthMultiplier * $monthlySalary * $variantMultiplier, 2);
    }

    /**
     * Menghitung uang pengganti cuti yang tidak diambil saat resign/PHK.
     *
     * Rumus: sisa kuota cuti tahunan × (gross_monthly / countWorkingDays(month))
     */
    public function calculateLeaveCashOut(Employee $employee): float
    {
        $leaveBalance = LeaveBalance::where('employee_id', $employee->id)
            ->where('year', now()->year)
            ->first();

        $remaining = $leaveBalance
            ? max(0, $leaveBalance->quota - $leaveBalance->used)
            : 0;

        if ($remaining <= 0) {
            return 0.0;
        }

        $monthlySalary = $this->getMonthlySalary($employee);
        $workingDays = $this->countWorkingDays(
            now()->startOfMonth(),
            now()->endOfMonth()
        );

        $dailyRate = $workingDays > 0 ? $monthlySalary / $workingDays : 0;

        return round($remaining * $dailyRate, 2);
    }

    /**
     * Menghitung uang kompensasi untuk PKWT yang kontraknya habis.
     *
     * Rumus: (masa_kerja_bulan / 12) × monthly_salary
     * Hanya berlaku untuk employment_type = CONTACT.
     */
    public function calculateUangKompensasi(Employee $employee): float
    {
        if ($employee->employment_type !== EmploymentType::CONTRACT) {
            return 0.0;
        }

        $endDate = $employee->resign_date ?? $employee->contract_end_date ?? now();
        $bulanKerja = $employee->join_date
            ? $employee->join_date->diffInMonths($endDate)
            : 0;

        if ($bulanKerja < 1) {
            return 0.0;
        }

        $monthlySalary = $this->getMonthlySalary($employee);

        return round(($bulanKerja / 12) * $monthlySalary, 2);
    }

    /**
     * Menghitung uang penghargaan masa kerja (UU Cipta Kerja Pasal 156).
     *
     * Tabel masa kerja → multiplier:
     *   < 3 thn = 0, 3-6 = 2, 6-9 = 3, 9-12 = 4, 12-15 = 5,
     *   15-18 = 6, 18-21 = 7, 21-24 = 8, ≥ 24 = 10 bulan gaji.
     */
    public function calculateUangPenghargaanMasaKerja(Employee $employee): float
    {
        $years = (int) ($employee->join_date?->diffInMonths(now()) / 12);

        $monthMultiplier = match (true) {
            $years < 3 => 0,
            $years < 6 => 2,
            $years < 9 => 3,
            $years < 12 => 4,
            $years < 15 => 5,
            $years < 18 => 6,
            $years < 21 => 7,
            $years < 24 => 8,
            default => 10,
        };

        if ($monthMultiplier === 0) {
            return 0.0;
        }

        $monthlySalary = $this->getMonthlySalary($employee);
        $variantMultiplier = $this->getPhkVariantMultiplier($employee->phk_variant);

        return round($monthMultiplier * $monthlySalary * $variantMultiplier, 2);
    }

    private function getMonthlySalary(Employee $employee): float
    {
        return ($employee->position?->basic_salary ?? 0)
            + ($employee->position?->allowance_jabatan ?? 0);
    }

    private function getPhkVariantMultiplier(?string $phkVariant): float
    {
        return match ($phkVariant) {
            'dismissed_severe' => 2.0,
            'mutual' => 0.5,
            default => 1.0,
        };
    }

    /**
     * Orkestrator penggajian akhir bulan.
     * - lockForUpdate() pada cek existing payroll (cegah race condition double-generation).
     * - forceDelete() pada existing payroll (cegah unique constraint violation karena soft delete).
     * - Semua kalkulasi & write berada di dalam SATU transaction.
     */
    public function generatePayroll(Employee $employee, string $period): Payroll
    {
        // B3.10 fix: explicit guard kalau employee belum punya position.
        // Sebelum: silent fallback ke 0 via ?->basic_salary ?? 0 → payroll Rp 0
        // tanpa peringatan apapun. Sekarang: throw eksplisit supaya HRD aware.
        if (! $employee->position) {
            throw new BusinessRuleException(
                "Karyawan {$employee->employee_number} belum memiliki jabatan (position). Hubungi HRD untuk konfigurasi sebelum generate payroll."
            );
        }

        $parsedPeriod = CarbonImmutable::createFromFormat('Y-m', $period);
        $targetYear = $parsedPeriod->year;
        $targetMonth = $parsedPeriod->month;
        $lock = Cache::lock("payroll:generate:{$employee->id}:{$period}", 120);

        if (! $lock->get()) {
            throw new BusinessRuleException("Payroll untuk periode {$period} sedang diproses. Coba lagi beberapa saat.");
        }

        try {
            return DB::transaction(function () use ($employee, $period, $targetYear, $targetMonth, $parsedPeriod) {
                $existingPayroll = Payroll::where('employee_id', $employee->id)
                    ->where('period', $period)
                    ->lockForUpdate()
                    ->first();
                if ($existingPayroll && $existingPayroll->status === PayrollStatus::PUBLISHED) {
                    // B3.6 fix: BusinessRuleException → HTTP 422 (business rule violation),
                    // bukan DomainException → HTTP 500 (generic server error).
                    throw new BusinessRuleException("Payroll untuk periode {$period} sudah dikunci permanen.");
                }
                // pendapatan kena pajak
                $grossSalary = $this->calculateProratedSalary($employee, $period);

                $overtimes = Overtime::where('employee_id', $employee->id)
                    ->where('status', RequestStatus::APPROVED)
                    ->whereYear('date', $targetYear)
                    ->whereMonth('date', $targetMonth)
                    ->get()
                    ->each(fn (Overtime $ot) => $ot->setRelation('employee', $employee));

                $totalOvertimePay = $overtimes
                    ->map(fn (Overtime $ot) => $this->calculateOvertimePay($ot))
                    ->sum();

                $taxableIncome = $grossSalary + $totalOvertimePay;

                // B-3: Filter reimbursement berdasarkan bulan periode
                $reimbursements = Reimbursement::where('employee_id', $employee->id)
                    ->where('status', ReimbursementStatus::APPROVED)
                    ->whereYear('expense_date', $targetYear)
                    ->whereMonth('expense_date', $targetMonth)
                    ->lockForUpdate()
                    ->get();
                $totalReimbursment = $reimbursements->sum('amount');
                $totalGross = $taxableIncome + $totalReimbursment;

                // flat per hari
                $penaltyPerDay = (int) CompanySetting::get('attendance_penalty_per_day', 50000);

                // 3.11 fix: lateCount filter WFA + exception. Karyawan WFA tidak
                // kena denda telat (tidak ada toleransi GPS), dan late yang sudah
                // di-approve exception (force majeure) juga tidak boleh kena denda.
                $lateCount = Attendance::where('employee_id', $employee->id)
                    ->whereYear('date', $targetYear)
                    ->whereMonth('date', $targetMonth)
                    ->where('late_minutes', '>', 0)
                    ->where('is_wfa', false)
                    ->whereNull('exception_type')
                    ->count();
                $latePenalty = $lateCount * $penaltyPerDay;

                // 3.11 fix: alphaCount filter exception. Alpha yang punya exception
                // approved (sakit mendadak diakui, izin force majeure) tidak kena denda.
                $alphaCount = Attendance::where('employee_id', $employee->id)
                    ->whereYear('date', $targetYear)
                    ->whereMonth('date', $targetMonth)
                    ->where('status', AttendanceStatus::ABSENT)
                    ->whereNull('exception_type')
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

                // B-1: UPDATE existing DRAFT instead of forceDelete+create
                // Preserve pdf_path, adjustments, and other data
                if ($existingPayroll) {
                    $existingPayroll->update([
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

                    $payroll = $existingPayroll;
                } else {
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
                }

                if ($reimbursements->isNotEmpty()) {
                    Reimbursement::whereIn('id', $reimbursements->pluck('id'))
                        ->update([
                            'payroll_id' => $payroll->id,
                            'status' => ReimbursementStatus::PAID,
                        ]);
                }

                return $payroll;
            });
        } finally {
            $lock->release();
        }
    }
}
