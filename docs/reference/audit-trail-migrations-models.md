# Audit Trail — Migrations & Models vs PRD

> **Tanggal Audit:** 2026-05-08
> **Auditor:** AI Assistant
> **Scope:** Semua migration files (34 existing) + model files (25 existing) vs PRD v2.0 + ERD
> **Status:** 🔴 BELUM DIPERBAIKI — Dokumentasi only, belum ada code yang dieksekusi

---

## 📊 Ringkasan Hasil Audit

| Kategori | OK | Issues | Missing |
|----------|----|--------|---------|
| **Migrations** | 15 | **18 issues** | **7 tabel** |
| **Models** | 10 | **20 issues** | **4 model** |
| **TOTAL** | 25 | **38** | **11** |

**Catatan:** Semua issue di bawah ini belum ada perubahan yang dieksekusi. Ini murni audit dokumentasi.

---

## 🔴 PART 1: MISSING TABLES (7 tabel belum ada migration)

### 1.1 `company_settings`
| Field | Detail |
|-------|--------|
| **PRD Reference** | Section 14.7 |
| **Tujuan** | Key-value config per company |
| **Kolom yang Dibutuhkan** | `id` (uuid), `company_id` (uuid FK, nullable), `key` (string, unique), `value` (json), `description` (text, nullable) |
| **Contoh Data** | `face_similarity_threshold = 0.85`, `payroll_cutoff_date = 25`, `attendance_penalty_per_day = 50000`, `wfa_note_min_chars = 20` |
| **Prioritas** | 🔴 HIGH — Dibutuhkan oleh AttendanceService, PayrollCalculatorService, GeofenceService |

### 1.2 `reimbursement_categories`
| Field | Detail |
|-------|--------|
| **PRD Reference** | Section 9.1 |
| **Tujuan** | Master kategori reimbursement |
| **Kolom yang Dibutuhkan** | `id` (uuid), `company_id` (uuid FK, nullable), `name` (string), `code` (string, unique), `is_active` (boolean, default true) |
| **Contoh Data** | `Transport`, `Makan`, `Akomodasi`, `Medis`, `Lainnya` |
| **Prioritas** | 🟡 MEDIUM — V2 prep, tapi reimbursement sudah masuk V1 |

### 1.3 `shift_schedules`
| Field | Detail |
|-------|--------|
| **PRD Reference** | Section 6 |
| **Tujuan** | Pivot jadwal shift per employee per tanggal |
| **Kolom yang Dibutuhkan** | `id` (uuid), `employee_id` (uuid FK), `shift_id` (uuid FK), `date` (date), unique(`employee_id`, `date`) |
| **Prioritas** | 🔴 HIGH — Dibutuhkan untuk attendance scheduling |

### 1.4 `leave_balances`
| Field | Detail |
|-------|--------|
| **PRD Reference** | Section 7.3 |
| **Tujuan** | Saldo cuti per employee per leave type per tahun |
| **Kolom yang Dibutuhkan** | `id` (uuid), `employee_id` (uuid FK), `leave_type_id` (uuid FK), `year` (integer), `quota` (integer), `used` (integer, default 0), `carry_forward` (integer, default 0), `carry_forward_deadline` (date, nullable), unique(`employee_id`, `leave_type_id`, `year`) |
| **Prioritas** | 🔴 HIGH — Dibutuhkan untuk LeaveService quota validation |

### 1.5 `tax_configs`
| Field | Detail |
|-------|--------|
| **PRD Reference** | Section 11.4 |
| **Tujuan** | PPh21 TER rates (Kategori A/B/C) |
| **Kolom yang Dibutuhkan** | `id` (uuid), `ter_category` (string: A/B/C), `min_income` (decimal), `max_income` (decimal), `rate` (decimal), `effective_rate` (decimal) |
| **Prioritas** | 🔴 HIGH — Dibutuhkan untuk PayrollCalculatorService PPh21 calculation |

