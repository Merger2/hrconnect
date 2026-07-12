# HRConnect - Complete File Blueprint

> **CATATAN ESTIMASI FILE (2026-05-31):** Total ~341 file baru + ~12 modifikasi di dokumen ini adalah audit terhadap struktur ideal, berbeda dengan `planning/sprint-branch-strategy.md` (~408 granular per-sprint) dan `planning/execution-schedule.md` (~353 mingguan). **Sumber kebenaran progress per-item**: `planning/task.md` v4.5.

## Errata

> **Peringatan:** Catatan berikut mengidentifikasi masalah (bugs, ketidakakuratan, item yang hilang) dalam blueprint ini yang harus diperbaiki saat implementasi.

1. **C1: ApprovalLevel enum comparison** — When the `Approval` model casts `level` to `ApprovalLevel` enum, comparisons like `$approval->level === 1` will ALWAYS be false. Use `$approval->level->value === 1` or `$approval->level === ApprovalLevel::L1_SUPERVISOR`.
2. **C2: Payroll forceDelete needed for regeneration** — `$existingPayroll->delete()` only soft-deletes (sets `deleted_at`). Use `forceDelete()` to permanently delete and avoid unique constraint violation when regenerating payroll for the same employee+period.
3. **C3: Sanctum not installed — add `HasApiTokens` to User model** — `laravel/sanctum` is not installed. The User model must use `HasApiTokens` trait for API authentication (PWA Clock-In, etc.). Add Sanctum to composer.json and the trait to `app/Models/User.php`.
4. **C4: Permission enum + seeders not created** — `app/Enums/Permission.php` and `database/seeders/RoleAndPermissionSeeder.php` are listed but have not been created. Without these, `$user->can()` always returns false and authorization is broken.
5. **SEC-5: BusinessRuleException HTTP 422** — `BusinessRuleException` should extend `Symfony\Component\HttpKernel\Exception\HttpException` with status 422, not the base `\Exception` class (which returns 500). All business rule violations must return HTTP 422 Unprocessable Entity.
6. **PayrollAdjustment.amount: decimal not integer** — The migration for `create_payroll_adjustments_table` must use `$table->decimal('amount', 15, 2)` not `$table->integer('amount')`. The blueprint's class diagram lists `integer amount` which is incorrect for monetary values.
7. **PayrollAdjustment.created_by: nullable** — The `created_by` FK column must be nullable (`$table->foreignId('created_by')->nullable()`) because system-generated adjustments may not have a user author.
8. **Employee `$hidden` needs PII fields** — The Employee model's `$hidden` array must include `nik`, `npwp`, `bank_account_number`, and `phone` to prevent PII exposure in API responses.
9. **KnowledgeBase needs vector cast** — The `KnowledgeBase` model's `$casts` array must include `'embedding' => \Pgvector\Laravel\Vector::class` for the pgvector embedding field to work correctly.
10. **Asset needs status enum + SoftDeletes** — The `Asset` model must use the `AssetStatus` enum cast instead of a boolean `is_available`, and must include the `SoftDeletes` trait plus `timestamps`.
11. **FamilyDetail needs encrypted fields** — The `FamilyDetail` model must properly configure CipherSweet encryption for `nik`, `phone`, and `address` fields (marked as "encrypted, blind_index" in the class diagram).
12. **Attendance needs 4 verification columns (ERR-004)** — The Attendance model migration must add `clock_in_verification_method`, `clock_in_face_similarity_score`, `clock_out_verification_method`, and `clock_out_face_similarity_score` as separate columns instead of relying on a single status/verification field.

---

> **Dokumen ini berisi SEMUA nama file yang akan dibuat/dimodifikasi selama pengembangan HRConnect.**
> Setiap file memiliki path absolut dari root project `/home/merger/hrconnect`.
> Gunakan dokumen ini sebagai satu-satunya referensi saat coding agar tidak bingung.

---

## LEGEND
| Symbol | Meaning |
|--------|---------|
| ✅ | Sudah ada (existing) |
| 🔧 | Perlu dimodifikasi |
| 🆕 | File baru yang harus dibuat |

---

# PHASE 1: FOUNDATION (Minggu 1-2)

## 1.1 ENUMS (app/Enums/) — 11 file baru

| No | Path Lengkap | Keterangan |
|----|-------------|------------|
| 1 | `app/Enums/EmploymentType.php` | FULLTIME, CONTRACT, INTERN |
| 2 | `app/Enums/ResignationReason.php` | Personal, BetterOffer, Relocation, Health, Other |
| 3 | `app/Enums/HandoverCategory.php` | DOCUMENT, ASSET, DATA, ACCESS, RESPONSIBILITY |
| 4 | `app/Enums/ApprovalLevel.php` | L1_SUPERVISOR, L2_MANAGER, L3_HRD, L4_DIRECTOR ⚠️ ERRATA C1: Cast to enum, use ApprovalLevel::L1_SUPERVISOR not integer comparison |
| 5 | `app/Enums/DeviceType.php` | DESKTOP, MOBILE, TABLET |
| 6 | `app/Enums/NotificationType.php` | ATTENDANCE, LEAVE, PAYROLL, SYSTEM, APPROVAL, REMINDER |
| 7 | `app/Enums/ShiftScheduleType.php` | REGULAR, ROTATING, CUSTOM |
| 8 | `app/Enums/AttendanceException.php` | LATE, EARLY_LEAVE, MISSED_CLOCK_IN, MISSED_CLOCK_OUT |
| 9 | `app/Enums/LeaveQuotaReset.php` | YEARLY, MONTHLY, ONE_TIME |
| 10 | `app/Enums/KnowledgeBaseCategory.php` | HR_POLICY, IT_GUIDE, GENERAL, FINANCE, OTHER |
| 11 | `app/Enums/CompanySettingType.php` | GEODATA, BRANDING, ATTENDANCE, LEAVE, PAYROLL, SYSTEM |
| ⚠️ **ERRATA C4**: `app/Enums/Permission.php` is MISSING from this list and must be created for `$user->can()` authorization to work. |

