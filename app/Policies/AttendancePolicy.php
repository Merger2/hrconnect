<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;
use App\Support\MultiCompanyService;

class AttendancePolicy
{
    public function before(User $user): ?bool
    {
        return $user->isSuperadmin ? true : null;
    }

    public function __construct(
        private readonly MultiCompanyService $multiCompany,
    ) {}

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function viewAdminAny(User $user): bool
    {
        return $user->can('view_attendances');
    }

    public function view(User $user, Attendance $attendance): bool
    {
        if (! $this->sameCompany($user, $attendance)) {
            return false;
        }

        return $attendance->employee?->user_id === $user->id
            || $user->can('view_attendances')
            || $this->canReview($user, $attendance);
    }

    public function create(User $user): bool
    {
        return $user->isUser;
    }

    public function approve(User $user, Attendance $attendance): bool
    {
        return $this->canReview($user, $attendance);
    }

    public function reject(User $user, Attendance $attendance): bool
    {
        return $this->approve($user, $attendance);
    }

    protected function canReview(User $user, Attendance $attendance): bool
    {
        if (! $this->sameCompany($user, $attendance)) {
            return false;
        }

        if (! in_array($attendance->status->value, Attendance::REQUEST_STATUSES, true)) {
            return false;
        }

        if ($user->can('manageLeaveApprovals')) {
            return true;
        }

        return $user->employee?->subordinates->contains('id', $attendance->employee_id);
    }

    protected function sameCompany(User $actor, Attendance $attendance): bool
    {
        $attendance->loadMissing('employee.user');

        return $attendance->employee?->user !== null
            && $this->multiCompany->canAccessUser($actor, $attendance->employee->user);
    }
}
