<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Asset;
use App\Models\User;

class AssetPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isSuperadmin ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view_assets');
    }

    public function view(User $user, Asset $asset): bool
    {
        return $user->can('view_assets');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_assets');
    }

    public function update(User $user, Asset $asset): bool
    {
        return $user->can('manage_assets');
    }

    public function delete(User $user, Asset $asset): bool
    {
        return $user->can('manage_assets');
    }
}