### Existing Enums (✅ 16 files - TIDAK PERLU DIBUAT ULANG)
```
app/Enums/ApprovalStatus.php          ✅
app/Enums/AttendanceStatus.php        ✅
app/Enums/BloodType.php               ✅
app/Enums/DayType.php                 ✅
app/Enums/EducationLevel.php          ✅
app/Enums/EmployeeStatus.php          ✅
app/Enums/FamilyRelationship.php      ✅
app/Enums/Gender.php                  ✅
app/Enums/LeaveStatus.php             ❌ DIGANTI RequestStatus
app/Enums/LoanStatus.php              ✅
app/Enums/MaritalStatus.php           ✅
app/Enums/OvertimeStatus.php          ❌ DIGANTI RequestStatus
app/Enums/PayrollItemType.php         ✅
app/Enums/PayrollStatus.php           ✅
app/Enums/ReimbursementStatus.php     ✅
app/Enums/RequestStatus.php           ✅ (merge Leave + Overtime)
app/Enums/SalaryType.php              ✅
```

---

## 1.2 MIGRATIONS (database/migrations/) — 16 file baru

| No | Path Lengkap | Keterangan |
|----|-------------|------------|
| 1 | `database/migrations/2026_05_08_000001_add_employment_type_and_resignation_to_employees_table.php` | employment_type, resignation fields, probation, leave quota |
| 2 | `database/migrations/2026_05_08_000002_add_face_photo_to_employees_table.php` | face_photo column |
| 3 | `database/migrations/2026_05_08_000003_create_company_settings_table.php` | id, company_id, key, value(json), description |
| 4 | `database/migrations/2026_05_08_000004_create_shift_schedules_table.php` | Pivot: employee_id, shift_id, date |
| 5 | `database/migrations/2026_05_08_000005_create_leave_quotas_table.php` | Quota per employee per leave type per year |
| 6 | `database/migrations/2026_05_08_000006_add_device_detection_to_devices_table.php` | device_type, device_name, browser, os |
| 7 | `database/migrations/2026_05_08_000007_add_clock_exception_to_attendances_table.php` | exception_type, exception_notes, approved_late_by ⚠️ ERRATA ERR-004: Also add clock_in_verification_method, clock_in_face_similarity_score, clock_out_verification_method, clock_out_face_similarity_score |
| 8 | `database/migrations/2026_05_08_000008_add_gps_validation_to_attendances_table.php` | is_mocked_gps, gps_accuracy, device_fingerprint |
| 9 | `database/migrations/2026_05_08_000009_create_employee_handovers_table.php` | id, resigning_employee_id, reassign_to, category, item_name, status |
| 10 | `database/migrations/2026_05_08_000010_add_payroll_locked_to_payrolls_table.php` | ❌ TIDAK DIBUAT — lock via status=published |
| 11 | `database/migrations/2026_05_08_000011_create_notifications_table.php` | Laravel notifications table |
| 12 | `database/migrations/2026_05_08_000012_add_force_password_change_to_users_table.php` | force_password_change flag |
| 13 | `database/migrations/2026_05_08_000013_add_google_oauth_to_users_table.php` | 🗑 DIHAPUS — google_id di-drop via new migration |
| 14 | `database/migrations/2026_05_08_000014_add_password_changed_at_to_users_table.php` | Verifikasi password_changed_at sudah ada |
| 15 | `database/migrations/2026_05_08_000015_create_knowledge_base_embeddings_table.php` | Chunks + embeddings untuk RAG |
| 16 | `database/migrations/2026_05_08_000016_create_payroll_adjustments_table.php` | id, payroll_id, amount, reason, created_by, applied_to_period ⚠️ ERRATA: amount must be decimal(15,2) not integer; created_by must be nullable |

---

## 1.3 MODELS — 7 baru + 7 dimodifikasi

### NEW (🆕 7 files)
| No | Path Lengkap | Keterangan |
|----|-------------|------------|
| 1 | `app/Models/CompanySetting.php` | Key-value settings per company |
| 2 | `app/Models/ShiftSchedule.php` | Pivot employee-shift-date |
| 3 | `app/Models/LeaveQuota.php` | Quota per employee per leave type |
| 4 | `app/Models/EmployeeHandover.php` | Handover items saat resign |
| 5 | `app/Models/KnowledgeBaseEmbedding.php` | Chunks + embeddings RAG |
| 6 | `app/Models/RolePermission.php` | Helper RBAC (opsional) ⚠️ ERRATA C4: Permission enum + RoleAndPermissionSeeder must be created; $user->can() won't work without them |
| 7 | `app/Models/PayrollAdjustment.php` | Adjustment payroll locked (amount, reason, applied_to_period) ⚠️ ERRATA: amount must be decimal(15,2), created_by must be nullable |

### EXISTING MODELS (✅ 25 files - TIDAK PERLU DIBUAT ULANG)
```
app/Models/ActivityLog.php         ✅  app/Models/Leave.php               ✅
app/Models/Approval.php            ✅  app/Models/LeaveType.php           ✅
app/Models/Asset.php               ✅ ⚠️ ERRATA: Use AssetStatus enum instead of is_available boolean; add SoftDeletes + timestamps  app/Models/Loan.php                ✅
app/Models/AssetHandover.php       ✅  app/Models/LoanInstallment.php     ✅
app/Models/Attendance.php          ✅  app/Models/Overtime.php            ✅
app/Models/Branch.php              ✅  app/Models/Payroll.php             ✅
app/Models/Company.php             ✅  app/Models/PayrollItem.php         ✅
app/Models/Department.php          ✅  app/Models/PerformanceReview.php   ✅
app/Models/Device.php              ✅  app/Models/Position.php            ✅
app/Models/Employee.php            ✅  app/Models/Reimbursement.php       ✅
app/Models/FamilyDetail.php        ✅ ⚠️ ERRATA: nik, phone, address need CipherSweet encryption setup in model  app/Models/Shift.php               ✅
app/Models/Holiday.php             ✅  app/Models/User.php                ✅
app/Models/KnowledgeBase.php       ✅ ⚠️ ERRATA: Add 'embedding' => \Pgvector\Laravel\Vector::class to $casts
```

