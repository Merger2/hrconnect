# Sprint & Branch Strategy — HRConnect

> **CATATAN ESTIMASI FILE (2026-06-06):** Estimasi total ~408 file di dokumen ini berbeda dengan scope dokumen lain:
> - `sprint-branch-strategy.md`: ~408 file (granular per-sprint, termasuk sub-modul Livewire)
> - `reference/complete-file-blueprint.md`: ~341 file baru + ~12 modifikasi (audit terhadap struktur ideal)
>
> **Sumber kebenaran**: `planning/task.md` (per-item status ✅/⚠️/❌). Estimasi total file di doc lain adalah perkiraan working — gunakan task.md untuk track progress aktual.
>
> **Status per 2026-06-06:** Sprint 11 ✅ Merged. Sprint 12-15 ✅ Selesai (full). Sprint 16 🟡 In Progress (Face Recognition frontend). Sprint 17-35 📋 Backlog.

## Branching Strategy

```
main (protected)
  │
  ├── develop (integration branch — semua merge ke sini dulu)
  │     │
  │     ├── feat/sprint12-services-core              → ✅ Done (dirangkum ke develop)
  │     ├── feat/sprint13-observers-jobs-commands    → ✅ Done (partial, dirangkum ke develop)
  │     ├── feat/sprint14-seeders-factories          → ✅ Done (partial, dirangkum ke develop)
  │     ├── feat/sprint15-middleware-policies        → ✅ Done (partial, dirangkum ke develop)
  │     ├── feat/sprint16-attendance-livewire        → 🟡 In Progress
  │     ├── feat/sprint17-attendance-history         → PR → develop
  │     ├── feat/sprint18-leave-overtime-livewire    → PR → develop
  │     ├── feat/sprint19-finance-reimbursement      → PR → develop
  │     ├── feat/sprint20-profile-devices            → PR → develop
  │     ├── feat/sprint21-hrd-employees-crud         → PR → develop
  │     ├── feat/sprint22-hrd-approvals-leaves       → PR → develop
  │     ├── feat/sprint23-hrd-approvals-shifts       → PR → develop
  │     ├── feat/sprint24-hrd-terminations-dashboard → PR → develop
  │     ├── feat/sprint25-finance-payroll-gen        → PR → develop
  │     ├── feat/sprint26-finance-reports-adjustment → PR → develop
  │     ├── feat/sprint27-knowledgebase-rag          → PR → develop
  │     ├── feat/sprint28-settings-users             → PR → develop
  │     ├── feat/sprint29-users-activity-log         → PR → develop
  │     ├── feat/sprint30-routes-api-pwa             → PR → develop
  │     ├── feat/sprint31-testing-attendance-leave   → PR → develop
  │     ├── feat/sprint32-testing-payroll-finance    → PR → develop
  │     ├── feat/sprint33-testing-hrd-knowledgebase  → PR → develop
  │     ├── feat/sprint34-lang-translations          → PR → develop
  │     └── feat/sprint35-final-integration          → PR → develop
  │
  └── main ← develop (hanya merge saat siap production)
```

## Aturan Branch

| Aturan | Detail |
|--------|--------|
| **main** | PROTECTED — tidak boleh push langsung |
| **develop** | Integration branch — semua PR masuk ke sini |
| **feat/sprintXX-*** | Branch kerja — buat dari develop, PR ke develop |
| **Naming** | `feat/sprint{number}-{short-description}` |
| **PR Rule** | Minimal 1 review, semua test pass sebelum merge ke develop |
| **Commit Style** | `feat:`, `fix:`, `refactor:`, `docs:`, `test:` |

---

## Sprint 1-11: FOUNDATION (SUDAH SELESAI ✅)

| Sprint | Branch | Status | Yang Dikerjakan |
|--------|--------|--------|-----------------|
| Sprint 1 | `feat/sprint1-foundation-schema` | ✅ Merged | Migrations: companies, branches, departments, positions |
| Sprint 2 | `feat/sprint2-relations` | ✅ Merged | Foreign keys, relationships |
| Sprint 3 | `feat/sprint3-core-users` | ✅ Merged | Users, employees, core models |
| Sprint 4 | `feat/sprint4-attendance-leave` | ✅ Merged | Attendance, leave migrations |
| Sprint 5 | `feat/sprint5-employee-entities` | ✅ Merged | Family, devices, assets, loans, overtime |
| Sprint 6 | `feat/sprint6-approval-payroll-base` | ✅ Merged | Approvals polymorphic, payroll base, loan installments |
| Sprint 7 | `feat/sprint7-payroll-details-logs` | ✅ Merged | Payroll items, activity logs |
| Sprint 8 | `feat/sprint8-address-normalization` | ✅ Merged | Address normalization, enums |
| Sprint 9 | *(tidak ada branch — digabung)* | — | — |
| Sprint 10 | `feat/sprint10-models-relationships` | ✅ Merged | Model relationships updates |
| Sprint 11 | `feat/sprint11-global-seeding-validation` | ✅ Merged | Seeding, validation, documentation |

---

## Sprint 12-35: DEVELOPMENT (SEBAGIAN SUDAH DIKERJAKAN)

### 🔴 FASE: BUSINESS LOGIC (Sprint 12-16)

#### Sprint 12: Services Core ✅
**Status:** Selesai (6/7 services exist; EmployeeTerminationService masih pending)

**Bonus services (di luar rencana awal):** FaceRecognitionService, KnowledgeBaseService, ReimbursementService, GeminiClient, EmbeddingService, PayslipPdfService, PayrollExportService

