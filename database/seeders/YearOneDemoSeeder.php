<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatus;
use App\Enums\EmployeeStatus;
use App\Enums\PayrollStatus;
use App\Enums\RequestStatus;
use App\Enums\VerificationMethod;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Overtime;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\User;
use App\Services\Payroll\PayrollCalculatorService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Seed SATU TAHUN berjalan (12 bulan kalender terakhir, ha 2025-08 s/d 2026-07)
 * untuk semua employee aktif: jadwal shift harian, absensi hari kerja (deterministik),
 * saldo + pengajuan cuti, lembur, dan payroll 12 periode (via PayrollCalculatorService
 * yang sudah teruji — reuse, bukan re-implementasi).
 *
 * DETERMINISTIK (hash email+date, bukan random) supaya seed berulang stabil, dan
 * idempoten (firstOrCreate) — aman dijalankan ulang. Demo/test-only: guard
 * isProduction() + DatabaseSeeder hanya memanggil di blok non-production.
 *
 * Rentang: 1 Agustus 2025 s/d 31 Juli 2026 (12 bulan). Hari ini TIDAK di-seed
 * supaya user masih bisa Clock In normal.
 */
class YearOneDemoSeeder extends Seeder
{
    private const MONTHS = 12;

    public function __construct(
        protected PayrollCalculatorService $payrollCalculator,
    ) {}

