<?php

namespace App\Models\Concerns;

use App\Enums\Permission;
use App\Models\Role;
use App\Support\RbacRegistry;

/**
 * Role/permission resolution for the User model: role lookup, permission
 * wildcard matching, admin-permission gating, and the legacy admin fallback.
 */
trait HasRolePermissions
{
    public function hasAssignedRoles(): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->isNotEmpty();
        }

        return $this->roles()->exists();
    }

    public function rolePermissionKeys(): array
    {
        if ($this->isSuperadmin) {
            return ['*'];
        }

        $this->loadMissing('roles');

        if ($this->roles->contains(fn (Role $role) => ! array_key_exists('permission_keys', $role->getAttributes())
            || ! array_key_exists('is_super_admin', $role->getAttributes()))) {
            $this->unsetRelation('roles');
            $this->load('roles');
        }

        if ($this->roles->contains(fn (Role $role) => $role->is_super_admin)) {
            return ['*'];
        }

        return $this->roles
            ->flatMap(fn (Role $role) => $role->permission_keys ?? [])
            ->filter(fn ($permission) => is_string($permission) && $permission !== '')
            ->unique()
            ->values()
            ->all();
    }

    public function checkPermissionTo(string $ability, ?string $guard = null): bool
    {
        return $this->hasPermission($ability);
    }

    public function assignRole(string|array ...$roles): static
    {
        $roleModels = collect($roles)
            ->flatten()
            ->map(fn (string $name) => Role::whereName($name)->firstOrFail());

        $this->roles()->syncWithoutDetaching($roleModels->pluck('id'));

        return $this;
    }

    public function hasAnyRole(string|array $roles): bool
    {
        return $this->roles()
            ->whereIn('name', (array) $roles)
            ->exists();
    }

    public function hasRole(string|array $slug): bool
    {
        $this->loadMissing('roles');

        $slugs = (array) $slug;

        return $this->roles->contains(fn (Role $role) => in_array($role->slug, $slugs, true));
    }

    public function hasPermission(string $permission): bool
    {
        $permissions = $this->rolePermissionKeys();

        if (in_array('*', $permissions, true) || in_array($permission, $permissions, true)) {
            return true;
        }

        $segments = explode('.', $permission);

        while (count($segments) > 1) {
            array_pop($segments);

            if (in_array(implode('.', $segments).'.*', $permissions, true)) {
                return true;
            }
        }

        return false;
    }

    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public function allowsAdminPermission(string|array $permissions, bool $legacyFallback = false): bool
    {
        if (! $this->isAdmin) {
            return false;
        }

        if ($this->isSuperadmin) {
            return true;
        }

        $permissions = (array) $permissions;

        if ($this->isDemo) {
            if ($this->hasAssignedRoles()) {
                return $this->hasAnyPermission($permissions);
            }

            // Fallback to readonly if no roles assigned
            $readOnlyPermissions = RbacRegistry::readOnlyPermissionKeys();
            foreach ($permissions as $permission) {
                if (in_array($permission, $readOnlyPermissions, true)) {
                    return true;
                }
            }

            return false;
        }

        if ($this->hasAssignedRoles()) {
            return $this->hasAnyPermission($permissions);
        }

        return $legacyFallback && $this->hasLegacyAdminPermission($permissions);
    }

    public function canAccessAdminPanel(): bool
    {
        return $this->isAdmin;
    }

    public function canViewAdminDashboard(): bool
    {
        return $this->isAdmin;
    }

    private function hasLegacyAdminPermission(string|array $permissions): bool
    {
        if ($this->isSuperadmin) {
            return true;
        }

        if ($this->group !== 'admin') {
            return false;
        }

        $legacyPermissions = RbacRegistry::presets()['admin']['permissions'] ?? null;

        if ($legacyPermissions === null) {
            $legacyPermissions = array_map(fn (Permission $case) => $case->value, Permission::cases());
        }

        foreach ((array) $permissions as $permission) {
            if (in_array($permission, $legacyPermissions, true)) {
                return true;
            }
        }

        return false;
    }

    public function canManageRbac(): bool
    {
        return $this->allowsAdminPermission('admin.rbac.manage');
    }

    public function canAssignRoles(): bool
    {
        return $this->allowsAdminPermission('admin.rbac.assign');
    }

    public function canViewSuperadminAccounts(): bool
    {
        return $this->allowsAdminPermission('admin.admin_accounts.superadmin_view');
    }

    public function canManageSuperadminAccounts(): bool
    {
        return $this->allowsAdminPermission('admin.admin_accounts.superadmin_manage');
    }

    public function canDeleteSuperadminAccounts(): bool
    {
        return $this->allowsAdminPermission('admin.admin_accounts.superadmin_delete');
    }

    public function hasGlobalAdminScope(): bool
    {
        return $this->allowsAdminPermission('admin.scope.global');
    }

    public function scopeRole($query, string $slug)
    {
        return $query->whereHas('roles', fn ($q) => $q->where('slug', $slug));
    }
}
