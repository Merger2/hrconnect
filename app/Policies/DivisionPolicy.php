<?php

namespace App\Policies;

use App\Models\Division;
use App\Models\User;

/**
 * DivisionPolicy — master data divisi (read-only endpoint API).
 *
 * Latar belakang (K6 2026-09-06): controller memakai authorize('viewAny')
 * tapi policy tidak ada → semua non-superadmin 403 di /api/v1/divisions,
 * padahal route gate `view_divisions` sudah benar. Policy ini menyamakan
 * layer policy dengan gate route (defense in depth, bukan double gate baru).
 */
class DivisionPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isSuperadmin ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view_divisions');
    }

    public function view(User $user, Division $division): bool
    {
        return $this->viewAny($user);
    }
}