### 1.6 `bpjs_configs`
| Field | Detail |
|-------|--------|
| **PRD Reference** | Section 11.5 |
| **Tujuan** | BPJS rates + ceilings |
| **Kolom yang Dibutuhkan** | `id` (uuid), `name` (string: kesehatan/jht/jp/jkk/jkm), `employer_rate` (decimal), `employee_rate` (decimal), `ceiling` (decimal, nullable) |
| **Prioritas** | 🔴 HIGH — Dibutuhkan untuk PayrollCalculatorService BPJS calculation |

### 1.7 `payroll_adjustments`
| Field | Detail |
|-------|--------|
| **PRD Reference** | Section 11.7 |
| **Tujuan** | Adjustment untuk payroll yang sudah locked (permanent) |
| **Kolom yang Dibutuhkan** | `id` (uuid), `payroll_id` (uuid FK), `amount` (integer, +/-), `reason` (text), `created_by` (uuid FK), `applied_to_period` (date) |
| **Prioritas** | 🔴 HIGH — Mekanisme koreksi payroll satu-satunya setelah lock |

---

## 🔴 PART 2: MISSING COLUMNS — Migrations Existing (18 masalah)

### 2.1 `employees` table — 5 kolom missing
**Migration:** `2026_04_16_192201_create_employees_table.php`

| Kolom | Tipe | Default | Nullable | PRD Reference | Alasan |
|-------|------|---------|----------|---------------|--------|
| `employment_type` | string(20) | 'permanent' | No | PRD 18, 19 | Membedakan permanent/contract/intern. Pengaruhi BPJS & PPh21 |
| `contract_start_date` | date | — | Yes | PRD 18, 19 | Wajib untuk karyawan kontrak (PKWT) |
| `contract_end_date` | date | — | Yes | PRD 18, 19 | Auto nonaktif saat habis masa kontrak |
| `deceased_date` | date | — | Yes | PRD 18, 19 | Karyawan meninggal → cuti dibayar, loan dihapus |
| `termination_reason` | text | — | Yes | PRD 18, 19 | Alasan resign/dismissed/contract_end/deceased |

**Migration baru yang perlu dibuat:**
```
2026_05_08_xxxxxx_add_employment_fields_to_employees_table.php
```

### 2.2 `payrolls` table — 12 kolom missing
**Migration:** `2026_04_20_192703_create_payrolls_table.php`

| Kolom | Tipe | Default | Nullable | PRD Reference | Alasan |
|-------|------|---------|----------|---------------|--------|
| `gross_salary` | decimal(15,2) | — | No | PRD 11.2, 18 | Total pendapatan sebelum potongan |
| `overtime_pay` | decimal(15,2) | 0 | No | PRD 11.2, 18 | Gaji lembur yang dihitung |
| `pph21` | decimal(15,2) | 0 | No | PRD 11.2, 11.4 | Pajak PPh21 TER |
| `bpjs_health` | decimal(15,2) | 0 | No | PRD 11.2, 11.5 | BPJS Kesehatan |
| `bpjs_employment` | decimal(15,2) | 0 | No | PRD 11.2, 11.5 | BPJS Ketenagakerjaan (JHT+JP) |
| `loan_deduction` | decimal(15,2) | 0 | No | PRD 11.2, 18 | Potongan cicilan loan |
| `attendance_penalty` | decimal(15,2) | 0 | No | PRD 11.2, 18 | Denda alpha/keterlambatan |
| `net_salary` | decimal(15,2) | — | No | PRD 11.2, 18 | Take home pay (gross - deductions) |
| `is_locked` | boolean | false | No | PRD 11.7, 18 | Flag payroll sudah dipublish (permanent) |
| `locked_at` | timestamp | — | Yes | PRD 11.7, 18 | Timestamp lock |
| `locked_by` | uuid (FK users) | — | Yes | PRD 11.7, 18 | Siapa yang lock |
| `published_at` | timestamp | — | Yes | PRD 11.7, 18 | Timestamp publish |
| `pdf_path` | text | — | Yes | PRD 11.8 | Path E-Payslip PDF |

**Migration baru yang perlu dibuat:**
```
2026_05_08_xxxxxx_add_payroll_calculation_fields_to_payrolls_table.php
```

### 2.3 `attendances` table — 4 kolom missing
**Migration:** `2026_04_17_175332_create_attendances_table.php`