    public function run(): void
    {
        // Demo/test-only: jangan pernah di-seed di production (polusi riwayat nyata).
        // Demo VPS (skripsi): SEED_DEMO=true mengizinkan di production — default off.
        if (app()->isProduction() && ! filter_var(env('SEED_DEMO', false), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $now = CarbonImmutable::today();
        $end = $now->subDay(); // hari ini di-skip
        $start = $now->subMonths(self::MONTHS)->startOfMonth();

        $this->command?->info("YearOneDemoSeeder: rentang {$start->toDateString()} s/d {$end->toDateString()}");

        $defaultShiftId = Shift::where('name', 'Office Hour')->first()?->id;
        $shifts = Shift::pluck('id', 'name')->all();

        $holidays = $this->holidaySet($start, $end);

        $employees = Employee::query()
            ->where('status', EmployeeStatus::ACTIVE->value)
            ->with('user:id,email')
            ->get();

        if ($employees->isEmpty()) {
            return;
        }

        // 1) Jadwal harian per karyawan (user_id + date unique) — weekend = off
        $scheduleCount = $this->seedSchedules($employees, $start, $end, $defaultShiftId);

        // 2) Saldo cuti (2025 + 2026) & pengajuan cuti approved
        [$leaveCount, $leaveDateKeys] = $this->seedLeaves($employees, $start, $end);

        // 3) Absensi hari kerja (skip weekend/holiday/leave-date/join-date)
        $attendanceCount = $this->seedAttendances($employees, $start, $end, $defaultShiftId, $holidays, $leaveDateKeys);

        // 4) Lembur approved (~10% employee, deterministik)
        $overtimeCount = $this->seedOvertimes($employees, $start, $end, $defaultShiftId, $holidays);

        // 5) Payroll 12 periode (generatePayroll — reuse service teruji)
        $payrollCount = $this->seedPayrolls($employees, $start, $end);

        $this->command?->info(sprintf(
            'YearOneDemoSeeder selesai: %d jadwal, %d absensi, %d pengajuan cuti, %d lembur, %d payroll.',
            $scheduleCount,
            $attendanceCount,
            $leaveCount,
            $overtimeCount,
            $payrollCount
        ));
    }

    private function holidaySet(CarbonImmutable $start, CarbonImmutable $end): array
    {
        return Holiday::where('is_active', true)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')
            ->map(fn ($d) => CarbonImmutable::parse($d)->toDateString())
            ->all();
    }

    private function seedSchedules($employees, CarbonImmutable $start, CarbonImmutable $end, ?int $defaultShiftId): int
    {
        $count = 0;
        $officeHourId = $defaultShiftId;

        foreach ($employees as $employee) {
            $userId = $employee->user_id;
            if (! $userId) {
                continue;
            }

            $shiftId = $employee->shift_id ?? $officeHourId;

            foreach ($this->datesBetween($start, $end) as $date) {
                Schedule::firstOrCreate(
                    ['user_id' => $userId, 'date' => $date->toDateString()],
                    [
                        'shift_id' => $date->isWeekend() ? null : $shiftId,
                        'is_off' => $date->isWeekend(),
                    ]
                );
                $count++;
            }
        }

        return $count;
    }

    /**
     * Saldo cuti tahun 2025 & 2026 + pengajuan approved (deterministik).
     *
     * @return array{0: int, 1: array<string, true>} [jumlah pengajuan, set "employeeId:date"]
     */
    private function seedLeaves($employees, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $leaveTypes = LeaveType::all();
        $annual = $leaveTypes->firstWhere('code', 'ANNUAL');
        $sick = $leaveTypes->firstWhere('code', 'SICK');

        if (! $annual || ! $sick) {
            return [0, []];
        }

        $leaveDateKeys = [];
        $requestCount = 0;
        $currentYear = (int) $end->format('Y');
        $prevYear = $currentYear - 1;

        foreach ($employees as $employee) {
            // Saldo quota proporsional thd join date (tahun bergabung)
            foreach ([$prevYear, $currentYear] as $year) {
                $quota = $this->proratedQuota($annual->quota, $employee->join_date, $year);

                LeaveBalance::firstOrCreate(
                    ['employee_id' => $employee->id, 'leave_type_id' => $annual->id, 'year' => $year],
                    [
                        'quota' => $quota,
                        'used' => 0,
                        'carry_forward' => 0,
                    ]
                );

                LeaveBalance::firstOrCreate(
                    ['employee_id' => $employee->id, 'leave_type_id' => $sick->id, 'year' => $year],
                    [
                        'quota' => $sick->quota,
                        'used' => 0,
                        'carry_forward' => 0,
                    ]
                );
            }

            // Pengajuan cuti: deterministik — ~60% employee punya 1-2 cuti tahunan,
            // ~25% pernah sakit. Hanya hari kerja, dalam rentang window.
            $seed = crc32((string) $employee->id.'|leave') & 0x7FFFFFFF;

            if ($seed % 100 < 60) {
                $annualBlocks = 1 + ($seed % 2); // 1-2 blok
                for ($b = 0; $b < $annualBlocks; $b++) {
                    $leave = $this->createLeaveBlock($employee, $annual, $start, $end, $leaveDateKeys, 'Cuti tahunan');
                    if ($leave) {
                        $leaveDateKeys = $this->collectLeaveDateKeys($leaveDateKeys, $leave);
                        $requestCount++;
                        $this->addLeaveApproval($employee, $leave);
                    }
                }
            }

            if (($seed >> 8) % 100 < 25) {
                $leave = $this->createLeaveBlock($employee, $sick, $start, $end, $leaveDateKeys, 'Sakit');
                if ($leave) {
                    $leaveDateKeys = $this->collectLeaveDateKeys($leaveDateKeys, $leave);
                    $requestCount++;
                    $this->addLeaveApproval($employee, $leave);
                }
            }
        }

        return [$requestCount, $leaveDateKeys];
    }

    private function createLeaveBlock(
        Employee $employee,
        LeaveType $leaveType,
        CarbonImmutable $start,
        CarbonImmutable $end,
        array $leaveDateKeys,
        string $reason
    ): ?Leave {
        // 2-4 hari kerja acak-deterministik dalam window
        $windowDays = (int) $start->diffInDays($end);
        $offset = (crc32($employee->id.'|'.$leaveType->id.'|'.$start->toDateString()) & 0x7FFFFFFF) % max(1, $windowDays - 10);
        $from = $start->addDays($offset)->startOfWeek();

        $days = 1 + ((crc32($employee->id.'|'.$leaveType->id.'|days') & 0x7FFFFFFF) % 3);

        $startDate = $this->nextWorkingDay($from);
        if (! $startDate || $startDate->lt($start) || $startDate->gt($end)) {
            return null;
        }

        $dates = [$startDate];
        $cursor = $startDate;
        for ($i = 1; $i < $days; $i++) {
            $cursor = $this->nextWorkingDay($cursor->addDay());
            if (! $cursor || $cursor->gt($end)) {
                break;
            }
            $dates[] = $cursor;
        }

        // Skip bila bentrok dgn leave lain di karyawan yg sama
        foreach ($dates as $d) {
            $key = $employee->id.':'.$d->toDateString();
            if (isset($leaveDateKeys[$key])) {
                return null;
            }
        }

        $totalDays = count($dates);
        $actualStart = $dates[0];
        $actualEnd = $dates[$totalDays - 1];

        return Leave::firstOrCreate(
            [
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'start_date' => $actualStart->toDateString(),
                'end_date' => $actualEnd->toDateString(),
            ],
            [
                'day_type' => 'full_day',
                'total_days' => $totalDays,
                'reason' => $reason,
                'status' => RequestStatus::APPROVED->value,
            ]
        );
    }

    private function collectLeaveDateKeys(array $keys, Leave $leave): array
    {
        // Iterasi start..end manual (Leave tidak punya metode daftar tanggal)
        $cursor = CarbonImmutable::parse($leave->start_date);
        $leaveEnd = CarbonImmutable::parse($leave->end_date);

        while ($cursor->lte($leaveEnd)) {
            if (! $cursor->isWeekend()) {
                $keys[$leave->employee_id.':'.$cursor->toDateString()] = true;
            }
            $cursor = $cursor->addDay();
        }

        return $keys;
    }

    private function seedAttendances(
        $employees,
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?int $defaultShiftId,
        array $holidays,
        array $leaveDateKeys
    ): int {
        $count = 0;

        foreach ($employees as $employee) {
            $shiftId = $employee->shift_id ?? $defaultShiftId;
            $joinDate = $employee->join_date;
            $email = $employee->user?->email ?? (string) $employee->id;

            foreach ($this->datesBetween($start, $end) as $date) {
                // Hanya hari kerja Senin-Jumat
                if ($date->isWeekend()) {
                    continue;
                }
                if (in_array($date->toDateString(), $holidays, true)) {
                    continue;
                }
                if ($joinDate && $date->lt($joinDate)) {
                    continue;
                }

                $dateKey = $employee->id.':'.$date->toDateString();

                // Hari cuti approved → absensi excused/sick (tanpa jam, tanpa denda alfa)
                if (isset($leaveDateKeys[$dateKey])) {
                    Attendance::firstOrCreate(
                        ['employee_id' => $employee->id, 'date' => $date->toDateString()],
                        [
                            'shift_id' => $shiftId,
                            'clock_in' => null,
                            'clock_out' => null,
                            'status' => AttendanceStatus::EXCUSED->value,
                            'late_minutes' => 0,
                            'is_wfa' => false,
                            'verification_method' => null,
                        ]
                    );
                    $count++;

                    continue;
                }

                // Status deterministik dari hash email+date (stabil antar re-seed)
                $roll = (crc32($email.'|'.$date->toDateString()) & 0x7FFFFFFF) % 100;

                $status = match (true) {
                    $roll < 8 => AttendanceStatus::ABSENT, // ~8%: alpa
                    $roll < 22 => AttendanceStatus::LATE, // ~14%: terlambat
                    $roll < 28 => AttendanceStatus::PERMISSION, // ~6%: izin (ada jam, status izin)
                    $roll < 34 => AttendanceStatus::MISSED_CLOCK_IN, // ~6%: lupa absen masuk
                    default => AttendanceStatus::ON_TIME, // ~66%: tepat waktu
                };

                $clockIn = null;
                $clockOut = null;
                $lateMinutes = 0;
                $isWfa = false;

                if ($status === AttendanceStatus::ON_TIME) {
                    $clockIn = $date->setTime(8, 0)->addMinutes(($roll % 15)); // 08:00-08:14
                    $clockOut = $date->setTime(17, 0)->addMinutes(($roll % 31));
                    if (($roll >> 4) % 100 < 8) { // ~8% hari WFA
                        $isWfa = true;
                    }
                } elseif ($status === AttendanceStatus::LATE) {
                    $lateMinutes = 15 + ($roll % 46); // 15-60 menit
                    $clockIn = $date->setTime(8, 0)->addMinutes($lateMinutes);
                    $clockOut = $date->setTime(17, 0)->addMinutes($roll % 31);
                } elseif ($status === AttendanceStatus::PERMISSION) {
                    $clockIn = $date->setTime(8, 0)->addMinutes($roll % 30);
                    $clockOut = $date->setTime(17, 0)->addMinutes($roll % 30);
                } elseif ($status === AttendanceStatus::MISSED_CLOCK_IN) {
                    $clockOut = $date->setTime(17, 0)->addMinutes($roll % 31);
                }
                // ABSENT: tanpa jam

                Attendance::firstOrCreate(
                    ['employee_id' => $employee->id, 'date' => $date->toDateString()],
                    [
                        'shift_id' => $shiftId,
                        'clock_in' => $clockIn,
                        'clock_out' => $clockOut,
                        'status' => $status,
                        'late_minutes' => $lateMinutes,
                        'is_wfa' => $isWfa,
                        'verification_method' => $status === AttendanceStatus::ABSENT
                            || $status === AttendanceStatus::MISSED_CLOCK_IN
                            ? null
                            : VerificationMethod::FACE_VERIFIED->value,
                    ]
                );

                $count++;
            }
        }

        return $count;
    }

    private function seedOvertimes(
        $employees,
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?int $defaultShiftId,
        array $holidays
    ): int {
        $count = 0;
        $days = $this->datesBetween($start, $end)->filter(fn ($d) => ! $d->isWeekend())->values();

        foreach ($employees as $employee) {
            $seed = crc32((string) $employee->id.'|overtime') & 0x7FFFFFFF;

            // ~25% employee pernah lembur 1-3x setahun
            if ($seed % 100 >= 25) {
                continue;
            }

            $times = 1 + (($seed >> 4) % 3);
            for ($t = 0; $t < $times; $t++) {
                $dayIndex = (($seed >> 8) + $t * 37) % max(1, $days->count());
                $date = $days[$dayIndex];
                if (in_array($date->toDateString(), $holidays, true)) {
                    continue;
                }

                $startHour = 18 + ($t % 2); // 18:00 / 19:00
                $duration = 1 + (($seed >> 12) % 3); // 1-3 jam

                $overtime = Overtime::firstOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'date' => $date->toDateString(),
                        'start_time' => $date->setTime($startHour, 0)->format('H:i:s'),
                        'end_time' => $date->setTime($startHour + $duration, 0)->format('H:i:s'),
                    ],
                    [
                        'description' => 'Penyelesaian pekerjaan target bulanan',
                        'total_hours' => $duration,
                        'status' => RequestStatus::APPROVED->value,
                        'approved_at' => $date->addDay()->setTime(9, 0),
                    ]
                );
                $this->addOvertimeApproval($employee, $overtime);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Payroll 12 periode via PayrollCalculatorService (sudah teruji — tidak re-implementasi).
     * Periode < bulan berjalan → status PAID + payment_date; bulan berjalan → DRAFT.
     */
    private function seedPayrolls($employees, CarbonImmutable $start, CarbonImmutable $end): int
    {
        $count = 0;
        $now = CarbonImmutable::today();
        $currentPeriod = $now->format('Y-m');

        for ($i = 0; $i < self::MONTHS; $i++) {
            $period = $start->copy()->addMonths($i)->format('Y-m');
            $periodEnd = CarbonImmutable::createFromFormat('Y-m', $period)->endOfMonth();

            foreach ($employees as $employee) {
                // Skip bila join setelah periode ini berakhir
                if ($employee->join_date && $employee->join_date->gt($periodEnd)) {
                    continue;
                }

                try {
                    $payroll = $this->payrollCalculator->generatePayroll($employee, $period);

                    // Periode lampau → status PAID via rantai transisi yang SAH
                    // (draft→submitted→verified→approved→paid — guard di model
                    // menolak lompatan langsung). Bulan berjalan → biarkan DRAFT.
                    if ($period < $currentPeriod && ! $payroll->isTerminal()) {
                        foreach ([PayrollStatus::SUBMITTED, PayrollStatus::VERIFIED, PayrollStatus::APPROVED] as $status) {
                            $payroll->forceFill(['status' => $status->value])->save();
                        }

                        // payment_date + payment_method di-set SEKALIGUS dgn transisi
                        // ke PAID: guard model menolak update terpisah pada payroll
                        // berstatus PAID (sebelumnya payment_date selalu gagal
                        // tersimpan — 617/617 paid NULL). 'transfer' = nilai valid
                        // CHECK constraint (migration enum transfer/cash/cheque).
                        $payroll->forceFill([
                            'status' => PayrollStatus::PAID->value,
                            'payment_date' => $periodEnd->addDays(3)->toDateString(),
                            'payment_method' => 'transfer',
                        ])->save();
                    }
                    $count++;
                } catch (\Throwable $e) {
                    // Employee tanpa posisi / konfigurasi — log, jangan gagalkan seluruh seed
                    $this->command?->warn("  [skip] payroll {$employee->id} {$period}: {$e->getMessage()}");
                }
            }
        }

        return $count;
    }

    private function proratedQuota(float $quota, ?CarbonImmutable $joinDate, int $year): float
    {
        if (! $joinDate) {
            return $quota;
        }

        $yearStart = CarbonImmutable::create($year, 1, 1);

        // Bergabung sebelum tahun tsb → quota penuh
        if ($joinDate->lt($yearStart)) {
            return $quota;
        }

        // Bergabung di tahun tsb → proporsional
        $joinMonth = (int) $joinDate->format('n');
        $monthsWorked = 13 - $joinMonth;

        return round($quota * min(1, max(0, $monthsWorked / 12)), 1);
    }

    private function nextWorkingDay(CarbonImmutable $date): ?CarbonImmutable
    {
        $cursor = $date;
        for ($i = 0; $i < 7; $i++) {
            if (! $cursor->isWeekend()) {
                return $cursor;
            }
            $cursor = $cursor->addDay();
        }

        return null;
    }

    /** @return Collection<int, CarbonImmutable> */
    private function datesBetween(CarbonImmutable $start, CarbonImmutable $end)
    {
        $dates = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $dates[] = $cursor;
            $cursor = $cursor->addDay();
        }

        return collect($dates);
    }

    private function getManagerEmployeeId(int $employeeId): ?int
    {
        $employee = Employee::find($employeeId);
        if ($employee && $employee->parent_id) {
            return $employee->parent_id;
        }

        // Fallback: find first manager employee
        return Employee::whereHas('user', fn ($q) => $q->whereHas('roles', fn ($q) => $q->where('name', 'manager')))
            ->where('id', '!=', $employeeId)
            ->value('id');
    }

    private function addLeaveApproval(Employee $employee, Leave $leave): void
    {
        $managerId = $this->getManagerEmployeeId($employee->id);
        if (! $managerId) {
            return;
        }

        DB::table('approvals')->insertOrIgnore([
            'approver_id' => $managerId,
            'approvable_id' => $leave->id,
            'approvable_type' => Leave::class,
            'level' => 1,
            'status' => 'approved',
            'approved_at' => now(),
            'notes' => 'Disetujui oleh manager (demo)',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // L2 approval by HR
        $hrId = Employee::whereHas('user', fn ($q) => $q->whereHas('roles', fn ($q) => $q->where('name', 'admin')))
            ->where('id', '!=', $employee->id)
            ->where('id', '!=', $managerId)
            ->value('id');

        if ($hrId) {
            DB::table('approvals')->insertOrIgnore([
                'approver_id' => $hrId,
                'approvable_id' => $leave->id,
                'approvable_type' => Leave::class,
                'level' => 2,
                'status' => 'approved',
                'approved_at' => now()->addDay(),
                'notes' => 'Disetujui oleh HR (demo)',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function addOvertimeApproval(Employee $employee, Overtime $overtime): void
    {
        $managerId = $this->getManagerEmployeeId($employee->id);
        if (! $managerId) {
            return;
        }

        DB::table('approvals')->insertOrIgnore([
            'approver_id' => $managerId,
            'approvable_id' => $overtime->id,
            'approvable_type' => Overtime::class,
            'level' => 1,
            'status' => 'approved',
            'approved_at' => $overtime->approved_at ?? now(),
            'notes' => 'Disetujui oleh manager (demo)',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
