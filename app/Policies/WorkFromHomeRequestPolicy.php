<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkFromHomeRequest;
use App\Support\ApprovalActorService;
use App\Support\MultiCompanyService;

class WorkFromHomeRequestPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isSuperadmin ? true : null;
    }

    public function __construct(
        private readonly MultiCompanyService $multiCompany,
        private readonly ApprovalActorService $approvalActors,
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
            || $this->isSubordinate($user, $request)
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
            && ($this->isSubordinate($user, $request) || $user->can('manageWfhRequests'));
    }

    public function reject(User $user, WorkFromHomeRequest $request): bool
    {
        return $this->approve($user, $request);
    }

    /**
     * Manager/subordinate check vía hierarki organisasi ATAU users.manager_id
     * eksplisit — konsisten dgn ApprovalActorService::subordinateIds (dipakai
     * daftar pending TeamApprovalQueryService). Sebelumnya hanya membaca
     * users.manager_id → manager TIDAK bisa approve WFH bawahan yg relasinya
     * via employees.parent_id/manager_id (seed/hierarki legacy): aksi approve
     * melempar AuthorizationException (403) padahal card tampil di daftar.
     */
    private function isSubordinate(User $actor, WorkFromHomeRequest $request): bool
    {
        return $this->approvalActors->subordinateIds($actor)->contains($request->user_id);
    }

    private function sameCompany(User $actor, WorkFromHomeRequest $request): bool
    {
        $request->loadMissing('user');

        return $request->user !== null
            && $this->multiCompany->canAccessUser($actor, $request->user);
    }
}
