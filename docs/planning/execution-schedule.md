# HRConnect - Execution Schedule (Jadwal Pengerjaan 12 Minggu)

> **Dokumen ini berisi jadwal pengerjaan lengkap untuk 12 minggu development HRConnect.**
> Estimasi: ~9 jam/hari, 6 hari/minggu.
> **Update terakhir: 2026-05-31** — Progress aktual + redistribusi + errata notes.

> **CATATAN ESTIMASI FILE:** Total ~353 file di dokumen ini adalah granularitas mingguan, berbeda dengan `sprint-branch-strategy.md` (~408 file granular per-sprint) dan `reference/complete-file-blueprint.md` (~341 baru + ~12 modifikasi). **Sumber kebenaran progress per-item**: `planning/task.md` v4.5.

---

## ⚠️ ERRATA — Critical Issues Found (2026-05-13)

Issues below were identified during comprehensive audit and must be resolved before Phase 1 can start. See `docs/PRD-errata.md` and `docs/planning/task.md` for full details.

| # | Issue | Impact | Fix |
|---|-------|--------|-----|
| C1 | `$approval->level === 1` always false (enum vs int) | APPROVED_L1 never reached | Use `$approval->level->value === 1` or `=== ApprovalLevel::L1_SUPERVISOR` |
| C2 | `$existingPayroll->delete()` only sets `deleted_at` | Unique constraint violation on regenerate | Use `forceDelete()` |
| C3 | `laravel/sanctum` not installed | API auth = 0% | Install Sanctum + configure |
| C4 | Permission enum + seeders missing | `$user->can()` always false | Create Permission enum + RoleAndPermissionSeeder |
| SEC-5 | BusinessRuleException extends Exception (500) | Wrong HTTP code for business errors | Change to HttpException (422) |

---

## 📅 OVERVIEW

| Phase | Minggu | Fokus | Target | Progress |
|-------|--------|-------|--------|:---:|
| **Phase 1** | 1-2 | Foundation | Backend siap (Models, Services, Migrations) | 🟢 85% |
| **Phase 2** | 3-4 | ESS Frontend | Employee bisa clock in, ajukan cuti, lihat gaji | ⬜ 0% |
| **Phase 3** | 5-6 | HRD & Finance | Admin bisa manage semua modul | ⬜ 0% |
| **Phase 4** | 7-8 | Admin & Polish | Super admin, PWA, UI polish | ⬜ 0% |
| **Phase 5** | 9-10 | Testing | Semua fitur ditest, bug fixed | ⬜ 0% |
| **Phase 6** | 11-12 | Deploy | Production ready, dokumentasi final | ⬜ 0% |

---

## MINGGU 1: FOUNDATION - ENUMS, MIGRATIONS, MODELS

### Hari 1-2: Enums & Migrations
| Task | Files | Estimasi |
|------|-------|----------|
| Buat 11 Enum baru | `app/Enums/*.php` (11 files) | 2 jam |
| Buat 16 Migration baru | `database/migrations/*.php` (16 files) | 3 jam |
| Verifikasi 33 migration existing | Cek schema vs ERD | 1 jam |
| Run migrations + test | `php artisan migrate` | 1 jam |
| **Total** | **27 files** | **7 jam** |

### Hari 3-4: Models
| Task | Files | Estimasi |
|------|-------|----------|
| Buat 7 Model baru | `app/Models/*.php` (7 files) | 2 jam |
| Update 7 Model existing | Modify existing models | 2 jam |
| Setup semua relationships | BelongsTo, HasMany, etc. | 2 jam |
| Setup accessors & mutators | Get/set attributes | 1 jam |
| **Total** | **13 files** | **7 jam** |

### Hari 5-6: Seeders & Config
| Task | Files | Estimasi |
|------|-------|----------|
| Buat 11 Seeder baru | `database/seeders/*.php` (11 files) | 3 jam |
| Update DatabaseSeeder | Call semua seeder baru | 1 jam |
| Buat 2 Config baru | `config/hrconnect.php`, `ciphersweet.php` | 1 jam |
| Jalankan semua seeder | `php artisan db:seed` | 1 jam |
| Verifikasi data seed | Cek database | 1 jam |
| **Total** | **14 files** | **7 jam** |

