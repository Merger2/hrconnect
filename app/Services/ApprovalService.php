<?php

namespace App\Services;

use App\Enums\ApprovalLevel;
use App\Enums\ApprovalStatus;
use App\Enums\RequestStatus;
use App\Models\Approval;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\Reimbursement;
use Carbon\Carbon;
use App\Models\User;
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
            $approvable = $approval->approvable()->lockForUpdate()->first();
            $approval->update([
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
                        ->where('year', Carbon::parse($approvable->start_date)->year)
                        ->lockForUpdate()
                        ->first();
                    $balance?->deduct((float) $approvable->total_days);
                }
            } elseif ($approval->level === ApprovalLevel::L1_SUPERVISOR) {
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
            $approvable = $approval->approvable()->lockForUpdate()->first();

            $approval->update([
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
     * PRD §9.2 + §12.1.
     */
    protected function resolveL2Approver(Model $approvable): ?Employee
    {
        if ($approvable instanceof Reimbursement) {
            return User::role('finance')->first()?->employee;
        }

        return User::role('hr-manager')->first()?->employee;
    }
}