| Kolom | Tipe | Default | Nullable | PRD Reference | Alasan |
|-------|------|---------|----------|---------------|--------|
| `late_minutes` | integer | 0 | No | PRD 18, 19 | Menit keterlambatan → input attendance_penalty |
| `status_wfa` | string(20) | — | Yes | ERD | Status WFA: pending/approved/rejected |
| `wfa_note` | string(255) | — | Yes | PRD 6.1 | Catatan WFA (min 20 karakter) |
| `overtime_id` | uuid (FK overtimes) | — | Yes | ERD | Link ke overtime jika ada |

**Migration baru yang perlu dibuat:**
```
2026_05_08_xxxxxx_add_attendance_validation_fields_to_attendances_table.php
```

### 2.4 `overtimes` table — 4 kolom missing
**Migration:** `2026_04_19_044028_create_overtimes_table.php`

| Kolom | Tipe | Default | Nullable | PRD Reference | Alasan |
|-------|------|---------|----------|---------------|--------|
| `start_time` | time | — | No | PRD 18, 19 | Jam mulai lembur |
| `end_time` | time | — | No | PRD 18, 19 | Jam selesai lembur |
| `description` | text | — | No | PRD 18, 19 | Alasan lembur (min 20 karakter) |
| `rejection_reason` | text | — | Yes | PRD 18, 19 | Alasan penolakan dari approver |

**Migration baru yang perlu dibuat:**
```
2026_05_08_xxxxxx_add_overtime_detail_fields_to_overtimes_table.php
```

### 2.5 `leaves` table — 1 kolom missing
**Migration:** `2026_04_17_175549_create_leaves_table.php`

| Kolom | Tipe | Default | Nullable | PRD Reference | Alasan |
|-------|------|---------|----------|---------------|--------|
| `rejection_reason` | text | — | Yes | PRD 18, 19 | Alasan penolakan cuti dari approver |

**Migration baru yang perlu dibuat:**
```
2026_05_08_xxxxxx_add_rejection_reason_to_leaves_table.php
```

### 2.6 `shifts` table — 1 kolom missing
**Migration:** `2026_04_12_211908_create_shifts_table.php`

| Kolom | Tipe | Default | Nullable | PRD Reference | Alasan |
|-------|------|---------|----------|---------------|--------|
| `late_tolerance_minutes` | integer | 0 | No | PRD 6.1, 14.5, 18 | Toleransi terlambat (menit) |

**Migration baru yang perlu dibuat:**
```
2026_05_08_xxxxxx_add_late_tolerance_to_shifts_table.php
```

### 2.7 `loan_installments` table — 2 kolom missing
**Migration:** `2026_04_20_192725_create_loan_installments_table.php`

| Kolom | Tipe | Default | Nullable | PRD Reference | Alasan |
|-------|------|---------|----------|---------------|--------|
| `status` | string(20) | 'pending' | No | PRD 18, 19 | pending/paid |
| `due_date` | date | — | No | PRD 18, 19 | Tanggal jatuh tempo cicilan |

**Migration baru yang perlu dibuat:**
```
2026_05_08_xxxxxx_add_status_and_due_date_to_loan_installments_table.php
```

### 2.8 Tabel Lainnya — Minor Issues

| Tabel | Kolom Missing | Tipe | PRD Reference | Alasan |
|-------|---------------|------|---------------|--------|
| `leave_types` | `deducts_from_quota` | boolean (default true) | ERD | Sakit tidak mengurangi kuota |
| `assets` | `company_id` | uuid (FK) | ERD | Multi-company asset tracking |
| `assets` | `code` | string | ERD | Asset code untuk labeling |
| `assets` | `category` | string | ERD | Kategori aset |
| `asset_handovers` | `condition` | string | ERD | Kondisi saat serah terima |
| `devices` | `device_name` | string | ERD | Nama device untuk UI display |
| `performance_reviews` | `period_year` | integer | ERD | Filter review per tahun |
| `performance_reviews` | `period_month` | integer | ERD | Filter review per bulan |
| `reimbursements` | `category_id` | uuid (FK) | ERD | Link ke reimbursement_categories |
| `approvals` | Index `idx_approvable` | composite index | ERD | Performance query polymorphic |

