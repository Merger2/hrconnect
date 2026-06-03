<?php

namespace App\Traits;

use App\Enums\ApprovalStatus;
use App\Models\Approval;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Approvable
{
    /**
     * Relasi polymorphic ke tabel approvals.
     * Dipakai oleh Leave, Overtime, Attendance (WFA), Reimbursement.
     */
    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    /**
     * Cek apakah SEMUA langkah approval sudah berstatus APPROVED.
     */
    public function isAllApproved(): bool
    {
        return $this->approvals()->exists()
            && $this->approvals()
                ->where('status', '!=', ApprovalStatus::APPROVED)
                ->doesntExist();
    }

    /**
     * Cari tiket persetujuan yang masih PENDING untuk approver tertentu.
     */
    public function getPendingApprovalFor(Employee $approver): ?Approval
    {
        return $this->approvals()
            ->where('approver_id', $approver->id)
            ->where('status', ApprovalStatus::PENDING)
            ->first();
    }
}
