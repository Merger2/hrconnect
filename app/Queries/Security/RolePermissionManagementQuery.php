<?php

namespace App\Queries\Security;

use App\Models\Role;
use Illuminate\Support\Collection;

class RolePermissionManagementQuery
{
    /**
     * @return Collection<int, Role>
     */
    public function roles(?string $search = null): Collection
    {
        $term = trim((string) $search);

        return Role::query()
            ->withCount('users')
            ->when($term !== '', function ($query) use ($term) {
                $like = '%'.$term.'%';

                $query->where(function ($subQuery) use ($like) {
                    $subQuery
                        ->where('name', 'like', $like)
                        ->orWhere('slug', 'like', $like)
                        ->orWhere('description', 'like', $like);
                });
            })
            ->get()
            ->sort(fn (Role $left, Role $right) => [
                $left->grantsFullAdminAccess() ? 0 : 1,
                $left->is_system ? 0 : 1,
                $left->name,
            ] <=> [
                $right->grantsFullAdminAccess() ? 0 : 1,
                $right->is_system ? 0 : 1,
                $right->name,
            ])
            ->values();
    }
}
