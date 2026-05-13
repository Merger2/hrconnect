# Phase 1: MVP Blockers

**Estimasi:** 2 minggu (~14 jam kerja)
**Tujuan:** Foundation backend siap — semua model, service, seeder berfungsi dengan benar
**Berdasarkan:** Jadwal Eksekusi (Minggu 1-2) + Audit Komprehensif vs PRD

> **ERRATA (2026-05-13):** Sebelum memulai Phase 1, resolve 5 critical issues terlebih dahulu:
> - **C1:** `$approval->level === 1` always false → use enum comparison
> - **C2:** Payroll `delete()` vs `forceDelete()` for regenerate
> - **C3:** Sanctum belum terinstall → install + configure
> - **C4:** Permission enum + seeders missing → `$user->can()` always false
> - **SEC-5:** BusinessRuleException HTTP 500 → should be 422
>
> Detail lengkap di `docs/planning/task.md` (§0.6-0.8, §1.3-1.5) dan `docs/PRD-errata.md`.

---

## Minggu 1: Foundation — Models, Migrations, Enum Fixes

### Hari 1-2: Fix Enum & Migration (3 jam)

| # | Task | File | Deskripsi |
|---|------|------|-----------|
| 1 | Fix `PayrollStatus` | `app/Enums/PayrollStatus.php` | Tambah `PAID = 'paid'` (PRD 18) |
| 2 | Fix `PayrollItemType` | `app/Enums/PayrollItemType.php` | Ganti `ALLOWANCE` → `EARNING` (PRD 18) |
| 3 | Fix `LoanStatus` | `app/Enums/LoanStatus.php` | Tambah `CANCELLED = 'cancelled'` (PRD 18) |
| 4 | Fix `Attendance` migration | `create_attendances_table.php` | Tambah `verification_method` (string, nullable) |
| 5 | Fix `Payroll` migration | `create_payrolls_table.php` | Tambah `is_locked`, `locked_at`, `locked_by`, `published_at`, `pdf_path` |
| 6 | Fix `Reimbursement` migration | `create_reimbursements_table.php` | Tambah `category_id` (FK → reimbursement_categories) |
| 7 | Fix `LeaveType` migration | `create_leave_types_table.php` | Tambah `deducts_from_quota` (boolean, default true) |

### Hari 3-4: Fix Models (4 jam)

| # | Model | Fix | PRD |
|---|-------|-----|-----|
| 1 | **Shift** | Tambah `late_tolerance_minutes` ke fillable + cast integer | 20 |
| 2 | **Shift** | Tambah `calculateLateMinutes(Carbon $clockIn): int` | 20 |
| 3 | **Payroll** | Update fillable: gross_salary, pph21, bpjs_*, dll | 20 |
| 4 | **Payroll** | Tambah `isLocked()`, `lock()`, `generatePdf()` | 20, 11.7 |
| 5 | **Payroll** | Tambah `adjustments()` HasMany | 20 |
| 6 | **Attendance** | Update fillable: `late_minutes`, `clock_in_is_mocked`, `clock_in_accuracy`, `device_fingerprint` | 20 |
| 7 | **Attendance** | Tambah `overtime()` BelongsTo | ERD |
| 8 | **Leave** | Tambah `rejection_reason` ke fillable | 20 |
| 9 | **Leave** | Tambah `approvals()` morphMany | 20 |
| 10 | **Leave** | Tambah `calculateTotalDays()`, `validateQuota()` | 20, 21.3 |
| 11 | **Overtime** | Tambah `start_time`, `end_time`, `description`, `rejection_reason` ke fillable | 20 |
| 12 | **Overtime** | Tambah `approvals()` morphMany | 20 |
| 13 | **CompanySetting** | Tambah `get()`, `set()` static helpers | 20 |
| 14 | **KnowledgeBase** | Tambah `processEmbedding()` | 20 |
| 15 | **ActivityLog** | Tambah `Prunable` trait | 17.3 |
| 16 | **Holiday** | Tambah `isHoliday(date)` static | 20 |
| 17 | **Employee** | Tambah `getDirectApprover()` | 12.3, 20 |

### Hari 5-6: Create Seeders (3 jam)

| # | Seeder | Data | PRD |
|---|--------|------|-----|
| 1 | **RolesAndPermissionsSeeder** | 5 roles + permissions | 22 |
| 2 | **CompanyAndDepartmentSeeder** | 1 company, 1 branch, 1 dept | 22 |
| 3 | **SuperAdminSeeder** | admin@521.com + Employee | 22 |
| 4 | **CompanySettingsSeeder** | Semua key-value defaults | 22 |
| 5 | **PayrollConfigSeeder** | tax_configs (A/B/C), bpjs_configs | 22 |
| 6 | **LeaveTypeSeeder** | Cuti Tahunan, Sakit, Menstruasi, Melahirkan, Penting, Unpaid | 22 |
| 7 | **HolidaySeeder** | Hari libur nasional 2026 | 22 |
| 8 | **ShiftSeeder** | Office Hour, Morning, Night, Flexible | 22 |