### MODIFY (🔧 7 files)
| No | Path | Perubahan |
|----|------|-----------|
| 1 | `app/Models/Employee.php` | employment_type, resignation, probation, face_photo ⚠️ ERRATA: $hidden must include nik, npwp, bank_account_number, phone |
| 2 | `app/Models/Attendance.php` | exception fields, GPS validation ⚠️ ERRATA ERR-004: Add clock_in_verification_method, clock_in_face_similarity_score, clock_out_verification_method, clock_out_face_similarity_score |
| 3 | `app/Models/Device.php` | device_type, device_name, browser, os |
| 4 | `app/Models/Payroll.php` | is_locked, locked_at, locked_by ⚠️ ERRATA C2: Use forceDelete() when regenerating payroll to avoid unique constraint violation |
| 5 | `app/Models/User.php` | password_changed_at verify ⚠️ ERRATA C3: Add HasApiTokens trait from laravel/sanctum (not yet installed) — google_id REMOVED via migration |
| 6 | `app/Models/Leave.php` | quota deduction, probation validation |
| 7 | `app/Models/Approval.php` | multi-level support, escalation ⚠️ ERRATA C1: level field casts to ApprovalLevel enum; compare with enum values, not integers |

---

## 1.4 SERVICE CLASSES (app/Services/) — 8 file baru

| No | Path Lengkap | Method Utama |
|----|-------------|--------------|
| 1 | `app/Services/AttendanceService.php` | clockIn, clockOut, validateGeofence, validateAntiFakeGPS, calculateLateMinutes |
| 2 | `app/Services/LeaveService.php` | requestLeave, validateLeaveQuota, deductLeaveQuota, isProbationBlocked |
| 3 | `app/Services/PayrollCalculatorService.php` | calculateBasicSalary, calculateBPJS, calculatePPh21, generatePayroll, lockPayroll |
| 4 | `app/Services/ApprovalService.php` | createApprovalChain, getNextApprover, approve, reject, escalate, reassignApprovals |
| 5 | `app/Services/EmployeeTerminationService.php` | initiateResignation, executeHandover, reassignSubordinates, processTermination |
| 6 | `app/Services/GeofenceService.php` | validateCoordinates, calculateHaversine, isWithinRadius, getOfficeGeofence |
| 7 | `app/Services/FaceRecognitionService.php` | generateEmbedding, compareFaces, calculateSimilarity, registerFace |
| 8 | `app/Services/DeviceDetectionService.php` | detectDeviceType, detectBrowser, detectOS, getDeviceFingerprint, isMobile |

---

## 1.5 OBSERVERS (app/Observers/) — 6 file baru

| No | Path Lengkap | Trigger |
|----|-------------|---------|
| 1 | `app/Observers/EmployeeObserver.php` | creating→generate number, updating→handle resignation |
| 2 | `app/Observers/AttendanceObserver.php` | creating→validate geofence, created→update cache |
| 3 | `app/Observers/LeaveObserver.php` | creating→validate quota, updated→deduct quota |
| 4 | `app/Observers/PayrollObserver.php` | updating→prevent if locked, updated→process deductions |
| 5 | `app/Observers/ApprovalObserver.php` | created→notify next approver, updated→notify requester |
| 6 | `app/Observers/UserObserver.php` | created→create employee record |

---

## 1.6 COMMANDS (app/Console/Commands/) — 7 file baru

| No | Path Lengkap | Signature |
|----|-------------|-----------|
| 1 | `app/Console/Commands/ResetLeaveQuotas.php` | `leave:reset-quotas` |
| 2 | `app/Console/Commands/GeneratePayroll.php` | `payroll:generate` |
| 3 | `app/Console/Commands/SendAttendanceReminders.php` | `attendance:send-reminders` |
| 4 | `app/Console/Commands/CleanupExpiredSessions.php` | `sessions:cleanup` |
| 5 | `app/Console/Commands/SyncDeviceVerification.php` | `devices:sync-verification` |
| 6 | `app/Console/Commands/ProcessLoanInstallments.php` | `loans:process-installments` |
| 7 | `app/Console/Commands/KnowledgeBaseIndex.php` | `kb:index` |

---

## 1.7 JOBS (app/Jobs/) — 9 file baru

| No | Path Lengkap | Queue |
|----|-------------|-------|
| 1 | `app/Jobs/GenerateFaceEmbedding.php` | face-recognition |
| 2 | `app/Jobs/ProcessPayrollGeneration.php` | payroll |
| 3 | `app/Jobs/SendNotificationJob.php` | notifications |
| 4 | `app/Jobs/SendAttendanceReminder.php` | notifications |
| 5 | `app/Jobs/GenerateLeaveQuotas.php` | leave |
| 6 | `app/Jobs/ProcessKnowledgeBaseEmbedding.php` | knowledge-base |
| 7 | `app/Jobs/CleanupOldActivityLogs.php` | maintenance |
| 8 | `app/Jobs/ProcessEmployeeHandover.php` | hr |
| 9 | `app/Jobs/SyncDeviceFingerprint.php` | attendance |

---

## 1.8 NOTIFICATIONS (app/Notifications/) — 12 file baru

| No | Path Lengkap | Via |
|----|-------------|-----|
| 1 | `app/Notifications/AttendanceReminderNotification.php` | mail, database |
| 2 | `app/Notifications/LeaveRequestNotification.php` | mail, database |
| 3 | `app/Notifications/LeaveApprovedNotification.php` | mail, database |
| 4 | `app/Notifications/LeaveRejectedNotification.php` | mail, database |
| 5 | `app/Notifications/PayrollGeneratedNotification.php` | mail, database |
| 6 | `app/Notifications/ApprovalRequestNotification.php` | mail, database |
| 7 | `app/Notifications/ApprovalApprovedNotification.php` | mail, database |
| 8 | `app/Notifications/ApprovalRejectedNotification.php` | mail, database |
| 9 | `app/Notifications/ForcePasswordChangeNotification.php` | mail, database |
| 10 | `app/Notifications/ResignationSubmittedNotification.php` | mail, database |
| 11 | `app/Notifications/HandoverCompletedNotification.php` | mail, database |
| 12 | `app/Notifications/KnowledgeBaseUpdatedNotification.php` | database |

---

