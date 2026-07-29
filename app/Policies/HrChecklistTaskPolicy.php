<?php

namespace App\Policies;

use App\Models\HrChecklistTask;
use App\Models\User;
use App\Support\MultiCompanyService;

class HrChecklistTaskPolicy
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
        return true;
    }

    public function view(User $user, HrChecklistTask $task): bool
    {
        if (! $this->sameCompany($user, $task)) {
            return false;
        }

        return $user->can('viewHrChecklists')
            || $task->assigned_to === $user->id
            || $task->case?->user_id === $user->id
            || $task->case?->user?->manager_id === $user->id;
    }

    public function update(User $user, HrChecklistTask $task): bool
    {
        return $this->sameCompany($user, $task)
            && ($user->can('manageHrChecklists') || $task->assigned_to === $user->id);
    }

    public function downloadAttachment(User $user, HrChecklistTask $task): bool
    {
        return $task->attachment_path !== null && $this->view($user, $task);
    }

    protected function sameCompany(User $actor, HrChecklistTask $task): bool
    {
        $task->loadMissing('case.user');
        $employee = $task->case?->user;

        return $employee !== null
            && $this->multiCompany->canAccessUser($actor, $employee);
    }
}
