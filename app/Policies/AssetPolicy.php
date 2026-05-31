<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Asset;
use App\Models\User;

/**
 * AssetPolicy — authorization untuk Asset model.
 *
 * Catatan: Asset Management didefer ke V2 (lihat AGENTS.md "Deferred to V2"),
 * tapi policy tetap dibuat sebagai placeholder supaya RBAC siap saat fitur diaktifkan.
 *
 * Matrix:
 * - HR Manager / Super Admin → manage (CRUD + handover)
 * - Employee → view (terbatas: asset yang di-handover ke dirinya, via Handover relation)
 *
 * Asset tidak punya employee_id langsung; relasi via AssetHandover.
 * Cek ownership employee di Livewire query scope, bukan di policy ini.
 */
class AssetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::VIEW_ASSETS->value);
    }

    public function view(User $user, Asset $asset): bool
    {
        // HR / Super Admin → all
        if ($user->can(Permission::MANAGE_ASSETS->value)) {
            return true;
        }

        // Employee → kalau permission view_assets dan asset di-handover ke dia
        // (cek di-handle di query scope Livewire).
        return $user->can(Permission::VIEW_ASSETS->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::MANAGE_ASSETS->value);
    }

    public function update(User $user, Asset $asset): bool
    {
        return $user->can(Permission::MANAGE_ASSETS->value);
    }

    public function delete(User $user, Asset $asset): bool
    {
        return $user->can(Permission::MANAGE_ASSETS->value);
    }

    public function forceDelete(User $user, Asset $asset): bool
    {
        return $user->hasRole('super-admin');
    }
}