## 1.9 SEEDERS (database/seeders/) — 11 baru + 1 modify

### NEW (🆕 11 files)
| No | Path Lengkap | Keterangan |
|----|-------------|------------|
| 1 | `database/seeders/RoleAndPermissionSeeder.php` | Roles: Super Admin, HRD, Finance, Supervisor, Employee ⚠️ ERRATA C4: Must be created; authorization will be broken without this + Permission enum |
| 2 | `database/seeders/CompanySeeder.php` | Default company + branches |
| 3 | `database/seeders/DepartmentSeeder.php` | Default departments per branch |
| 4 | `database/seeders/PositionSeeder.php` | Default positions + grade + salary |
| 5 | `database/seeders/ShiftSeeder.php` | Shifts: Pagi, Siang, Malam |
| 6 | `database/seeders/LeaveTypeSeeder.php` | Leave types: Tahunan, Sakit, Menstruasi, Penting, Maternity |
| 7 | `database/seeders/HolidaySeeder.php` | National holidays Indonesia 2026 |
| 8 | `database/seeders/CompanySettingSeeder.php` | Default settings: geofence, attendance rules |
| 9 | `database/seeders/EmployeeSeeder.php` | Sample employees |
| 10 | `database/seeders/KnowledgeBaseSeeder.php` | Sample KB articles |
| 11 | `database/seeders/DemoDataSeeder.php` | Demo: attendances, leaves, payrolls |

### EXISTING (🔧 1 file)
```
database/seeders/DatabaseSeeder.php                  🔧 (perlu ditambah call ke seeders baru)
```

---

## 1.10 MIDDLEWARE (app/Http/Middleware/) — 5 file baru

| No | Path Lengkap | Keterangan |
|----|-------------|------------|
| 1 | `app/Http/Middleware/DeviceDetectionMiddleware.php` | Detect mobile vs desktop, redirect ESS |
| 2 | `app/Http/Middleware/ForcePasswordChangeMiddleware.php` | Redirect ke password change jika true |
| 3 | `app/Http/Middleware/CheckRoleMiddleware.php` | Cek role: role:super-admin,hrd,finance |
| 4 | `app/Http/Middleware/CheckPermissionMiddleware.php` | Cek permission: permission:view-payroll ⚠️ ERRATA C4: Requires Permission enum + RoleAndPermissionSeeder which are not yet created |
| 5 | `app/Http/Middleware/GeofenceMiddleware.php` | Validate GPS, set device fingerprint |

---

## 1.11 FORM REQUESTS (app/Http/Requests/) — 15 file baru

| No | Path Lengkap | Keterangan |
|----|-------------|------------|
| 1 | `app/Http/Requests/StoreEmployeeRequest.php` | Validasi create employee |
| 2 | `app/Http/Requests/UpdateEmployeeRequest.php` | Validasi update employee |
| 3 | `app/Http/Requests/StoreAttendanceRequest.php` | Validasi clock in/out (GPS, face, photo) |
| 4 | `app/Http/Requests/StoreLeaveRequest.php` | Validasi leave request |
| 5 | `app/Http/Requests/UpdateLeaveRequest.php` | Validasi approve/reject leave |
| 6 | `app/Http/Requests/StorePayrollRequest.php` | Validasi generate payroll |
| 7 | `app/Http/Requests/UpdatePayrollRequest.php` | Validasi update payroll |
| 8 | `app/Http/Requests/StoreLoanRequest.php` | Validasi loan request |
| 9 | `app/Http/Requests/StoreReimbursementRequest.php` | Validasi reimbursement request |
| 10 | `app/Http/Requests/StoreApprovalRequest.php` | Validasi approve/reject approval |
| 11 | `app/Http/Requests/StoreResignationRequest.php` | Validasi resignation submission |
| 12 | `app/Http/Requests/StoreKnowledgeBaseRequest.php` | Validasi upload KB PDF |
| 13 | `app/Http/Requests/UpdateProfileRequest.php` | Validasi update profile |
| 14 | `app/Http/Requests/UpdatePasswordRequest.php` | Validasi change password |
| 15 | `app/Http/Requests/StoreDeviceRequest.php` | Validasi device registration |

---

## 1.12 POLICIES (app/Policies/) — 8 file baru

| No | Path Lengkap |
|----|-------------|
| 1 | `app/Policies/EmployeePolicy.php` |
| 2 | `app/Policies/AttendancePolicy.php` |
| 3 | `app/Policies/LeavePolicy.php` |
| 4 | `app/Policies/PayrollPolicy.php` |
| 5 | `app/Policies/ApprovalPolicy.php` |
| 6 | `app/Policies/LoanPolicy.php` |
| 7 | `app/Policies/ReimbursementPolicy.php` |
| 8 | `app/Policies/KnowledgeBasePolicy.php` |

---

## 1.13 CONFIG FILES (config/) — 2 baru

| No | Path Lengkap | Keterangan |
|----|-------------|------------|
| 1 | `config/hrconnect.php` | Face threshold, geofence, attendance rules, RAG settings |
| 2 | `config/ciphersweet.php` | CipherSweet configuration |
| ⚠️ **ERRATA C3**: `config/sanctum.php` is MISSING — required for API authentication. Install `laravel/sanctum` first. |

---

## 1.14 ROUTES (routes/) — 4 baru + 2 modify

| No | Path Lengkap | Status | Keterangan |
|----|-------------|--------|------------|
| 1 | `routes/api.php` | 🆕 | API untuk PWA (face recognition, GPS sync) ⚠️ ERRATA C3: Requires laravel/sanctum for API auth |
| 2 | `routes/employee.php` | 🆕 | ESS routes |
| 3 | `routes/hrd.php` | 🆕 | HRD Admin routes |
| 4 | `routes/finance.php` | 🆕 | Finance Admin routes |
| 5 | `routes/web.php` | 🔧 | Tambah routes baru |
| 6 | `routes/console.php` | 🔧 | Tambah scheduled commands |

---

# PHASE 2: LIVEWIRE COMPONENTS (Minggu 3-6)

## 2.1 ESS — ATTENDANCE (app/Livewire/Employee/Attendance/)

