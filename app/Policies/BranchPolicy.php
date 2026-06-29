<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::VIEW_BRANCHES->value);
    }

    public function view(User $user, Branch $branch): bool
    {
        return $user->can(Permission::VIEW_BRANCHES->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::MANAGE_BRANCHES->value);
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->can(Permission::MANAGE_BRANCHES->value);
    }

    public function delete(User $user, Branch $branch): bool
    {
        return $user->can(Permission::MANAGE_BRANCHES->value);
    }
}
