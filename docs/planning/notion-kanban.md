# 🗂️ HRConnect Development Kanban — Notion Import

## Setup Notion

1. **Buat Database baru** → pilih **Board View** (atau Table View)
2. **Buat Properties:**

| Property | Type | Values |
|----------|------|--------|
| **Task** | Title | — |
| **Fase** | Select | `Fase 1-Foundation`, `Fase 2-Attendance`, `Fase 3-Leave`, `Fase 4-Finance`, `Fase 5-Profile`, `Fase 6-HRD Admin`, `Fase 7-Finance Admin`, `Fase 8-KnowledgeBase`, `Fase 9-Settings`, `Fase 10-Testing`, `Fase 11-Polish` |
| **Kategori** | Select | `Model`, `Migration`, `Seeder`, `Factory`, `Service`, `Observer`, `Job`, `Command`, `Notification`, `Livewire`, `View`, `JS`, `Route`, `Policy`, `Request`, `Middleware`, `Test`, `Lang`, `Config`, `PWA` |
| **Status** | Select | `📋 Backlog`, `🔄 In Progress`, `✅ Done`, `🚫 Blocked` |
| **Priority** | Select | `🔴 High`, `🟡 Medium`, `🟢 Low` |
| **Estimasi** | Select | `0.5 jam`, `1 jam`, `2 jam`, `3 jam`, `4 jam+` |
| **Jumlah File** | Number | Count |
| **File Path** | Text | Path absolut |
| **Notes** | Text | Keterangan |

3. **Group by Fase** → Board View
4. **Copy-paste** tiap card di bawah ini

---

# 📋 SEMUA CARD — COPY PASTE KE NOTION

## 🔴 FASE 1: Foundation (Minggu 1-2)

### Models Baru (7)
```
📌 Buat Model CompanySetting
Fase: Fase 1-Foundation
Kategori: Model
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Models/CompanySetting.php
Notes: Key-value settings per company, fillable: company_id, key, value, description
```
```
📌 Buat Model ShiftSchedule
Fase: Fase 1-Foundation
Kategori: Model
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Models/ShiftSchedule.php
Notes: Pivot employee-shift-date, belongsTo Employee, Shift
```
```
📌 Buat Model LeaveQuota
Fase: Fase 1-Foundation
Kategori: Model
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Models/LeaveQuota.php
Notes: Quota per employee per leave type per year, belongsTo Employee, LeaveType
```
```
📌 Buat Model EmployeeHandover
Fase: Fase 1-Foundation
Kategori: Model
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Models/EmployeeHandover.php
Notes: Handover items saat resign, belongsTo resigning_employee, reassign_to
```
```
📌 Buat Model KnowledgeBaseEmbedding
Fase: Fase 1-Foundation
Kategori: Model
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Models/KnowledgeBaseEmbedding.php
Notes: Chunks + embeddings RAG, vector(1536), belongsTo KnowledgeBase
```
```
📌 Buat Model PayrollAdjustment
Fase: Fase 1-Foundation
Kategori: Model
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Models/PayrollAdjustment.php
Notes: Adjustment untuk payroll locked, amount (+/-), reason, applied_to_period
```
```
📌 Buat Model RolePermission (opsional)
Fase: Fase 1-Foundation
Kategori: Model
Priority: 🟢 Low
Estimasi: 0.5 jam
Jumlah File: 1
File Path: app/Models/RolePermission.php
Notes: Helper RBAC, Spatie Permission wrapper
```

### Migrations Baru (16)
```
📌 Migration: employment_type + resignation ke employees
Fase: Fase 1-Foundation
Kategori: Migration
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: 2026_05_08_000001_add_employment_type_and_resignation_to_employees_table.php
Notes: employment_type, resignation_date, resignation_reason, probation_end_date, annual_leave_quota
```
```
📌 Migration: face_photo ke employees
Fase: Fase 1-Foundation
Kategori: Migration
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: 2026_05_08_000002_add_face_photo_to_employees_table.php
Notes: face_photo (string, nullable), path ke file photo
```
```
📌 Migration: create company_settings
Fase: Fase 1-Foundation
Kategori: Migration
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: 2026_05_08_000003_create_company_settings_table.php
Notes: id, company_id FK, key (unique), value (json), description
```
```
📌 Migration: create shift_schedules
Fase: Fase 1-Foundation
Kategori: Migration
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: 2026_05_08_000004_create_shift_schedules_table.php
Notes: employee_id FK, shift_id FK, date, unique(employee_id, date)
```
```
📌 Migration: create leave_quotas
Fase: Fase 1-Foundation
Kategori: Migration
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: 2026_05_08_000005_create_leave_quotas_table.php
Notes: employee_id FK, leave_type_id FK, year, quota, used, carry_forward, unique(employee, type, year)
```
```
📌 Migration: device_detection ke devices
Fase: Fase 1-Foundation
Kategori: Migration
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: 2026_05_08_000006_add_device_detection_to_devices_table.php
Notes: device_type, device_name, browser, os
```
```
📌 Migration: clock_exception ke attendances
Fase: Fase 1-Foundation
Kategori: Migration
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: 2026_05_08_000007_add_clock_exception_to_attendances_table.php
Notes: exception_type, exception_notes, approved_late_by
```
```
📌 Migration: gps_validation ke attendances
Fase: Fase 1-Foundation
Kategori: Migration
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: 2026_05_08_000008_add_gps_validation_to_attendances_table.php
Notes: is_mocked_gps, gps_accuracy, device_fingerprint
```
```
📌 Migration: create employee_handovers
Fase: Fase 1-Foundation
Kategori: Migration
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: 2026_05_08_000009_create_employee_handovers_table.php
Notes: resigning_employee_id, reassign_to, category, item_name, status
```
```
📌 Migration: payroll_locked ke payrolls
Fase: Fase 1-Foundation
Kategori: Migration
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: 2026_05_08_000010_add_payroll_locked_to_payrolls_table.php
Notes: is_locked (bool), locked_at, locked_by FK
```
```
📌 Migration: create notifications (Laravel default)
Fase: Fase 1-Foundation
Kategori: Migration
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: 2026_05_08_000011_create_notifications_table.php
Notes: php artisan notifications:table
```
```
📌 Migration: force_password_change ke users
Fase: Fase 1-Foundation
Kategori: Migration
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: 2026_05_08_000012_add_force_password_change_to_users_table.php
Notes: force_password_change (bool, default true)
```
```
📌 Migration: google_oauth ke users (cek existing)
Fase: Fase 1-Foundation
Kategori: Migration
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: 2026_05_08_000013_add_google_oauth_to_users_table.php
Notes: Cek google_id sudah ada, jika tidak buat migration baru
```
```
📌 Migration: password_changed_at ke users (cek existing)
Fase: Fase 1-Foundation
Kategori: Migration
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: 2026_05_08_000014_add_password_changed_at_to_users_table.php
Notes: Cek sudah ada, jika tidak buat migration baru
```
```
📌 Migration: create knowledge_base_embeddings
Fase: Fase 1-Foundation
Kategori: Migration
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: 2026_05_08_000015_create_knowledge_base_embeddings_table.php
Notes: knowledge_base_id FK, chunk_text, embedding vector(1536), chunk_index
```
```
📌 Migration: create payroll_adjustments
Fase: Fase 1-Foundation
Kategori: Migration
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: 2026_05_08_000016_create_payroll_adjustments_table.php
Notes: payroll_id FK, amount (int +/-), reason (text), created_by FK, applied_to_period (date)
```