| No | Path Lengkap | Keterangan |
|----|-------------|------------|
| 1 | `app/Livewire/Employee/Attendance/ClockIn.php` | Clock in + face + GPS |
| 2 | `app/Livewire/Employee/Attendance/ClockOut.php` | Clock out + face + GPS |
| 3 | `app/Livewire/Employee/Attendance/History.php` | Riwayat bulanan |
| 4 | `app/Livewire/Employee/Attendance/Summary.php` | Summary bulan ini |

## 2.2 ESS — LEAVE (app/Livewire/Employee/Leave/)

| No | Path Lengkap | Keterangan |
|----|-------------|------------|
| 5 | `app/Livewire/Employee/Leave/Create.php` | Form pengajuan leave |
| 6 | `app/Livewire/Employee/Leave/History.php` | Riwayat leave requests |
| 7 | `app/Livewire/Employee/Leave/Quota.php` | Sisa quota leave |

## 2.3 ESS — FINANCE (app/Livewire/Employee/Finance/)

| No | Path Lengkap | Keterangan |
|----|-------------|------------|
| 8 | `app/Livewire/Employee/Finance/LoanRequest.php` | Pengajuan pinjaman |
| 9 | `app/Livewire/Employee/Finance/ReimbursementRequest.php` | Pengajuan reimbursement |
| 10 | `app/Livewire/Employee/Finance/PayrollSlip.php` | View payroll slip |

## 2.4 ESS — PROFILE (app/Livewire/Employee/Profile/)

| No | Path Lengkap | Keterangan |
|----|-------------|------------|
| 11 | `app/Livewire/Employee/Profile/PersonalInfo.php` | Edit secondary data |
| 12 | `app/Livewire/Employee/Profile/FamilyDetails.php` | CRUD data keluarga |
| 13 | `app/Livewire/Employee/Profile/FaceRegistration.php` | Register face embedding |
| 14 | `app/Livewire/Employee/Profile/Devices.php` | List registered devices |

## 2.5 HRD — DASHBOARD (app/Livewire/Hrd/Dashboard/)

| No | Path Lengkap | Keterangan |
|----|-------------|------------|
| 15 | `app/Livewire/Hrd/Dashboard/Overview.php` | Summary stats |
| 16 | `app/Livewire/Hrd/Dashboard/AttendanceToday.php` | Real-time attendance |

## 2.6 HRD — EMPLOYEES (app/Livewire/Hrd/Employees/)

| No | Path Lengkap | Keterangan |
|----|-------------|------------|
| 17 | `app/Livewire/Hrd/Employees/Index.php` | List + search + filter |
| 18 | `app/Livewire/Hrd/Employees/Create.php` | Form create employee |
| 19 | `app/Livewire/Hrd/Employees/Edit.php` | Form edit employee |
| 20 | `app/Livewire/Hrd/Employees/Show.php` | Detail profile |
| 21 | `app/Livewire/Hrd/Employees/BulkUpload.php` | Upload CSV |

## 2.7 HRD — APPROVALS (app/Livewire/Hrd/Approvals/)

| No | Path Lengkap | Keterangan |
|----|-------------|------------|
| 22 | `app/Livewire/Hrd/Approvals/Pending.php` | Pending approvals |
| 23 | `app/Livewire/Hrd/Approvals/All.php` | Semua approvals |
| 24 | `app/Livewire/Hrd/Approvals/Escalated.php` | Escalated approvals |

## 2.8 HRD — LEAVES (app/Livewire/Hrd/Leaves/)

| No | Path Lengkap | Keterangan |
|----|-------------|------------|
| 25 | `app/Livewire/Hrd/Leaves/Pending.php` | Pending leave requests |
| 26 | `app/Livewire/Hrd/Leaves/Calendar.php` | Calendar view |
| 27 | `app/Livewire/Hrd/Leaves/QuotaManagement.php` | Manage quotas |

## 2.9 HRD — SHIFTS (app/Livewire/Hrd/Shifts/)

| No | Path Lengkap | Keterangan |
|----|-------------|------------|
| 28 | `app/Livewire/Hrd/Shifts/Index.php` | List shifts |
| 29 | `app/Livewire/Hrd/Shifts/Schedule.php` | Schedule per employee |

## 2.10 HRD — TERMINATIONS (app/Livewire/Hrd/Terminations/)

| No | Path Lengkap | Keterangan |
|----|-------------|------------|
| 30 | `app/Livewire/Hrd/Terminations/Pending.php` | Pending resignations |
| 31 | `app/Livewire/Hrd/Terminations/Handover.php` | Manage handover |
| 32 | `app/Livewire/Hrd/Terminations/Reassignment.php` | Reassign subordinates |

## 2.11 HRD — REPORTS (app/Livewire/Hrd/Reports/)

| No | Path Lengkap | Keterangan |
|----|-------------|------------|
| 33 | `app/Livewire/Hrd/Reports/Attendance.php` | Attendance report |
| 34 | `app/Livewire/Hrd/Reports/Leave.php` | Leave report |
| 35 | `app/Livewire/Hrd/Reports/Employee.php` | Employee report |

## 2.12 FINANCE — PAYROLL (app/Livewire/Finance/Payroll/)

| No | Path Lengkap | Keterangan |
|----|-------------|------------|
| 36 | `app/Livewire/Finance/Payroll/Index.php` | List payrolls |
| 37 | `app/Livewire/Finance/Payroll/Generate.php` | Generate payroll |
| 38 | `app/Livewire/Finance/Payroll/Detail.php` | Detail per employee |
| 39 | `app/Livewire/Finance/Payroll/Publish.php` | Publish payroll |
| 40 | `app/Livewire/Finance/Payroll/BulkGenerate.php` | Bulk generate via queue |

## 2.13 FINANCE — LOANS (app/Livewire/Finance/Loans/)

| No | Path Lengkap | Keterangan |
|----|-------------|------------|
| 41 | `app/Livewire/Finance/Loans/Pending.php` | Pending loan requests |
| 42 | `app/Livewire/Finance/Loans/Installments.php` | Track installments |
| 43 | `app/Livewire/Finance/Loans/Report.php` | Loan report |

## 2.14 FINANCE — REIMBURSEMENTS (app/Livewire/Finance/Reimbursements/)

