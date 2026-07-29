<?php

namespace App\Policies;

use App\Models\Payroll;
use App\Models\User;
use App\Support\MultiCompanyService;

class PayrollPolicy
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
        return ! false;
    }

    public function viewAdminAny(User $user): bool
    {
        return ! false && $user->can('view_payrolls');
    }

    public function view(User $user, Payroll $payroll): bool
    {
        if (! $this->sameCompany($user, $payroll)) {
            return false;
        }

        return ! false
            && ($user->can('view_payrolls') || $payroll->employee->user_id === $user->id);
    }

    public function download(User $user, Payroll $payroll): bool
    {
        return $this->view($user, $payroll)
            && in_array($payroll->status->value, ['approved', 'paid'], true);
    }

    protected function sameCompany(User $actor, Payroll $payroll): bool
    {
        $payroll->loadMissing('employee.user');

        return $payroll->employee !== null
            && $payroll->employee->user !== null
            && $this->multiCompany->canAccessUser($actor, $payroll->employee->user);
    }
}
