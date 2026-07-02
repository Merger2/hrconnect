<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class NavigationService
{
    /**
     * Build the sidebar menu for the given user.
     *
     * Pattern adapted from:
     * - laravel-smarthr: config/menu.php + MenuService pipeline
     * - HRMS: role-scoped menu sections
     * - PasPapan: @can permission gates (no @role)
     * - PRD §5: exact navigation structure per role
     * - PRD §5 line 244: "Gunakan @can, BUKAN @role"
     *
     * @return array<int, array{title: string, items: array<int, array>}>
     */
    public function build(Authenticatable $user): array
    {
        if (! $user instanceof User) {
            return [];
        }

        $role = $user->roles->first()?->name;
        $groups = config('navigation.groups', []);
        $menu = [];

        foreach ($groups as $group) {
            if (! in_array($role, $group['roles'] ?? [], true)) {
                continue;
            }

            $items = [];
            foreach ($group['items'] ?? [] as $item) {
                if (! $this->authorized($user, $item['can'] ?? null)) {
                    continue;
                }

                $label = $item['label_for'][$role] ?? $item['label'];

                $items[] = [
                    'label' => $label,
                    'route' => $item['route'],
                    'icon' => $item['icon'],
                    'active_pattern' => $item['active_pattern'] ?? null,
                ];
            }

            if (! empty($items)) {
                $menu[] = [
                    'title' => $group['title'],
                    'items' => $items,
                ];
            }
        }

        return $menu;
    }

    /**
     * Check if the user is authorized via Gate.
     *
     * Format: 'ability' or 'ability,ModelClass' (for policy-based checks).
     */
    private function authorized(Authenticatable $user, ?string $can): bool
    {
        if ($can === null) {
            return true;
        }

        $parts = explode(',', $can);
        $ability = trim($parts[0]);
        $args = isset($parts[1]) ? [app(trim($parts[1]))] : [];

        return $user->can($ability, $args);
    }
}
