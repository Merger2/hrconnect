<?php

namespace App\Support;

use App\Models\Role;

class RoleAccessPreviewService
{
    /**
     * @return array<int|string, array<int, array{label:string,actions:list<string>,enterprise:bool}>>
     */
    public function forRoles(iterable $roles): array
    {
        $previews = [];

        foreach ($roles as $role) {
            if ($role instanceof Role) {
                $previews[$role->id] = $this->forRole($role);
            }
        }

        return $previews;
    }

    /**
     * @return array<int, array{label:string,actions:list<string>,enterprise:bool}>
     */
    public function forRole(Role $role): array
    {
        // NOTE: use the custom `permission_keys` JSON column, NOT the Spatie
        // `permissions` relationship (unused pivot) — `$role->permissions` returns
        // an Eloquent Collection which breaks array_filter() below.
        $rolePermissions = $role->grantsFullAdminAccess()
            ? RbacRegistry::permissionKeys()
            : ($role->permission_keys ?? []);

        $rolePermissions = array_values(array_filter($rolePermissions, 'is_string'));
        $modules = [];

        foreach (RbacRegistry::modules() as $module) {
            $actions = collect($module['actions'] ?? [])
                ->filter(fn (array $action): bool => in_array($action['permission'] ?? null, $rolePermissions, true))
                ->map(fn (array $action): string => (string) __($action['label'] ?? 'Action'))
                ->values()
                ->all();

            if ($actions === []) {
                continue;
            }

            $modules[] = [
                'label' => (string) __($module['label'] ?? 'Module'),
                'actions' => $actions,
                'enterprise' => (bool) ($module['enterprise'] ?? false),
            ];
        }

        return $modules;
    }
}