| Task | Kategori | File | Status |
|------|----------|------|--------|
| Buat AttendanceService | Service | `app/Services/AttendanceService.php` | ✅ |
| Buat LeaveService | Service | `app/Services/LeaveService.php` | ✅ |
| Buat GeofenceService | Service | `app/Services/GeofenceService.php` | ✅ |
| Buat ApprovalService | Service | `app/Services/ApprovalService.php` | ✅ |
| Buat EmployeeTerminationService | Service | `app/Services/EmployeeTerminationService.php` | ✅ Done termasuk endpoint |
| Buat PayrollCalculatorService | Service | `app/Services/PayrollCalculatorService.php` | ✅ |

---

#### Sprint 13: Observers, Jobs, Commands, Notifications ✅
**Status:** Lengkap. Observers ✅ (6). Jobs ✅ (2—fungsionalitas ProcessPayrollGeneration & ProcessKnowledgeBaseChunking ter-cover nama berbeda). Commands ✅ (6 + 2 baru: KnowledgeBaseIndex, AttendanceSendReminders). Notifications ✅ (7 class).

| Task | Kategori | File | Status |
|------|----------|------|--------|
| Buat EmployeeObserver | Observer | `app/Observers/EmployeeObserver.php` | ✅ |
| Buat AttendanceObserver | Observer | `app/Observers/AttendanceObserver.php` | ✅ |
| Buat LeaveObserver | Observer | `app/Observers/LeaveObserver.php` | ✅ |
| Register Observers di AppServiceProvider | Model | `app/Providers/AppServiceProvider.php` | ✅ (5 registered) |
| Buat ProcessPayrollGeneration Job | Job | `app/Jobs/ProcessPayrollGeneration.php` | ⬜ Pending |
| Buat GenerateEmployeePayrollJob | Job | `app/Jobs/GenerateEmployeePayrollJob.php` | ✅ |
| Buat ProcessKnowledgeBaseChunking Job | Job | `app/Jobs/ProcessKnowledgeBaseChunking.php` | ⬜ Pending |
| Buat ProcessKnowledgeBaseEmbedding Job | Job | `app/Jobs/ProcessKnowledgeBaseEmbedding.php` | ✅ |
| Buat 5 Notification Jobs | Job | `app/Jobs/Send*Notification.php` | ⬜ Pending |
| Buat DetectAlpha Command | Command | `app/Console/Commands/DetectAlphaAttendanceCommand.php` | ✅ (nama berbeda) |
| Buat LeaveResetQuota Command | Command | `app/Console/Commands/ResetLeaveQuotaCommand.php` | ✅ (nama berbeda) |
| Buat KnowledgeBaseIndex Command | Command | `app/Console/Commands/KnowledgeBaseIndex.php` | ✅ |
| Buat AttendanceSendReminders Command | Command | `app/Console/Commands/AttendanceSendReminders.php` | ✅ |
| Buat 6 Notification Classes | Notification | `app/Notifications/*.php` | ✅ (7 class exist) |

Bonus: Commands `GeneratePayrollCommand`, `DetectChronicLateCommand`, `WarmCacheCommand` (registered di `routes/console.php`). Notifications: `LeaveApproved`, `LeaveRejected`, `ChronicLateWarning`, `ApprovalOverdue`, `LeaveRequestSubmitted`, `PayrollPublished`, `NewDeviceLogin`.

---

#### Sprint 14: Seeders & Factories ✅
**Status:** Selesai. 7 seeders ✅ (RoleAndPermission, CompanyAndDepartment, CompanySetting, Shift, Employee, LeaveType, Holiday, PayrollConfig, Branch, SuperAdmin, DemoData). 13 factories ✅ (User, Employee, Company, Branch, Department, Position, Shift, LeaveType, Holiday, Attendance, Leave, Overtime, Reimbursement, ReimbursementCategory).

| Task | Kategori | File | Status |
|------|----------|------|--------|
| Buat RoleAndPermissionSeeder | Seeder | `database/seeders/RoleAndPermissionSeeder.php` | ✅ (nama: RoleAndPermission) |
| Buat CompanyAndDepartmentSeeder | Seeder | `database/seeders/CompanyAndDepartmentSeeder.php` | ✅ (gabung company + dept) |
| Buat BranchSeeder | Seeder | `database/seeders/BranchSeeder.php` | ⬜ Pending |
| Buat ShiftSeeder | Seeder | `database/seeders/ShiftSeeder.php` | ✅ |
| Buat EmployeeSeeder | Seeder | `database/seeders/EmployeeSeeder.php` | ✅ |
| Buat LeaveTypeSeeder | Seeder | `database/seeders/LeaveTypeSeeder.php` | ✅ |
| Buat HolidaySeeder | Seeder | `database/seeders/HolidaySeeder.php` | ✅ |
| Buat TaxConfigSeeder | Seeder | `database/seeders/TaxConfigSeeder.php` | ⬜ Pending |
| Buat BpjsConfigSeeder | Seeder | `database/seeders/BpjsConfigSeeder.php` | ⬜ Pending |
| Buat DemoDataSeeder | Seeder | `database/seeders/DemoDataSeeder.php` | ⬜ Pending |
| Buat Factory files | Factory | `database/factories/*.php` | ⚠️ UserFactory + EmployeeFactory ✅ |

---