### Minggu 1 Deliverables
- ✅ 11 Enums created
- ✅ 16 Migrations created & run
- ✅ 7 Models created
- ✅ 7 Models updated
- ✅ 11 Seeders created & run
- ✅ 2 Config files created
- ✅ Database fully populated with seed data

---

## MINGGU 2: FOUNDATION - SERVICES, OBSERVERS, COMMANDS

### Hari 1-2: Services
| Task | Files | Estimasi |
|------|-------|----------|
| AttendanceService | `app/Services/AttendanceService.php` | 2 jam |
| LeaveService | `app/Services/LeaveService.php` | 2 jam |
| GeofenceService | `app/Services/GeofenceService.php` | 1 jam |
| PayrollCalculatorService | `app/Services/PayrollCalculatorService.php` | 2 jam |
| **Total** | **4 files** | **7 jam** |

### Hari 3-4: More Services + Observers
| Task | Files | Estimasi |
|------|-------|----------|
| ApprovalService | `app/Services/ApprovalService.php` | 2 jam |
| EmployeeTerminationService | `app/Services/EmployeeTerminationService.php` | 2 jam |
| FaceRecognitionService | `app/Services/FaceRecognitionService.php` | 1 jam |
| DeviceDetectionService | `app/Services/DeviceDetectionService.php` | 1 jam |
| Buat 6 Observers | `app/Observers/*.php` (6 files) | 1 jam |
| **Total** | **10 files** | **7 jam** |

### Hari 5-6: Commands, Jobs, Notifications
| Task | Files | Estimasi |
|------|-------|----------|
| Buat 7 Commands | `app/Console/Commands/*.php` (7 files) | 3 jam |
| Buat 9 Jobs | `app/Jobs/*.php` (9 files) | 2 jam |
| Buat 12 Notifications | `app/Notifications/*.php` (12 files) | 2 jam |
| **Total** | **28 files** | **7 jam** |

### Minggu 2 Deliverables
- ✅ 8 Services created
- ✅ 6 Observers created & registered
- ✅ 7 Commands created
- ✅ 9 Jobs created
- ✅ 12 Notifications created
- ✅ All business logic implemented
- ✅ Observer pattern working

---

## MINGGU 3: ESS FRONTEND - ATTENDANCE & LEAVE

### Hari 1-2: ESS Layout & Attendance
| Task | Files | Estimasi |
|------|-------|----------|
| Buat ESS Layout | `resources/views/layouts/ess.blade.php` | 1 jam |
| ClockIn Livewire | `app/Livewire/Employee/Attendance/ClockIn.php` + view | 2 jam |
| ClockOut Livewire | `app/Livewire/Employee/Attendance/ClockOut.php` + view | 2 jam |
| Face capture JS | `resources/js/face-detection.js` | 1 jam |
| GPS locator JS | `resources/js/gps-locator.js` | 1 jam |
| **Total** | **7 files** | **7 jam** |

### Hari 3-4: Attendance History & Leave
| Task | Files | Estimasi |
|------|-------|----------|
| Attendance History | Livewire + view (2 files) | 2 jam |
| Attendance Summary | Livewire + view (2 files) | 1 jam |
| Leave Create | Livewire + view (2 files) | 2 jam |
| Leave History | Livewire + view (2 files) | 1 jam |
| Leave Quota | Livewire + view (2 files) | 1 jam |
| **Total** | **10 files** | **7 jam** |

### Hari 5-6: Overtime & Routes
| Task | Files | Estimasi |
|------|-------|----------|
| Overtime Create | Livewire + view (2 files) | 2 jam |
| Overtime History | Livewire + view (2 files) | 1 jam |
| Buat routes/employee.php | Route definitions | 1 jam |
| Update routes/web.php | Include employee routes | 0.5 jam |
| StoreLeaveRequest | `app/Http/Requests/StoreLeaveRequest.php` | 1 jam |
| StoreOvertimeRequest | `app/Http/Requests/StoreOvertimeRequest.php` | 1 jam |
| Employee Policy | `app/Policies/EmployeePolicy.php` | 1 jam |
| Attendance Policy | `app/Policies/AttendancePolicy.php` | 1 jam |
| Leave Policy | `app/Policies/LeavePolicy.php` | 0.5 jam |
| Middleware setup | DeviceDetection + Geofence | 1 jam |
| **Total** | **12 files** | **10 jam** |