---

## 🔴 PART 3: MODEL ISSUES (20 masalah)

### 3.1 `Employee.php`
**File:** `app/Models/Employee.php`

| Issue | Detail | Fix |
|-------|--------|-----|
| `$fillable` tidak lengkap | Missing: `contract_start_date`, `contract_end_date`, `deceased_date`, `termination_reason`, `employment_type`, `shift_id` | Tambah ke `$fillable` array |
| Missing relationship | `leaveBalances()` | `return $this->hasMany(LeaveBalance::class);` |
| Missing relationship | `shiftSchedules()` | `return $this->hasMany(ShiftSchedule::class);` |
| Missing boot method | Auto generate employee number | `protected static function booted() { static::creating(...); }` |
| Missing helper | `faceRegistered()` | `return $this->face_embedding !== null;` |

### 3.2 `Payroll.php`
**File:** `app/Models/Payroll.php`

| Issue | Detail | Fix |
|-------|--------|-----|
| `$fillable` tidak lengkap | Missing: `gross_salary`, `overtime_pay`, `pph21`, `bpjs_health`, `bpjs_employment`, `loan_deduction`, `attendance_penalty`, `net_salary`, `is_locked`, `locked_at`, `locked_by`, `published_at`, `pdf_path` | Tambah ke `$fillable` array |
| Missing relationship | `adjustments()` | `return $this->hasMany(PayrollAdjustment::class);` |
| Missing relationship | `locker()` (User) | `return $this->belongsTo(User::class, 'locked_by');` |
| Missing method | `isLocked()` | `return $this->is_locked;` |
| Missing method | `lock(User $user)` | Set `is_locked = true`, `locked_at = now()`, `locked_by = $user->id`, `published_at = now()` |
| Missing casts | `is_locked` | `'is_locked' => 'boolean'` |

### 3.3 `User.php`
**File:** `app/Models/User.php`

| Issue | Detail | Fix |
|-------|--------|-----|
| Missing `$fillable` | `force_password_change`, `password_changed_at` | Tambah ke `$fillable` array |
| Missing casts | `force_password_change`, `password_changed_at` | `'force_password_change' => 'boolean'`, `'password_changed_at' => 'datetime'` |
| Missing relationship | `employee()` | `return $this->hasOne(Employee::class);` |

### 3.4 `PayrollItem.php`
**File:** `app/Models/PayrollItem.php`

| Issue | Detail | Fix |
|-------|--------|-----|
| Tipe adjustment | `type` masih bisa 'adjustment' — padahal sudah pindah ke `payroll_adjustments` table | Di migration: ubah type hanya 'allowance'/'deduction'. Di model: tidak perlu diubah |

### 3.5 `Attendance.php`
**File:** `app/Models/Attendance.php`

| Issue | Detail | Fix |
|-------|--------|-----|
| Missing `$fillable` | `late_minutes`, `status_wfa`, `wfa_note`, `overtime_id` | Tambah ke `$fillable` array |
| Missing casts | `late_minutes` | `'late_minutes' => 'integer'` |
| Missing relationship | `overtime()` | `return $this->belongsTo(Overtime::class);` |

### 3.6 `Leave.php`
**File:** `app/Models/Leave.php`

| Issue | Detail | Fix |
|-------|--------|-----|
| Missing `$fillable` | `rejection_reason` | Tambah ke `$fillable` array |
| Missing relationship | `approvals()` | `return $this->morphMany(Approval::class, 'approvable');` |
| Missing method | `calculateTotalDays()` | Hitung hari kerja exclude weekend & holidays |
| Missing method | `validateQuota()` | Cek apakah kuota cukup |

### 3.7 `Overtime.php`
**File:** `app/Models/Overtime.php`

| Issue | Detail | Fix |
|-------|--------|-----|
| Missing `$fillable` | `start_time`, `end_time`, `description`, `rejection_reason` | Tambah ke `$fillable` array |
| Missing casts | `start_time`, `end_time` | `'start_time' => 'datetime:H:i'`, `'end_time' => 'datetime:H:i'` |
| Missing relationship | `approvals()` | `return $this->morphMany(Approval::class, 'approvable');` |
| Missing method | `durationHours()` | Hitung durasi lembur dalam jam |

