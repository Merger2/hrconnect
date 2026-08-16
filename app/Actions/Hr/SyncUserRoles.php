<?php

namespace App\Actions\Hr;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class SyncUserRoles
{
    /**
     * Apply the requested role assignment to $subject on behalf of $actor,
     * enforcing assignment authorization, super-admin guards, and the
     * admin/superadmin group synchronization. Returns the resolved selection
     * state for the caller (UserForm) to reflect back into its inputs.
     *
     * @param  list<int|string>  $requestedRoleIds  the raw selected role ids
     * @param  list<int|string>  $fallbackOriginalIds  the caller's known original role ids
     * @return array{role_ids: list<int|string>, original_role_ids: list<int|string>, group: string, changed: bool}
     */
    public function handle(User $subject, ?User $actor, array $requestedRoleIds, array $fallbackOriginalIds): array
    {
        $rawRoleIds = array_values(array_unique($requestedRoleIds));
        $normalizedRoleIds = $this->normalizeRequestedRoleIds($subject, $rawRoleIds);
        $originalRoleIds = $actor?->is($subject)
            ? $subject->roles()->pluck('roles.id')->all()
            : array_values(array_unique($fallbackOriginalIds));
        $usingImplicitDefaultRole = $rawRoleIds === [] && $originalRoleIds === [];

        // Compare both sides as strings: requested ids from the Livewire form
        // are strings, while pluck()/value() return native types (int on pgsql,
        // string on sqlite). Strict === comparison would false-fail otherwise.
        // The returned arrays keep native types so callers (and tests) see the
        // same id types they passed in.
        $normalizedForCompare = array_map('strval', $normalizedRoleIds);
        $originalForCompare = array_map('strval', $originalRoleIds);

        if ($normalizedForCompare === $originalForCompare) {
            return [
                'role_ids' => $rawRoleIds,
                'original_role_ids' => $originalRoleIds,
                'group' => $subject->group,
                'changed' => false,
            ];
        }

        if (! $usingImplicitDefaultRole && ! $actor?->can('assignRoles')) {
            throw new AuthorizationException(__('You do not have permission to assign roles.'));
        }

        if (! $usingImplicitDefaultRole && $actor->is($subject)) {
            throw new AuthorizationException(__('You cannot change your own role assignment.'));
        }

        // Reject non-numeric ids up front: pgsql raises SQLSTATE[22P02] when a
        // bigint whereIn receives non-numeric strings, while sqlite silently
        // returns an empty set. Filtering here keeps the count check below
        // consistent and yields a clean AuthorizationException on both drivers.
        // ctype_digit is stricter than is_numeric (rejects '12.5', '1e3') so no
        // non-integer value can reach the bigint comparison.
        $numericRoleIds = array_values(array_filter(
            $normalizedRoleIds,
            static fn (mixed $id): bool => ctype_digit((string) $id),
        ));

        if (count($numericRoleIds) !== count($normalizedRoleIds)) {
            throw new AuthorizationException(__('One or more selected roles are invalid.'));
        }

        $roles = Role::query()
            ->whereIn('id', $numericRoleIds)
            ->get();

        if ($roles->count() !== count($numericRoleIds)) {
            throw new AuthorizationException(__('One or more selected roles are invalid.'));
        }

        $grantsFullAdminAccess = $roles->contains(fn (Role $role) => $role->grantsFullAdminAccess());

        if ($grantsFullAdminAccess && ! $actor->canManageSuperadminAccounts()) {
            throw new AuthorizationException(__('You do not have permission to assign the Super Admin role.'));
        }

        if ($subject->isSuperadmin && ! $actor->canManageSuperadminAccounts()) {
            throw new AuthorizationException(__('You do not have permission to manage Super Admin accounts.'));
        }

        $subject->roles()->sync($roles->pluck('id')->all());
        $group = $this->synchronizeSubjectGroup($subject, $grantsFullAdminAccess);

        return [
            'role_ids' => $roles->pluck('id')->all(),
            'original_role_ids' => $normalizedRoleIds,
            'group' => $group,
            'changed' => true,
        ];
    }

    /**
     * @param  list<string>  $requestedRoleIds
     * @return list<string>
     */
    private function normalizeRequestedRoleIds(User $subject, array $requestedRoleIds): array
    {
        if ($requestedRoleIds !== [] || ! in_array($subject->group, ['admin', 'superadmin'], true)) {
            return $requestedRoleIds;
        }

        $defaultRoleSlug = $subject->group === 'superadmin' ? 'super-admin' : 'admin';
        $defaultRoleId = Role::query()->where('slug', $defaultRoleSlug)->value('id');

        if ($defaultRoleId === null || $defaultRoleId === '') {
            throw new AuthorizationException(__('The default :group role is missing.', ['group' => $subject->group]));
        }

        // Keep the DB-native value (int on pgsql, string on sqlite) so the strict
        // === comparison in handle() stays consistent with pluck('roles.id').
        return [$defaultRoleId];
    }

    private function synchronizeSubjectGroup(User $subject, bool $grantsFullAdminAccess): string
    {
        if ($subject->group === 'user') {
            return $subject->group;
        }

        $resolvedGroup = $grantsFullAdminAccess ? 'superadmin' : 'admin';

        if ($subject->group === $resolvedGroup) {
            return $subject->group;
        }

        $subject->forceFill(['group' => $resolvedGroup])->save();

        return $resolvedGroup;
    }
}
