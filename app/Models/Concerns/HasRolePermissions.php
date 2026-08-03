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

        // Legacy alias: enum-style key `view_assets` is also granted by the
        // old `admin.assets.view` (module.action) convention used in many tests.
        if ($this->legacyAdminPermissionKey($permission) !== null
            && in_array($this->legacyAdminPermissionKey($permission), $permissions, true)) {
            return true;
        }

        return false;
    }

    /**
     * Map an enum-style permission key (`view_assets`, `manage_companies`) to
     * the legacy `admin.{module}.{action}` convention, or null when no mapping
     * applies.
     */
    protected function legacyAdminPermissionKey(string $permission): ?string
    {
        $parts = explode('_', $permission);

        if (count($parts) < 2) {
            return null;
        }

        $action = array_shift($parts);
        $module = implode('_', $parts);

        // `view_admin_dashboard` → module `dashboard` (strip the leading admin)
        if (str_starts_with($module, 'admin_')) {
            $module = substr($module, 6);
        }

        return 'admin.'.$module.'.'.$action;
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
            $permissionKeys = $this->rolePermissionKeys();

            if ($permissionKeys === []) {
                // Assigned roles carry no effective permissions (e.g. stale role
                // records) — behave like a roleless admin and use the legacy
                // fallback so the dashboard remains reachable.
                return $legacyFallback && $this->hasLegacyAdminPermission($permissions);
            }

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
            // Legacy fallback grants READ-ONLY admin access only (view/export/
            // download), never management or approval abilities, so roleless
            // admins cannot silently reach management UIs. This matches the
            // strict-RBAC tests (e.g. AdminCompanyManagerTest) and closes the
            // security hole where a roleless admin received every permission.
            $legacyPermissions = array_values(array_filter(
                array_map(fn (Permission $case) => $case->value, Permission::cases()),
                static fn (string $value): bool => (
                    str_starts_with($value, 'view_')
                    || str_starts_with($value, 'export_')
                    || $value === 'download_payslip'
                )
                    // Payroll/payslip sangat sensitif — roleless admin tidak
                    // otomatis berhak melihat data gaji orang lain.
                    && ! str_contains($value, 'payroll')
                    && ! str_contains($value, 'payslip'),
            ));
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
