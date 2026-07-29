<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ApprovalActorService
{
    /**
     * @return Collection<int, string>
     */
    public function subordinateIds(User $user): Collection
    {
        $explicitReportIds = User::query()
            ->where('manager_id', $user->id)
            ->when($user->company_id !== null, fn (Builder $query) => $query->where('company_id', $user->company_id))
            ->pluck('id');

        if (! $this->canManageDivisionSubordinates($user) || ! $user->division_id || ! $user->jobTitle?->jobLevel) {
            return $explicitReportIds->unique()->values();
        }

        $rank = (int) $user->jobTitle->jobLevel->rank;

        $divisionReportIds = User::query()
            ->where('id', '!=', $user->id)
            ->where('division_id', $user->division_id)
            ->whereNull('manager_id')
            ->when($user->company_id !== null, fn (Builder $query) => $query->where('company_id', $user->company_id))
            ->whereHas('jobTitle.jobLevel', fn (Builder $query) => $query->where('rank', '>', $rank))
            ->pluck('id');

        return $explicitReportIds
            ->merge($divisionReportIds)
            ->unique()
            ->values();
    }

    public function hasSubordinates(User $user): bool
    {
        return $this->subordinateIds($user)->isNotEmpty();
    }

    public function canFinalizeReimbursementApproval(User $user): bool
    {
        return $user->allowsAdminPermission('admin.reimbursements.approve')
            || $this->isFinanceHead($user);
    }

    public function canFinalizeCashAdvanceApproval(User $user): bool
    {
        return $user->can('manageCashAdvances')
            || $this->isFinanceHead($user);
    }

    public function isFinanceHead(User $user): bool
    {
        return (int) ($user->jobTitle?->jobLevel?->rank ?? 99) <= 2
            && strtolower((string) $user->division?->name) === 'finance';
    }

    public function canManageDivisionSubordinates(User $user): bool
    {
        return (int) ($user->jobTitle?->jobLevel?->rank ?? 99) <= 2;
    }
}