### 3.8 `Shift.php`
**File:** `app/Models/Shift.php`

| Issue | Detail | Fix |
|-------|--------|-----|
| Missing `$fillable` | `late_tolerance_minutes` | Tambah ke `$fillable` array |
| Missing casts | `late_tolerance_minutes` | `'late_tolerance_minutes' => 'integer'` |

### 3.9 `LeaveType.php`
**File:** `app/Models/LeaveType.php`

| Issue | Detail | Fix |
|-------|--------|-----|
| Missing relationship | `leaveBalances()` | `return $this->hasMany(LeaveBalance::class);` |
| Missing `$fillable` | `deducts_from_quota` | Tambah ke `$fillable` array |

### 3.10 `ActivityLog.php`
**File:** `app/Models/ActivityLog.php`

| Issue | Detail | Fix |
|-------|--------|-----|
| Missing trait | `Prunable` | `use Illuminate\Database\Eloquent\Prunable;` — Auto delete > 1 tahun (PRD 17.3) |

### 3.11-3.20 Models Lainnya — Minor

| Model | Issue | Fix |
|-------|-------|-----|
| `Holiday.php` | Missing `isHoliday(date)` method | Tambah static method untuk cek tanggal |
| `Branch.php` | Missing `validateRadius(lat, lng)` method | Tambah method Haversine validation |
| `KnowledgeBase.php` | Missing `processEmbedding()` method | Tambah method untuk trigger embedding job |
| `Asset.php` | Missing `$fillable`: `company_id`, `code`, `category` | Tambah ke `$fillable` array |
| `Device.php` | Missing `$fillable`: `device_name` | Tambah ke `$fillable` array |
| `LoanInstallment.php` | Missing `$fillable`: `status`, `due_date` | Tambah ke `$fillable` array |
| `PerformanceReview.php` | Missing `$fillable`: `period_year`, `period_month` | Tambah ke `$fillable` array |
| `Reimbursement.php` | Missing `$fillable`: `category_id` | Tambah ke `$fillable` array |
| `Loan.php` | Missing `$fillable`: `interest_rate` | Tambah ke `$fillable` array |
| `Approval.php` | Missing index definition | Migration perlu tambah composite index |

---

## 🔴 PART 4: MISSING MODELS (4 file)

### 4.1 `CompanySetting`
| Field | Detail |
|-------|--------|
| **File** | `app/Models/CompanySetting.php` |
| **Tabel** | `company_settings` |
| **Tujuan** | Key-value config helper dengan get/set static methods |
| **Relationships** | `belongsTo(Company::class)` |
| **Methods Needed** | `static::get($key, $default)`, `static::set($key, $value)` |

### 4.2 `ShiftSchedule`
| Field | Detail |
|-------|--------|
| **File** | `app/Models/ShiftSchedule.php` |
| **Tabel** | `shift_schedules` |
| **Tujuan** | Pivot jadwal shift per employee per tanggal |
| **Relationships** | `belongsTo(Employee::class)`, `belongsTo(Shift::class)` |
| **Unique** | `employee_id` + `date` |

### 4.3 `LeaveBalance`
| Field | Detail |
|-------|--------|
| **File** | `app/Models/LeaveBalance.php` |
| **Tabel** | `leave_balances` |
| **Tujuan** | Saldo cuti per employee per leave type per tahun |
| **Relationships** | `belongsTo(Employee::class)`, `belongsTo(LeaveType::class)` |
| **Unique** | `employee_id` + `leave_type_id` + `year` |
| **Methods Needed** | `deduct($days)`, `restore($days)`, `remaining()` |

### 4.4 `ReimbursementCategory`
| Field | Detail |
|-------|--------|
| **File** | `app/Models/ReimbursementCategory.php` |
| **Tabel** | `reimbursement_categories` |
| **Tujuan** | Master kategori reimbursement (V2 prep, tapi sudah V1) |
| **Relationships** | `hasMany(Reimbursement::class)`, `belongsTo(Company::class, nullable)` |

