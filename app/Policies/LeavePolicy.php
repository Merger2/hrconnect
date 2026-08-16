<?php

namespace App\Policies;

use App\Models\Leave;
use App\Models\User;

class LeavePolicy
{
    public function before(User $user): ?bool
    {
        return $user->isSuperadmin ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isUser || $user->can('view_leaves');
    }

    public function view(User $user, Leave $leave): bool
    {
        return $user->can('view_leaves')
            || $user->can('approve_leaves_l1')
            || $user->can('approve_leaves_l2')
            || $leave->employee?->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isUser;
    }

    public function update(User $user, Leave $leave): bool
    {
        return $this->view($user, $leave);
    }

    public function delete(User $user, Leave $leave): bool
    {
        return $this->view($user, $leave);
    }
}