#### Sprint 15: Middleware, Policies, Form Requests ✅
**Status:** Lengkap. Policies ✅ (8). Middleware ✅ (3: CheckPasswordExpired, DeviceDetection, GeofenceValidation, terdaftar di bootstrap). Form Requests ✅ (13 di `Api/`, semuanya di-wire ke controller).

| Task | Kategori | File | Status |
|------|----------|------|--------|
| Buat CheckPasswordExpired Middleware | Middleware | `app/Http/Middleware/CheckPasswordExpired.php` | ✅ |
| Buat DeviceDetection Middleware | Middleware | `app/Http/Middleware/DeviceDetection.php` | ⬜ Pending |
| Buat GeofenceValidation Middleware | Middleware | `app/Http/Middleware/GeofenceValidation.php` | ⬜ Pending |
| Register Middleware di bootstrap | Config | `bootstrap/app.php` | ✅ |
| Buat AttendancePolicy | Policy | `app/Policies/AttendancePolicy.php` | ✅ |
| Buat LeavePolicy | Policy | `app/Policies/LeavePolicy.php` | ✅ |
| Buat OvertimePolicy | Policy | `app/Policies/OvertimePolicy.php` | ✅ |
| Buat ReimbursementPolicy | Policy | `app/Policies/ReimbursementPolicy.php` | ✅ |
| Buat EmployeePolicy | Policy | `app/Policies/EmployeePolicy.php` | ✅ |
| Buat ClockInRequest | Request | `app/Http/Requests/Api/ClockInRequest.php` | ✅ (di Api/) |
| Buat StoreOvertimeRequest | Request | `app/Http/Requests/Api/StoreOvertimeRequest.php` | ✅ (di Api/) |
| Buat Form Request lainnya | Request | `app/Http/Requests/Store*.php` | ⬜ Pending |

Bonus policies: `PayrollPolicy`, `KnowledgeBasePolicy`, `AssetPolicy`.

---

### 🟠 FASE: ESS FRONTEND (Sprint 16-20)

#### Sprint 16: ESS Attendance (Clock In/Out) 🟡
**Status:** IN PROGRESS — Face Recognition frontend sedang dikerjakan.
**Branch:** `feat/sprint16-attendance-livewire`

| Task | Kategori | File | Status |
|------|----------|------|--------|
| Buat ESS Layout (PWA mobile-first) | View | `resources/views/layouts/ess.blade.php` | ⬜ Pending |
| Buat Livewire ClockIn | Livewire | `app/Livewire/Attendance/ClockIn.php` | 🟡 In Progress |
| Buat View ClockIn | View | `resources/views/livewire/attendance/clock-in.blade.php` | 🟡 In Progress |
| Buat face-detection.js | JS | `resources/js/face-detection.js` | 🟡 In Progress |
| Buat gps-locator.js | JS | `resources/js/gps-locator.js` | ⬜ Pending |
| Buat face-enrollment.js | JS | `resources/js/face-enrollment.js` | ⬜ Pending |

---

#### Sprint 17: ESS Attendance History & Summary
**Branch:** `feat/sprint17-attendance-history`

| Task | Kategori | File | Estimasi |
|------|----------|------|----------|
| Buat Livewire Attendance History | Livewire | `app/Livewire/Employee/Attendance/History.php` | 2 jam |
| Buat View Attendance History | View | `resources/views/employee/attendance/history.blade.php` | 1 jam |
| Buat Livewire Attendance Summary | Livewire | `app/Livewire/Employee/Attendance/Summary.php` | 1 jam |
| Buat View Attendance Summary | View | `resources/views/employee/attendance/summary.blade.php` | 1 jam |
| Buat Service Worker PWA | PWA | `public/sw.js` | 2 jam |
| Buat PWA Manifest | PWA | `public/manifest.json` | ✅ |
| Buat Service Worker | PWA | `public/service-worker.js` | ✅ |
| Buat PWA Install Prompt | PWA | `resources/js/pwa-install.js` | ✅ |
| Buat Icon SVG | PWA | `public/icon-192.svg`, `public/icon-512.svg` | ✅ |
| **TOTAL** | | **6 files** | **8 jam** |

---

#### Sprint 18: ESS Leave & Overtime
**Branch:** `feat/sprint18-leave-overtime-livewire`

| Task | Kategori | File | Estimasi |
|------|----------|------|----------|
| Buat Livewire Leave Create | Livewire | `app/Livewire/Employee/Leave/Create.php` | 2 jam |
| Buat View Leave Create | View | `resources/views/employee/leave/create.blade.php` | 1 jam |
| Buat Livewire Leave History | Livewire | `app/Livewire/Employee/Leave/History.php` | 1 jam |
| Buat View Leave History | View | `resources/views/employee/leave/history.blade.php` | 1 jam |
| Buat Livewire Leave Quota | Livewire | `app/Livewire/Employee/Leave/Quota.php` | 1 jam |
| Buat View Leave Quota | View | `resources/views/employee/leave/quota.blade.php` | 0.5 jam |
| Buat Livewire Overtime Create | Livewire | `app/Livewire/Employee/Overtime/Create.php` | 2 jam |
| Buat View Overtime Create | View | `resources/views/employee/overtime/create.blade.php` | 1 jam |
| Buat Livewire Overtime History | Livewire | `app/Livewire/Employee/Overtime/History.php` | 1 jam |
| Buat View Overtime History | View | `resources/views/employee/overtime/history.blade.php` | 1 jam |
| **TOTAL** | | **10 files** | **11.5 jam** |