---

## 📋 PART 5: REKOMENDASI URUTAN PERBAIKAN

| Prioritas | Action | Jumlah File | Estimasi | Dependencies |
|-----------|--------|-------------|----------|--------------|
| **1** | Buat 7 migration tabel baru | 7 | 2 jam | — |
| **2** | Buat 8 migration add columns ke tabel existing | 8 | 2 jam | — |
| **3** | Buat 4 model baru | 4 | 2 jam | Step 1 selesai |
| **4** | Fix 20 model existing ($fillable, relationships, methods) | 20 | 4 jam | Step 2 selesai |
| **5** | Run `php artisan migrate` + verify | 1 | 1 jam | Step 1-4 selesai |
| **6** | Update seeders sesuai kolom baru | ~5 | 2 jam | Step 5 selesai |
| **TOTAL** | | **45 file changes** | **~13 jam** | |

---

## 📝 PART 6: DETAIL MIGRATION FILES YANG PERLU DIBUAT

### New Tables (7 files)
```
database/migrations/2026_05_08_000001_create_company_settings_table.php
database/migrations/2026_05_08_000002_create_reimbursement_categories_table.php
database/migrations/2026_05_08_000003_create_shift_schedules_table.php
database/migrations/2026_05_08_000004_create_leave_balances_table.php
database/migrations/2026_05_08_000005_create_tax_configs_table.php
database/migrations/2026_05_08_000006_create_bpjs_configs_table.php
database/migrations/2026_05_08_000007_create_payroll_adjustments_table.php
```

### Add Columns (8 files)
```
database/migrations/2026_05_08_000008_add_employment_fields_to_employees_table.php
database/migrations/2026_05_08_000009_add_payroll_calculation_fields_to_payrolls_table.php
database/migrations/2026_05_08_000010_add_attendance_validation_fields_to_attendances_table.php
database/migrations/2026_05_08_000011_add_overtime_detail_fields_to_overtimes_table.php
database/migrations/2026_05_08_000012_add_rejection_reason_to_leaves_table.php
database/migrations/2026_05_08_000013_add_late_tolerance_to_shifts_table.php
database/migrations/2026_05_08_000014_add_status_and_due_date_to_loan_installments_table.php
database/migrations/2026_05_08_000015_add_minor_columns_to_existing_tables.php
```

### New Models (4 files)
```
app/Models/CompanySetting.php
app/Models/ShiftSchedule.php
app/Models/LeaveBalance.php
app/Models/ReimbursementCategory.php
```

### Models to Fix (20 files)
```
app/Models/Employee.php
app/Models/Payroll.php
app/Models/User.php
app/Models/PayrollItem.php
app/Models/Attendance.php
app/Models/Leave.php
app/Models/Overtime.php
app/Models/Shift.php
app/Models/LeaveType.php
app/Models/ActivityLog.php
app/Models/Holiday.php
app/Models/Branch.php
app/Models/KnowledgeBase.php
app/Models/Asset.php
app/Models/Device.php
app/Models/LoanInstallment.php
app/Models/PerformanceReview.php
app/Models/Reimbursement.php
app/Models/Loan.php
app/Models/Approval.php
```

---

## ✅ CHECKLIST STATUS

### Migrations
- [ ] 7 new table migrations created
- [ ] 8 add-column migrations created
- [ ] All migrations run successfully
- [ ] Schema verified against ERD

### Models
- [ ] 4 new models created
- [ ] 20 existing models fixed ($fillable, casts, relationships, methods)
- [ ] All relationships verified
- [ ] All methods tested

### Seeders
- [ ] Seeders updated for new columns
- [ ] New seeders created for new tables
- [ ] Demo data verified

---

> **Status:** 📋 DOKUMENTASI ONLY — Belum ada perubahan yang dieksekusi
> **Next Step:** Buat migration files sesuai Part 5 & 6, lalu fix models sesuai Part 3
> **Catatan:** Semua perubahan harus dilakukan di branch `feat/sprint12-services-core` atau branch terpisah, TIDAK langsung di `develop` atau `main`