| No | Path Lengkap | Keterangan |
|----|-------------|------------|
| 44 | `app/Livewire/Finance/Reimbursements/Pending.php` | Pending requests |
| 45 | `app/Livewire/Finance/Reimbursements/Report.php` | Reimbursement report |

## 2.15 FINANCE — REPORTS (app/Livewire/Finance/Reports/)

| No | Path Lengkap | Keterangan |
|----|-------------|------------|
| 46 | `app/Livewire/Finance/Reports/Payroll.php` | Payroll report |
| 47 | `app/Livewire/Finance/Reports/Tax.php` | PPh21 + BPJS report |

## 2.16 ADMIN — SETTINGS (app/Livewire/Admin/Settings/)

| No | Path Lengkap | Keterangan |
|----|-------------|------------|
| 48 | `app/Livewire/Admin/Settings/Company.php` | Company profile |
| 49 | `app/Livewire/Admin/Settings/Attendance.php` | Attendance rules |
| 50 | `app/Livewire/Admin/Settings/Leave.php` | Leave config |
| 51 | `app/Livewire/Admin/Settings/Branding.php` | App branding |
| 52 | `app/Livewire/Admin/Settings/Security.php` | Security settings |
| 53 | `app/Livewire/Admin/Settings/System.php` | System settings |

## 2.17 ADMIN — USERS (app/Livewire/Admin/Users/)

| No | Path Lengkap | Keterangan |
|----|-------------|------------|
| 54 | `app/Livewire/Admin/Users/Index.php` | List users |
| 55 | `app/Livewire/Admin/Users/Create.php` | Create user |
| 56 | `app/Livewire/Admin/Users/Edit.php` | Edit user, change role |

## 2.18 ADMIN — KNOWLEDGE BASE (app/Livewire/Admin/KnowledgeBase/)

| No | Path Lengkap | Keterangan |
|----|-------------|------------|
| 57 | `app/Livewire/Admin/KnowledgeBase/Index.php` | List articles |
| 58 | `app/Livewire/Admin/KnowledgeBase/Create.php` | Create / upload PDF |
| 59 | `app/Livewire/Admin/KnowledgeBase/Edit.php` | Edit article |
| 60 | `app/Livewire/Admin/KnowledgeBase/Chat.php` | AI chat interface |

## 2.19 ADMIN — ACTIVITY LOG (app/Livewire/Admin/ActivityLog/)

| No | Path Lengkap | Keterangan |
|----|-------------|------------|
| 61 | `app/Livewire/Admin/ActivityLog/Index.php` | Activity log viewer |

## 2.20 SHARED COMPONENTS (app/Livewire/Components/)

| No | Path Lengkap | Keterangan |
|----|-------------|------------|
| 62 | `app/Livewire/Components/Notifications.php` | Notification dropdown |
| 63 | `app/Livewire/Components/Search.php` | Global search |
| 64 | `app/Livewire/Components/ApprovalTimeline.php` | Approval timeline |
| 65 | `app/Livewire/Components/DataTable.php` | Reusable data table |
| 66 | `app/Livewire/Components/FileUpload.php` | Reusable file upload |
| 67 | `app/Livewire/Components/FaceCapture.php` | Face capture UI |
| 68 | `app/Livewire/Components/GpsLocator.php` | GPS locator component |

---

# PHASE 3: VIEWS (resources/views/)

## 3.1 LAYOUTS — 4 file baru

| No | Path Lengkap | Keterangan |
|----|-------------|------------|
| 1 | `resources/views/layouts/ess.blade.php` | ESS Mobile PWA layout |
| 2 | `resources/views/layouts/hrd.blade.php` | HRD Admin layout |
| 3 | `resources/views/layouts/finance.blade.php` | Finance Admin layout |
| 4 | `resources/views/layouts/admin.blade.php` | Super Admin layout |

## 3.2 ESS PAGES — 15 file baru

| No | Path Lengkap | Livewire Component |
|----|-------------|-------------------|
| 1 | `resources/views/employee/dashboard.blade.php` | — |
| 2 | `resources/views/employee/attendance/clock-in.blade.php` | ClockIn |
| 3 | `resources/views/employee/attendance/clock-out.blade.php` | ClockOut |
| 4 | `resources/views/employee/attendance/history.blade.php` | History |
| 5 | `resources/views/employee/attendance/summary.blade.php` | Summary |
| 6 | `resources/views/employee/leave/create.blade.php` | Create |
| 7 | `resources/views/employee/leave/history.blade.php` | History |
| 8 | `resources/views/employee/leave/quota.blade.php` | Quota |
| 9 | `resources/views/employee/finance/loan-request.blade.php` | LoanRequest |
| 10 | `resources/views/employee/finance/reimbursement-request.blade.php` | ReimbursementRequest |
| 11 | `resources/views/employee/finance/payroll-slip.blade.php` | PayrollSlip |
| 12 | `resources/views/employee/profile/personal-info.blade.php` | PersonalInfo |
| 13 | `resources/views/employee/profile/family-details.blade.php` | FamilyDetails |
| 14 | `resources/views/employee/profile/face-registration.blade.php` | FaceRegistration |
| 15 | `resources/views/employee/profile/devices.blade.php` | Devices |

## 3.3 HRD PAGES — 21 file baru

| No | Path Lengkap |
|----|-------------|
| 1 | `resources/views/hrd/dashboard.blade.php` |
| 2 | `resources/views/hrd/attendance/today.blade.php` |
| 3 | `resources/views/hrd/employees/index.blade.php` |
| 4 | `resources/views/hrd/employees/create.blade.php` |
| 5 | `resources/views/hrd/employees/edit.blade.php` |
| 6 | `resources/views/hrd/employees/show.blade.php` |
| 7 | `resources/views/hrd/employees/bulk-upload.blade.php` |
| 8 | `resources/views/hrd/approvals/pending.blade.php` |
| 9 | `resources/views/hrd/approvals/all.blade.php` |
| 10 | `resources/views/hrd/approvals/escalated.blade.php` |
| 11 | `resources/views/hrd/leaves/pending.blade.php` |
| 12 | `resources/views/hrd/leaves/calendar.blade.php` |
| 13 | `resources/views/hrd/leaves/quota-management.blade.php` |
| 14 | `resources/views/hrd/shifts/index.blade.php` |
| 15 | `resources/views/hrd/shifts/schedule.blade.php` |
| 16 | `resources/views/hrd/terminations/pending.blade.php` |
| 17 | `resources/views/hrd/terminations/handover.blade.php` |
| 18 | `resources/views/hrd/terminations/reassignment.blade.php` |
| 19 | `resources/views/hrd/reports/attendance.blade.php` |
| 20 | `resources/views/hrd/reports/leave.blade.php` |
| 21 | `resources/views/hrd/reports/employee.blade.php` |

