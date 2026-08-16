<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\CarbonInterface;

class LeaveEntitlementService
{
    public function canAccessUser(User $actor, User $target): bool
    {
        if ($actor->isSuperadmin) {
            return true;
        }

        return $actor->company_id !== null && $actor->company_id === $target->company_id;
    }

    public function createOrUpdateAnnualEntitlement(
        User $user,
        int $year,
        float $allocatedDays,
        ?CarbonInterface $expiresAt = null,
        float $carriedOverDays = 0,
        ?string $notes = null,
    ): void {
        $employee = $user->employee;
        if (! $employee) {
            return;
        }

        $leaveType = LeaveType::where('is_active', true)->where('deducts_from_quota', true)->first();

        // M11 (2026-08-06): single-writer — hanya `leave_balances` yang ditulis
        // (source of truth; `leave_entitlements` legacy sudah di-drop).
        LeaveBalance::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType?->id,
                'year' => $year,
            ],
            [
                'quota' => $allocatedDays,
                'used' => 0,
                'carry_forward' => $carriedOverDays,
                'carry_forward_deadline' => $expiresAt,
            ]
        );
    }

    public function summaryFor(User $user, ?LeaveType $leaveType): array
    {
        $employee = $user->employee;
        if (! $employee || ! $leaveType) {
            return [
                'total_allocated' => 0,
                'used_days' => 0,
                'remaining_days' => 0,
                'expires_at' => null,
                'is_expired' => false,
            ];
        }

        $balance = LeaveBalance::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', now()->year)
            ->first();

        if (! $balance) {
            return [
                'total_allocated' => 0,
                'used_days' => 0,
                'remaining_days' => 0,
                'expires_at' => null,
                'is_expired' => false,
            ];
        }

        $carryForward = 0;
        $isExpired = false;

        if ($balance->carry_forward_deadline) {
            if (today()->greaterThan($balance->carry_forward_deadline)) {
                $isExpired = true;
            } else {
                $carryForward = $balance->carry_forward;
            }
        }

        $totalAllocated = $balance->quota + $carryForward;
        $remaining = $totalAllocated - $balance->used;

        return [
            'total_allocated' => $totalAllocated,
            'used_days' => (float) $balance->used,
            'remaining_days' => max(0, $remaining),
            'expires_at' => $balance->carry_forward_deadline?->toDateString(),
            'is_expired' => $isExpired,
        ];
    }

    public function quotaSummariesFor(User $user): array
    {
        $employee = $user->employee;
        if (! $employee) {
            return [];
        }

        $balances = LeaveBalance::with('leaveType')
            ->where('employee_id', $employee->id)
            ->where('year', now()->year)
            ->get();

        return $balances->map(function (LeaveBalance $balance) {
            $carryForward = 0;
            if ($balance->carry_forward_deadline && today()->lessThanOrEqualTo($balance->carry_forward_deadline)) {
                $carryForward = $balance->carry_forward;
            }

            return [
                'leave_type_id' => $balance->leave_type_id,
                'leave_type_name' => $balance->leaveType->name,
                'quota' => (float) $balance->quota,
                'used' => (float) $balance->used,
                'carry_forward' => $carryForward,
                'available' => $balance->quota + $carryForward - (float) $balance->used,
                'expires_at' => $balance->carry_forward_deadline?->toDateString(),
            ];
        })->toArray();
    }

    public function quotaErrorForRequest(
        User $user,
        string $status,
        ?LeaveType $leaveType = null,
        ?CarbonInterface $fromDate = null,
        ?CarbonInterface $toDate = null,
        float $requestedDays = 0,
    ): ?string {
        if (! $leaveType || ! $leaveType->deductsFromQuota()) {
            return null;
        }

        $employee = $user->employee;
        if (! $employee) {
            return null;
        }

        $year = $fromDate ? $fromDate->year : now()->year;

        $balance = LeaveBalance::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $year)
            ->first();

        if (! $balance) {
            return 'Tidak ditemukan saldo cuti untuk tahun ini.';
        }

        if (! $balance->hasEnoughQuota($requestedDays)) {
            return "Saldo cuti tidak mencukupi. Sisa: {$balance->available()} hari, diminta: {$requestedDays} hari.";
        }

        return null;
    }
}
