<?php

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Enums\AttendanceStatus;
use App\Enums\EmploymentType;
use App\Enums\FamilyRelationship;
use App\Enums\MaritalStatus;
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
use App\Models\KategoriTer;
use App\Models\LeaveBalance;
use App\Models\Overtime;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\Reimbursement;
use App\Models\TarifTer;
use App\Support\ApprovalService;
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
            throw new BusinessRuleException(
                "Tidak ada hari kerja untuk periode {$period}. "
                .'Kemungkinan ada masalah pada kalkulasi hari kerja atau kalender libur. '
                .'Hubungi HRD untuk investigasi sebelum generate payroll ulang.'
            );
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
     * Tarif dibaca dari TarifTer via kategori TER + range penghasilan bruto.
     */
    public function calculatePPh21(Employee $employee, float $grossIncome, TerCategory $category): float
    {
        if ($employee->employment_type === EmploymentType::INTERN) {
            return 0.0;
        }

        $kategoriTer = KategoriTer::where('kode', $category->value)->first();
        if (! $kategoriTer) {
            throw new BusinessRuleException(
                "Kategori TER '{$category->value}' tidak ditemukan di database. "
                .'Hubungi admin untuk melengkapi konfigurasi PTKP/TER sebelum generate payroll.'
            );
        }

        $tarifTer = TarifTer::where('kategori_ter_id', $kategoriTer->id)
            ->where('batas_bawah', '<=', $grossIncome)
            ->where(function ($query) use ($grossIncome) {
                $query->where('batas_atas', '>=', $grossIncome)
                    ->orWhereNull('batas_atas');
            })
            ->first();

        if (! $tarifTer) {
            throw new BusinessRuleException(
                "Tarif TER untuk kategori '{$category->value}' dengan penghasilan Rp "
                .number_format($grossIncome, 0, ',', '.').' tidak ditemukan. '
                .'Periksa kelengkapan data TarifTer di database.'
            );
        }

        return round($grossIncome * $tarifTer->tarif, 2);
    }

    /**
     * PTKP tahunan berdasarkan PP 58/2023.
     * TK = Rp 54.000.000, K = +Rp 4.500.000, setiap tanggungan = +Rp 4.500.000 (max 3).
     */
    private function getPtkpAmount(Employee $employee): float
    {
        $base = 54_000_000;

        if ($employee->marital_status === MaritalStatus::MARRIED) {
            $base += 4_500_000;
        }

        $childrenCount = $employee->children_count
            ?? $employee->families->where('relationship', FamilyRelationship::CHILD)->count();

        return $base + (min($childrenCount, 3) * 4_500_000);
    }

    /**
     * PPh21 metode progresif Pasal 17 UU PPh (setahun).
     * PP 58/2023 mewajibkan true-up di masa pajak terakhir (Desember/terminasi).
     *
     * Lapisan PKP:
     * 0 - 60jt      → 5%
     * 60jt - 250jt  → 15%
     * 250jt - 500jt → 25%
     * 500jt - 5M    → 30%
     * > 5M          → 35%
     */
    public function calculateAnnualPPh21Progressive(Employee $employee, float $annualGrossIncome): float
    {
        $ptkp = $this->getPtkpAmount($employee);
        $pkp = max(0, $annualGrossIncome - $ptkp);

        if ($pkp <= 0) {
            return 0.0;
        }

        $tax = 0.0;
        $remaining = $pkp;

        $layers = [
            ['limit' => 60_000_000, 'rate' => 0.05],
            ['limit' => 190_000_000, 'rate' => 0.15],
            ['limit' => 250_000_000, 'rate' => 0.25],
            ['limit' => 4_500_000_000, 'rate' => 0.30],
        ];

        foreach ($layers as $layer) {
            if ($remaining <= 0) {
                break;
            }
            $portion = min($remaining, $layer['limit']);
            $tax += $portion * $layer['rate'];
            $remaining -= $portion;
        }

        if ($remaining > 0) {
            $tax += $remaining * 0.35;
        }

        return round($tax, 2);
    }

    /**
     * Akumulasi PPh21 TER yang sudah dipotong Jan-Nov (year-to-date).
     */
    private function getYtdCumulativePPh21(Employee $employee, string $currentPeriod): float
    {
        $year = substr($currentPeriod, 0, 4);

        // pgsql SUM() mengembalikan string — cast float (golden test PR-12/13/14 menemukan TypeError).
        return (float) Payroll::where('employee_id', $employee->id)
            ->where('period', 'like', "$year-%")
            ->where('period', '<', $currentPeriod)
            ->sum('pph21');
    }

    /**
     * Akumulasi penghasilan bruto year-to-date.
     */
    private function getYtdGrossIncome(Employee $employee, string $currentPeriod): float
    {
        $year = substr($currentPeriod, 0, 4);

        return (float) Payroll::where('employee_id', $employee->id)
            ->where('period', 'like', "$year-%")
            ->where('period', '<', $currentPeriod)
            ->sum(DB::raw('gross_salary + COALESCE(overtime_pay, 0)'));
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

        // PP 35/2021 Pasal 40 Ayat 4: upah sehari = upah sebulan / divisor tetap
        // 21 for 5-day work week, 25 for 6-day work week
        // Pattern from Quanta HRIS: fixed divisor, not dynamic countWorkingDays()
        $dailyDivisor = (int) config('hrconnect.leave_cash_out_daily_divisor', 21);
        $dailyRate = $dailyDivisor > 0 ? $monthlySalary / $dailyDivisor : 0;

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

        if (! $employee->join_date) {
            return 0.0;
        }

        $endDate = $employee->resign_date ?? $employee->contract_end_date ?? now();

        if ($endDate->lt($employee->join_date)) {
            return 0.0;
        }

        // Cast (int) konsisten dengan calculatePesangon — Carbon 3 diffInMonths
        // mengembalikan float; kompensasi PKWT dihitung per bulan penuh (PP 35/2021).
        // Temuan golden test CP-06.
        $bulanKerja = (int) $employee->join_date->diffInMonths($endDate);

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
        if (! $employee->join_date) {
            return 0.0;
        }

        $years = (int) ($employee->join_date->diffInMonths(now()) / 12);

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
                if ($existingPayroll && in_array($existingPayroll->status, [PayrollStatus::APPROVED, PayrollStatus::PAID], true)) {
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
                // Denda kehadiran BUKAN pengurang penghasilan bruto pajak (PMK 168/2023:
                // pengurang terbatas pada biaya jabatan/iuran pensiun/JHT). Konsisten dengan
                // annual true-up (getYtdGrossIncome) yang juga tidak mengurangi denda.
                // Temuan golden test PR-09/PR-15 — sebelum: taxableIncome - attendancePenalty.
                $pph21Deduction = $this->calculatePPh21($employee, $taxableIncome, $terCategory);

                // PP 58/2023: Desember/bulan terminasi wajib true-up progresif Pasal 17
                $isTerminationMonth = $employee->resign_date && CarbonImmutable::parse($employee->resign_date)->format('Y-m') === $period;
                if ($targetMonth === 12 || $isTerminationMonth) {
                    $ytdGross = $this->getYtdGrossIncome($employee, $period) + $totalGross;
                    $annualPph21 = $this->calculateAnnualPPh21Progressive($employee, $ytdGross);
                    $ytdPph21 = $this->getYtdCumulativePPh21($employee, $period);
                    $pph21Deduction = max(0, round($annualPph21 - $ytdPph21, 2));
                }

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

                // Create PayrollItem records for allowances and deductions (for PDF view)
                $this->createPayrollItems($payroll, [
                    'allowances' => [
                        'Gaji Pokok' => $basicSalary,
                        'Tunjangan Jabatan' => $totalAllowance,
                        'Lembur' => $totalOvertimePay,
                        'Reimbursement' => $totalReimbursment,
                    ],
                    'deductions' => [
                        'BPJS Kesehatan' => $bpjsKesehatanDeduction,
                        'BPJS Ketenagakerjaan' => $bpjsEmploymentDeduction,
                        'PPh 21' => $pph21Deduction,
                        'Denda Kehadiran' => $attendancePenalty,
                    ],
                ]);

                return $payroll;
            });
        } finally {
            $lock->release();
        }
    }

    /**
     * Create PayrollItem records for allowances and deductions.
     */
    protected function createPayrollItems(Payroll $payroll, array $data): void
    {
        foreach ($data['allowances'] as $name => $amount) {
            if ($amount > 0) {
                PayrollItem::create([
                    'payroll_id' => $payroll->id,
                    'name' => $name,
                    'amount' => $amount,
                    'type' => 'allowance',
                ]);
            }
        }

        foreach ($data['deductions'] as $name => $amount) {
            if ($amount > 0) {
                PayrollItem::create([
                    'payroll_id' => $payroll->id,
                    'name' => $name,
                    'amount' => $amount,
                    'type' => 'deduction',
                ]);
            }
        }
    }
}