## 3.4 FINANCE PAGES — 13 file baru

| No | Path Lengkap |
|----|-------------|
| 1 | `resources/views/finance/dashboard.blade.php` |
| 2 | `resources/views/finance/payroll/index.blade.php` |
| 3 | `resources/views/finance/payroll/generate.blade.php` |
| 4 | `resources/views/finance/payroll/detail.blade.php` |
| 5 | `resources/views/finance/payroll/publish.blade.php` |
| 6 | `resources/views/finance/payroll/bulk-generate.blade.php` |
| 7 | `resources/views/finance/loans/pending.blade.php` |
| 8 | `resources/views/finance/loans/installments.blade.php` |
| 9 | `resources/views/finance/loans/report.blade.php` |
| 10 | `resources/views/finance/reimbursements/pending.blade.php` |
| 11 | `resources/views/finance/reimbursements/report.blade.php` |
| 12 | `resources/views/finance/reports/payroll.blade.php` |
| 13 | `resources/views/finance/reports/tax.blade.php` |

## 3.5 ADMIN PAGES — 15 file baru

| No | Path Lengkap |
|----|-------------|
| 1 | `resources/views/admin/dashboard.blade.php` |
| 2 | `resources/views/admin/settings/company.blade.php` |
| 3 | `resources/views/admin/settings/attendance.blade.php` |
| 4 | `resources/views/admin/settings/leave.blade.php` |
| 5 | `resources/views/admin/settings/branding.blade.php` |
| 6 | `resources/views/admin/settings/security.blade.php` |
| 7 | `resources/views/admin/settings/system.blade.php` |
| 8 | `resources/views/admin/users/index.blade.php` |
| 9 | `resources/views/admin/users/create.blade.php` |
| 10 | `resources/views/admin/users/edit.blade.php` |
| 11 | `resources/views/admin/knowledge-base/index.blade.php` |
| 12 | `resources/views/admin/knowledge-base/create.blade.php` |
| 3 | `resources/views/admin/knowledge-base/edit.blade.php` |
| 14 | `resources/views/admin/knowledge-base/chat.blade.php` |
| 15 | `resources/views/admin/activity-log/index.blade.php` |

## 3.6 PARTIALS — 7 file baru

| No | Path Lengkap |
|----|-------------|
| 1 | `resources/views/partials/sidebar-nav.blade.php` |
| 2 | `resources/views/partials/ess-nav.blade.php` |
| 3 | `resources/views/partials/hrd-nav.blade.php` |
| 4 | `resources/views/partials/finance-nav.blade.php` |
| 5 | `resources/views/partials/admin-nav.blade.php` |
| 6 | `resources/views/partials/flash-messages.blade.php` |
| 7 | `resources/views/partials/breadcrumbs.blade.php` |

## 3.7 COMPONENTS — 9 file baru

| No | Path Lengkap |
|----|-------------|
| 1 | `resources/views/components/status-badge.blade.php` |
| 2 | `resources/views/components/data-card.blade.php` |
| 3 | `resources/views/components/empty-state.blade.php` |
| 4 | `resources/views/components/loading-spinner.blade.php` |
| 5 | `resources/views/components/confirmation-modal.blade.php` |
| 6 | `resources/views/components/approval-timeline.blade.php` |
| 7 | `resources/views/components/face-capture.blade.php` |
| 8 | `resources/views/components/gps-map.blade.php` |
| 9 | `resources/views/components/pdf-preview.blade.php` |

## 3.8 FLUX ICONS — 14 file baru

| No | Path Lengkap |
|----|-------------|
| 1 | `resources/views/flux/icon/clock.blade.php` |
| 2 | `resources/views/flux/icon/calendar.blade.php` |
| 3 | `resources/views/flux/icon/users.blade.php` |
| 4 | `resources/views/flux/icon/money.blade.php` |
| 5 | `resources/views/flux/icon/settings.blade.php` |
| 6 | `resources/views/flux/icon/chart.blade.php` |
| 7 | `resources/views/flux/icon/file-text.blade.php` |
| 8 | `resources/views/flux/icon/bell.blade.php` |
| 9 | `resources/views/flux/icon/search.blade.php` |
| 10 | `resources/views/flux/icon/camera.blade.php` |
| 11 | `resources/views/flux/icon/map-pin.blade.php` |
| 12 | `resources/views/flux/icon/check-circle.blade.php` |
| 13 | `resources/views/flux/icon/x-circle.blade.php` |
| 14 | `resources/views/flux/icon/alert-circle.blade.php` |

---

# PHASE 4: TESTS

## 4.1 FEATURE TESTS — 36 file baru