---

#### Sprint 19: ESS Finance & Reimbursement
**Branch:** `feat/sprint19-finance-reimbursement`

| Task | Kategori | File | Estimasi |
|------|----------|------|----------|
| Buat Livewire PayrollSlip | Livewire | `app/Livewire/Employee/Finance/PayrollSlip.php` | 2 jam |
| Buat View PayrollSlip | View | `resources/views/employee/finance/payroll-slip.blade.php` | 1 jam |
| Buat Livewire ReimbursementRequest | Livewire | `app/Livewire/Employee/Finance/ReimbursementRequest.php` | 2 jam |
| Buat View ReimbursementRequest | View | `resources/views/employee/finance/reimbursement-request.blade.php` | 1 jam |
| Buat Livewire ReimbursementHistory | Livewire | `app/Livewire/Employee/Finance/ReimbursementHistory.php` | 1 jam |
| Buat View ReimbursementHistory | View | `resources/views/employee/finance/reimbursement-history.blade.php` | 1 jam |
| **TOTAL** | | **6 files** | **8 jam** |

---

#### Sprint 20: ESS Profile & Devices
**Branch:** `feat/sprint20-profile-devices`

| Task | Kategori | File | Estimasi |
|------|----------|------|----------|
| Buat Livewire PersonalInfo | Livewire | `app/Livewire/Employee/Profile/PersonalInfo.php` | 2 jam |
| Buat View PersonalInfo | View | `resources/views/employee/profile/personal-info.blade.php` | 1 jam |
| Buat Livewire FamilyDetails | Livewire | `app/Livewire/Employee/Profile/FamilyDetails.php` | 2 jam |
| Buat View FamilyDetails | View | `resources/views/employee/profile/family-details.blade.php` | 1 jam |
| Buat Livewire Devices | Livewire | `app/Livewire/Employee/Profile/Devices.php` | 1 jam |
| Buat View Devices | View | `resources/views/employee/profile/devices.blade.php` | 0.5 jam |
| **TOTAL** | | **6 files** | **7.5 jam** |

---

### 🟡 FASE: HRD ADMIN (Sprint 21-24)

#### Sprint 21: HRD Employees CRUD
**Branch:** `feat/sprint21-hrd-employees-crud`

| Task | Kategori | File | Estimasi |
|------|----------|------|----------|
| Buat HRD Layout | View | `resources/views/layouts/hrd.blade.php` | 2 jam |
| Buat Livewire Employees Index | Livewire | `app/Livewire/Hrd/Employees/Index.php` | 2 jam |
| Buat View Employees Index | View | `resources/views/hrd/employees/index.blade.php` | 1 jam |
| Buat Livewire Employees Create | Livewire | `app/Livewire/Hrd/Employees/Create.php` | 2 jam |
| Buat View Employees Create | View | `resources/views/hrd/employees/create.blade.php` | 1 jam |
| Buat Livewire Employees Edit | Livewire | `app/Livewire/Hrd/Employees/Edit.php` | 2 jam |
| Buat View Employees Edit | View | `resources/views/hrd/employees/edit.blade.php` | 1 jam |
| Buat Livewire Employees Show | Livewire | `app/Livewire/Hrd/Employees/Show.php` | 1 jam |
| Buat View Employees Show | View | `resources/views/hrd/employees/show.blade.php` | 1 jam |
| **TOTAL** | | **9 files** | **13 jam** |

---

#### Sprint 22: HRD Leaves & Overtimes Approval
**Branch:** `feat/sprint22-hrd-approvals-leaves`

| Task | Kategori | File | Estimasi |
|------|----------|------|----------|
| Buat Livewire Leaves Pending | Livewire | `app/Livewire/Hrd/Leaves/Pending.php` | 2 jam |
| Buat View Leaves Pending | View | `resources/views/hrd/leaves/pending.blade.php` | 1 jam |
| Buat Livewire Leaves Calendar | Livewire | `app/Livewire/Hrd/Leaves/Calendar.php` | 2 jam |
| Buat View Leaves Calendar | View | `resources/views/hrd/leaves/calendar.blade.php` | 1 jam |
| Buat Livewire Leaves QuotaManagement | Livewire | `app/Livewire/Hrd/Leaves/QuotaManagement.php` | 2 jam |
| Buat View Leaves QuotaManagement | View | `resources/views/hrd/leaves/quota-management.blade.php` | 1 jam |
| Buat Livewire Overtimes Pending | Livewire | `app/Livewire/Hrd/Overtimes/Pending.php` | 2 jam |
| Buat View Overtimes Pending | View | `resources/views/hrd/overtimes/pending.blade.php` | 1 jam |
| **TOTAL** | | **8 files** | **12 jam** |

---

#### Sprint 23: HRD Approvals & Shifts
**Branch:** `feat/sprint23-hrd-approvals-shifts`