### Models Existing — Update (9)
```
📌 Update Model Employee
Fase: Fase 1-Foundation
Kategori: Model
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: app/Models/Employee.php
Notes: Tambah relationships, fillable employment_type/resignation fields, boot() untuk auto employee number, face_registered() helper
```
```
📌 Update Model User
Fase: Fase 1-Foundation
Kategori: Model
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Models/User.php
Notes: Tambah force_password_change, password_changed_at, employee() relationship
```
```
📌 Update Model Payroll
Fase: Fase 1-Foundation
Kategori: Model
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Models/Payroll.php
Notes: Tambah is_locked, locked_at, locked_by, published_at, adjustments() relationship
```
```
📌 Update Model PayrollItem
Fase: Fase 1-Foundation
Kategori: Model
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: app/Models/PayrollItem.php
Notes: Hapus type 'adjustment', hanya 'allowance' dan 'deduction'
```
```
📌 Update Model Attendance
Fase: Fase 1-Foundation
Kategori: Model
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Models/Attendance.php
Notes: Tambah fillable is_mocked_gps/gps_accuracy/device_fingerprint/exception fields
```
```
📌 Update Model Leave
Fase: Fase 1-Foundation
Kategori: Model
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: app/Models/Leave.php
Notes: Tambah rejection_reason, approval relationships
```
```
📌 Update Model Overtime
Fase: Fase 1-Foundation
Kategori: Model
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: app/Models/Overtime.php
Notes: Tambah start_time, end_time, description, rejection_reason
```
```
📌 Update Model Shift
Fase: Fase 1-Foundation
Kategori: Model
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: app/Models/Shift.php
Notes: Tambah late_tolerance_minutes
```
```
📌 Update Model Device
Fase: Fase 1-Foundation
Kategori: Model
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: app/Models/Device.php
Notes: Tambah device_type, device_name, browser, os
```

### Services Baru (5)
```
📌 Buat Service LeaveService
Fase: Fase 1-Foundation
Kategori: Service
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: app/Services/LeaveService.php
Notes: Submit leave, validate quota, calculate working days (exclude weekend/holiday), withdraw
```
```
📌 Buat Service PayrollCalculatorService
Fase: Fase 1-Foundation
Kategori: Service
Priority: 🔴 High
Estimasi: 3 jam
Jumlah File: 1
File Path: app/Services/PayrollCalculatorService.php
Notes: PPh21 TER, BPJS, gross/net calc, pro-rated salary, overtime calc
```
```
📌 Buat Service ApprovalService
Fase: Fase 1-Foundation
Kategori: Service
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: app/Services/ApprovalService.php
Notes: 2-level approval chain, auto-escalate >24h, get approvers by parent_id
```
```
📌 Buat Service GeofenceService
Fase: Fase 1-Foundation
Kategori: Service
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Services/GeofenceService.php
Notes: Haversine formula, validate GPS vs branch radius, detect mock GPS
```
```
📌 Buat Service EmployeeTerminationService
Fase: Fase 1-Foundation
Kategori: Service
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: app/Services/EmployeeTerminationService.php
Notes: Resign handling, reassign approvals/subordinates, DB::transaction, wasChanged()
```

### Observers Baru (3)
```
📌 Buat Observer EmployeeObserver
Fase: Fase 1-Foundation
Kategori: Observer
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Observers/EmployeeObserver.php
Notes: Auto employee number (creating), resign handling (updating dengan wasChanged)
```
```
📌 Buat Observer AttendanceObserver
Fase: Fase 1-Foundation
Kategori: Observer
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Observers/AttendanceObserver.php
Notes: Auto status on create, detect alpha, set late_minutes
```
```
📌 Buat Observer LeaveObserver
Fase: Fase 1-Foundation
Kategori: Observer
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Observers/LeaveObserver.php
Notes: Deduct quota on approved, restore on rejected/withdrawn
```
```
📌 Register Observers di AppServiceProvider
Fase: Fase 1-Foundation
Kategori: Model
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: app/Providers/AppServiceProvider.php
Notes: Employee::observe(EmployeeObserver::class), dst
```

### Jobs Baru (9)
```
📌 Buat Job ProcessPayrollGeneration
Fase: Fase 1-Foundation
Kategori: Job
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: app/Jobs/ProcessPayrollGeneration.php
Notes: Queue payroll_high, bulk generate per period, Dispatchable, ShouldQueue
```
```
📌 Buat Job GenerateEmployeePayrollJob
Fase: Fase 1-Foundation
Kategori: Job
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: app/Jobs/GenerateEmployeePayrollJob.php
Notes: Queue payroll_high, single employee payroll calc
```
```
📌 Buat Job ProcessKnowledgeBaseChunking
Fase: Fase 1-Foundation
Kategori: Job
Priority: 🟡 Medium
Estimasi: 2 jam
Jumlah File: 1
File Path: app/Jobs/ProcessKnowledgeBaseChunking.php
Notes: Chunk PDF text (~600 tokens, overlap 100), save chunks
```
```
📌 Buat Job ProcessKnowledgeBaseEmbedding
Fase: Fase 1-Foundation
Kategori: Job
Priority: 🟡 Medium
Estimasi: 2 jam
Jumlah File: 1
File Path: app/Jobs/ProcessKnowledgeBaseEmbedding.php
Notes: Generate embedding via Gemini/OpenAI, save to knowledge_base_embeddings
```
```
📌 Buat Job SendLeaveNotification
Fase: Fase 1-Foundation
Kategori: Job
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Jobs/SendLeaveNotification.php
Notes: Queue notifications, notify approver + requester
```
```
📌 Buat Job SendApprovalNotification
Fase: Fase 1-Foundation
Kategori: Job
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Jobs/SendApprovalNotification.php
Notes: Notify requester on approved/rejected
```
```
📌 Buat Job SendPayrollNotification
Fase: Fase 1-Foundation
Kategori: Job
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Jobs/SendPayrollNotification.php
Notes: Notify all employees when payroll published
```
```
📌 Buat Job SendDeviceNotification
Fase: Fase 1-Foundation
Kategori: Job
Priority: 🟢 Low
Estimasi: 0.5 jam
Jumlah File: 1
File Path: app/Jobs/SendDeviceNotification.php
Notes: Notify HRD of new device registration
```
```
📌 Buat Job SendAttendanceReminder
Fase: Fase 1-Foundation
Kategori: Job
Priority: 🟢 Low
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Jobs/SendAttendanceReminder.php
Notes: Reminder untuk karyawan yang belum clock in
```

### Commands Baru (4)
```
📌 Buat Command AttendanceDetectAlpha
Fase: Fase 1-Foundation
Kategori: Command
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Console/Commands/AttendanceDetectAlpha.php
Notes: Signature attendance:detect-alpha, schedule dailyAt 23:59
```
```
📌 Buat Command LeaveResetQuota
Fase:fase 1-Foundation
Kategori: Command
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Console/Commands/LeaveResetQuota.php
Notes: Signature leave:reset-quota, schedule yearOn 1 Jan 00:00
```
```
📌 Buat Command KnowledgeBaseIndex
Fase: Fase 1-Foundation
Kategori: Command
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Console/Commands/KnowledgeBaseIndex.php
Notes: Signature kb:index, manual trigger untuk re-index
```
```
📌 Buat Command AttendanceSendReminders
Fase: Fase 1-Foundation
Kategori: Command
Priority: 🟢 Low
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Console/Commands/AttendanceSendReminders.php
Notes: Signature attendance:send-reminders, schedule configurable
```

