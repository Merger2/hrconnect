<?php

namespace App\Policies;

use App\Models\Position;
use App\Models\User;

/**
 * PositionPolicy — master data jabatan (read-only endpoint API).
 *
 * Latar belakang (K6 2026-09-06): controller memakai authorize('viewAny')
 * tapi policy tidak ada → semua non-superadmin 403 di /api/v1/positions,
 * padahal route gate `view_positions` sudah benar. Policy ini menyamakan
 * layer policy dengan gate route (defense in depth, bukan double gate baru).
 */
class PositionPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isSuperadmin ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view_positions');
    }

    public function view(User $user, Position $position): bool
    {
        return $this->viewAny($user);
    }
}
