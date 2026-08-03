# HRConnect Project Map

Generated: 2026-07-29 22:31:33

## High-Level Snapshot
- Root: /home/merger/hrconnect
- Framework: Laravel 13
- UI: Livewire 4 + Blade + Tailwind
- Route count: 133
- Controllers: 72
- Livewire components: 91
- Services: 27
- Models: 101

## Boot / Entry Files
- bootstrap/app.php
- routes/web.php
- routes/api.php
- routes/web/system.php
- routes/web/files.php
- routes/web/user.php
- routes/web/payroll.php
- routes/web/admin.php
- app/helpers.php

## Documentation / Guidance Files
- docs/ARCHITECTURE.md
- docs/audit-checklist.md
- docs/AUDIT-MAP.md
- docs/FEATURE-INVENTORY.md
- docs/FEATURES.md
- docs/PROGRESS.md
- docs/ROLES-PERMISSIONS.md
- docs/UI-UX-GUIDELINES.md
- AGENTS.md
- CLAUDE.md
- GEMINI.md

## Route Files
- routes/api.php
- routes/channels.php
- routes/console.php
- routes/jetstream.php
- routes/knowledge-base.php
- routes/web/admin/assets.php
- routes/web/admin/attendance.php
- routes/web/admin/dashboard.php
- routes/web/admin/hr-checklists.php
- routes/web/admin/import-export.php
- routes/web/admin/master-data.php
- routes/web/admin/operations.php
- routes/web/admin/payroll.php
- routes/web/admin.php
- routes/web/admin/reports.php
- routes/web/admin/security.php
- routes/web/admin/settings.php
- routes/web/files.php
- routes/web/payroll.php
- routes/web.php
- routes/web/system.php
- routes/web/user.php

## Admin Route Modules
- routes/web/admin/assets.php
- routes/web/admin/attendance.php
- routes/web/admin/dashboard.php
- routes/web/admin/hr-checklists.php
- routes/web/admin/import-export.php
- routes/web/admin/master-data.php
- routes/web/admin/operations.php
- routes/web/admin/payroll.php
- routes/web/admin/reports.php
- routes/web/admin/security.php
- routes/web/admin/settings.php

## Feature Buckets (directory-based heuristic)
- Attendance: app/Services/Attendance, attendance-related controllers/livewire/routes
- Payroll: app/Services/Payroll, routes/web/payroll.php, admin payroll routes/components
- HR Requests: leave, reimbursement, overtime, WFH, shift swap, document requests
- Admin Ops: dashboard, reports, import/export, settings, security, assets
- Knowledge Base / RAG: knowledge-base routes/controllers/services/models
- Security / Face Recognition: app/Services/Security, Api/FaceController, enrollment screens
- Integrations / Device APIs: app/Http/Controllers/Api/Device, Integrations

