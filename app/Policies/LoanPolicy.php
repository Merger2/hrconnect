<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\LoanStatus;
use App\Models\Loan;
use App\Models\User;

class LoanPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isSuperadmin ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isUser || $user->isAdmin || $user->can('manageLoans');
    }

    public function view(User $user, Loan $loan): bool
    {
        return $this->isOwnedBy($user, $loan) || $user->isAdmin || $user->can('manageLoans');
    }

    public function create(User $user): bool
    {
        return $user->isUser;
    }

    public function update(User $user, Loan $loan): bool
    {
        return $this->canAlter($user, $loan);
    }

    public function delete(User $user, Loan $loan): bool
    {
        return $this->canAlter($user, $loan);
    }

    /**
     * Owner dapat mengubah/membatalkan pinjaman yang masih PENDING/APPROVED;
     * admin dengan manageLoans dapat mengelola semua.
     */
    protected function canAlter(User $user, Loan $loan): bool
    {
        if ($user->can('manageLoans')) {
            return true;
        }

        return $this->isOwnedBy($user, $loan)
            && in_array($loan->status, [LoanStatus::PENDING, LoanStatus::APPROVED], true);
    }

    protected function isOwnedBy(User $user, Loan $loan): bool
    {
        return $loan->employee?->user_id === $user->id;
    }
}