### Notifications Baru (6)
```
📌 Buat Notification LeaveRequestSubmitted
Fase: Fase 1-Foundation
Kategori: Notification
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Notifications/LeaveRequestSubmitted.php
Notes: Notify L1 approver via mail + database
```
```
📌 Buat Notification LeaveApproved
Fase: Fase 1-Foundation
Kategori: Notification
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Notifications/LeaveApproved.php
Notes: Notify employee via database
```
```
📌 Buat Notification LeaveRejected
Fase: Fase 1-Foundation
Kategori: Notification
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Notifications/LeaveRejected.php
Notes: Notify employee + rejection reason
```
```
📌 Buat Notification PayrollPublished
Fase: Fase 1-Foundation
Kategori: Notification
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Notifications/PayrollPublished.php
Notes: Notify all employees, includes period info
```
```
📌 Buat Notification ApprovalOverdue
Fase: Fase 1-Foundation
Kategori: Notification
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Notifications/ApprovalOverdue.php
Notes: Notify escalations >24h
```
```
📌 Buat Notification NewDeviceLogin
Fase: Fase 1-Foundation
Kategori: Notification
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Notifications/NewDeviceLogin.php
Notes: Notify employee + HRD of new device
```

### Seeders Baru (12)
```
📌 Buat Seeder RolePermissionSeeder
Fase: Fase 1-Foundation
Kategori: Seeder
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: database/seeders/RolePermissionSeeder.php
Notes: Spatie roles: super_admin, hrd, finance, manager, employee + semua permissions
```
```
📌 Buat Seeder CompanySeeder
Fase: Fase 1-Foundation
Kategori: Seeder
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: database/seeders/CompanySeeder.php
Notes: 1 demo company (PT Demo Indonesia)
```
```
📌 Buat Seeder BranchSeeder
Fase: Fase 1-Foundation
Kategori: Seeder
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: database/seeders/BranchSeeder.php
Notes: 1 demo branch (Jakarta Pusat) + GPS coords + radius
```
```
📌 Buat Seeder DepartmentSeeder
Fase: Fase 1-Foundation
Kategori: Seeder
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: database/seeders/DepartmentSeeder.php
Notes: Engineering, HR, Finance, Operations
```
```
📌 Buat Seeder PositionSeeder
Fase: Fase 1-Foundation
Kategori: Seeder
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: database/seeders/PositionSeeder.php
Notes: 8 positions sesuai demo data hierarchy
```
```
📌 Buat Seeder ShiftSeeder
Fase: Fase 1-Foundation
Kategori: Seeder
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: database/seeders/ShiftSeeder.php
Notes: Pagi (08-17), Siang (14-23), Malam (23-08)
```
```
📌 Update EmployeeSeeder (existing)
Fase: Fase 1-Foundation
Kategori: Seeder
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: database/seeders/EmployeeSeeder.php
Notes: 8 employees dengan parent_id hierarchy, face_registered, employment_type
```
```
📌 Buat Seeder LeaveTypeSeeder
Fase: Fase 1-Foundation
Kategori: Seeder
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: database/seeders/LeaveTypeSeeder.php
Notes: Tahunan (12 hari), Sakit (unlimited), Penting (3), Menstruasi (2), Melahirkan (90)
```
```
📌 Buat Seeder HolidaySeeder
Fase: Fase 1-Foundation
Kategori: Seeder
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: database/seeders/HolidaySeeder.php
Notes: National holidays 2026 Indonesia
```
```
📌 Buat Seeder TaxConfigSeeder
Fase: Fase 1-Foundation
Kategori: Seeder
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: database/seeders/TaxConfigSeeder.php
Notes: PPh21 TER kategori A/B/C rates
```
```
📌 Buat Seeder BpjsConfigSeeder
Fase: Fase 1-Foundation
Kategori: Seeder
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: database/seeders/BpjsConfigSeeder.php
Notes: BPJS Kesehatan, JHT, JP, JKK, JKM rates
```
```
📌 Buat Seeder DemoDataSeeder
Fase: Fase 1-Foundation
Kategori: Seeder
Priority: 🔴 High
Estimasi: 3 jam
Jumlah File: 1
File Path: database/seeders/DemoDataSeeder.php
Notes: Demo attendances, leaves, overtimes, reimbursements, payrolls untuk 8 employees
```

### Factories Baru (23)
```
📌 Buat Factory CompanyFactory
Fase: Fase 1-Foundation
Kategori: Factory
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: database/factories/CompanyFactory.php
```
```
📌 Buat Factory BranchFactory
Fase: Fase 1-Foundation
Kategori: Factory
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: database/factories/BranchFactory.php
```
```
📌 Buat Factory DepartmentFactory
Fase: Fase 1-Foundation
Kategori: Factory
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: database/factories/DepartmentFactory.php
```
```
📌 Buat Factory PositionFactory
Fase: Fase 1-Foundation
Kategori: Factory
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: database/factories/PositionFactory.php
```
```
📌 Buat Factory ShiftFactory
Fase: Fase 1-Foundation
Kategori: Factory
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: database/factories/ShiftFactory.php
```
```
📌 Buat Factory AttendanceFactory
Fase: Fase 1-Foundation
Kategori: Factory
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: database/factories/AttendanceFactory.php
```
```
📌 Buat Factory LeaveFactory
Fase: Fase 1-Foundation
Kategori: Factory
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: database/factories/LeaveFactory.php
```
```
📌 Buat Factory LeaveTypeFactory
Fase: Fase 1-Foundation
Kategori: Factory
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: database/factories/LeaveTypeFactory.php
```
```
📌 Buat Factory OvertimeFactory
Fase: Fase 1-Foundation
Kategori: Factory
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: database/factories/OvertimeFactory.php
```
```
📌 Buat Factory PayrollFactory
Fase: Fase 1-Foundation
Kategori: Factory
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: database/factories/PayrollFactory.php
```
```
📌 Buat Factory PayrollItemFactory
Fase: Fase 1-Foundation
Kategori: Factory
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: database/factories/PayrollItemFactory.php
```
```
📌 Buat Factory ApprovalFactory
Fase: Fase 1-Foundation
Kategori: Factory
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: database/factories/ApprovalFactory.php
```
```
📌 Buat Factory DeviceFactory
Fase: Fase 1-Foundation
Kategori: Factory
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: database/factories/DeviceFactory.php
```
```
📌 Buat Factory FamilyDetailFactory
Fase: Fase 1-Foundation
Kategori: Factory
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: database/factories/FamilyDetailFactory.php
```
```
📌 Buat Factory HolidayFactory
Fase: Fase 1-Foundation
Kategori: Factory
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: database/factories/HolidayFactory.php
```
```
📌 Buat Factory KnowledgeBaseFactory
Fase: Fase 1-Foundation
Kategori: Factory
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: database/factories/KnowledgeBaseFactory.php
```
```
📌 Buat Factory ReimbursementFactory
Fase: Fase 1-Foundation
Kategori: Factory
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: database/factories/ReimbursementFactory.php
```
```
📌 Buat Factory LoanFactory
Fase: Fase 1-Foundation
Kategori: Factory
Priority: 🟢 Low
Estimasi: 0.5 jam
Jumlah File: 1
File Path: database/factories/LoanFactory.php
Notes: V2, tapi factory siap
```
```
📌 Buat Factory LoanInstallmentFactory
Fase: Fase 1-Foundation
Kategori: Factory
Priority: 🟢 Low
Estimasi: 0.5 jam
Jumlah File: 1
File Path: database/factories/LoanInstallmentFactory.php
Notes: V2, tapi factory siap
```
```
📌 Buat Factory AssetFactory
Fase: Fase 1-Foundation
Kategori: Factory
Priority: 🟢 Low
Estimasi: 0.5 jam
Jumlah File: 1
File Path: database/factories/AssetFactory.php
Notes: V2, tapi factory siap
```
```
📌 Buat Factory AssetHandoverFactory
Fase: Fase 1-Foundation
Kategori: Factory
Priority: 🟢 Low
Estimasi: 0.5 jam
Jumlah File: 1
File Path: database/factories/AssetHandoverFactory.php
Notes: V2, tapi factory siap
```
```
📌 Buat Factory PerformanceReviewFactory
Fase: Fase 1-Foundation
Kategori: Factory
Priority: 🟢 Low
Estimasi: 0.5 jam
Jumlah File: 1
File Path: database/factories/PerformanceReviewFactory.php
Notes: V2, tapi factory siap
```
```
📌 Buat Factory CompanySettingFactory
Fase: Fase 1-Foundation
Kategori: Factory
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: database/factories/CompanySettingFactory.php
```