### Minggu 3 Deliverables
- ✅ ESS mobile layout working
- ✅ Clock in/out dengan face + GPS
- ✅ Attendance history & summary
- ✅ Leave request, history, quota
- ✅ Overtime request & history
- ✅ Employee routes configured
- ✅ Form requests + policies created
- ✅ Device detection middleware

---

## MINGGU 4: ESS FRONTEND - FINANCE & PROFILE

### Hari 1-2: Employee Finance
| Task | Files | Estimasi |
|------|-------|----------|
| ReimbursementRequest | Livewire + view (2 files) | 2 jam |
| PayrollSlip | Livewire + view (2 files) | 2 jam |
| Reimbursement Policy | `app/Policies/ReimbursementPolicy.php` | 1 jam |
| **Total** | **5 files** | **5 jam** |

### Hari 3-4: Employee Profile
| Task | Files | Estimasi |
|------|-------|----------|
| PersonalInfo | Livewire + view (2 files) | 2 jam |
| FamilyDetails | Livewire + view (2 files) | 2 jam |
| FaceRegistration | Livewire + view (2 files) | 1.5 jam |
| Devices | Livewire + view (2 files) | 1.5 jam |
| **Total** | **8 files** | **7 jam** |

### Hari 5-6: ESS Dashboard & Shared Components
| Task | Files | Estimasi |
|------|-------|----------|
| ESS Dashboard | View + nav partials (3 files) | 2 jam |
| Notifications Component | Livewire + view (2 files) | 1 jam |
| ApprovalTimeline Component | View component (1 file) | 1 jam |
| FileUpload Component | Livewire + view (2 files) | 1.5 jam |
| StatusBadge Component | View component (1 file) | 0.5 jam |
| Form Requests | StoreReimbursement, UpdateProfile, StoreDevice (3 files) | 2 jam |
| **Total** | **13 files** | **7 jam** |

### Minggu 4 Deliverables
- ✅ Reimbursement request
- ✅ Payroll slip view
- ✅ Profile editing (secondary data)
- ✅ Family details CRUD
- ✅ Face registration
- ✅ Device management
- ✅ ESS dashboard complete
- ✅ Shared components working

---

## MINGGU 5: HRD ADMIN - DASHBOARD & EMPLOYEES

### Hari 1-2: HRD Layout & Dashboard
| Task | Files | Estimasi |
|------|-------|----------|
| HRD Layout | `resources/views/layouts/hrd.blade.php` | 1 jam |
| HRD Nav Partial | `resources/views/partials/hrd-nav.blade.php` | 0.5 jam |
| Dashboard Overview | Livewire + view (2 files) | 2 jam |
| AttendanceToday | Livewire + view (2 files) | 2 jam |
| DataCard Component | View component (1 file) | 0.5 jam |
| DataTable Component | Livewire + view (2 files) | 1 jam |
| **Total** | **9 files** | **7 jam** |

### Hari 3-4: Employee Management
| Task | Files | Estimasi |
|------|-------|----------|
| Employee Index | Livewire + view (2 files) | 2 jam |
| Employee Create | Livewire + view + request (3 files) | 2 jam |
| Employee Edit | Livewire + view + request (3 files) | 2 jam |
| Employee Show | View (1 file) | 1 jam |
| **Total** | **9 files** | **7 jam** |

### Hari 5-6: Bulk Upload & Routes
| Task | Files | Estimasi |
|------|-------|----------|
| BulkUpload | Livewire + view (2 files) | 2 jam |
| Employee Form Requests | StoreEmployee, UpdateEmployee (2 files) | 1 jam |
| Buat routes/hrd.php | Route definitions | 1 jam |
| HRD Policies | Update EmployeePolicy, AttendancePolicy, LeavePolicy | 1 jam |
| Middleware | CheckRole + CheckPermission | 1 jam |
| Shared Components | EmptyState, LoadingSpinner, ConfirmationModal (3 files) | 1 jam |
| **Total** | **10 files** | **7 jam** |

