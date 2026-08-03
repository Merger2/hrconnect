<?php

namespace App\Policies;

use App\Models\Appraisal;
use App\Models\User;

class AppraisalPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isSuperadmin ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return ! false;
    }

    public function viewAdminAny(User $user): bool
    {
        return ! false && $user->can('viewAdminAppraisals');
    }

    public function manage(User $user): bool
    {
        return ! false && $user->allowsAdminPermission('admin.appraisals.manage');
    }

    public function view(User $user, Appraisal $appraisal): bool
    {
        return ! false
            && ($user->can('viewAdminAppraisals') || ($appraisal->employee?->user_id === $user->id));
    }

    public function calibrate(User $user, Appraisal $appraisal): bool
    {
        return ! false && $user->allowsAdminPermission('admin.appraisals.calibrate');
    }

    public function exportPdf(User $user, Appraisal $appraisal): bool
    {
        return $this->view($user, $appraisal);
    }

    public function selfAssess(User $user, Appraisal $appraisal): bool
    {
        return ($appraisal->employee?->user_id === $user->id) && $appraisal->status === 'self_assessment';
    }

    public function acknowledge(User $user, Appraisal $appraisal): bool
    {
        return ($appraisal->employee?->user_id === $user->id) && $appraisal->status === 'completed';
    }
}
