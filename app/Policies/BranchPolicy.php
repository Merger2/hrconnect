<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

/**
 * BranchPolicy — master data branch (read-only endpoint API).
 *
 * Latar belakang (K6 2026-09-06): controller memakai authorize('viewAny')
 * tapi policy tidak ada → semua non-superadmin 403 di /api/v1/branches,
 * padahal route gate `view_branches` sudah benar. Policy ini menyamakan
 * layer policy dengan gate route (defense in depth, bukan double gate baru).
 *
 * Mutasi (create/update/delete) tetap lewat string gate `manage_branches`
 * langsung di controller — tidak lewat policy ini.
 */
class BranchPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isSuperadmin ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view_branches');
    }

    public function view(User $user, Branch $branch): bool
    {
        return $this->viewAny($user);
    }
}