| Task | Kategori | File | Estimasi |
|------|----------|------|----------|
| Buat Livewire Approvals Pending | Livewire | `app/Livewire/Hrd/Approvals/Pending.php` | 2 jam |
| Buat View Approvals Pending | View | `resources/views/hrd/approvals/pending.blade.php` | 1 jam |
| Buat Livewire Approvals All | Livewire | `app/Livewire/Hrd/Approvals/All.php` | 1 jam |
| Buat View Approvals All | View | `resources/views/hrd/approvals/all.blade.php` | 1 jam |
| Buat Livewire Approvals Escalated | Livewire | `app/Livewire/Hrd/Approvals/Escalated.php` | 1 jam |
| Buat View Approvals Escalated | View | `resources/views/hrd/approvals/escalated.blade.php` | 1 jam |
| Buat Livewire Shifts Index | Livewire | `app/Livewire/Hrd/Shifts/Index.php` | 1 jam |
| Buat View Shifts Index | View | `resources/views/hrd/shifts/index.blade.php` | 0.5 jam |
| Buat Livewire Shifts Schedule | Livewire | `app/Livewire/Hrd/Shifts/Schedule.php` | 2 jam |
| Buat View Shifts Schedule | View | `resources/views/hrd/shifts/schedule.blade.php` | 1 jam |
| **TOTAL** | | **10 files** | **11.5 jam** |

---

#### Sprint 24: HRD Terminations & Dashboard
**Branch:** `feat/sprint24-hrd-terminations-dashboard`

| Task | Kategori | File | Estimasi |
|------|----------|------|----------|
| Buat Livewire Terminations Pending | Livewire | `app/Livewire/Hrd/Terminations/Pending.php` | 1 jam |
| Buat View Terminations Pending | View | `resources/views/hrd/terminations/pending.blade.php` | 0.5 jam |
| Buat Livewire Terminations Handover | Livewire | `app/Livewire/Hrd/Terminations/Handover.php` | 2 jam |
| Buat View Terminations Handover | View | `resources/views/hrd/terminations/handover.blade.php` | 1 jam |
| Buat Livewire Dashboard Overview | Livewire | `app/Livewire/Hrd/Dashboard/Overview.php` | 2 jam |
| Buat View Dashboard Overview | View | `resources/views/hrd/dashboard/overview.blade.php` | 1 jam |
| Buat Livewire Dashboard AttendanceToday | Livewire | `app/Livewire/Hrd/Dashboard/AttendanceToday.php` | 1 jam |
| Buat View Dashboard AttendanceToday | View | `resources/views/hrd/dashboard/attendance-today.blade.php` | 0.5 jam |
| **TOTAL** | | **8 files** | **9 jam** |

---

### 🟢 FASE: FINANCE ADMIN (Sprint 25-26)

#### Sprint 25: Finance Payroll Generation
**Branch:** `feat/sprint25-finance-payroll-gen`

| Task | Kategori | File | Estimasi |
|------|----------|------|----------|
| Buat Finance Layout | View | `resources/views/layouts/finance.blade.php` | 1 jam |
| Buat Livewire Payroll Index | Livewire | `app/Livewire/Finance/Payroll/Index.php` | 2 jam |
| Buat View Payroll Index | View | `resources/views/finance/payroll/index.blade.php` | 1 jam |
| Buat Livewire Payroll Generate | Livewire | `app/Livewire/Finance/Payroll/Generate.php` | 3 jam |
| Buat View Payroll Generate | View | `resources/views/finance/payroll/generate.blade.php` | 1 jam |
| Buat Livewire Payroll Detail | Livewire | `app/Livewire/Finance/Payroll/Detail.php` | 1 jam |
| Buat View Payroll Detail | View | `resources/views/finance/payroll/detail.blade.php` | 0.5 jam |
| Buat Livewire Payroll Publish | Livewire | `app/Livewire/Finance/Payroll/Publish.php` | 1 jam |
| Buat View Payroll Publish | View | `resources/views/finance/payroll/publish.blade.php` | 0.5 jam |
| Buat Livewire Payroll BulkGenerate | Livewire | `app/Livewire/Finance/Payroll/BulkGenerate.php` | 2 jam |
| Buat View Payroll BulkGenerate | View | `resources/views/finance/payroll/bulk-generate.blade.php` | 0.5 jam |
| Buat Command GeneratePayroll | Command | `app/Console/Commands/GeneratePayroll.php` | 2 jam |
| **TOTAL** | | **12 files** | **15.5 jam** |

---

#### Sprint 26: Finance Reports & Adjustment
**Branch:** `feat/sprint26-finance-reports-adjustment`

| Task | Kategori | File | Estimasi |
|------|----------|------|----------|
| Buat Livewire Payroll Adjustment | Livewire | `app/Livewire/Finance/Payroll/Adjustment.php` | 2 jam |
| Buat View Payroll Adjustment | View | `resources/views/finance/payroll/adjustment.blade.php` | 0.5 jam |
| Buat Livewire Reimbursements Pending | Livewire | `app/Livewire/Finance/Reimbursements/Pending.php` | 2 jam |
| Buat View Reimbursements Pending | View | `resources/views/finance/reimbursements/pending.blade.php` | 1 jam |
| Buat Livewire Reports Payroll | Livewire | `app/Livewire/Finance/Reports/Payroll.php` | 2 jam |
| Buat View Reports Payroll | View | `resources/views/finance/reports/payroll.blade.php` | 1 jam |
| Buat Livewire Reports Tax | Livewire | `app/Livewire/Finance/Reports/Tax.php` | 2 jam |
| Buat View Reports Tax | View | `resources/views/finance/reports/tax.blade.php` | 1 jam |
| Buat UpdatePayrollRequest | Request | `app/Http/Requests/UpdatePayrollRequest.php` | 0.5 jam |
| **TOTAL** | | **9 files** | **12 jam** |

---

### 🔵 FASE: KNOWLEDGEBASE + SETTINGS (Sprint 27-29)

#### Sprint 27: KnowledgeBase RAG
**Branch:** `feat/sprint27-knowledgebase-rag`

