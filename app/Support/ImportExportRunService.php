<?php

namespace App\Support;

use App\Jobs\ProcessActivityLogExportRun;
use App\Jobs\ProcessAttendanceExportRun;
use App\Jobs\ProcessAttendanceImportRun;
use App\Jobs\ProcessAttendanceReportExportRun;
use App\Jobs\ProcessUserExportRun;
use App\Jobs\ProcessUserImportRun;
use App\Models\ImportExportRun;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class ImportExportRunService
{
    public function queueUsersExport(User $user, array $groups): ImportExportRun
    {
        $run = ImportExportRun::create([
            'resource' => 'users',
            'operation' => 'export',
            'status' => 'queued',
            'requested_by_user_id' => $user->id,
            'meta' => ['groups' => $groups],
        ]);

        ProcessUserExportRun::dispatch($run->id);

        return $run;
    }

    public function queueUsersImport(User $user, UploadedFile $file): ImportExportRun
    {
        $path = $file->store('imports');

        $run = ImportExportRun::create([
            'resource' => 'users',
            'operation' => 'import',
            'status' => 'queued',
            'requested_by_user_id' => $user->id,
            'source_path' => $path,
            'source_name' => $file->getClientOriginalName(),
        ]);

        ProcessUserImportRun::dispatch($run->id);

        return $run;
    }

    public function queueAttendanceExport(User $user, array $data): ImportExportRun
    {
        $run = ImportExportRun::create([
            'resource' => 'attendances',
            'operation' => 'export',
            'status' => 'queued',
            'requested_by_user_id' => $user->id,
            'meta' => $data,
        ]);

        ProcessAttendanceExportRun::dispatch($run->id);

        return $run;
    }

    public function queueAttendanceImport(User $user, UploadedFile $file): ImportExportRun
    {
        $path = $file->store('imports');

        $run = ImportExportRun::create([
            'resource' => 'attendances',
            'operation' => 'import',
            'status' => 'queued',
            'requested_by_user_id' => $user->id,
            'source_path' => $path,
            'source_name' => $file->getClientOriginalName(),
        ]);

        ProcessAttendanceImportRun::dispatch($run->id);

        return $run;
    }

    public function queueActivityLogExport(User $user, array $validated): ImportExportRun
    {
        $run = ImportExportRun::create([
            'resource' => 'activity_logs',
            'operation' => 'export',
            'status' => 'queued',
            'requested_by_user_id' => $user->id,
            'meta' => $validated,
        ]);

        ProcessActivityLogExportRun::dispatch($run->id);

        return $run;
    }

    public function queueMonthlyAttendanceReport(User $user, int $year, int $month): ImportExportRun
    {
        $run = ImportExportRun::create([
            'resource' => 'attendance_report',
            'operation' => 'export',
            'status' => 'queued',
            'requested_by_user_id' => $user->id,
            'meta' => ['year' => $year, 'month' => $month],
        ]);

        ProcessAttendanceReportExportRun::dispatch($run->id);

        return $run;
    }
}
