<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CashAdvance;
use App\Models\User;
use App\Support\CashAdvanceApprovalService;
use App\Support\MultiCompanyService;

class CashAdvancePolicy
{
    public function before(User $user): ?bool
    {
        return $user->isSuperadmin ? true : null;
    }

    public function __construct(
        private readonly CashAdvanceApprovalService $cashAdvanceApprovals,
        private readonly MultiCompanyService $multiCompany,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->isUser;
    }

    public function view(User $user, CashAdvance $cashAdvance): bool
    {
        if (! $this->sameCompany($user, $cashAdvance)) {
            return false;
        }

        return $cashAdvance->user_id === $user->id || $user->can('manageCashAdvances');
    }

    public function create(User $user): bool
    {
        return $user->isUser;
    }

    public function approve(User $user, CashAdvance $cashAdvance): bool
    {
        if (! $this->sameCompany($user, $cashAdvance)) {
            return false;
        }

        return $this->cashAdvanceApprovals->canManage($cashAdvance, $user);
    }

    public function reject(User $user, CashAdvance $cashAdvance): bool
    {
        return $this->approve($user, $cashAdvance);
    }

    public function delete(User $user, CashAdvance $cashAdvance): bool
    {
        if (! $this->sameCompany($user, $cashAdvance)) {
            return false;
        }

        return $user->can('manageCashAdvances');
    }

    protected function sameCompany(User $actor, CashAdvance $cashAdvance): bool
    {
        $cashAdvance->loadMissing('user');

        return $cashAdvance->user !== null
            && $this->multiCompany->canAccessUser($actor, $cashAdvance->user);
    }
}