### Minggu 5 Deliverables
- ✅ HRD admin layout
- ✅ HRD dashboard with stats
- ✅ Employee CRUD (create, read, update, delete)
- ✅ Employee bulk upload
- ✅ Employee detail view
- ✅ HRD routes configured
- ✅ Role/permission middleware

---

## MINGGU 6: HRD ADMIN - APPROVALS, LEAVES, SHIFTS, TERMINATIONS

### Hari 1-2: Approvals
| Task | Files | Estimasi |
|------|-------|----------|
| Pending Approvals | Livewire + view (2 files) | 2 jam |
| All Approvals | Livewire + view (2 files) | 2 jam |
| Escalated Approvals | Livewire + view (2 files) | 1.5 jam |
| Approval Policy | `app/Policies/ApprovalPolicy.php` | 0.5 jam |
| Approval Request | StoreApprovalRequest | 1 jam |
| **Total** | **8 files** | **7 jam** |

### Hari 3-4: Leaves & Shifts
| Task | Files | Estimasi |
|------|-------|----------|
| Leave Pending | Livewire + view (2 files) | 2 jam |
| Leave Calendar | Livewire + view (2 files) | 2 jam |
| QuotaManagement | Livewire + view (2 files) | 1.5 jam |
| Shift Index | Livewire + view (2 files) | 1.5 jam |
| **Total** | **8 files** | **7 jam** |

### Hari 5-6: Terminations & Reports
| Task | Files | Estimasi |
|------|-------|----------|
| Termination Pending | Livewire + view (2 files) | 2 jam |
| Handover Management | Livewire + view (2 files) | 2 jam |
| Reassignment | Livewire + view (2 files) | 1.5 jam |
| Resignation Request | StoreResignationRequest | 0.5 jam |
| HRD Reports | Attendance, Leave, Employee (3 views) | 1 jam |
| **Total** | **10 files** | **7 jam** |

### Minggu 6 Deliverables
- ✅ Approval management (pending, all, escalated)
- ✅ Leave management (pending, calendar, quota)
- ✅ Shift management
- ✅ Resignation & handover
- ✅ Approval reassignment
- ✅ HRD reports (attendance, leave, employee)

---

## MINGGU 7: FINANCE ADMIN - PAYROLL & LOANS

### Hari 1-2: Finance Layout & Dashboard
| Task | Files | Estimasi |
|------|-------|----------|
| Finance Layout | `resources/views/layouts/finance.blade.php` | 1 jam |
| Finance Nav Partial | `resources/views/partials/finance-nav.blade.php` | 0.5 jam |
| Finance Dashboard | Livewire + view (2 files) | 2 jam |
| Routes finance.php | Route definitions | 1 jam |
| **Total** | **5 files** | **4.5 jam** + polish |

### Hari 3-4: Payroll
| Task | Files | Estimasi |
|------|-------|----------|
| Payroll Index | Livewire + view (2 files) | 2 jam |
| Payroll Generate | Livewire + view (2 files) | 2 jam |
| Payroll Detail | Livewire + view (2 files) | 2 jam |
| Payroll Publish | Livewire + view (2 files) | 1 jam |
| **Total** | **8 files** | **7 jam** |

### Hari 5-6: Bulk Generate, Loans, Reimbursements
| Task | Files | Estimasi |
|------|-------|----------|
| BulkGenerate | Livewire + view (2 files) | 2 jam |
| Loan Pending | Livewire + view (2 files) | 1.5 jam |
| Loan Installments | Livewire + view (2 files) | 1.5 jam |
| Loan Report | View (1 file) | 0.5 jam |
| Reimbursement Pending | Livewire + view (2 files) | 1.5 jam |
| Reimbursement Report | View (1 file) | 0.5 jam |
| **Total** | **10 files** | **7 jam** |

### Minggu 7 Deliverables
- ✅ Finance admin layout
- ✅ Finance dashboard
- ✅ Payroll management (index, generate, detail, publish)
- ✅ Bulk payroll generation via queue
- ✅ Loan management (pending, installments, report)
- ✅ Reimbursement management

