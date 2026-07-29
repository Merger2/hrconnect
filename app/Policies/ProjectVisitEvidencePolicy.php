<?php

namespace App\Policies;

use App\Models\ProjectVisitEvidence;
use App\Models\User;

class ProjectVisitEvidencePolicy
{
    public function before(User $user): ?bool
    {
        return $user->isSuperadmin ? true : null;
    }

    public function view(User $user, ProjectVisitEvidence $evidence): bool
    {
        if (! $this->sameCompany($user, $evidence)) {
            return false;
        }

        $evidence->loadMissing('project', 'task');

        return $user->id === $evidence->user_id
            || $user->can('viewOperationsWorkspace')
            || $user->can('manageOperationsWorkspace')
            || $evidence->project?->manager_id === $user->id
            || $evidence->task?->assigned_to === $user->id;
    }

    public function downloadPhoto(User $user, ProjectVisitEvidence $evidence): bool
    {
        return $evidence->photo_path !== null && $this->view($user, $evidence);
    }

    private function sameCompany(User $actor, ProjectVisitEvidence $evidence): bool
    {
        return $actor->isSuperadmin
            || $actor->company_id === null
            || (int) $actor->company_id === (int) $evidence->company_id;
    }
}
