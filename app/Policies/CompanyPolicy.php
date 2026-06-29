<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::VIEW_COMPANIES->value);
    }

    public function view(User $user, Company $company): bool
    {
        return $user->can(Permission::VIEW_COMPANIES->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::MANAGE_COMPANIES->value);
    }

    public function update(User $user, Company $company): bool
    {
        return $user->can(Permission::MANAGE_COMPANIES->value);
    }

    public function delete(User $user, Company $company): bool
    {
        return $user->hasRole('super-admin');
    }
}
