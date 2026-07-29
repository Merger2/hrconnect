<?php

namespace App\Policies;

use App\Models\HrChecklistCase;
use App\Models\User;
use App\Support\MultiCompanyService;

class HrChecklistCasePolicy
{
    public function before(User $user): ?bool
    {
        return $user->isSuperadmin ? true : null;
    }

    public function __construct(
        private readonly MultiCompanyService $multiCompany,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->can('viewHrChecklists');
    }

    public function view(User $user, HrChecklistCase $case): bool
    {
        if (! $this->sameCompany($user, $case)) {
            return false;
        }

        return $user->can('viewHrChecklists')
            || $case->user_id === $user->id
            || $case->user?->manager_id === $user->id
            || $case->tasks()->where('assigned_to', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('manageHrChecklists');
    }

    public function update(User $user, HrChecklistCase $case): bool
    {
        return $this->sameCompany($user, $case) && $user->can('manageHrChecklists');
    }

    public function cancel(User $user, HrChecklistCase $case): bool
    {
        return $this->sameCompany($user, $case) && $user->can('manageHrChecklists');
    }

    protected function sameCompany(User $actor, HrChecklistCase $case): bool
    {
        $case->loadMissing('user');

        return $case->user !== null
            && $this->multiCompany->canAccessUser($actor, $case->user);
    }
}