---

## 🟠 FASE 2: ESS Attendance (Minggu 2-3)

```
📌 Buat ESS Layout (PWA mobile-first)
Fase: Fase 2-Attendance
Kategori: View
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: resources/views/layouts/ess.blade.php
Notes: Mobile-first layout, bottom nav, header, service worker support
```
```
📌 Livewire ClockIn
Fase: Fase 2-Attendance
Kategori: Livewire
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: app/Livewire/Employee/Attendance/ClockIn.php
Notes: Face capture, GPS validation, submit ke AttendanceService
```
```
📌 View ClockIn
Fase: Fase 2-Attendance
Kategori: View
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: resources/views/employee/attendance/clock-in.blade.php
Notes: Camera preview, face detection overlay, GPS status, clock-in button
```
```
📌 Livewire ClockOut
Fase: Fase 2-Attendance
Kategori: Livewire
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: app/Livewire/Employee/Attendance/ClockOut.php
Notes: Same flow sebagai ClockIn, validasi sudah clock in
```
```
📌 View ClockOut
Fase: Fase 2-Attendance
Kategori: View
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: resources/views/employee/attendance/clock-out.blade.php
Notes: Camera preview, face detection, clock-out button
```
```
📌 Livewire Attendance History
Fase: Fase 2-Attendance
Kategori: Livewire
Priority: 🟡 Medium
Estimasi: 2 jam
Jumlah File: 1
File Path: app/Livewire/Employee/Attendance/History.php
Notes: Monthly filter, pagination, summary stats
```
```
📌 View Attendance History
Fase: Fase 2-Attendance
Kategori: View
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: resources/views/employee/attendance/history.blade.php
Notes: Table/card view per day, status badges, late indicator
```
```
📌 Livewire Attendance Summary
Fase: Fase 2-Attendance
Kategori: Livewire
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Livewire/Employee/Attendance/Summary.php
Notes: Stats: on_time, late, absent, wfa count, total hours
```
```
📌 View Attendance Summary
Fase: Fase 2-Attendance
Kategori: View
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: resources/views/employee/attendance/summary.blade.php
Notes: Cards with stats, chart monthly trend
```
```
📌 face-detection.js
Fase: Fase 2-Attendance
Kategori: JS
Priority: 🔴 High
Estimasi: 3 jam
Jumlah File: 1
File Path: resources/js/face-detection.js
Notes: face-api.js init, load models, detect face, get 128D embedding, send to API
```
```
📌 gps-locator.js
Fase: Fase 2-Attendance
Kategori: JS
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: resources/js/gps-locator.js
Notes: Browser geolocation, accuracy check, mock GPS detection flags
```
```
📌 face-enrollment.js
Fase: Fase 2-Attendance
Kategori: JS
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: resources/js/face-enrollment.js
Notes: Face registration flow, capture multiple angles, send embedding to API
```
```
📌 Form Request StoreAttendanceRequest
Fase: Fase 2-Attendance
Kategori: Request
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: app/Http/Requests/StoreAttendanceRequest.php
Notes: Validate latitude, longitude, face_embedding, is_wfa, wfa_note
```
```
📌 Policy AttendancePolicy
Fase: Fase 2-Attendance
Kategori: Policy
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: app/Policies/AttendancePolicy.php
Notes: view, create, viewAny (self only untuk employee)
```
```
📌 Routes employee.php (attendance section)
Fase: Fase 2-Attendance
Kategori: Route
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: routes/employee.php
Notes: GET/POST clock-in, clock-out, history, summary
```
```
📌 Service Worker PWA
Fase: Fase 2-Attendance
Kategori: PWA
Priority: 🟡 Medium
Estimasi: 2 jam
Jumlah File: 1
File Path: public/sw.js
Notes: Cache static assets, offline fallback, install/activate events
```
```
📌 PWA Manifest
Fase: Fase 2-Attendance
Kategori: PWA
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: public/manifest.json
Notes: name, short_name, icons, start_url, display standalone, theme_color
```

---

## 🟡 FASE 3: ESS Leave & Overtime (Minggu 3-4)

```
📌 Livewire Leave Create
Fase: Fase 3-Leave
Kategori: Livewire
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: app/Livewire/Employee/Leave/Create.php
Notes: Form: leave_type, start_date, end_date, day_type, reason, attachment
```
```
📌 View Leave Create
Fase: Fase 3-Leave
Kategori: View
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: resources/views/employee/leave/create.blade.php
```
```
📌 Livewire Leave History
Fase: Fase 3-Leave
Kategori: Livewire
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Livewire/Employee/Leave/History.php
Notes: Filter by status, year, pagination
```
```
📌 View Leave History
Fase: Fase 3-Leave
Kategori: View
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: resources/views/employee/leave/history.blade.php
```
```
📌 Livewire Leave Quota
Fase: Fase 3-Leave
Kategori: Livewire
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Livewire/Employee/Leave/Quota.php
Notes: Show remaining quota per leave type, carry_forward info
```
```
📌 View Leave Quota
Fase: Fase 3-Leave
Kategori: View
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: resources/views/employee/leave/quota.blade.php
```
```
📌 Livewire Overtime Create
Fase: Fase 3-Leave
Kategori: Livewire
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: app/Livewire/Employee/Overtime/Create.php
Notes: Form: date, start_time, end_time, description
```
```
📌 View Overtime Create
Fase: Fase 3-Leave
Kategori: View
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: resources/views/employee/overtime/create.blade.php
```
```
📌 Livewire Overtime History
Fase: Fase 3-Leave
Kategori: Livewire
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Livewire/Employee/Overtime/History.php
```
```
📌 View Overtime History
Fase: Fase 3-Leave
Kategori: View
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: resources/views/employee/overtime/history.blade.php
```
```
📌 Form Request StoreLeaveRequest
Fase: Fase 3-Leave
Kategori: Request
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: app/Http/Requests/StoreLeaveRequest.php
Notes: Validate dates, day_type, reason, attachment (sick leave only)
```
```
📌 Form Request StoreOvertimeRequest
Fase: Fase 3-Leave
Kategori: Request
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: app/Http/Requests/StoreOvertimeRequest.php
Notes: Validate date (today or past), start_time < end_time, description min 20 chars
```
```
📌 Policy LeavePolicy
Fase: Fase 3-Leave
Kategori: Policy
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: app/Policies/LeavePolicy.php
Notes: create, view, withdraw (owner only), approve (approver only)
```
```
📌 Policy OvertimePolicy
Fase: Fase 3-Leave
Kategori: Policy
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: app/Policies/OvertimePolicy.php
Notes: create, view, withdraw (owner only), approve (approver only)
```

---

## 🟢 FASE 4: ESS Finance & Reimbursement (Minggu 4-5)