---

## MINGGU 8: SUPER ADMIN & KNOWLEDGE BASE

### Hari 1-2: Admin Layout & Settings
| Task | Files | Estimasi |
|------|-------|----------|
| Admin Layout | `resources/views/layouts/admin.blade.php` | 1 jam |
| Admin Nav Partial | `resources/views/partials/admin-nav.blade.php` | 0.5 jam |
| Admin Dashboard | Livewire + view (2 files) | 1.5 jam |
| Company Settings | Livewire + view (2 files) | 2 jam |
| **Total** | **6 files** | **5 jam** + polish |

### Hari 3-4: More Settings & Users
| Task | Files | Estimasi |
|------|-------|----------|
| Attendance Settings | Livewire + view (2 files) | 1.5 jam |
| Leave Settings | Livewire + view (2 files) | 1.5 jam |
| Branding Settings | Livewire + view (2 files) | 1.5 jam |
| Security Settings | Livewire + view (2 files) | 1.5 jam |
| User Management | Index, Create, Edit (6 files) | 1 jam |
| **Total** | **14 files** | **7 jam** |

### Hari 5-6: Knowledge Base & Activity Log
| Task | Files | Estimasi |
|------|-------|----------|
| KB Index | Livewire + view (2 files) | 1.5 jam |
| KB Create | Livewire + view (2 files) | 2 jam |
| KB Edit | Livewire + view (2 files) | 1.5 jam |
| KB Chat | Livewire + view (2 files) | 2 jam |
| Activity Log | Livewire + view (2 files) | 1 jam |
| KB Policy | `app/Policies/KnowledgeBasePolicy.php` | 0.5 jam |
| KB Request | StoreKnowledgeBaseRequest | 0.5 jam |
| **Total** | **12 files** | **7 jam** |

### Minggu 8 Deliverables
- ✅ Super admin layout
- ✅ All admin settings (company, attendance, leave, branding, security)
- ✅ User management (CRUD + role assignment)
- ✅ Knowledge base CRUD
- ✅ AI chat interface (RAG)
- ✅ Activity log viewer

---

## MINGGU 9: FRONTEND POLISH & PWA

### Hari 1-2: UI Polish
| Task | Files | Estimasi |
|------|-------|----------|
| Flux Icons | 14 icon files | 2 jam |
| Partials | Flash messages, breadcrumbs, navs (7 files) | 2 jam |
| Components | Face capture, GPS map, PDF preview (3 files) | 2 jam |
| CSS Polish | Custom CSS if needed (2 files) | 1 jam |
| **Total** | **14 files** | **7 jam** |

### Hari 3-4: PWA
| Task | Files | Estimasi |
|------|-------|----------|
| manifest.json | PWA manifest | 1 jam |
| sw.js | Service worker | 2 jam |
| offline.html | Offline fallback | 1 jam |
| PWA icons | icon-192, icon-512 | 0.5 jam |
| pwa-install.js | Install prompt | 1 jam |
| app-debounce.js | Utility | 0.5 jam |
| app-formatters.js | Formatters | 1 jam |
| **Total** | **7 files** | **7 jam** |

### Hari 5-6: Language Files
| Task | Files | Estimasi |
|------|-------|----------|
| Create 13 lang files | `lang/id/*.php` (13 files) | 5 jam |
| Test all translations | Manual testing | 2 jam |
| **Total** | **13 files** | **7 jam** |

### Minggu 9 Deliverables
- ✅ All UI components polished
- ✅ Flux icons integrated
- ✅ PWA fully functional
- ✅ Service worker for offline cache
- ✅ All Bahasa Indonesia translations
- ✅ Flash messages, breadcrumbs working

---

## MINGGU 10: TESTING (FEATURE TESTS)

