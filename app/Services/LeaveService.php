<?php

namespace App\Services;

use App\Enums\DayType;
use App\Enums\EmploymentType;
use App\Enums\RequestStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Traits\ManagesWorkDays;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeaveService
{
    use ManagesWorkDays;

    public function __construct(
        protected ApprovalService $approvalService,
    ) {}

    /**
     * Proses pengajuan cuti: 6 pos validasi → simpan → kurangi kuota → approval workflow.
     */
    public function applyLeave(Employee $employee, array $data): Leave
    {
        $leaveType = LeaveType::findOrFail($data['leave_type_id']);
        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);
        $dayType = DayType::from($data['day_type'] ?? 'full_day');

        if ($startDate->isBefore(now()->startOfDay()->subDays(3))) {
            throw new BusinessRuleException('Pengajuan cuti maksimal mundur H+3 dari hari ini.');
        }

        if ($employee->employment_type === EmploymentType::PROBATION
            && $leaveType->deductsFromQuota()
        ) {
            throw new BusinessRuleException('Karyawan masa percobaan tidak dapat mengajukan cuti tahunan.');
        }

        $sickLeaveCode = CompanySetting::get('leave_sick_code', 'sick');
        if ($leaveType->code === $sickLeaveCode && empty($data['proof_file'])) {
            throw new BusinessRuleException('Cuti Sakit wajib menyertakan bukti (Surat Dokter).');
        }

        $totalDays = $this->calculateWorkDays($startDate, $endDate, $dayType);

        if ($totalDays <= 0) {
            throw new BusinessRuleException('Durasi cuti 0 hari. Tanggal hanya weekend atau libur.');
        }

        if (Leave::hasOverlap($employee->id, $startDate, $endDate)) {
            throw new BusinessRuleException('Tanggal bertabrakan dengan pengajuan cuti lain.');
        }

        return DB::transaction(function () use ($employee, $leaveType, $data, $totalDays) {
            if ($leaveType->deductsFromQuota()) {
                $balance = LeaveBalance::where('employee_id', $employee->id)
                    ->where('leave_type_id', $leaveType->id)
                    ->where('year', now()->year)
                    ->lockForUpdate()
                    ->first();

                if (! $balance) {
                    throw new BusinessRuleException('Saldo cuti Anda belum diinisialisasi.');
                }

                if (! $balance->hasEnoughQuota($totalDays)) {
                    $remaining = $balance->available();
                    throw new BusinessRuleException(
                        "Kuota cuti tidak mencukupi. (Sisa: {$remaining} hari, Diminta: {$totalDays} hari)"
                    );
                }

                $balance->deduct($totalDays);
            }

            $leave = Leave::create([
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'day_type' => $data['day_type'],
                'total_days' => $totalDays,
                'reason' => $data['reason'],
                'proof_file' => $data['proof_file'] ?? null,
                'status' => RequestStatus::PENDING,
            ]);

            $this->approvalService->createApprovalWorkflow($leave);

            return $leave;
        });
    }

    /**
     * Hitung hari kerja: exclude weekend + holiday. Half-day = ×0.5.
     */
    public function calculateWorkDays(Carbon $start, Carbon $end, DayType $dayType): float
    {
        $multiplier = in_array($dayType, [DayType::MORNING, DayType::AFTERNOON]) ? 0.5 : 1.0;

        $workDaysCount = $this->countWorkingDays($start, $end);

        return $workDaysCount * $multiplier;
    }

    /**
     * Inisialisasi saldo cuti. Bulk insert + deadline dari CompanySetting.
     */
    public function initializeBalance(Employee $employee, int $year): void
    {
        $leaveTypes = LeaveType::where('is_active', true)->get();

        $existingBalances = LeaveBalance::where('employee_id', $employee->id)
            ->where('year', $year)
            ->pluck('leave_type_id')
            ->toArray();

        $deadlineSetting = CompanySetting::get('leave_carry_forward_deadline', '03-31');
        $deadlineDate = Carbon::parse(($year + 1).'-'.$deadlineSetting)->toDateString();

        $balancesToInsert = [];

        foreach ($leaveTypes as $type) {
            if (in_array($type->id, $existingBalances)) {
                continue;
            }

            $quota = $type->quota;

            if ($employee->join_date && $employee->join_date->year === $year) {
                $remainingMonths = 12 - $employee->join_date->month + 1;
                $quota = (int) round($type->quota * ($remainingMonths / 12));
            }

            $balancesToInsert[] = [
                'employee_id' => $employee->id,
                'leave_type_id' => $type->id,
                'year' => $year,
                'quota' => $quota,
                'used' => 0,
                'carry_forward' => 0,
                'carry_forward_deadline' => $deadlineDate,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (! empty($balancesToInsert)) {
            LeaveBalance::insert($balancesToInsert);
        }
    }

    /**
     * Carry-forward sisa cuti. Maks 3 hari, batas hangus dinamis dari CompanySetting.
     */
    public function carryForward(Employee $employee, int $fromYear, int $toYear): void
    {
        $previousBalances = LeaveBalance::with('leaveType')
            ->where('employee_id', $employee->id)
            ->where('year', $fromYear)
            ->get();

        $deadlineSetting = CompanySetting::get('leave_carry_forward_deadline', '03-31');
        $deadlineDate = Carbon::parse($toYear.'-'.$deadlineSetting)->toDateString();

        foreach ($previousBalances as $prevBalance) {
            $remaining = $prevBalance->quota - $prevBalance->used;

            if ($remaining <= 0) {
                continue;
            }

            $carryForward = min($remaining, 3);

            LeaveBalance::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'leave_type_id' => $prevBalance->leave_type_id,
                    'year' => $toYear,
                ],
                [
                    'quota' => $prevBalance->leaveType->quota,
                    'carry_forward' => $carryForward,
                    'carry_forward_deadline' => $deadlineDate,
                ]
            );
        }
    }
}