```
📌 Livewire PayrollSlip
Fase: Fase 4-Finance
Kategori: Livewire
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: app/Livewire/Employee/Finance/PayrollSlip.php
Notes: View slip per period, earnings/deductions breakdown, download PDF
```
```
📌 View PayrollSlip
Fase: Fase 4-Finance
Kategori: View
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: resources/views/employee/finance/payroll-slip.blade.php
Notes: 2 kolom: Pendapatan vs Potongan, THP besar di bawah
```
```
📌 Livewire ReimbursementRequest
Fase: Fase 4-Finance
Kategori: Livewire
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: app/Livewire/Employee/Finance/ReimbursementRequest.php
Notes: Form: category, amount, description, attachment (JPG/PNG/PDF max 2MB)
```
```
📌 View ReimbursementRequest
Fase: Fase 4-Finance
Kategori: View
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: resources/views/employee/finance/reimbursement-request.blade.php
```
```
📌 Livewire ReimbursementHistory
Fase: Fase 4-Finance
Kategori: Livewire
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Livewire/Employee/Finance/ReimbursementHistory.php
```
```
📌 View ReimbursementHistory
Fase: Fase 4-Finance
Kategori: View
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: resources/views/employee/finance/reimbursement-history.blade.php
```
```
📌 Form Request StoreReimbursementRequest
Fase: Fase 4-Finance
Kategori: Request
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: app/Http/Requests/StoreReimbursementRequest.php
Notes: Validate category_id, amount > 0, description min 20, attachment max 2MB
```
```
📌 Policy ReimbursementPolicy
Fase: Fase 4-Finance
Kategori: Policy
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: app/Policies/ReimbursementPolicy.php
Notes: create, view (self), approve (finance only)
```

---

## 🔵 FASE 5: ESS Profile (Minggu 5)

```
📌 Livewire PersonalInfo
Fase: Fase 5-Profile
Kategori: Livewire
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: app/Livewire/Employee/Profile/PersonalInfo.php
Notes: Update: phone, address, bank_name, bank_account_number, marital_status
```
```
📌 View PersonalInfo
Fase: Fase 5-Profile
Kategori: View
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: resources/views/employee/profile/personal-info.blade.php
```
```
📌 Livewire FamilyDetails
Fase: Fase 5-Profile
Kategori: Livewire
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: app/Livewire/Employee/Profile/FamilyDetails.php
Notes: CRUD family members, encrypted NIK/phone
```
```
📌 View FamilyDetails
Fase: Fase 5-Profile
Kategori: View
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: resources/views/employee/profile/family-details.blade.php
```
```
📌 Livewire Devices
Fase: Fase 5-Profile
Kategori: Livewire
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Livewire/Employee/Profile/Devices.php
Notes: List registered devices, unregister, verify status
```
```
📌 View Devices
Fase: Fase 5-Profile
Kategori: View
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: resources/views/employee/profile/devices.blade.php
```
```
📌 Form Request UpdateProfileRequest
Fase: Fase 5-Profile
Kategori: Request
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: app/Http/Requests/UpdateProfileRequest.php
```
```
📌 Policy EmployeePolicy
Fase: Fase 5-Profile
Kategori: Policy
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: app/Policies/EmployeePolicy.php
Notes: view (self), update (self), viewAny (HRD)
```

---

## 🟣 FASE 6: HRD Admin (Minggu 5-7)

```
📌 HRD Layout
Fase: Fase 6-HRD Admin
Kategori: View
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: resources/views/layouts/hrd.blade.php
Notes: Desktop layout, sidebar nav, topbar, breadcrumb
```
```
📌 Livewire Employees Index
Fase: Fase 6-HRD Admin
Kategori: Livewire
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: app/Livewire/Hrd/Employees/Index.php
Notes: Table with search, filter by department/status, pagination
```
```
📌 View Employees Index
Fase: Fase 6-HRD Admin
Kategori: View
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: resources/views/hrd/employees/index.blade.php
```
```
📌 Livewire Employees Create
Fase: Fase 6-HRD Admin
Kategori: Livewire
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: app/Livewire/Hrd/Employees/Create.php
Notes: Full form: personal info, position, parent_id, employment_type, shift
```
```
📌 View Employees Create
Fase: Fase 6-HRD Admin
Kategori: View
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: resources/views/hrd/employees/create.blade.php
```
```
📌 Livewire Employees Edit
Fase: Fase 6-HRD Admin
Kategori: Livewire
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: app/Livewire/Hrd/Employees/Edit.php
```
```
📌 View Employees Edit
Fase: Fase 6-HRD Admin
Kategori: View
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: resources/views/hrd/employees/edit.blade.php
```
```
📌 Livewire Employees Show
Fase: Fase 6-HRD Admin
Kategori: Livewire
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Livewire/Hrd/Employees/Show.php
Notes: Detail view: info, attendance summary, leave history, approval chain
```
```
📌 View Employees Show
Fase: Fase 6-HRD Admin
Kategori: View
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: resources/views/hrd/employees/show.blade.php
```
```
📌 Livewire Leaves Pending
Fase: Fase 6-HRD Admin
Kategori: Livewire
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: app/Livewire/Hrd/Leaves/Pending.php
Notes: Approve/reject L1/L2, notes, approval chain
```
```
📌 View Leaves Pending
Fase: Fase 6-HRD Admin
Kategori: View
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: resources/views/hrd/leaves/pending.blade.php
```
```
📌 Livewire Leaves Calendar
Fase: Fase 6-HRD Admin
Kategori: Livewire
Priority: 🟡 Medium
Estimasi: 2 jam
Jumlah File: 1
File Path: app/Livewire/Hrd/Leaves/Calendar.php
Notes: Monthly calendar view, color-coded by leave type
```
```
📌 View Leaves Calendar
Fase: Fase 6-HRD Admin
Kategori: View
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: resources/views/hrd/leaves/calendar.blade.php
```
```
📌 Livewire Leaves QuotaManagement
Fase: Fase 6-HRD Admin
Kategori: Livewire
Priority: 🟡 Medium
Estimasi: 2 jam
Jumlah File: 1
File Path: app/Livewire/Hrd/Leaves/QuotaManagement.php
Notes: Adjust quotas, bulk reset, carry_forward management
```
```
📌 View Leaves QuotaManagement
Fase: Fase 6-HRD Admin
Kategori: View
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: resources/views/hrd/leaves/quota-management.blade.php
```
```
📌 Livewire Overtimes Pending
Fase: Fase 6-HRD Admin
Kategori: Livewire
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: app/Livewire/Hrd/Overtimes/Pending.php
```
```
📌 View Overtimes Pending
Fase: Fase 6-HRD Admin
Kategori: View
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: resources/views/hrd/overtimes/pending.blade.php
```
```
📌 Livewire Approvals Pending
Fase: Fase 6-HRD Admin
Kategori: Livewire
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: app/Livewire/Hrd/Approvals/Pending.php
Notes: Combined view: leave + overtime pending approvals
```
```
📌 View Approvals Pending
Fase: Fase 6-HRD Admin
Kategori: View
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: resources/views/hrd/approvals/pending.blade.php
```
```
📌 Livewire Approvals All
Fase: Fase 6-HRD Admin
Kategori: Livewire
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Livewire/Hrd/Approvals/All.php
Notes: Filter by status, type
```
```
📌 View Approvals All
Fase: Fase 6-HRD Admin
Kategori: View
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: resources/views/hrd/approvals/all.blade.php
```
```
📌 Livewire Approvals Escalated
Fase: Fase 6-HRD Admin
Kategori: Livewire
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Livewire/Hrd/Approvals/Escalated.php
Notes: Approvals >24h without action
```
```
📌 View Approvals Escalated
Fase: Fase 6-HRD Admin
Kategori: View
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: resources/views/hrd/approvals/escalated.blade.php
```
```
📌 Livewire Shifts Index
Fase: Fase 6-HRD Admin
Kategori: Livewire
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Livewire/Hrd/Shifts/Index.php
Notes: CRUD shifts, late_tolerance_minutes
```
```
📌 View Shifts Index
Fase: Fase 6-HRD Admin
Kategori: View
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: resources/views/hrd/shifts/index.blade.php
```
```
📌 Livewire Shifts Schedule
Fase: Fase 6-HRD Admin
Kategori: Livewire
Priority: 🟡 Medium
Estimasi: 2 jam
Jumlah File: 1
File Path: app/Livewire/Hrd/Shifts/Schedule.php
Notes: Assign shift to employee per date
```
```
📌 View Shifts Schedule
Fase: Fase 6-HRD Admin
Kategori: View
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: resources/views/hrd/shifts/schedule.blade.php
```
```
📌 Livewire Terminations Pending
Fase: Fase 6-HRD Admin
Kategori: Livewire
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Livewire/Hrd/Terminations/Pending.php
```
```
📌 View Terminations Pending
Fase: Fase 6-HRD Admin
Kategori: View
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: resources/views/hrd/terminations/pending.blade.php
```
```
📌 Livewire Terminations Handover
Fase: Fase 6-HRD Admin
Kategori: Livewire
Priority: 🟡 Medium
Estimasi: 2 jam
Jumlah File: 1
File Path: app/Livewire/Hrd/Terminations/Handover.php
Notes: Create/manage handover items for resigning employee
```
```
📌 View Terminations Handover
Fase: Fase 6-HRD Admin
Kategori: View
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: resources/views/hrd/terminations/handover.blade.php
```
```
📌 Livewire Dashboard Overview
Fase: Fase 6-HRD Admin
Kategori: Livewire
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: app/Livewire/Hrd/Dashboard/Overview.php
Notes: Stats: total employees, present today, pending approvals, absent
```
```
📌 View Dashboard Overview
Fase: Fase 6-HRD Admin
Kategori: View
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: resources/views/hrd/dashboard/overview.blade.php
```
```
📌 Livewire Dashboard AttendanceToday
Fase: Fase 6-HRD Admin
Kategori: Livewire
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Livewire/Hrd/Dashboard/AttendanceToday.php
Notes: Who's in, who's late, who's absent today
```
```
📌 View Dashboard AttendanceToday
Fase: Fase 6-HRD Admin
Kategori: View
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: resources/views/hrd/dashboard/attendance-today.blade.php
```
```
📌 Middleware DeviceDetection
Fase: Fase 6-HRD Admin
Kategori: Middleware
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Http/Middleware/DeviceDetection.php
Notes: Detect device type, browser, OS, register if new
```
```
📌 Middleware GeofenceValidation
Fase: Fase 6-HRD Admin
Kategori: Middleware
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Http/Middleware/GeofenceValidation.php
Notes: Validate GPS on clock-in/out routes
```
```
📌 Middleware ForcePasswordChange
Fase: Fase 6-HRD Admin
Kategori: Middleware
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: app/Http/Middleware/ForcePasswordChange.php
Notes: Redirect to change password if force_password_change = true
```
```
📌 Routes hrd.php
Fase: Fase 6-HRD Admin
Kategori: Route
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: routes/hrd.php
Notes: All HRD routes: employees, leaves, overtimes, approvals, shifts, terminations
```

