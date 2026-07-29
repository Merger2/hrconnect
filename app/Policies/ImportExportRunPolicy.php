<?php

namespace App\Policies;

use App\Models\ImportExportRun;
use App\Models\User;
use App\Support\MultiCompanyService;

class ImportExportRunPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isSuperadmin ? true : null;
    }

    public function __construct(
        private readonly MultiCompanyService $multiCompany,
    ) {}

    public function download(User $user, ImportExportRun $run): bool
    {
        if (! $user->can('accessAdminPanel')) {
            return false;
        }

        if (! $this->sameCompany($user, $run)) {
            return false;
        }

        if ($run->requested_by_user_id && $user->id !== $run->requested_by_user_id && ! $this->canDownloadSharedRun($user, $run)) {
            return false;
        }

        return $this->canDownloadSharedRun($user, $run);
    }

    private function canDownloadSharedRun(User $user, ImportExportRun $run): bool
    {
        return match ($run->resource) {
            'users' => $run->operation === 'import'
                ? $user->can('importUsers')
                : $user->can('exportUsers'),
            'attendances' => $run->operation === 'import'
                ? $user->can('importAttendances')
                : $user->can('exportAttendances'),
            'activity_logs' => $user->can('exportActivityLogs'),
            'monthly_report_pdf' => $user->can('exportAdminReports'),
            'attendance_report' => $user->can('viewAttendanceReports'),
            default => false,
        };
    }

    private function sameCompany(User $user, ImportExportRun $run): bool
    {
        if (! $run->requested_by_user_id) {
            return true;
        }

        $run->loadMissing('requestedBy');

        return $run->requestedBy !== null
            && $this->multiCompany->canAccessUser($user, $run->requestedBy);
    }
}
