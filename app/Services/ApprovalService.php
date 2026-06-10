<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ApprovalLevel;
use App\Enums\ApprovalStatus;
use App\Enums\RequestStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Approval;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\Reimbursement;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LogicException;

class ApprovalService
{
    /**
     * Membuat rantai approval 2-level untuk satu pengajuan.
     * Jika parent_id = NULL → skip Level 1, langsung ke Level 2 (HR Manager).
     * Jika tidak ada approver sama sekali → LogicException.
     */
    public function createApprovalWorkflow(Model $approvable): void
    {
        DB::transaction(function () use ($approvable) {
            $employee = $approvable->employee;
            $directApprover = $employee->getDirectApprover();
            $l2Approver = $this->resolveL2Approver($approvable);

            $approversCount = 0;

            if ($directApprover) {
                $approvable->approvals()->create([
                    'approver_id' => $directApprover->id,
                    'level' => 1,
                    'status' => ApprovalStatus::PENDING,
                ]);
                $approversCount++;
            }

            if ($l2Approver) {
                $approvable->approvals()->create([
                    'approver_id' => $l2Approver->id,
                    'level' => 2,
                    'status' => ApprovalStatus::PENDING,
                ]);
                $approversCount++;
            }

            if ($approversCount === 0) {
                throw new LogicException('Tidak ada Approver (Atasan/HR) yang tersedia.');
            }
        });
    }

    /**
     * Menyetujui satu langkah approval.
     * lockForUpdate() di parent model cegah race condition.
     */
    public function approve(Approval $approval, string $notes = ''): void
    {
        DB::transaction(function () use ($approval, $notes) {
            $lockedApproval = Approval::query()
                ->whereKey($approval->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedApproval->status !== ApprovalStatus::PENDING) {
                throw new BusinessRuleException('Approval sudah diproses sebelumnya.');
            }

            $approvable = $lockedApproval->approvable()->lockForUpdate()->first();

            if (! $approvable) {
                throw new BusinessRuleException('Pengajuan tidak ditemukan.');
            }

            if ($lockedApproval->level->value > ApprovalLevel::L1_SUPERVISOR->value) {
                $hasPendingPreviousLevel = $approvable->approvals()
                    ->where('level', '<', $lockedApproval->level->value)
                    ->where('status', '!=', ApprovalStatus::APPROVED)
                    ->exists();

                if ($hasPendingPreviousLevel) {
                    throw new BusinessRuleException('Approval level sebelumnya harus disetujui terlebih dahulu.');
                }
            }

            $lockedApproval->update([
                'status' => ApprovalStatus::APPROVED,
                'approved_at' => now(),
                'notes' => $notes ?: null,
            ]);
            if ($approvable->isAllApproved()) {
                $approvable->update(['status' => RequestStatus::APPROVED]);
                // Fix B3: deduct kuota saat full approval (bukan saat submit)
                if (
                    $approvable instanceof Leave
                    && $approvable->leaveType?->deductsFromQuota()
                ) {
                    $balance = LeaveBalance::where('employee_id', $approvable->employee_id)
                        ->where('leave_type_id', $approvable->leave_type_id)
                        ->where('year', CarbonImmutable::parse($approvable->start_date)->year)
                        ->lockForUpdate()
                        ->first();

                    // B-22: Throw if balance not found instead of silent null
                    if (! $balance) {
                        throw new BusinessRuleException('Saldo cuti tidak ditemukan saat approval.');
                    }

                    $balance->deduct((float) $approvable->total_days);
                }
            } elseif ($lockedApproval->level === ApprovalLevel::L1_SUPERVISOR) {
                $approvable->update(['status' => RequestStatus::APPROVED_L1]);
            }
        });
    }

    /**
     * Menolak satu langkah approval — seluruh pengajuan batal permanen.
     */
    public function reject(Approval $approval, string $reason): void
    {
        DB::transaction(function () use ($approval, $reason) {
            $lockedApproval = Approval::query()
                ->whereKey($approval->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedApproval->status !== ApprovalStatus::PENDING) {
                throw new BusinessRuleException('Approval sudah diproses sebelumnya.');
            }

            $approvable = $lockedApproval->approvable()->lockForUpdate()->first();

            if (! $approvable) {
                throw new BusinessRuleException('Pengajuan tidak ditemukan.');
            }

            $lockedApproval->update([
                'status' => ApprovalStatus::REJECTED,
                'notes' => $reason,
            ]);

            $approvable->update([
                'status' => RequestStatus::REJECTED,
                'rejection_reason' => $reason,
            ]);
        });
    }

    /**
     * Resolve approver Level 2 berdasarkan tipe pengajuan.
     * Reimbursement → Finance. Cuti/Lembur → HR Manager.
     */
    protected function resolveL2Approver(Model $approvable): ?Employee
    {
        if ($approvable instanceof Reimbursement) {
            return User::role('finance')->first()?->employee;
        }

        return User::role('hr-manager')->first()?->employee;
    }
}