## Route Snapshot

  GET|HEAD  / .................................. System\RootRedirectController
  GET|HEAD  __auth-debug .......................... System\AuthDebugController
  POST      __e2e-document-upload ......... System\E2eDocumentUploadController
  GET|HEAD  __e2e-login ............................ System\E2eLoginController
  POST      __vercel-migrate .............. System\VercelMaintenanceController
  POST      _boost/browser-logs boost.browser-logs › routes/web/system.php:19
  GET|HEAD  admin .......................... Admin\AdminRootRedirectController
  GET|HEAD  admin/activity-logs/export admin.activity-logs.export › Admin\Imp…
  ANY       admin/activity-logs/viewer admin.activity-logs.viewer › Illuminat…
  GET|HEAD  admin/attendances/export admin.attendances.export › Admin\ImportE…
  POST      admin/attendances/import admin.attendances.import › Admin\ImportE…
  GET|HEAD  admin/attendances/report admin.attendances.report › Admin\Attenda…
  GET|HEAD  admin/collaboration/files/{file}/download admin.collaboration.fil…
  GET|HEAD  admin/commercial ..... admin.commercial › routes/web/system.php:82
  GET|HEAD  admin/document-requests/{documentRequest}/download admin.document…
  GET|HEAD  admin/document-requests/{documentRequest}/uploaded admin.document…
  ANY       admin/document-templates/library admin.document-templates.library…
  GET|HEAD  admin/import-export/runs/{run}/download admin.import-export.runs.…
  ANY       admin/leaves/admin admin.leaves.admin › Illuminate\Routing › Redi…
  GET|HEAD  admin/operational-health admin.operational-health › Admin\Operati…
  GET|HEAD  admin/profile ................................. admin.profile.show
  GET|HEAD  admin/reports admin.reports.index › Admin\Reports\ReportCenterCon…
  GET|HEAD  admin/reports/export-pdf admin.reports.export-pdf › Admin\ImportE…
  GET|HEAD  admin/reports/leaves/export admin.reports.leaves.export › Admin\R…
  GET|HEAD  admin/reports/overtime/export admin.reports.overtime.export › Adm…
  GET|HEAD  admin/reports/payrolls/export admin.reports.payrolls.export › Adm…
  GET|HEAD  admin/reports/schedules/export admin.reports.schedules.export › A…
  GET|HEAD  admin/users/export admin.users.export › Admin\ImportExport\Export…
  POST      admin/users/import admin.users.import › Admin\ImportExport\Import…
  GET|HEAD  api/v1/approvals .................... Api\ApprovalController@index
  PUT       api/v1/approvals/{approval}/approve Api\ApprovalController@approve
  PUT       api/v1/approvals/{approval}/reject . Api\ApprovalController@reject
  GET|HEAD  api/v1/assets .......................... Api\AssetController@index
  POST      api/v1/assets .......................... Api\AssetController@store
  GET|HEAD  api/v1/assets/{asset} ................... Api\AssetController@show
  PUT       api/v1/assets/{asset} ................. Api\AssetController@update
  DELETE    api/v1/assets/{asset} ................ Api\AssetController@destroy
  POST      api/v1/auth/email/verification-notification Api\EmailVerification…
  GET|HEAD  api/v1/auth/email/verify/{id}/{hash} api.verification.verify › Ap…
  GET|HEAD  api/v1/branches ....................... Api\BranchController@index
  GET|HEAD  api/v1/branches/{branch} ............... Api\BranchController@show
  GET|HEAD  api/v1/company/branches . Api\CompanyController@getCompanyBranches
  GET|HEAD  api/v1/company/hours Api\CompanyController@getCompanyOperationalH…
  POST      api/v1/device/location ............. Api\Device\LocationController
  POST      api/v1/device/offline-attendance Api\Device\OfflineAttendanceSync…
  GET|HEAD  api/v1/device/permissions . Api\Device\PermissionsStatusController
  POST      api/v1/device/photo ............. Api\Device\PhotoUploadController
  GET|HEAD  api/v1/divisions .................... Api\DivisionController@index
  GET|HEAD  api/v1/divisions/{division} .......... Api\DivisionController@show
  GET|HEAD  api/v1/employee-terminations Api\EmployeeTerminationController@in…
  POST      api/v1/employee-terminations/process-contract-end Api\EmployeeTer…
  POST      api/v1/employee-terminations/{employee} Api\EmployeeTerminationCo…
  GET|HEAD  api/v1/employees .................... Api\EmployeeController@index
  POST      api/v1/employees .................... Api\EmployeeController@store
  GET|HEAD  api/v1/employees/me .................... Api\EmployeeController@me
  GET|HEAD  api/v1/employees/{employee} .......... Api\EmployeeController@show
  PUT       api/v1/employees/{employee} ........ Api\EmployeeController@update
  DELETE    api/v1/employees/{employee} ....... Api\EmployeeController@destroy
  POST      api/v1/face/register ................. Api\FaceController@register
  POST      api/v1/face/verify ..................... Api\FaceController@verify
  GET|HEAD  api/v1/health ............................... Api\HealthController
  POST      api/v1/integrations/attendance-events Api\Integrations\Attendance…
  GET|HEAD  api/v1/knowledge-base .......... Api\KnowledgeBaseController@index
  POST      api/v1/knowledge-base/chat ...... Api\KnowledgeBaseController@chat
  POST      api/v1/knowledge-base/upload .. Api\KnowledgeBaseController@upload
  GET|HEAD  api/v1/knowledge-base/{knowledgeBase} Api\KnowledgeBaseController…
  DELETE    api/v1/knowledge-base/{knowledgeBase} Api\KnowledgeBaseController…
  GET|HEAD  api/v1/leaves .......................... Api\LeaveController@index
  POST      api/v1/leaves .......................... Api\LeaveController@store
  GET|HEAD  api/v1/leaves/{leave} ................... Api\LeaveController@show
  PUT       api/v1/leaves/{leave} ................. Api\LeaveController@update
  DELETE    api/v1/leaves/{leave} ................ Api\LeaveController@destroy
  GET|HEAD  api/v1/loans ............................ Api\LoanController@index
  POST      api/v1/loans ............................ Api\LoanController@store
  GET|HEAD  api/v1/loans/{loan} ...................... Api\LoanController@show
  PUT       api/v1/loans/{loan} .................... Api\LoanController@update
  DELETE    api/v1/loans/{loan} ................... Api\LoanController@destroy
  POST      api/v1/loans/{loan}/installments Api\LoanController@payInstallment
  GET|HEAD  api/v1/notifications ............ Api\NotificationController@index