---

## ⚫ FASE 7: Finance Admin (Minggu 7-8)

```
📌 Finance Layout
Fase: Fase 7-Finance Admin
Kategori: View
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: resources/views/layouts/finance.blade.php
Notes: Extend hrd layout or separate
```
```
📌 Livewire Payroll Index
Fase: Fase 7-Finance Admin
Kategori: Livewire
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: app/Livewire/Finance/Payroll/Index.php
Notes: List payroll periods, status filter
```
```
📌 View Payroll Index
Fase: Fase 7-Finance Admin
Kategori: View
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: resources/views/finance/payroll/index.blade.php
```
```
📌 Livewire Payroll Generate
Fase: Fase 7-Finance Admin
Kategori: Livewire
Priority: 🔴 High
Estimasi: 3 jam
Jumlah File: 1
File Path: app/Livewire/Finance/Payroll/Generate.php
Notes: Select period, preview calculation, generate
```
```
📌 View Payroll Generate
Fase: Fase 7-Finance Admin
Kategori: View
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: resources/views/finance/payroll/generate.blade.php
```
```
📌 Livewire Payroll Detail
Fase: Fase 7-Finance Admin
Kategori: Livewire
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Livewire/Finance/Payroll/Detail.php
Notes: Per-employee breakdown
```
```
📌 View Payroll Detail
Fase: Fase 7-Finance Admin
Kategori: View
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: resources/views/finance/payroll/detail.blade.php
```
```
📌 Livewire Payroll Publish
Fase: Fase 7-Finance Admin
Kategori: Livewire
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Livewire/Finance/Payroll/Publish.php
Notes: Publish = lock permanently, send notifications
```
```
📌 View Payroll Publish
Fase: Fase 7-Finance Admin
Kategori: View
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: resources/views/finance/payroll/publish.blade.php
```
```
📌 Livewire Payroll BulkGenerate
Fase: Fase 7-Finance Admin
Kategori: Livewire
Priority: 🟡 Medium
Estimasi: 2 jam
Jumlah File: 1
File Path: app/Livewire/Finance/Payroll/BulkGenerate.php
Notes: Queue-based bulk generation via ProcessPayrollGeneration job
```
```
📌 View Payroll BulkGenerate
Fase: Fase 7-Finance Admin
Kategori: View
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: resources/views/finance/payroll/bulk-generate.blade.php
```
```
📌 Livewire Payroll Adjustment
Fase: Fase 7-Finance Admin
Kategori: Livewire
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: app/Livewire/Finance/Payroll/Adjustment.php
Notes: Create adjustment for locked payroll, applied to next period
```
```
📌 View Payroll Adjustment
Fase: Fase 7-Finance Admin
Kategori: View
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: resources/views/finance/payroll/adjustment.blade.php
```
```
📌 Livewire Reimbursements Pending
Fase: Fase 7-Finance Admin
Kategori: Livewire
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: app/Livewire/Finance/Reimbursements/Pending.php
Notes: L2 approval for reimbursements
```
```
📌 View Reimbursements Pending
Fase: Fase 7-Finance Admin
Kategori: View
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: resources/views/finance/reimbursements/pending.blade.php
```
```
📌 Livewire Reports Payroll
Fase: Fase 7-Finance Admin
Kategori: Livewire
Priority: 🟡 Medium
Estimasi: 2 jam
Jumlah File: 1
File Path: app/Livewire/Finance/Reports/Payroll.php
Notes: Payroll report per period, export
```
```
📌 View Reports Payroll
Fase: Fase 7-Finance Admin
Kategori: View
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: resources/views/finance/reports/payroll.blade.php
```
```
📌 Livewire Reports Tax
Fase: Fase 7-Finance Admin
Kategori: Livewire
Priority: 🟡 Medium
Estimasi: 2 jam
Jumlah File: 1
File Path: app/Livewire/Finance/Reports/Tax.php
Notes: PPh21 + BPJS report per year
```
```
📌 View Reports Tax
Fase: Fase 7-Finance Admin
Kategori: View
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: resources/views/finance/reports/tax.blade.php
```
```
📌 Command GeneratePayroll
Fase: Fase 7-Finance Admin
Kategori: Command
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: app/Console/Commands/GeneratePayroll.php
Notes: Signature payroll:generate {period}, dispatch jobs
```
```
📌 Form Request StorePayrollRequest
Fase: Fase 7-Finance Admin
Kategori: Request
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: app/Http/Requests/StorePayrollRequest.php
Notes: Validate period, employee selection
```
```
📌 Form Request UpdatePayrollRequest
Fase: Fase 7-Finance Admin
Kategori: Request
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: app/Http/Requests/UpdatePayrollRequest.php
Notes: Validate adjustments (only if not locked)
```
```
📌 Routes finance.php
Fase: Fase 7-Finance Admin
Kategori: Route
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: routes/finance.php
Notes: All finance routes: payroll, reimbursements, reports
```

