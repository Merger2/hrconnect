<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Position;
use App\Models\User;

class PositionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::VIEW_POSITIONS->value);
    }

    public function view(User $user, Position $position): bool
    {
        return $user->can(Permission::VIEW_POSITIONS->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::MANAGE_POSITIONS->value);
    }

    public function update(User $user, Position $position): bool
    {
        return $user->can(Permission::MANAGE_POSITIONS->value);
    }

    public function delete(User $user, Position $position): bool
    {
        return $user->can(Permission::MANAGE_POSITIONS->value);
    }
}