| Task | Kategori | File | Estimasi |
|------|----------|------|----------|
| Buat Livewire KB Index | Livewire | `app/Livewire/Hrd/KnowledgeBase/Index.php` | 1 jam |
| Buat View KB Index | View | `resources/views/hrd/knowledge-base/index.blade.php` | 0.5 jam |
| Buat Livewire KB Create | Livewire | `app/Livewire/Hrd/KnowledgeBase/Create.php` | 2 jam |
| Buat View KB Create | View | `resources/views/hrd/knowledge-base/create.blade.php` | 0.5 jam |
| Buat Livewire KB Edit | Livewire | `app/Livewire/Hrd/KnowledgeBase/Edit.php` | 1 jam |
| Buat View KB Edit | View | `resources/views/hrd/knowledge-base/edit.blade.php` | 0.5 jam |
| Buat Livewire KB Chat | Livewire | `app/Livewire/Hrd/KnowledgeBase/Chat.php` | 3 jam |
| Buat View KB Chat | View | `resources/views/hrd/knowledge-base/chat.blade.php` | 1 jam |
| Buat API KB Controller | Controller | `app/Http/Controllers/Api/KnowledgeBaseController.php` | 2 jam |
| **TOTAL** | | **9 files** | **12 jam** |

---

#### Sprint 28: Admin Settings
**Branch:** `feat/sprint28-settings-users`

| Task | Kategori | File | Estimasi |
|------|----------|------|----------|
| Buat Livewire Settings Company | Livewire | `app/Livewire/Settings/Company.php` | 1 jam |
| Buat View Settings Company | View | `resources/views/settings/company.blade.php` | 0.5 jam |
| Buat Livewire Settings Attendance | Livewire | `app/Livewire/Settings/Attendance.php` | 1 jam |
| Buat View Settings Attendance | View | `resources/views/settings/attendance.blade.php` | 0.5 jam |
| Buat Livewire Settings Leave | Livewire | `app/Livewire/Settings/Leave.php` | 1 jam |
| Buat View Settings Leave | View | `resources/views/settings/leave.blade.php` | 0.5 jam |
| Buat Livewire Settings Branding | Livewire | `app/Livewire/Settings/Branding.php` | 1 jam |
| Buat View Settings Branding | View | `resources/views/settings/branding.blade.php` | 0.5 jam |
| Buat Livewire Settings Security | Livewire | `app/Livewire/Settings/Security.php` | 1 jam |
| Buat View Settings Security | View | `resources/views/settings/security.blade.php` | 0.5 jam |
| Buat Livewire Settings System | Livewire | `app/Livewire/Settings/System.php` | 1 jam |
| Buat View Settings System | View | `resources/views/settings/system.blade.php` | 0.5 jam |
| **TOTAL** | | **12 files** | **9 jam** |

---

#### Sprint 29: User Management & Activity Log
**Branch:** `feat/sprint29-users-activity-log`

| Task | Kategori | File | Estimasi |
|------|----------|------|----------|
| Buat Livewire Users Index | Livewire | `app/Livewire/Users/Index.php` | 1 jam |
| Buat View Users Index | View | `resources/views/users/index.blade.php` | 0.5 jam |
| Buat Livewire Users Create | Livewire | `app/Livewire/Users/Create.php` | 1 jam |
| Buat View Users Create | View | `resources/views/users/create.blade.php` | 0.5 jam |
| Buat Livewire Users Edit | Livewire | `app/Livewire/Users/Edit.php` | 1 jam |
| Buat View Users Edit | View | `resources/views/users/edit.blade.php` | 0.5 jam |
| Buat Livewire ActivityLog Index | Livewire | `app/Livewire/Admin/ActivityLog/Index.php` | 1 jam |
| Buat View ActivityLog Index | View | `resources/views/admin/activity-log/index.blade.php` | 0.5 jam |
| Buat Config hrconnect.php | Config | `config/hrconnect.php` | 0.5 jam |
| **TOTAL** | | **9 files** | **6.5 jam** |

---

### 🟣 FASE: ROUTES + API (Sprint 30)

#### Sprint 30: Routes & API Integration ✅
**Status:** Selesai. `routes/api.php` ✅ (50 endpoints via Scramble). `routes/web.php` ✅ (dashboard + auth). Module route files (attendance, leave, overtime, dll) ✅ — 11 files. Bootstrap ✅. PWA ✅ (manifest, service worker, icons, install prompt). Hanya file `employee.php`, `hrd.php`, `finance.php`, `admin.php` yang belum dibuat (akan menyusul saat Livewire frontend siap).

| Task | Kategori | File | Status |
|------|----------|------|--------|
| Buat routes/attendance.php | Route | `routes/attendance.php` | ✅ (bonus, module-based) |
| Buat routes/leave.php | Route | `routes/leave.php` | ✅ |
| Buat routes/overtime.php | Route | `routes/overtime.php` | ✅ |
| Buat routes/payroll.php | Route | `routes/payroll.php` | ✅ |
| Buat routes/approval.php | Route | `routes/approval.php` | ✅ |
| Buat routes/knowledge-base.php | Route | `routes/knowledge-base.php` | ✅ |
| Buat routes/api.php | Route | `routes/api.php` | ✅ |
| Update routes/web.php | Route | `routes/web.php` | ✅ |
| Register route files di bootstrap | Config | `bootstrap/app.php` | ✅ (9 module routes auto-loaded via `then:`) |
| Buat routes/employee.php, hrd.php, etc | Route | role-based route files | ⬜ Pending (menyusul) |