### Hari 1-2: Attendance & Leave Tests
| Task | Files | Estimasi |
|------|-------|----------|
| ClockInTest | Feature test | 2 jam |
| ClockOutTest | Feature test | 1.5 jam |
| GeofenceValidationTest | Feature test | 1.5 jam |
| AntiFakeGPSTest | Feature test | 1 jam |
| AttendanceHistoryTest | Feature test | 1 jam |
| LeaveRequestTest | Feature test | 1.5 jam |
| LeaveQuotaTest | Feature test | 1 jam |
| LeaveApprovalTest | Feature test | 1 jam |
| ProbationLeaveBlockTest | Feature test | 1 jam |
| **Total** | **9 files** | **7 jam** |

### Hari 3-4: Payroll & Finance Tests
| Task | Files | Estimasi |
|------|-------|----------|
| PayrollGenerationTest | Feature test | 2 jam |
| PayrollCalculationTest | Feature test | 2 jam |
| PayrollLockTest | Feature test | 1 jam |
| BPJSAndTaxTest | Feature test | 1.5 jam |
| InternExemptTest | Feature test | 1 jam |
| LoanRequestTest | Feature test | 1.5 jam |
| LoanInstallmentTest | Feature test | 1 jam |
| ReimbursementRequestTest | Feature test | 1 jam |
| **Total** | **8 files** | **7 jam** |

### Hari 5-6: Employee, Approval, Security Tests
| Task | Files | Estimasi |
|------|-------|----------|
| EmployeeCRUDTest | Feature test | 2 jam |
| EmployeeNumberGenerationTest | Feature test | 1 jam |
| ResignationTest | Feature test | 1.5 jam |
| HandoverTest | Feature test | 1 jam |
| ProbationTest | Feature test | 1 jam |
| MultiLevelApprovalTest | Feature test | 1.5 jam |
| ApprovalReassignmentTest | Feature test | 1 jam |
| RBAC Tests | RolePermission, Middleware (2 files) | 1.5 jam |
| Security Tests | ForcePassword, CipherSweet, OAuth (3 files) | 2 jam |
| Notification Test | Feature test | 1 jam |
| **Total** | **11 files** | **7 jam** |

### Minggu 10 Deliverables
- ✅ 28 Feature tests written
- ✅ All tests passing
- ✅ Bug fixes from test results
- ✅ Coverage > 70%

---

## MINGGU 11: TESTING (UNIT TESTS) & BUG FIXES

### Hari 1-2: Unit Tests
| Task | Files | Estimasi |
|------|-------|----------|
| Enum Tests | EmploymentType, ApprovalStatus (2 files) | 1 jam |
| Service Tests | Attendance, Payroll, Geofence, Leave, Approval (5 files) | 4 jam |
| Model Tests | Employee, Attendance, Payroll, Leave (4 files) | 2 jam |
| Observer Tests | Employee, Attendance, Leave (3 files) | 2 jam |
| **Total** | **14 files** | **7 jam** |

### Hari 3-4: Bug Fixes & Optimization
| Task | Estimasi |
|------|----------|
| Fix bugs from test results | 3 jam |
| Optimize N+1 queries | 2 jam |
| Add missing indexes | 1 jam |
| Improve error handling | 1 jam |
| **Total** | **7 jam** |

### Hari 5-6: Final Testing & Polish
| Task | Estimasi |
|------|----------|
| Run all tests | 1 jam |
| Fix remaining bugs | 3 jam |
| Manual testing all flows | 2 jam |
| Performance check | 1 jam |
| **Total** | **7 jam** |

### Minggu 11 Deliverables
- ✅ 14 Unit tests written
- ✅ All tests passing (Feature + Unit = 42 tests)
- ✅ Bugs fixed
- ✅ Performance optimized
- ✅ Manual testing complete

---

## MINGGU 12: DEPLOYMENT & FINAL DOCUMENTATION

### Hari 1-2: Documentation
| Task | Files | Estimasi |
|------|-------|----------|
| Update wireframes.md | Finalize | 1 jam |
| Create testing-strategy.md | Finalize | 1 jam |
| Create security-config.md | Finalize | 1.5 jam |
| Create caching-strategy.md | Finalize | 1.5 jam |
| Create deployment-guide.md | Finalize | 2 jam |
| **Total** | **5 files** | **7 jam** |

