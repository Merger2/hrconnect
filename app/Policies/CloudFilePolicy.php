<?php

namespace App\Policies;

use App\Models\CloudFile;
use App\Models\User;

class CloudFilePolicy
{
    public function before(User $user): ?bool
    {
        return $user->isSuperadmin ? true : null;
    }

    public function view(User $user, CloudFile $cloudFile): bool
    {
        if (! $this->sameCompany($user, $cloudFile)) {
            return false;
        }

        if ($user->can('viewCollaborationWorkspace')) {
            return true;
        }

        if ((string) $cloudFile->owner_id === (string) $user->id) {
            return true;
        }

        if ($cloudFile->visibility === CloudFile::VISIBILITY_THREAD) {
            return $cloudFile->thread?->members()->whereKey($user->id)->exists() ?? false;
        }

        return $cloudFile->visibility === CloudFile::VISIBILITY_COMPANY && $user->company_id !== null;
    }

    public function download(User $user, CloudFile $cloudFile): bool
    {
        return $this->view($user, $cloudFile);
    }

    private function sameCompany(User $actor, CloudFile $cloudFile): bool
    {
        if ($cloudFile->company_id === null) {
            return $actor->isSuperadmin || $actor->company_id === null;
        }

        return $actor->isSuperadmin
            || $actor->company_id === null
            || (int) $actor->company_id === (int) $cloudFile->company_id;
    }
}