---

## 🟤 FASE 8: KnowledgeBase AI (Minggu 8-9)

```
📌 Livewire KnowledgeBase Index
Fase: Fase 8-KnowledgeBase
Kategori: Livewire
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Livewire/Hrd/KnowledgeBase/Index.php
Notes: List articles, search, filter by category
```
```
📌 View KnowledgeBase Index
Fase: Fase 8-KnowledgeBase
Kategori: View
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: resources/views/hrd/knowledge-base/index.blade.php
```
```
📌 Livewire KnowledgeBase Create
Fase: Fase 8-KnowledgeBase
Kategori: Livewire
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: app/Livewire/Hrd/KnowledgeBase/Create.php
Notes: Upload PDF, dispatch chunking job, generate embeddings
```
```
📌 View KnowledgeBase Create
Fase: Fase 8-KnowledgeBase
Kategori: View
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: resources/views/hrd/knowledge-base/create.blade.php
```
```
📌 Livewire KnowledgeBase Edit
Fase: Fase 8-KnowledgeBase
Kategori: Livewire
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Livewire/Hrd/KnowledgeBase/Edit.php
Notes: Update title, category, re-process embeddings
```
```
📌 View KnowledgeBase Edit
Fase: Fase 8-KnowledgeBase
Kategori: View
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: resources/views/hrd/knowledge-base/edit.blade.php
```
```
📌 Livewire KnowledgeBase Chat
Fase: Fase 8-KnowledgeBase
Kategori: Livewire
Priority: 🔴 High
Estimasi: 3 jam
Jumlah File: 1
File Path: app/Livewire/Hrd/KnowledgeBase/Chat.php
Notes: Chat interface, query embeddings via Gemini/OpenAI, show sources, fallback to pg_trgm
```
```
📌 View KnowledgeBase Chat
Fase: Fase 8-KnowledgeBase
Kategori: View
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: resources/views/hrd/knowledge-base/chat.blade.php
Notes: Chat UI, streaming response, source citations
```
```
📌 API Controller KnowledgeBaseController
Fase: Fase 8-KnowledgeBase
Kategori: JS
Priority: 🟡 Medium
Estimasi: 2 jam
Jumlah File: 1
File Path: app/Http/Controllers/Api/KnowledgeBaseController.php
Notes: POST /api/v1/knowledge-base/query, GET /api/v1/knowledge-base/search
```

---

## ⚪ FASE 9: Admin Settings & User Management (Minggu 9-10)

```
📌 Livewire Settings Company
Fase: Fase 9-Settings
Kategori: Livewire
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Livewire/Settings/Company.php
Notes: Update company info, NPWP, logo
```
```
📌 View Settings Company
Fase: Fase 9-Settings
Kategori: View
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: resources/views/settings/company.blade.php
```
```
📌 Livewire Settings Attendance
Fase: Fase 9-Settings
Kategori: Livewire
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Livewire/Settings/Attendance.php
Notes: face_similarity_threshold, payroll_cutoff_date, wfa_note_min_chars
```
```
📌 View Settings Attendance
Fase: Fase 9-Settings
Kategori: View
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: resources/views/settings/attendance.blade.php
```
```
📌 Livewire Settings Leave
Fase: Fase 9-Settings
Kategori: Livewire
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Livewire/Settings/Leave.php
Notes: leave_carry_forward_max, carry_forward_deadline
```
```
📌 View Settings Leave
Fase: Fase 9-Settings
Kategori: View
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: resources/views/settings/leave.blade.php
```
```
📌 Livewire Settings Branding
Fase: Fase 9-Settings
Kategori: Livewire
Priority: 🟢 Low
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Livewire/Settings/Branding.php
Notes: app_name, logo, primary_color, favicon
```
```
📌 View Settings Branding
Fase: Fase 9-Settings
Kategori: View
Priority: 🟢 Low
Estimasi: 0.5 jam
Jumlah File: 1
File Path: resources/views/settings/branding.blade.php
```
```
📌 Livewire Settings Security
Fase: Fase 9-Settings
Kategori: Livewire
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Livewire/Settings/Security.php
Notes: session_timeout, password_policy, 2fa_required
```
```
📌 View Settings Security
Fase: Fase 9-Settings
Kategori: View
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: resources/views/settings/security.blade.php
```
```
📌 Livewire Settings System
Fase: Fase 9-Settings
Kategori: Livewire
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Livewire/Settings/System.php
Notes: bpjs_ceiling, overtime_multiplier, tax configs
```
```
📌 View Settings System
Fase: Fase 9-Settings
Kategori: View
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: resources/views/settings/system.blade.php
```
```
📌 Livewire Users Index
Fase: Fase 9-Settings
Kategori: Livewire
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Livewire/Users/Index.php
Notes: List users, role assignment, search
```
```
📌 View Users Index
Fase: Fase 9-Settings
Kategori: View
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: resources/views/users/index.blade.php
```
```
📌 Livewire Users Create
Fase: Fase 9-Settings
Kategori: Livewire
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Livewire/Users/Create.php
Notes: Create user + assign roles
```
```
📌 View Users Create
Fase: Fase 9-Settings
Kategori: View
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: resources/views/users/create.blade.php
```
```
📌 Livewire Users Edit
Fase: Fase 9-Settings
Kategori: Livewire
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Livewire/Users/Edit.php
Notes: Update user info, roles, reset password
```
```
📌 View Users Edit
Fase: Fase 9-Settings
Kategori: View
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: resources/views/users/edit.blade.php
```
```
📌 Livewire ActivityLog Index
Fase: Fase 9-Settings
Kategori: Livewire
Priority: 🟢 Low
Estimasi: 1 jam
Jumlah File: 1
File Path: app/Livewire/Admin/ActivityLog/Index.php
Notes: Filter by date, user, log_name, pagination
```
```
📌 View ActivityLog Index
Fase: Fase 9-Settings
Kategori: View
Priority: 🟢 Low
Estimasi: 0.5 jam
Jumlah File: 1
File Path: resources/views/admin/activity-log/index.blade.php
```
```
📌 Config hrconnect.php
Fase: Fase 9-Settings
Kategori: Config
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: config/hrconnect.php
Notes: Custom app config: defaults, feature flags, mock mode
```
```
📌 Routes admin.php
Fase: Fase 9-Settings
Kategori: Route
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: routes/admin.php
Notes: All admin routes: settings, users, activity-log
```
```
📌 Update routes/web.php
Fase: Fase 9-Settings
Kategori: Route
Priority: 🔴 High
Estimasi: 0.5 jam
Jumlah File: 1
File Path: routes/web.php
Notes: Include employee.php, hrd.php, finance.php, admin.php
```

---

## 🟢 FASE 10: Testing (Minggu 10-11)