---

### ⚫ FASE: TESTING (Sprint 31-33)

#### Sprint 31: Testing Attendance & Leave
**Branch:** `feat/sprint31-testing-attendance-leave`

| Task | Kategori | File | Estimasi |
|------|----------|------|----------|
| Test ClockIn Feature | Test | `tests/Feature/Attendance/ClockInTest.php` | 2 jam |
| Test ClockOut Feature | Test | `tests/Feature/Attendance/ClockOutTest.php` | 1 jam |
| Test LeaveRequest Feature | Test | `tests/Feature/Leave/LeaveRequestTest.php` | 2 jam |
| Test LeaveApproval Feature | Test | `tests/Feature/Leave/LeaveApprovalTest.php` | 2 jam |
| Test LeaveQuota Feature | Test | `tests/Feature/Leave/LeaveQuotaTest.php` | 1 jam |
| Test OvertimeRequest Feature | Test | `tests/Feature/Overtime/OvertimeRequestTest.php` | 1 jam |
| Test FaceRegistration Feature | Test | `tests/Feature/Auth/FaceRegistrationTest.php` | 1 jam |
| Test GeofenceService Unit | Test | `tests/Unit/GeofenceServiceTest.php` | 1 jam |
| Test AttendanceService Unit | Test | `tests/Unit/AttendanceServiceTest.php` | 1 jam |
| Test FaceValidation Unit | Test | `tests/Unit/FaceValidationTest.php` | 1 jam |
| **TOTAL** | | **10 files** | **13 jam** |

---

#### Sprint 32: Testing Payroll & Finance
**Branch:** `feat/sprint32-testing-payroll-finance`

| Task | Kategori | File | Estimasi |
|------|----------|------|----------|
| Test PayrollGeneration Feature | Test | `tests/Feature/Payroll/PayrollGenerationTest.php` | 3 jam |
| Test PayrollLock Feature | Test | `tests/Feature/Payroll/PayrollLockTest.php` | 2 jam |
| Test PayrollAdjustment Feature | Test | `tests/Feature/Payroll/PayrollAdjustmentTest.php` | 2 jam |
| Test Reimbursement Feature | Test | `tests/Feature/Reimbursement/ReimbursementTest.php` | 2 jam |
| Test PayrollCalculator Unit | Test | `tests/Unit/PayrollCalculatorTest.php` | 2 jam |
| Test EmployeeCrud Feature | Test | `tests/Feature/Hrd/EmployeeCrudTest.php` | 2 jam |
| **TOTAL** | | **6 files** | **13 jam** |

---

#### Sprint 33: Testing HRD & KnowledgeBase
**Branch:** `feat/sprint33-testing-hrd-knowledgebase`

| Task | Kategori | File | Estimasi |
|------|----------|------|----------|
| Test ApprovalWorkflow Feature | Test | `tests/Feature/Hrd/ApprovalWorkflowTest.php` | 2 jam |
| Test Termination Feature | Test | `tests/Feature/Hrd/TerminationTest.php` | 2 jam |
| Test KnowledgeBase RAG Feature | Test | `tests/Feature/KnowledgeBase/RagTest.php` | 2 jam |
| Test LeaveQuota Unit | Test | `tests/Unit/LeaveQuotaTest.php` | 1 jam |
| **TOTAL** | | **4 files** | **7 jam** |

---

### ⚪ FASE: POLISH + DEPLOY (Sprint 34-35)

#### Sprint 34: Language Translations
**Branch:** `feat/sprint34-lang-translations`

| Task | Kategori | File | Estimasi |
|------|----------|------|----------|
| Translation attendance.php | Lang | `lang/id/attendance.php` | 0.5 jam |
| Translation leave.php | Lang | `lang/id/leave.php` | 0.5 jam |
| Translation overtime.php | Lang | `lang/id/overtime.php` | 0.5 jam |
| Translation reimbursement.php | Lang | `lang/id/reimbursement.php` | 0.5 jam |
| Translation payroll.php | Lang | `lang/id/payroll.php` | 0.5 jam |
| Translation knowledgebase.php | Lang | `lang/id/knowledgebase.php` | 0.5 jam |
| Translation employee.php | Lang | `lang/id/employee.php` | 0.5 jam |
| Translation settings.php | Lang | `lang/id/settings.php` | 0.5 jam |
| Translation notification.php | Lang | `lang/id/notification.php` | 0.5 jam |
| **TOTAL** | | **9 files** | **4.5 jam** |

---

#### Sprint 35: Final Integration & Polish
**Branch:** `feat/sprint35-final-integration`

| Task | Kategori | File | Estimasi |
|------|----------|------|----------|
| Merge semua branch ke develop | Git | `develop` | 2 jam |
| Fix integration bugs | Fix | various | 4 jam |
| Performance optimization | Perf | various | 3 jam |
| Run full test suite | Test | `php artisan test` | 2 jam |
| Fix remaining test failures | Fix | various | 4 jam |
| Pint code format | Style | `vendor/bin/pint` | 1 jam |
| Final review & cleanup | Review | various | 2 jam |
| Prepare deploy checklist | Docs | `docs/deploy-checklist.md` | 1 jam |
| **TOTAL** | | **~19 jam** |

---

## Workflow Harian

