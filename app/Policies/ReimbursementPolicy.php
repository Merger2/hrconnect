<?php

namespace App\Policies;

use App\Models\Reimbursement;
use App\Models\User;
use App\Support\ApprovalMatrixService;
use App\Support\MultiCompanyService;

class ReimbursementPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isSuperadmin ? true : null;
    }

    public function __construct(
        private readonly ApprovalMatrixService $approvalMatrix,
        private readonly MultiCompanyService $multiCompany,
    ) {}

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function viewAdminAny(User $user): bool
    {
        return $user->can('view_reimbursements');
    }

    public function view(User $user, Reimbursement $reimbursement): bool
    {
        if (! $this->sameCompany($user, $reimbursement)) {
            return false;
        }

        return $user->can('view_reimbursements')
            || $reimbursement->employee?->user_id === $user->id
            || $this->canReview($user, $reimbursement);
    }

    public function create(User $user): bool
    {
        return $user->isUser;
    }

    public function approve(User $user, Reimbursement $reimbursement): bool
    {
        if (! $this->sameCompany($user, $reimbursement)) {
            return false;
        }

        return $user->allowsAdminPermission('admin.reimbursements.approve')
            || $this->canReview($user, $reimbursement);
    }

    public function reject(User $user, Reimbursement $reimbursement): bool
    {
        return $this->approve($user, $reimbursement);
    }

    private function canReview(User $user, Reimbursement $reimbursement): bool
    {
        if (! $this->sameCompany($user, $reimbursement)) {
            return false;
        }

        if ($this->approvalMatrix->canActorApprove($user, 'reimbursement', $reimbursement)) {
            return true;
        }

        if ($user->employee?->subordinates->contains('id', $reimbursement->employee_id)) {
            return true;
        }

        return $this->isFinanceHead($user) && $reimbursement->status === 'pending_finance';
    }

    private function isFinanceHead(User $user): bool
    {
        $employee = $user->employee;

        if ($employee === null) {
            return false;
        }

        $position = $employee->position;

        if ($position === null) {
            return false;
        }

        $rank = $position->jobLevel?->rank ?? 99;
        $divisionName = $position->division?->name ?? '';

        return (int) $rank <= 2 && strtolower((string) $divisionName) === 'finance';
    }

    private function sameCompany(User $actor, Reimbursement $reimbursement): bool
    {
        $reimbursement->loadMissing('employee.user');

        return $reimbursement->employee?->user !== null
            && $this->multiCompany->canAccessUser($actor, $reimbursement->employee->user);
    }
}
