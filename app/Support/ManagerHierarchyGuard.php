<?php

namespace App\Support;

use App\Models\User;

class ManagerHierarchyGuard
{
    /**
     * Detect whether assigning $candidateManager as the direct manager of
     * $employee would close a reporting loop. A loop is created when the
     * candidate manager is already a descendant of the employee (the
     * candidate's own reporting chain leads back to the employee).
     */
    public function wouldCreateCycle(User $employee, User $candidateManager): bool
    {
        if ($employee->id === $candidateManager->id) {
            return true;
        }

        $visited = [$candidateManager->id];
        $current = $candidateManager;

        while ($current->manager_id && ! in_array($current->manager_id, $visited, true)) {
            if ($current->manager_id === $employee->id) {
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
