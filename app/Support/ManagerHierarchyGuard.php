<?php

namespace App\Support;

use App\Models\User;

class ManagerHierarchyGuard
{
    public function wouldCreateCycle(User $manager, User $employee): bool
    {
        if ($manager->id === $employee->id) {
            return true;
        }

        $visited = [$employee->id];
        $current = $employee;

        while ($current->manager_id && ! in_array($current->manager_id, $visited, true)) {
            if ($current->manager_id === $manager->id) {
                return true;
            }

            $visited[] = $current->manager_id;
            $next = User::find($current->manager_id);

            if (! $next) {
                break;
            }

            $current = $next;
        }

        return false;
    }
}
