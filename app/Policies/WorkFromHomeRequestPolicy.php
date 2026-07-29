<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkFromHomeRequest;
use App\Support\MultiCompanyService;

class WorkFromHomeRequestPolicy
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
        return $user->employee !== null;
    }

    public function view(User $user, WorkFromHomeRequest $request): bool
    {
        if (! $this->sameCompany($user, $request)) {
            return false;
        }

        return $request->user_id === $user->id
            || $request->user?->manager_id === $user->id
            || $user->can('manageWfhRequests');
    }

    public function create(User $user): bool
    {
        return $user->employee !== null;
    }

    public function approve(User $user, WorkFromHomeRequest $request): bool
    {
        return $request->status === WorkFromHomeRequest::STATUS_PENDING
            && $this->sameCompany($user, $request)
            && ($request->user?->manager_id === $user->id || $user->can('manageWfhRequests'));
    }

    public function reject(User $user, WorkFromHomeRequest $request): bool
    {
        return $this->approve($user, $request);
    }

    private function sameCompany(User $actor, WorkFromHomeRequest $request): bool
    {
        $request->loadMissing('user');

        return $request->user !== null
            && $this->multiCompany->canAccessUser($actor, $request->user);
    }
}