| No | Path Lengkap |
|----|-------------|
| 1 | `tests/Feature/Attendance/ClockInTest.php` |
| 2 | `tests/Feature/Attendance/ClockOutTest.php` |
| 3 | `tests/Feature/Attendance/GeofenceValidationTest.php` |
| 4 | `tests/Feature/Attendance/AntiFakeGPSTest.php` |
| 5 | `tests/Feature/Attendance/AttendanceHistoryTest.php` |
| 6 | `tests/Feature/Leave/LeaveRequestTest.php` |
| 7 | `tests/Feature/Leave/LeaveQuotaTest.php` |
| 8 | `tests/Feature/Leave/LeaveApprovalTest.php` |
| 9 | `tests/Feature/Leave/ProbationLeaveBlockTest.php` |
| 10 | `tests/Feature/Payroll/PayrollGenerationTest.php` |
| 11 | `tests/Feature/Payroll/PayrollCalculationTest.php` |
| 12 | `tests/Feature/Payroll/PayrollLockTest.php` |
| 13 | `tests/Feature/Payroll/BPJSAndTaxTest.php` |
| 14 | `tests/Feature/Payroll/InternExemptTest.php` |
| 15 | `tests/Feature/Loan/LoanRequestTest.php` |
| 16 | `tests/Feature/Loan/LoanInstallmentTest.php` |
| 17 | `tests/Feature/Reimbursement/ReimbursementRequestTest.php` |
| 18 | `tests/Feature/Approval/MultiLevelApprovalTest.php` |
| 19 | `tests/Feature/Approval/ApprovalReassignmentTest.php` |
| 20 | `tests/Feature/Employee/EmployeeCRUDTest.php` |
| 21 | `tests/Feature/Employee/EmployeeNumberGenerationTest.php` |
| 22 | `tests/Feature/Employee/ResignationTest.php` |
| 23 | `tests/Feature/Employee/HandoverTest.php` |
| 24 | `tests/Feature/Employee/ProbationTest.php` |
| 25 | `tests/Feature/FaceRecognition/FaceEmbeddingTest.php` |
| 26 | `tests/Feature/FaceRecognition/FaceSimilarityTest.php` |
| 27 | `tests/Feature/KnowledgeBase/KnowledgeBaseCRUDTest.php` |
| 28 | `tests/Feature/KnowledgeBase/RagQueryTest.php` |
| 29 | `tests/Feature/RBAC/RolePermissionTest.php` |
| 30 | `tests/Feature/RBAC/MiddlewareTest.php` |
| 31 | `tests/Feature/Device/DeviceDetectionTest.php` |
| 32 | `tests/Feature/Device/DeviceVerificationTest.php` |
| 33 | `tests/Feature/Notification/NotificationTest.php` |
| 34 | `tests/Feature/Security/ForcePasswordChangeTest.php` |
| 35 | `tests/Feature/Security/CipherSweetEncryptionTest.php` |
| 36 | `tests/Feature/Security/GoogleOAuthTest.php` |

## 4.2 UNIT TESTS — 14 file baru

| No | Path Lengkap |
|----|-------------|
| 1 | `tests/Unit/Enums/EmploymentTypeTest.php` |
| 2 | `tests/Unit/Enums/ApprovalStatusTest.php` |
| 3 | `tests/Unit/Services/AttendanceServiceTest.php` |
| 4 | `tests/Unit/Services/PayrollCalculatorServiceTest.php` |
| 5 | `tests/Unit/Services/GeofenceServiceTest.php` |
| 6 | `tests/Unit/Services/LeaveServiceTest.php` |
| 7 | `tests/Unit/Services/ApprovalServiceTest.php` |
| 8 | `tests/Unit/Models/EmployeeTest.php` |
| 9 | `tests/Unit/Models/AttendanceTest.php` |
| 10 | `tests/Unit/Models/PayrollTest.php` |
| 11 | `tests/Unit/Models/LeaveTest.php` |
| 12 | `tests/Unit/Observers/EmployeeObserverTest.php` |
| 13 | `tests/Unit/Observers/AttendanceObserverTest.php` |
| 14 | `tests/Unit/Observers/LeaveObserverTest.php` |

---

# PHASE 5: FRONTEND ASSETS & PWA

## 5.1 JAVASCRIPT — 6 file baru

| No | Path Lengkap | Keterangan |
|----|-------------|------------|
| 1 | `resources/js/face-detection.js` | Face detection logic |
| 2 | `resources/js/gps-locator.js` | GPS tracking + anti-fake GPS |
| 3 | `resources/js/pwa-install.js` | PWA install prompt |
| 4 | `resources/js/service-worker.js` | Service worker offline cache |
| 5 | `resources/js/app-debounce.js` | Debounce utility |
| 6 | `resources/js/app-formatters.js` | Date, currency formatters |

## 5.2 PWA — 5 file baru

| No | Path Lengkap | Keterangan |
|----|-------------|------------|
| 1 | `public/manifest.json` | PWA manifest |
| 2 | `public/sw.js` | Service worker |
| 3 | `public/offline.html` | Offline fallback |
| 4 | `public/icons/icon-192.png` | PWA icon 192x192 |
| 5 | `public/icons/icon-512.png` | PWA icon 512x512 |

---

# PHASE 6: LANGUAGE FILES (lang/id/) — 13 file baru

| No | Path Lengkap |
|----|-------------|
| 1 | `lang/id/auth.php` |
| 2 | `lang/id/pagination.php` |
| 3 | `lang/id/passwords.php` |
| 4 | `lang/id/validation.php` |
| 5 | `lang/id/attendance.php` |
| 6 | `lang/id/leave.php` |
| 7 | `lang/id/payroll.php` |
| 8 | `lang/id/employee.php` |
| 9 | `lang/id/approval.php` |
| 10 | `lang/id/finance.php` |
| 11 | `lang/id/settings.php` |
| 12 | `lang/id/common.php` |
| 13 | `lang/id/errors.php` |

---

# SUMMARY

| Kategori | Existing | New | Modify |
|----------|----------|-----|--------|
| Enums | 16 | 11 | - |
| Migrations | 33 | 15 | - |
| Models | 25 | 6 | 7 |
| Services | - | 8 | - |
| Observers | - | 6 | - |
| Commands | - | 7 | - |
| Jobs | - | 9 | - |
| Notifications | - | 12 | - |
| Seeders | 1 | 11 | 1 |
| Middleware | - | 5 | - |
| Form Requests | - | 15 | - |
| Policies | - | 8 | - |
| Config | 15 | 2 | - |
| Routes | 3 | 4 | 2 |
| Livewire Components | 1 | 68 | - |
| Views | 32 | 80+ | 2 |
| Tests | 13 | 50 | - |
| JS Files | 1 | 6 | - |
| PWA Files | - | 5 | - |
| Language Files | - | 13 | - |
| **TOTAL** | ~145 | **~341** | ~12 |

---

*Dokumen ini adalah SATU-SATUNYA referensi untuk nama file saat coding.*
*Terakhir diupdate: 2026-05-31*
