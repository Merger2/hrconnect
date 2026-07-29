<?php

namespace App\Actions\Reimbursement;

use App\Models\Reimbursement;
use App\Models\User;
use App\Support\ReimbursementApprovalService;
use Illuminate\Support\Facades\Gate;

class ReviewReimbursement
{
    public function __construct(
        private readonly ReimbursementApprovalService $approvals,
    ) {}

    public function approve(int|string $id, User $actor): string
    {
        $reimbursement = Reimbursement::query()->findOrFail($id);
        Gate::forUser($actor)->authorize('approve', $reimbursement);

        return $this->approvals->approve($reimbursement, $actor);
    }

    public function reject(int|string $id, User $actor): string
    {
        $reimbursement = Reimbursement::query()->findOrFail($id);
        Gate::forUser($actor)->authorize('reject', $reimbursement);

        return $this->approvals->reject($reimbursement, $actor);
    }
}