### Hari 3-4: Deployment Prep
| Task | Estimasi |
|------|----------|
| Setup VPS | 2 jam |
| Configure Nginx + PHP-FPM | 1.5 jam |
| Setup SSL | 0.5 jam |
| Deploy application | 2 jam |
| Configure queue workers | 1 jam |
| **Total** | **7 jam** |

### Hari 5-6: Final Testing & Launch
| Task | Estimasi |
|------|----------|
| Post-deployment verification | 2 jam |
| Create Super Admin | 0.5 jam |
| Test all flows in production | 3 jam |
| Final bug fixes | 1.5 jam |
| **Total** | **7 jam** |

### Minggu 12 Deliverables
- ✅ All documentation complete
- ✅ Application deployed to production
- ✅ SSL configured
- ✅ Queue workers running
- ✅ All flows tested in production
- ✅ Super Admin created
- ✅ Project LAUNCH! 🚀

---

## 📊 DAILY SCHEDULE (9 jam/hari)

| Time | Activity |
|------|----------|
| 08:00 - 10:00 | Deep work (coding new features) |
| 10:00 - 10:15 | Break |
| 10:15 - 12:00 | Deep work (coding new features) |
| 12:00 - 13:00 | Lunch break |
| 13:00 - 15:00 | Coding + testing |
| 15:00 - 15:15 | Break |
| 15:15 - 17:00 | Testing + bug fixes |
| 17:00 - 17:30 | Documentation + review |

---

## 📋 TOTAL FILE COUNT BY PHASE

| Phase | Minggu | New Files | Modified Files | Total |
|-------|--------|-----------|----------------|-------|
| Phase 1 | 1-2 | 83 | 9 | 92 |
| Phase 2 | 3-4 | 63 | 2 | 65 |
| Phase 3 | 5-6 | 57 | 2 | 59 |
| Phase 4 | 7-8 | 52 | 2 | 54 |
| Phase 5 | 9 | 34 | 2 | 36 |
| Phase 6 | 10-11 | 42 | 0 | 42 |
| Phase 7 | 12 | 5 | 0 | 5 |
| **TOTAL** | **12** | **~336** | **~17** | **~353** |

---

## ✅ WEEKLY CHECKLIST

### Week 1-2: Foundation
- [x] All enums (30 enum created)
- [x] All migrations (41 migration, all synced)
- [x] All models (32 model, 100% PRD §20 compliant)
- [ ] All seeders (0/11 created)
- [x] All services (7 service: Attendance, Leave, Payroll, Approval, Reimbursement, Geofence, FaceRecognition)
- [ ] All observers (0/6 created)
- [ ] All commands (0/3 created)
- [x] Jobs: GenerateEmployeePayrollJob (1/2 created)
- [ ] All notifications (0/7 created)
- [ ] Config files (0/2 created)
- [x] 2 Traits (Approvable, ManagesWorkDays)
- [x] 9 Exceptions (all with render() method)
- [x] 1 Controller + 1 FormRequest (Attendance API)
- [x] 15 bug fixes (CRITICAL 2/2, HIGH 7/7)

### Week 3-4: ESS
- [ ] ESS layout working
- [ ] Clock in/out working
- [ ] Attendance history working
- [ ] Leave request working
- [ ] Overtime request working
- [ ] Reimbursement request working
- [ ] Profile editing working
- [ ] All ESS routes configured

### Week 5-6: HRD
- [ ] HRD layout working
- [ ] Employee CRUD working
- [ ] Approval management working
- [ ] Leave management working
- [ ] Shift management working
- [ ] Termination & handover working
- [ ] All HRD routes configured

### Week 7-8: Finance & Admin
- [ ] Finance layout working
- [ ] Payroll generation working
- [ ] Reimbursement working
- [ ] Admin settings working
- [ ] User management working
- [ ] Knowledge base working
- [ ] AI chat working

### Week 9: Polish
- [ ] All UI components polished
- [ ] PWA working
- [ ] All translations done
- [ ] Flux icons integrated

### Week 10-11: Testing
- [ ] All feature tests written
- [ ] All unit tests written
- [ ] All tests passing
- [ ] Bugs fixed
- [ ] Performance optimized

