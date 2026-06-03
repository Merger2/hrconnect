<?php

namespace App\Services;

use App\Enums\ReimbursementStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Reimbursement;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ReimbursementService
{
    public function __construct(
        protected ApprovalService $approvalService,
    ) {}

    /**
     * Buat pengajuan reimbursement baru + rantai approval.
     */
    public function createReimbursement(Employee $employee, array $data): Reimbursement
    {
        $attachmentPath = null;

        if (isset($data['attachment']) && $data['attachment'] instanceof UploadedFile) {
            $attachmentPath = $data['attachment']->store('reimbursements', 'public');
        }

        return DB::transaction(function () use ($employee, $data, $attachmentPath) {
            $reimbursement = Reimbursement::create([
                'employee_id' => $employee->id,
                'category_id' => $data['category_id'] ?? null,
                'payroll_id' => null,
                'title' => $data['title'],
                'expense_date' => $data['expense_date'],
                'amount' => $data['amount'],
                'description' => $data['description'] ?? null,
                'attachment_path' => $attachmentPath,
                'status' => ReimbursementStatus::PENDING,
            ]);

            $this->approvalService->createApprovalWorkflow($reimbursement);

            return $reimbursement;
        });
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
     */
    public function linkToPayroll(Reimbursement $reimbursement, int $payrollId): void
    {
        if (! $reimbursement->isApproved()) {
            throw new BusinessRuleException('Hanya reimbursement yang sudah disetujui penuh yang dapat dimasukkan ke payroll.');
        }

        if ($reimbursement->payroll_id !== null) {
            throw new BusinessRuleException('Reimbursement sudah terhubung ke payroll lain.');
        }

        $payroll = Payroll::findOrFail($payrollId);
        if ($payroll->isLocked()) {
            throw new BusinessRuleException('Payroll sudah dikunci dan tidak dapat diubah.');
        }

        $reimbursement->update([
            'payroll_id' => $payrollId,
            'status' => ReimbursementStatus::PAID,
        ]);
    }
}