---

## Minggu 2: Services & Business Logic

### Hari 1-2: Create 3 Missing Services (4 jam)

#### 1. PayrollCalculatorService (`app/Services/PayrollCalculatorService.php`)

```php
calculateProratedSalary(Employee $employee, string $period): float
calculatePTKP(Employee $employee): float
getTERCategory(Employee $employee): string  // A/B/C
calculatePPh21(float $grossIncome, string $category): float
calculateBPJS(Employee $employee, float $grossIncome): array
calculateOvertimePay(Overtime $overtime, Employee $employee): float
countWorkingDays(Carbon $start, Carbon $end): int
calculateThrProrated(Employee $employee, float $monthlySalary, int $monthsWorked): float
```

#### 2. LeaveService (`app/Services/LeaveService.php`)

```php
calculateWorkDays(Carbon $start, Carbon $end, string $dayType): float
validateLeaveQuota(Employee $employee, LeaveType $type, float $days): bool
applyLeave(Leave $leave): Leave
initializeBalance(Employee $employee, int $year): void
carryForward(Employee $employee, int $fromYear, int $toYear): void
```

#### 3. ApprovalService (`app/Services/ApprovalService.php`)

```php
createApprovalWorkflow(Model $approvable): void
approve(Approval $approval, string $notes = ''): void
reject(Approval $approval, string $reason): void
checkAllApproved(Model $approvable): bool
getDirectApprover(Employee $employee): Employee
```

### Hari 3-4: Fix AttendanceService (3 jam)

| # | Issue | Fix |
|---|-------|-----|
| 1 | `clockIn()` tidak panggil `validateGeofence`/`validateAntiFakeGPS` | Tambah validasi di awal method |
| 2 | `calculateLateMinutes()` panggil method yang tidak ada | Ganti jadi `$employee->shift->calculateLateMinutes($clockIn)` |
| 3 | Column name mismatch | `clock_in_latitude` → `lat_in`, `clock_in_longitude` → `long_in` |
| 4 | `verification_method` tidak ada di DB | Tambah kolom di migration |
| 5 | Status pakai string `'present'` | Ganti ke `AttendanceStatus::ON_TIME` / `LATE` |
| 6 | `clockOut()` method tidak ada | Implementasikan |
| 7 | Face similarity score tidak disimpan | Capture return value dari `verifyFace()` |
| 8 | WFA note min 20 chars tidak divalidasi | Tambah validasi |

### Hari 5-6: Commands & Jobs (2 jam)

| # | File | PRD |
|---|------|-----|
| 1 | `app/Console/Commands/AttendanceDetectAlphaCommand.php` | 16, 6.3 |
| 2 | `app/Console/Commands/LeaveResetQuotaCommand.php` | 16, 7.3 |
| 3 | `app/Jobs/GenerateEmployeePayrollJob.php` | 11.9, 20 |
| 4 | `app/Jobs/ProcessKnowledgeBaseEmbeddingJob.php` | 13.3, 20 |

---

## Verification

```bash
# 1. Semua migration clean
php artisan migrate:fresh --seed

# 2. Enum values match PRD
php artisan tinker --execute 'foreach (glob("app/Enums/*.php") as $f) { echo basename($f) . PHP_EOL; }'

# 3. Model relationships work
php artisan tinker --execute '$e = new App\Models\Employee; dd($e->shift(), $e->approvals(), $e->leaveBalances());'

# 4. Service methods callable
php artisan tinker --execute '$s = app(App\Services\AttendanceService::class);'

# 5. All seeders pass
php artisan db:seed

# 6. Tests passing
php artisan test --compact
```

---

## Open Questions

| # | Question | Options | Decision |
|---|----------|---------|----------|
| 1 | `PayrollItemType`: PRD says `earning`, code uses `allowance`? | A) Ikut PRD (ubah kode) / B) Ikut kode (update PRD) | **B — IKUT `allowance`** (sesuai ERD) |
| 2 | PIN absensi terpisah dari password? | A) Tambah kolom `pin` di Employee / B) Biarkan pakai password | **A — WAJIB TERPISAH** (6 digit, hash, fallback face) |
| 3 | `verification_method` kolom? | A) Tambah migration / B) Hapus dari kode | **A — TAMBAH MIGRATION** (denormalisasi ringan, cegah N+1) |