```
📌 Test: ClockIn Feature
Fase: Fase 10-Testing
Kategori: Test
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: tests/Feature/Attendance/ClockInTest.php
Notes: Clock in success, face mismatch, outside geofence, already clocked in
```
```
📌 Test: ClockOut Feature
Fase: Fase 10-Testing
Kategori: Test
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: tests/Feature/Attendance/ClockOutTest.php
Notes: Clock out success, not clocked in, face mismatch
```
```
📌 Test: LeaveRequest Feature
Fase: Fase 10-Testing
Kategori: Test
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: tests/Feature/Leave/LeaveRequestTest.php
Notes: Submit leave, quota check, probation block, withdraw
```
```
📌 Test: LeaveApproval Feature
Fase: Fase 10-Testing
Kategori: Test
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: tests/Feature/Leave/LeaveApprovalTest.php
Notes: L1 approve, L2 approve, reject, quota deduction
```
```
📌 Test: LeaveQuota Feature
Fase: Fase 10-Testing
Kategori: Test
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: tests/Feature/Leave/LeaveQuotaTest.php
Notes: Quota calculation, carry_forward, yearly reset
```
```
📌 Test: OvertimeRequest Feature
Fase: Fase 10-Testing
Kategori: Test
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: tests/Feature/Overtime/OvertimeRequestTest.php
Notes: Submit, approve, reject, withdraw
```
```
📌 Test: Reimbursement Feature
Fase: Fase 10-Testing
Kategori: Test
Priority: 🟡 Medium
Estimasi: 2 jam
Jumlah File: 1
File Path: tests/Feature/Reimbursement/ReimbursementTest.php
Notes: Submit, upload attachment, approve L1/L2, payroll integration
```
```
📌 Test: PayrollGeneration Feature
Fase: Fase 10-Testing
Kategori: Test
Priority: 🔴 High
Estimasi: 3 jam
Jumlah File: 1
File Path: tests/Feature/Payroll/PayrollGenerationTest.php
Notes: Generate, pro-rated salary, PPh21, BPJS, overtime, reimbursement
```
```
📌 Test: PayrollLock Feature
Fase: Fase 10-Testing
Kategori: Test
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: tests/Feature/Payroll/PayrollLockTest.php
Notes: Lock permanent, cannot edit after lock, adjustment only
```
```
📌 Test: PayrollAdjustment Feature
Fase: Fase 10-Testing
Kategori: Test
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: tests/Feature/Payroll/PayrollAdjustmentTest.php
Notes: Create adjustment, apply to next period, amount +/- 
```
```
📌 Test: KnowledgeBase RAG Feature
Fase: Fase 10-Testing
Kategori: Test
Priority: 🟡 Medium
Estimasi: 2 jam
Jumlah File: 1
File Path: tests/Feature/KnowledgeBase/RagTest.php
Notes: Upload PDF, query, sources, fallback text search
```
```
📌 Test: FaceRegistration Feature
Fase: Fase 10-Testing
Kategori: Test
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: tests/Feature/Auth/FaceRegistrationTest.php
Notes: Register face, validate similarity, threshold check
```
```
📌 Test: EmployeeCrud Feature
Fase: Fase 10-Testing
Kategori: Test
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: tests/Feature/Hrd/EmployeeCrudTest.php
Notes: Create, update, auto employee number, parent_id
```
```
📌 Test: ApprovalWorkflow Feature
Fase: Fase 10-Testing
Kategori: Test
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: tests/Feature/Hrd/ApprovalWorkflowTest.php
Notes: 2-level chain, escalation, reassignment on resign
```
```
📌 Test: Termination Feature
Fase: Fase 10-Testing
Kategori: Test
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: tests/Feature/Hrd/TerminationTest.php
Notes: Resign, handover, reassign subordinates, wasChanged()
```
```
📌 Test: PayrollCalculator Unit
Fase: Fase 10-Testing
Kategori: Test
Priority: 🔴 High
Estimasi: 2 jam
Jumlah File: 1
File Path: tests/Unit/PayrollCalculatorTest.php
Notes: PPh21 TER, BPJS, pro-rated, division by zero
```
```
📌 Test: GeofenceService Unit
Fase: Fase 10-Testing
Kategori: Test
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: tests/Unit/GeofenceServiceTest.php
Notes: Haversine calc, within/outside radius, mock GPS
```
```
📌 Test: LeaveQuota Unit
Fase: Fase 10-Testing
Kategori: Test
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: tests/Unit/LeaveQuotaTest.php
Notes: Working days calc, exclude weekend/holiday
```
```
📌 Test: AttendanceService Unit
Fase: Fase 10-Testing
Kategori: Test
Priority: 🟡 Medium
Estimasi: 1 jam
Jumlah File: 1
File Path: tests/Unit/AttendanceServiceTest.php
Notes: Clock in/out logic, alpha detection, WFA
```
```
📌 Test: FaceValidation Unit
Fase: Fase 10-Testing
Kategori: Test
Priority: 🔴 High
Estimasi: 1 jam
Jumlah File: 1
File Path: tests/Unit/FaceValidationTest.php
Notes: Cosine similarity, threshold, embedding comparison
```

---

## 🔵 FASE 11: Language & Polish (Minggu 11-12)

```
📌 Translation: attendance.php
Fase: Fase 11-Polish
Kategori: Lang
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: lang/id/attendance.php
Notes: All attendance labels, statuses, messages
```
```
📌 Translation: leave.php
Fase: Fase 11-Polish
Kategori: Lang
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: lang/id/leave.php
Notes: Leave types, statuses, messages
```
```
📌 Translation: overtime.php
Fase: Fase 11-Polish
Kategori: Lang
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: lang/id/overtime.php
Notes: Overtime labels, statuses
```
```
📌 Translation: reimbursement.php
Fase: Fase 11-Polish
Kategori: Lang
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: lang/id/reimbursement.php
Notes: Categories, statuses, messages
```
```
📌 Translation: payroll.php
Fase: Fase 11-Polish
Kategori: Lang
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: lang/id/payroll.php
Notes: Earnings, deductions, labels
```
```
📌 Translation: knowledgebase.php
Fase: Fase 11-Polish
Kategori: Lang
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: lang/id/knowledgebase.php
Notes: AI chat labels, categories
```
```
📌 Translation: employee.php
Fase: Fase 11-Polish
Kategori: Lang
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: lang/id/employee.php
Notes: Employment types, positions, labels
```
```
📌 Translation: settings.php
Fase: Fase 11-Polish
Kategori: Lang
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: lang/id/settings.php
Notes: Settings labels, descriptions
```
```
📌 Translation: notification.php
Fase: Fase 11-Polish
Kategori: Lang
Priority: 🟡 Medium
Estimasi: 0.5 jam
Jumlah File: 1
File Path: lang/id/notification.php
Notes: Notification titles, messages
```

---

## 📊 REKAP TOTAL

| Fase | Kategori Tasks | Jumlah Card | Estimasi Total |
|------|---------------|-------------|----------------|
| Fase 1 - Foundation | Models, Migrations, Services, Observers, Jobs, Commands, Notifications, Seeders, Factories | **92** | ~70 jam |
| Fase 2 - Attendance | Livewire, Views, JS, Routes, Policies, PWA | **17** | ~25 jam |
| Fase 3 - Leave & Overtime | Livewire, Views, Requests, Policies | **14** | ~14 jam |
| Fase 4 - Finance & Reimbursement | Livewire, Views, Requests, Policies | **8** | ~9 jam |
| Fase 5 - Profile | Livewire, Views, Requests, Policies | **8** | ~8 jam |
| Fase 6 - HRD Admin | Livewire, Views, Middleware, Routes | **40** | ~40 jam |
| Fase 7 - Finance Admin | Livewire, Views, Requests, Command, Routes | **23** | ~25 jam |
| Fase 8 - KnowledgeBase | Livewire, Views, Controller | **9** | ~12 jam |
| Fase 9 - Settings | Livewire, Views, Config, Routes | **23** | ~18 jam |
| Fase 10 - Testing | Feature Tests, Unit Tests | **20** | ~32 jam |
| Fase 11 - Polish | Translation files | **9** | ~4.5 jam |
| **TOTAL** | | **263** | **~257 jam** |

---

> **Last Updated:** 2026-05-08
> **Version:** 1.0
> **Status:** Ready to import to Notion