```
1. Checkout develop:     git checkout develop
2. Pull latest:          git pull origin develop
3. Buat branch sprint:   git checkout -b feat/sprint16-attendance-livewire
4. Kerja di branch ini:  coding, commit, test
5. Push branch:          git push origin feat/sprint16-attendance-livewire
6. Buat PR ke develop:   GitHub → Pull Request → develop
7. Setelah approved:     Merge PR ke develop
8. Hapus branch lama:    git branch -d feat/sprint16-attendance-livewire
9. Ulangi untuk sprint berikutnya
```

## Merge ke Production

```
Setelah semua sprint selesai dan develop stabil:
1. git checkout main
2. git pull origin main
3. git merge develop
4. git push origin main
5. Deploy
```

---

## Sprint Checklist (Copy ke Notion)

| Sprint | Branch | Files | Est. | Status |
|--------|--------|-------|------|--------|
| Sprint 12 | `feat/sprint12-services-core` | 6 | 12 jam | ✅ Selesai (7/7 services, + endpoint termination) |

| Sprint 13 | `feat/sprint13-observers-jobs-commands` | 25 | 25.5 jam | ✅ Selesai (observers ✅, commands ✅, notif ✅, jobs ✅) |

| Sprint 14 | `feat/sprint14-seeders-factories` | 35 | 18 jam | ✅ Selesai (7 seeders, 13 factories) |

| Sprint 15 | `feat/sprint15-middleware-policies-requests` | 15 | 8 jam | ✅ Selesai (8 policies, 3 middleware, 6 form requests) |
| Sprint 16 | `feat/sprint16-attendance-livewire` | 8 | 15 jam | 🟡 In Progress |
| Sprint 17 | `feat/sprint17-attendance-history` | 6 | 8 jam | 📋 Backlog |
| Sprint 18 | `feat/sprint18-leave-overtime-livewire` | 10 | 11.5 jam | 📋 Backlog |
| Sprint 19 | `feat/sprint19-finance-reimbursement` | 6 | 8 jam | 📋 Backlog |
| Sprint 20 | `feat/sprint20-profile-devices` | 6 | 7.5 jam | 📋 Backlog |
| Sprint 21 | `feat/sprint21-hrd-employees-crud` | 9 | 13 jam | 📋 Backlog |
| Sprint 22 | `feat/sprint22-hrd-approvals-leaves` | 8 | 12 jam | 📋 Backlog |
| Sprint 23 | `feat/sprint23-hrd-approvals-shifts` | 10 | 11.5 jam | 📋 Backlog |
| Sprint 24 | `feat/sprint24-hrd-terminations-dashboard` | 8 | 9 jam | 📋 Backlog |
| Sprint 25 | `feat/sprint25-finance-payroll-gen` | 12 | 15.5 jam | 📋 Backlog |
| Sprint 26 | `feat/sprint26-finance-reports-adjustment` | 9 | 12 jam | 📋 Backlog |
| Sprint 27 | `feat/sprint27-knowledgebase-rag` | 9 | 12 jam | 📋 Backlog |
| Sprint 28 | `feat/sprint28-settings-users` | 12 | 9 jam | 📋 Backlog |
| Sprint 29 | `feat/sprint29-users-activity-log` | 9 | 6.5 jam | 📋 Backlog |
| Sprint 30 | `feat/sprint30-routes-api-pwa` | 7 | 5 jam | ⚠️ Sebagian (api.php ✅, module routes ✅, role routes ⬜) |
| Sprint 31 | `feat/sprint31-testing-attendance-leave` | 10 | 13 jam | 📋 Backlog |
| Sprint 32 | `feat/sprint32-testing-payroll-finance` | 6 | 13 jam | 📋 Backlog |
| Sprint 33 | `feat/sprint33-testing-hrd-knowledgebase` | 4 | 7 jam | 📋 Backlog |
| Sprint 34 | `feat/sprint34-lang-translations` | 9 | 4.5 jam | 📋 Backlog |
| Sprint 35 | `feat/sprint35-final-integration` | ~19 | 19 jam | 📋 Backlog |

---

## Total Summary

| Phase | Sprint Range | Files | Estimasi |
|-------|-------------|-------|----------|
| Foundation (1-11) | ✅ DONE | ~150 | ~120 jam |
| Business Logic | Sprint 12-15 | 81 | ~63.5 jam | <sup>✅ Selesai (full)</sup> |
| ESS Frontend | Sprint 16-20 | 36 | ~50 jam | <sup>🟡 Sprint 16 In Progress</sup> |
| HRD Admin | Sprint 21-24 | 35 | ~45.5 jam | <sup>📋 Backlog</sup> |
| Finance Admin | Sprint 25-26 | 21 | ~27.5 jam | <sup>📋 Backlog</sup> |
| KnowledgeBase + Settings | Sprint 27-29 | 30 | ~27.5 jam | <sup>📋 Backlog</sup> |
| Routes + API | Sprint 30 | 7 | ~5 jam | <sup>⚠️ Sebagian</sup> |
| Testing | Sprint 31-33 | 20 | ~33 jam | <sup>📋 Backlog</sup> |
| Polish + Deploy | Sprint 34-35 | 28 | ~23.5 jam | <sup>📋 Backlog</sup> |
| **TOTAL** | **Sprint 1-35** | **~408** | **~395 jam** |

---

> **Last Updated:** 2026-06-06
> **Current Branch:** `develop` (integration — semua sprint dirangkum ke sini)
> **In Progress:** `feat/sprint16-attendance-livewire` (Face Recognition frontend)
