<?php

namespace App\Support;

class RbacRegistry
{
    public static function sections(): array
    {
        return config('rbac.sections', []);
    }

    public static function modules(): array
    {
        return config('rbac.modules', []);
    }

    public static function module(string $key): ?array
    {
        return static::modules()[$key] ?? null;
    }

    public static function groupedModules(): array
    {
        $grouped = [];

        foreach (static::modules() as $key => $module) {
            $section = $module['section'] ?? 'system';

            $grouped[$section]['meta'] = static::sections()[$section] ?? [
                'label' => ucfirst(str_replace('_', ' ', $section)),
                'description' => null,
            ];

            // Add enum_permission to each action for UI consistency
            $moduleWithEnum = $module;
            foreach (($module['actions'] ?? []) as $actionKey => $action) {
                $moduleWithEnum['actions'][$actionKey]['enum_permission'] =
                    static::dotNotationToEnum($action['permission'] ?? '');
            }

            $grouped[$section]['modules'][$key] = $moduleWithEnum;
        }

        return $grouped;
    }

    public static function permissionKeys(): array
    {
        static $permissions;

        if ($permissions !== null) {
            return $permissions;
        }

        $permissions = [];

        foreach (static::modules() as $module) {
            foreach (($module['actions'] ?? []) as $action) {
                $permissions[] = $action['permission'];
            }
        }

        return array_values(array_unique($permissions));
    }

    /**
     * Convert a dot-notation permission key (e.g. 'admin.dashboard.view')
     * to its enum-style equivalent (e.g. 'view_dashboard').
     *
     * Returns null when no enum equivalent exists (e.g. 'admin.scope.global').
     */
    public static function dotNotationToEnum(string $dot): ?string
    {
        // Special cases where the mapping is not mechanical
        $specialCases = [
            'admin.rbac.assign' => 'assign_roles',
            'admin.reports.export' => 'export_admin_reports',
            'admin.notifications.manage' => 'manage_admin_notifications',
            'admin.appraisals.view' => 'view_admin_appraisals',
            'admin.scope.global' => null,
        ];

        if (array_key_exists($dot, $specialCases)) {
            return $specialCases[$dot];
        }

        // Mechanical conversion: admin.{module}.{action} → {action}_{module}
        $parts = explode('.', $dot);

        if (count($parts) !== 3 || $parts[0] !== 'admin') {
            return null;
        }

        [$prefix, $module, $action] = $parts;

        return "{$action}_{$module}";
    }

    /**
     * Return all permission keys in enum-style format (snake_case).
     *
     * This bridges the gap between config/rbac.php (dot-notation) and
     * App\Enums\Permission (snake_case) used by the seeder and enforcement layer.
     */
    public static function permissionKeysAsEnum(): array
    {
        static $enumKeys;

        if ($enumKeys !== null) {
            return $enumKeys;
        }

        $enumKeys = [];

        foreach (static::permissionKeys() as $dot) {
            $enum = static::dotNotationToEnum($dot);

            if ($enum !== null) {
                $enumKeys[] = $enum;
            }
        }

        return array_values(array_unique($enumKeys));
    }

    public static function readOnlyPermissionKeys(): array
    {
        $permissions = [];
        $readOnlyActions = [
            'view',
            'report',
            'export',
            'export_leave',
            'export_schedule',
            'export_overtime',
            'export_payroll',
        ];

        foreach (static::modules() as $module) {
            foreach (($module['actions'] ?? []) as $actionKey => $action) {
                if (! in_array($actionKey, $readOnlyActions, true)) {
                    continue;
                }

                if (isset($action['permission'])) {
                    $permissions[] = $action['permission'];
                }
            }
        }

        return array_values(array_unique($permissions));
    }

    public static function modulePermissionKeys(string $moduleKey): array
    {
        $module = static::module($moduleKey);

        if ($module === null) {
            return [];
        }

        return array_values(array_map(
            fn (array $action) => $action['permission'],
            $module['actions'] ?? [],
        ));
    }

    public static function resolveModuleActions(array $moduleActions): array
    {
        if (isset($moduleActions['*']) && in_array('*', (array) $moduleActions['*'], true)) {
            return static::permissionKeys();
        }

        $resolved = [];

        foreach ($moduleActions as $moduleKey => $actions) {
            $module = static::module($moduleKey);

            if ($module === null) {
                continue;
            }

            $availableActions = $module['actions'] ?? [];

            foreach ((array) $actions as $actionName) {
                if ($actionName === '*') {
                    $resolved = [
                        ...$resolved,
                        ...static::modulePermissionKeys($moduleKey),
                    ];

                    continue;
                }

                if (isset($availableActions[$actionName]['permission'])) {
                    $resolved[] = $availableActions[$actionName]['permission'];
                }
            }
        }

        return array_values(array_unique($resolved));
    }

    public static function presets(): array
    {
        static $presets;

        if ($presets !== null) {
            return $presets;
        }

        $presets = [];

        foreach (config('rbac.presets', []) as $slug => $preset) {
            $presets[$slug] = [
                'slug' => $slug,
                'name' => $preset['name'],
                'description' => $preset['description'] ?? null,
                'permissions' => static::resolveModuleActions($preset['permissions'] ?? []),
                'is_system' => (bool) ($preset['is_system'] ?? false),
                'is_super_admin' => (bool) ($preset['is_super_admin'] ?? false),
            ];
        }

        return $presets;
    }

    public static function adminAccessPermissions(): array
    {
        return static::permissionKeys();
    }
}
