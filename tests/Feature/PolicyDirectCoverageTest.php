<?php

use App\Models\Announcement;
use App\Models\Appraisal;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\CashAdvance;
use App\Models\CloudFile;
use App\Models\CompanyAsset;
use App\Models\EmployeeDocumentRequest;
use App\Models\Holiday;
use App\Models\HrChecklistCase;
use App\Models\HrChecklistTask;
use App\Models\ImportExportRun;
use App\Models\Overtime;
use App\Models\Payroll;
use App\Models\ProjectVisitEvidence;
use App\Models\Reimbursement;
use App\Models\ShiftSwapRequest;
use App\Models\SystemBackupRun;
use App\Models\WorkFromHomeRequest;
use App\Policies\AnnouncementPolicy;
use App\Policies\AppraisalPolicy;
use App\Policies\AttendanceCorrectionPolicy;
use App\Policies\AttendancePolicy;
use App\Policies\CashAdvancePolicy;
use App\Policies\CloudFilePolicy;
use App\Policies\CompanyAssetPolicy;
use App\Policies\EmployeeDocumentRequestPolicy;
use App\Policies\HolidayPolicy;
use App\Policies\HrChecklistCasePolicy;
use App\Policies\HrChecklistTaskPolicy;
use App\Policies\ImportExportRunPolicy;
use App\Policies\OvertimePolicy;
use App\Policies\PayrollPolicy;
use App\Policies\ProjectVisitEvidencePolicy;
use App\Policies\ReimbursementPolicy;
use App\Policies\ShiftSwapRequestPolicy;
use App\Policies\SystemBackupRunPolicy;
use App\Policies\WorkFromHomeRequestPolicy;
use Illuminate\Support\Facades\Gate;

test('registered policy classes resolve through the gate', function () {
    foreach ([
        Announcement::class => AnnouncementPolicy::class,
        Appraisal::class => AppraisalPolicy::class,
        Attendance::class => AttendancePolicy::class,
        AttendanceCorrection::class => AttendanceCorrectionPolicy::class,
        CashAdvance::class => CashAdvancePolicy::class,
        CloudFile::class => CloudFilePolicy::class,
        CompanyAsset::class => CompanyAssetPolicy::class,
        EmployeeDocumentRequest::class => EmployeeDocumentRequestPolicy::class,
        Holiday::class => HolidayPolicy::class,
        HrChecklistCase::class => HrChecklistCasePolicy::class,
        HrChecklistTask::class => HrChecklistTaskPolicy::class,
        ImportExportRun::class => ImportExportRunPolicy::class,
        Overtime::class => OvertimePolicy::class,
        Payroll::class => PayrollPolicy::class,
        ProjectVisitEvidence::class => ProjectVisitEvidencePolicy::class,
        Reimbursement::class => ReimbursementPolicy::class,
        ShiftSwapRequest::class => ShiftSwapRequestPolicy::class,
        SystemBackupRun::class => SystemBackupRunPolicy::class,
        WorkFromHomeRequest::class => WorkFromHomeRequestPolicy::class,
    ] as $model => $policy) {
        expect(Gate::getPolicyFor($model))
            ->toBeInstanceOf($policy);
    }
});