### Week 12: Deploy
- [ ] Documentation complete
- [ ] VPS setup
- [ ] Application deployed
- [ ] SSL configured
- [ ] Production tested
- [ ] **LAUNCH! 🚀**

---

## 🚧 GAP PRD — STATUS (Update: 2026-05-11)

> Audit dilakukan pada 11 Mei 2026 terhadap semua kode vs PRD.
> Backend core 85% siap. Berikut gap yang tersisa.

### 🔴 CRITICAL GAP (3 — Formula/Logic Salah)

| # | Task | PRD | File |
|---|------|-----|------|
| G1 | Overtime max 4h/hari, 18h/minggu validation | §8.1 | Service baru / Observer |
| G2 | Attendance penalty: flat per hari (Rp 50.000), **bukan** per menit | §11.6 | `PayrollCalculatorService.php` |
| G3 | Reimbursement L2 approval ke **Finance**, bukan HR Manager | §9.2 | `ApprovalService.php` |

### 🟡 MEDIUM GAP (6 — Fitur Kurang)

| # | Task | PRD | File |
|---|------|-----|------|
| G4 | `tunjangan_makan × hari_hadir` di payroll | §11.2 | `PayrollCalculatorService.php` |
| G5 | THR dipanggil di `generatePayroll()` | §11.11 | `PayrollCalculatorService.php` |
| G6 | Payroll cut-off date (default 25) check | §11.1 | `PayrollCalculatorService.php` |
| G7 | Alpha penalty: 1 hari = gross_monthly / 22 | §11.6 | `PayrollCalculatorService.php` |
| G8 | `linkOvertimeToAttendance()` Observer | §8.2 | `app/Observers/` baru |
| G9 | Reimbursement file MIME + size validation | §9.1 | `ReimbursementService.php` |

### 🟢 LOW GAP (8 — Minor / Nice-to-Have)

| # | Task | PRD | File |
|---|------|-----|------|
| G10 | `attendance:detect-alpha` command | §6.3 | `app/Console/Commands/` |
| G11 | `attendance:detect-chronic-late` command | §6.5 | `app/Console/Commands/` |
| G12 | `leave:reset-quota` command | §7.3 | `app/Console/Commands/` |
| G13 | WFA rejection → ubah attendance ke `absent` | §26.9 | `ApprovalService.php` |
| G14 | Set `early` status (clockOut < shift.end_time) | §6.2 | `AttendanceService.php` |
| G15 | Set `holiday` / `permission` status | §6.2 | `AttendanceService.php` |
| G16 | Withdraw approval (status `pending`) | §12.2 | `ApprovalService.php` |
| G17 | `HasFactory` di `KnowledgeBase` + `PerformanceReview` | §20 | `app/Models/` |

---

## ✅ YANG SUDAH SELESAI DI BRANCH INI

| Layer | Detail | File Count |
|-------|--------|:---:|
| **Enum** | 30 PHP Backed Enum, semua valid + business logic | 30 |
| **Migration** | 41 file, 1 batch, 0 pending, semua index + FK + constraint | 41 |
| **Model** | 32 model, 100% PRD §20 compliant, semua fillable + casts + relasi | 32 |
| **Service** | 7 service: Attendance, Leave, Payroll, Approval, Reimbursement, Geofence, FaceRecognition | 7 |
| **Trait** | 2 trait: Approvable (4 model), ManagesWorkDays (2 service) | 2 |
| **Exception** | 9 exception, semua dengan `render()` method (status code via `getCode()`) | 9 |
| **Controller** | 1: AttendanceController (Skinny, tanpa try-catch) | 1 |
| **FormRequest** | 1: ClockInRequest (StopOnFirstFailure + required_unless) | 1 |
| **Job** | 1: GenerateEmployeePayrollJob (queue: payroll_high, tries: 3) | 1 |
| **Bug Fix** | 15 bug ditemukan + difix (2 CRITICAL, 7 HIGH, 6 lainnya) | — |

---

*Dokumen ini adalah panduan utama untuk eksekusi project.*
*Ikuti jadwal ini agar tidak bingung dan tetap on track.*
*Terakhir diupdate: 2026-05-31*
