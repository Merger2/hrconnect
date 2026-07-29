<?php

declare(strict_types=1);

namespace App\Services\HR;

use App\Enums\ReimbursementStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Reimbursement;
use App\Notifications\ReimbursementRequested;
use App\Notifications\ReimbursementRequestedMail;
use App\Notifications\ReimbursementStatusUpdated;
use App\Support\ApprovalService;
use App\Support\SecureUploadPolicy;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class ReimbursementService
{
    public function __construct(
        protected ApprovalService $approvalService,
    ) {}

    /**
     * Buat pengajuan reimbursement baru + rantai approval.
     *
     * B-27: File upload wrapped in try-catch for storage failures.
     * Upload ke local disk (private), akses via controller.
     */
    public function createReimbursement(Employee $employee, array $data): Reimbursement
    {
        $attachmentPath = null;

        if (isset($data['receipt']) && $data['receipt'] instanceof UploadedFile) {
            $policy = new SecureUploadPolicy;
            $filename = $policy->randomFilename($data['receipt'], 'reimbursement');

            try {
                $attachmentPath = $data['receipt']->storeAs('reimbursements', $filename, 'local');
            } catch (\Throwable $e) {
                throw new BusinessRuleException('Gagal menyimpan lampiran: '.$e->getMessage());
            }
        }

        $reimbursement = DB::transaction(function () use ($employee, $data, $attachmentPath) {
            $reimbursement = Reimbursement::create([
                'employee_id' => $employee->id,
                'category_id' => $data['category_id'] ?? null,
                'payroll_id' => null,
                'title' => $data['title'] ?? mb_substr($data['description'] ?? '', 0, 100),
                'expense_date' => $data['expense_date'],
                'amount' => $data['amount'],
                'description' => $data['description'] ?? null,
                'receipt_file' => $attachmentPath,
                'status' => ReimbursementStatus::PENDING,
            ]);

            $this->approvalService->createApprovalWorkflow($reimbursement);

            return $reimbursement;
        });

        $this->notifyApprovers($reimbursement);

        return $reimbursement;
    }

    /**
     * Setujui reimbursement via ApprovalService.
     */
    public function approve(Reimbursement $reimbursement, Employee $approver, string $notes = ''): void
    {
        $approval = $reimbursement->getPendingApprovalFor($approver);

        if (! $approval) {
            throw new BusinessRuleException('Tiket persetujuan tidak ditemukan atau sudah diproses.');
        }

        $this->approvalService->approve($approval, $notes);
    }

    /**
     * Tolak reimbursement via ApprovalService.
     */
    public function reject(Reimbursement $reimbursement, Employee $approver, string $reason): void
    {
        $approval = $reimbursement->getPendingApprovalFor($approver);

        if (! $approval) {
            throw new BusinessRuleException('Tiket persetujuan tidak ditemukan atau sudah diproses.');
        }

        $this->approvalService->reject($approval, $reason);
    }

    /**
     * Sambungkan reimbursement approved ke payroll.
     *
     * B-8 fix: tambah DB::transaction + lockForUpdate() cegah race condition
     * di mana reimbursement yang sama bisa di-link ke 2 payroll berbeda.
     * Kirim notifikasi ke employee setelah berhasil di-link.
     */
    public function linkToPayroll(Reimbursement $reimbursement, int $payrollId): void
    {
        DB::transaction(function () use ($reimbursement, $payrollId) {
            $locked = Reimbursement::lockForUpdate()->findOrFail($reimbursement->id);

            if (! $locked->isApproved()) {
                throw new BusinessRuleException('Hanya reimbursement yang sudah disetujui penuh yang dapat dimasukkan ke payroll.');
            }

            if ($locked->payroll_id !== null) {
                throw new BusinessRuleException('Reimbursement sudah terhubung ke payroll lain.');
            }

            $payroll = Payroll::lockForUpdate()->findOrFail($payrollId);
            if ($payroll->isLocked()) {
                throw new BusinessRuleException('Payroll sudah dikunci dan tidak dapat diubah.');
            }

            $locked->update([
                'payroll_id' => $payrollId,
                'status' => ReimbursementStatus::PAID,
            ]);

            $this->notifyStatusChange($locked);
        });
    }

    /**
     * Update reimbursement yang masih PENDING.
     * B-12: Guard — hanya PENDING yang bisa diubah.
     */
    public function updateReimbursement(Reimbursement $reimbursement, array $data): Reimbursement
    {
        if ($reimbursement->status !== ReimbursementStatus::PENDING) {
            throw new BusinessRuleException('Hanya reimbursement dengan status PENDING yang dapat diubah.');
        }

        if (isset($data['receipt']) && $data['receipt'] instanceof UploadedFile) {
            if ($reimbursement->receipt_file) {
                try {
                    Storage::disk('local')->delete($reimbursement->receipt_file);
                } catch (\Throwable) {
                    // Ignore delete failure
                }
            }

            $policy = new SecureUploadPolicy;
            $filename = $policy->randomFilename($data['receipt'], 'reimbursement');

            try {
                $data['receipt_file'] = $data['receipt']->storeAs('reimbursements', $filename, 'local');
            } catch (\Throwable $e) {
                throw new BusinessRuleException('Gagal menyimpan lampiran: '.$e->getMessage());
            }

            unset($data['receipt']);
        }

        $reimbursement->update($data);

        return $reimbursement->fresh();
    }

    protected function notifyApprovers(Reimbursement $reimbursement): void
    {
        $approvals = $reimbursement->approvals()->with('approver.user')->get();

        foreach ($approvals as $approval) {
            $user = $approval->approver?->user;

            if ($user) {
                $user->notify(new ReimbursementRequested($reimbursement));
                Notification::send($user, new ReimbursementRequestedMail($reimbursement));
            }
        }
    }

    protected function notifyStatusChange(Reimbursement $reimbursement): void
    {
        $user = $reimbursement->employee?->user;

        if ($user) {
            $user->notify(new ReimbursementStatusUpdated($reimbursement));
        }
    }
}
