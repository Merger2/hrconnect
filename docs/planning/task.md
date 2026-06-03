# HRConnect — Spesifikasi Eksekusi Perbaikan

> **Version:** 4.6 — Audit 2026-06-03: 60 → 87 ✅ SELESAI, 3 🔀 MERGED, 0 ⚠️ PARTIAL, 7 ❌ NOT DONE  
> **Tanggal:** 3 Juni 2026  
> **Errata v4.0:** 49 koreksi total — 87 ✅ SELESAI, 3 🔀 MERGED, 0 ⚠️ PARTIAL, 7 ❌ NOT DONE  
> **Cara Pakai:** Item bertanda ✅ SELESAI tidak perlu dikerjakan lagi. Fokus pada item ❌ NOT DONE.

---

## STATUS RINGKASAN

| Kategori | Jumlah | Detail |
|----------|--------|--------|
| ✅ SELESAI | 87 | Semua item Fase 0-3 + audit migration + partial solved (60→87) |
| 🔀 MERGED | 3 | §2.9 + §2.31 dikonsolidasi ke §0.10 (5 observer), §3.8 merged ke §2.24 |
| ⚠️ PARTIAL | 0 | Semua partial sudah diselesaikan |
| ❌ NOT DONE | 7 | 1.5, 1.7, 2.2, 2.24, 2.32, 4.3, 4.4 |

### ✅ SELESAI (60 item)

Item berikut sudah diimplementasi dan diverifikasi. Kode fix detail dihapus untuk ringkas.

| # | Item | Bukti |
|---|------|-------|
| E1 | `bpjsKesehetanDeduction` typo | PayrollCalculatorService uses `$bpjsKesehatanDeduction` |
| E4 | PayrollAdjustment decimal:2 | Migration `decimal(15,2)` + model cast `'decimal:2'` |
| E6 | Unique constraint migration not needed | Attendance migration has unique index |
| E9 | loan_installments redundant | Already had status + due_date columns |
| E11 | §3.1 title 2x/3x/4x | PRD v3.0 updated |
| E12 | password_changed_at | Migration + User model fillable/cast/hidden |
| E13 | overtimes description nullable | Migration: `$table->text('description')->nullable()` |
| E14 | employees address_detail nullable | Migration: `$table->text('address_detail')->nullable()` |
| E21 | attendances shift_id nullable | Migration: `$table->foreignId('shift_id')->nullable()` |
| E22 | payroll_adjustments created_by nullable | Migration: `$table->foreignId('created_by')->nullable()` |
| E23 | knowledge_bases embedding nullable | Migration: `$table->vector(...)->nullable()` with pgsql guard |
| E26 | employees npwp nullable | Migration: `$table->text('npwp')->nullable()` |
| E27 | employees bank_account_number + bank_name nullable | Both nullable |
| E31 | KnowledgeBase columns + enums | status, category, source_document, page_number + 3 enums |
| E34 | EmploymentType 4 values | Enum has PERMANENT, CONTRACT, PROBATION, INTERN |
| E35 | overtime flat rate keys → tiered | PRD v3.0 updated |
| E36 | PRD §27 duplicate Excel | PRD v3.0 updated |
| E37 | AttendanceStatus 8 values | Enum has MISSED_CLOCK_IN, MISSED_CLOCK_OUT |
| E43 | Employee face_embedding vector cast | `'face_embedding' => 'vector'` in casts |
| E44 | AttendanceService invalidateCache dead code | Method removed entirely |
| E47 | VerificationMethod enum | Enum created, AttendanceService uses it |
| — | §0.2 Clock-out overwrites verification_method | Separate columns + AttendanceService clockOut fix |
| — | §0.3 Clock-in double-submit 500 | UniqueConstraintViolationException replaces QueryException+23505 |
| — | **§0.4 RC-2 Payroll race condition** | **HARI 1**: lockForUpdate + forceDelete + outer transaction (verified tinker) |
| — | **§0.6 DL-2 Leave quota deduct timing** | **HARI 1**: dipindah ke ApprovalService::approve() saat isAllApproved (verified tinker) |
| — | §0.9 KnowledgeBase columns | 4 cols + 3 enums |
| — | §2.4 PayrollAdjustment decimal | Cast + migration |
| — | **§2.7 B8 LeaveBalance.deduct() negatif** | **HARI 1**: guard `available() < days` throw + increment + refund method (verified tinker) |
| — | §2.11 Employee vector cast | Done |
| — | §2.12 KnowledgeBase vector cast | Done |
| — | §2.15 Employee npwp/bank nullable | Done |
| — | **§2.19 M14 bpjs_configs.name unique** | **HARI 1**: Migration `unique()` (verified tinker — duplicate throw UniqueConstraintViolationException) |
| — | **Cache Clean Code: Eloquent Collection → plain array** | **HARI 1 BONUS**: `PayrollCalculatorService::calculatePPh21()` (line 124-132) + `calculateBPJS()` (line 164-171) cache plain array via `->toArray()` + unwrap enum + float cast. Cegah "incomplete object" cross-session crash. Doc `caching-strategy.md` §1.1 update aturan keras |
| — | §2.25 EmploymentType 4 values | Done |
| — | §2.28 AttendanceStatus 8 values | Done |
| — | §2.30 Cache dead code removed | Done |
| — | §2.19 M14-NEW: 16 enum classification HAPUS color(), 17 enum status STANDARDIZE ke Flux UI | Done ✅ |
| — | §2.18 LoanInstallmentStatus model cast | Done ✅ |
| — | §2.20 WfaStatus migration column + fillable | Done ✅ |
| — | §4.1d overtimes nullable | Done |
| — | §4.1e employees address_detail nullable | Done |
| — | §4.1f password_changed_at | Done |
| — | §4.1g attendances shift_id nullable | Done |
| — | §4.1h payroll_adjustments created_by nullable | Done |
| — | §4.1i knowledge_bases embedding nullable | Done |
| — | §4.1k employees npwp/bank nullable | Done |
| — | Companies 3NF (address FK removed) | 5 FK address columns removed from migration + model |
| — | Branches 3NF (address FK removed) | 5 FK address columns removed, `address` text kept |
| — | Model fixes (Company Hidden, Branch fillable, Attendance fillable/casts, Asset SoftDeletes, User password_changed_at, Employee PII+vector, FamilyDetail Hidden) | All done |
| — | Defensive Migration pattern | 3 migrations guarded with `DB::getDriverName() === 'pgsql'` |
| — | UniqueConstraintViolationException | AttendanceService clockIn |
| — | CAT-018 + CAT-019 | PRD-errata v2.0 + testing-strategy.md §9 |
| — | PRD v3.0 + PRD-errata v2.0 + INDEX.md | All docs updated |
| — | .env.example + config/database.php default pgsql | Done |
| E24 | companies.address_detail NOT NULL | Kolom dihapus seluruhnya (3NF — address di branches) |
| E25 | branches.address_detail NOT NULL | Diganti `address` text (FK alamat dihapus per ERD) |
| — | §2.5 Company missing 6 address columns | Dihapus (3NF clean architecture) |
| — | §2.14 Company/Branch address_detail nullable | Tidak relevan — kolom address_detail tidak ada, diganti text |
| — | §4.1j companies/branches address_detail | Sama seperti §2.14 — tidak relevan |
| E5 | Unique index verification step | Note only — unique index sudah ada di migration |
| — | §0.7 ApprovalService enum vs int bug | `$approval->level === 1` → `=== ApprovalLevel::L1_SUPERVISOR` |
| — | §2.3 Payroll isLocked() block PAID | `=== PUBLISHED` → `in_array(..., [PUBLISHED, PAID])` |
| — | §2.10 Approval mass-assignment | Hapus `approvable_type`, `approvable_id` dari fillable |
| — | §2.13 Holiday date cast | Tambah `'date' => 'date'` di casts |
| — | N1 | overtimes start_time/end_time → nullable |
| — | N3 | companies.logo text → string(255)->nullable() |
| — | N4 | branches lat/lon decimal(10,8)/(11,8) → decimal(10,7) |
| — | Audit: attendances exception columns | +exception_type, +exception_notes, +approved_late_by FK |
| — | Audit: devices detection columns | +device_type, +device_name, +browser, +os |
| — | Audit: assets columns | +company_id FK, +code, +category, +status AssetStatus |
| — | Audit: asset_handovers columns | +condition, +category HandoverCategory |
| — | Audit: performance_reviews columns | +status, +review_date, +period, final_score→nullable, +softDeletes |
| — | Audit: loans interest_rate | +interest_rate decimal(5,2) default(0) |
| — | Audit: leave_types quota default | quota → default(12) |
| — | Audit: Branch model lat/lon cast | decimal:8 → decimal:7 |
| — | Audit: Employee graduation_year cast | +graduation_year → integer |
| — | Audit: User company() relationship | +company() BelongsTo |
| — | Audit: Company users() relationship | +users() HasMany |
| — | Audit: Shift shiftSchedules() relationship | +shiftSchedules() HasMany |
| — | Audit: Attendance approvedLateBy() relationship | +approvedLateBy() BelongsTo Employee |
| — | Audit: Asset company() relationship | +company() BelongsTo |
| — | Audit: ERD sync | 15+ updates: users, departments, positions, leave_types, attendances, devices, assets, asset_handovers, performance_reviews, knowledge_bases, leave_balances, loan_installments, wfa_status + loan_installment_status enums, FK refs |

### ✅ SEMUA PARTIAL SELESAI (3 item — ✅ DONE)

| # | Item | Status |
|---|------|--------|
| E20 | family_details_count — `children()` scoped relation sudah dibuat, `getTERCategory()` pakai `children_count` | ✅ Auditor: 2026-06-03 |
| E42 | overtimes description nullable — `StoreOvertimeRequest` validation `required`→`nullable` | ✅ Fix: 2026-06-03 |
| — | §0.1 Queue retry_after — `GenerateEmployeePayrollJob::$queue = 'payroll_high'` ditambahkan | ✅ Fix: 2026-06-03 |

### Item yang sudah SELESAI dari sesi v4.2 (dipindah dari PARTIAL):

| # | Item | Bukti |
|---|------|-------|
| E7/§2.6 | Asset migration + fillable + cast + relationship | company_id, code, category, status + AssetStatus cast + company() |
| E39 | Asset SoftDeletes + status columns | Migration columns + fillable + cast semua done |
| E10 | 3 migrations tanpa kode | KnowledgeBase ✅, exception_fields ✅, wfa_status ✅, device_detection ✅ |
| — | §2.17 AssetStatus enum | Model cast ✅, migration column ✅ |
| — | §2.18 LoanInstallmentStatus | Model cast ✅ |
| — | §2.20 WfaStatus | Migration column ✅, fillable ✅ |
| — | §2.35 FaceNotRecognized fallback | Face→PIN logic ✅ (verification_fallback defer) |
| — | §49 tiered fallback | VerificationMethod enum ✅ (verification_fallback defer) |
| — | §33 CAT-005 Password expiry | password_changed_at ✅ (CheckPasswordExpired middleware defer) |

---

## DAFTAR ISI

1. [Fase 0 — Critical Data-Loss & Race Condition](#fase-0--critical-data-loss--race-condition)
2. [Fase 1 — Security & Authorization](#fase-1--security--authorization)
3. [Fase 2 — Missing Infrastructure](#fase-2--missing-infrastructure)
4. [Fase 3 — Service Bug Fixes](#fase-3--service-bug-fixes)
5. [Fase 4 — PRD Gap Implementation](#fase-4--prd-gap-implementation)
6. [Urutan Eksekusi Lengkap](#urutan-eksekusi-lengkap)
7. [PRD vs Kode vs ERD — Cross Reference](#prd-vs-kode-vs-erd--cross-reference)

---

## FASE 0 — Critical Data-Loss & Race Condition

> **WAJIB SELESAI SEBELUM SEMUA FASE LAIN.**  
> Urutan berdasarkan dependency. Kerjakan dari atas ke bawah.

---

### ✅ 0.1 RC-5: Queue retry_after < timeout — SELESAI

> **Status:** ⚠️ PARTIAL — Config fixed (`retry_after=180`), tapi `GenerateEmployeePayrollJob::$queue = 'payroll_high'` belum ditambahkan.

**Sisa yang perlu dikerjakan:**

```php
// app/Jobs/GenerateEmployeePayrollJob.php — tambah properti
public string $queue = 'payroll_high';
```

---

### ✅ 0.2 DL-3: face_similarity_score & verification_method Ditimpa Saat Clock-Out — SELESAI

> **Status:** ✅ SELESAI — Kolom `clock_out_verification_method` + `clock_out_face_similarity_score` ditambahkan. AttendanceService clockOut() menulis ke kolom terpisah.

---

### ✅ 0.3 RC-1: Clock-In Double-Submit → 500 Error — SELESAI

> **Status:** ✅ SELESAI — `UniqueConstraintViolationException` menggantikan `QueryException` + hardcoded `23505`.

---

### ✅ 0.4 RC-2: Payroll Double-Generation Race Condition — SELESAI

> **Status:** ✅ SELESAI — `PayrollCalculatorService::generatePayroll()` sudah pakai `lockForUpdate()` + `forceDelete()` di dalam SATU `DB::transaction`. Verified via tinker: regenerate menghasilkan id baru, count=1, withTrashed=1 (force-deleted, bukan soft).

**Bukti:**
- `PayrollCalculatorService.php:200` — outer `DB::transaction`
- `PayrollCalculatorService.php:203` — `lockForUpdate()` saat cek existing payroll
- `PayrollCalculatorService.php:263` — `$existingPayroll->forceDelete()` (bukan `delete()`)
- Migration `payrolls` sudah punya unique index `(employee_id, period)`

---

### ✅ 0.5 DL-1: Reimbursement PAID Tanpa payroll_id Setelah Regenerate — SELESAI

> **Status:** ✅ SELESAI — Included in 0.4 fix (PayrollCalculatorService forceDelete resets reimbursements).  
**Severity:** CRITICAL  
**Depends On:** 0.4  
**Estimasi:** 0 menit (sudah diperbaiki di 0.4)

**Verifikasi tambahan:**
```bash
php artisan tinker --execute '
// Buat reimbursement approved, link ke payroll, delete payroll
// Cek bahwa reimbursement status kembali ke APPROVED dan payroll_id = NULL
'
```

---

### ✅ 0.6 DL-2: Leave Quota Di-deduct Saat SUBMIT, Bukan Saat APPROVED — SELESAI

> **Status:** ✅ SELESAI — Quota validate-on-submit, deduct-on-final-approval. Verified via tinker: submit (used=0) → approve full (used=3) → submit baru (used=3) → reject (used=3 tetap).

**Bukti:**
- `LeaveService.php:62-79` — validasi quota saja (no deduct) saat applyLeave
- `ApprovalService.php:72-85` — deduct kuota saat `isAllApproved()` di method `approve()`
- `LeaveBalance.php:51-57` — `deduct()` dengan guard + `increment('used', $days)`
- `LeaveBalance.php:59-63` — method `refund()` untuk withdraw scenario (V2)

---

### ✅ 0.7 C1: ApprovalLevel Enum vs Integer Strict Comparison — APPROVED_L1 Never Reached — SELESAI

> **Status:** ✅ SELESAI — `ApprovalService.php:86` uses `$approval->level === ApprovalLevel::L1_SUPERVISOR` (enum comparison, not integer). Auditor: 2026-06-03.  
**Severity:** CRITICAL  
**File:** `app/Services/ApprovalService.php:72`, `app/Traits/Approvable.php`  
**Depends On:** —  
**Estimasi:** 5 menit

> **CATATAN:** Bug ini JUGA ada di §0.6 fix yang diusulkan! Baris `$approval->level === 1` di §0.6 Langkah 2 harus diperbaiki juga.

**Langkah 1 — Fix ApprovalService::approve():**

```php
// app/Services/ApprovalService.php — di dalam method approve()
// GANTI:
} elseif ($approval->level === 1) {

// MENJADI:
} elseif ($approval->level === ApprovalLevel::L1_SUPERVISOR) {
```

**Langkah 2 — Verifikasi §0.6 fix juga diperbaiki:**
Jika §0.6 Langkah 2 sudah diimplementasi, pastikan baris perbandingan level menggunakan enum, bukan integer.

**Verifikasi:**
```bash
php artisan tinker --execute '
$app = App\Models\Approval::first();
if ($app) {
    echo "Level type: " . get_class($app->level) . "\n";
    echo "Level value: " . $app->level->value . "\n";
} else {
    echo "No approval records yet. Test after creating one.";
}
'
```

---

### ✅ 0.8 C2: Payroll SoftDelete Memblokir Regenerasi — Unique Constraint Violation — SELESAI

> **Status:** ✅ SELESAI — `PayrollCalculatorService.php:322` uses `forceDelete()` instead of `delete()`. Auditor: 2026-06-03.  
**Severity:** CRITICAL  
**File:** `app/Services/PayrollCalculatorService.php`, `app/Models/Payroll.php`  
**Depends On:** 0.4 (RC-2 fix)  
**Estimasi:** 10 menit

**Langkah 1 — Ganti `$existingPayroll->delete()` ke `$existingPayroll->forceDelete()` di PayrollCalculatorService:**

```php
// app/Services/PayrollCalculatorService.php — di dalam method generatePayroll()
// GANTI:
if ($existingPayroll) {
    $existingPayroll->delete();
}

// MENJADI:
if ($existingPayroll) {
    // Reset reimbursements sebelum force delete
    Reimbursement::where('payroll_id', $existingPayroll->id)->update([
        'payroll_id' => null,
        'status' => ReimbursementStatus::APPROVED,
    ]);
    $existingPayroll->forceDelete();
}
```

> **CATATAN:** Ini menghapus baris "Reset reimbursements" yang terpisah di §0.4 dan menggabungkannya ke sini, karena `forceDelete()` harus didahului oleh reset reimbursements.

**Verifikasi:**
```bash
php artisan tinker --execute '
$payroll = App\Models\Payroll::first();
if ($payroll) {
    echo "Payroll soft deletes: " . ($payroll->forceDeleting ? "forceDeleting" : "soft deleting");
    echo "\nPayroll uses SoftDeletes trait: " . (method_exists($payroll, "trashed") ? "YES" : "NO");
}
'
```

---

### ✅ 0.9 DL-4: KnowledgeBase Columns — SELESAI KnowledgeBase::processEmbedding() Crash — Missing Columns

**Masalah:** `KnowledgeBase::processEmbedding()` (line 26) menjalankan `$this->update(['status' => 'processing'])`, tapi migration `create_knowledge_bases_table` **tidak punya kolom `status`**. Juga tidak punya `category`, `source_document`, `page_number`. Runtime SQL error saat `ProcessKnowledgeBaseEmbedding` job dijalankan.  
**Severity:** HIGH  
**Depends On:** —  
**Estimasi:** 15 menit

> **CATATAN:** §2.1 menambahkan kolom ini via migration terpisah. Karena development stage, edit langsung di migration asli `create_knowledge_bases_table`, lalu `php artisan migrate:fresh`.

**Langkah 1 — Edit migration asli `create_knowledge_bases_table`:**

```php
// database/migrations/*_create_knowledge_bases_table.php
// Tambahkan kolom berikut SEBELUM timestamps():

$table->string('status', 20)->default('processing')->after('embedding');
$table->string('category', 30)->default('general')->after('title');
$table->string('source_document')->nullable()->after('content');
$table->integer('page_number')->nullable()->after('source_document');

// CATATAN: Polymorphic columns harus nullable (global KB tanpa owner)
// Jika menggunakan migration alter, tambahkan:
// $table->string('knowledgeable_type', 255)->nullable()->change();
// $table->unsignedBigInteger('knowledgeable_id')->nullable()->change();
// Jika edit migration asli, pastikan create sudah nullable.

// PASTIKAN embedding sudah nullable (bukan NOT NULL):
// Migration sekarang sudah `->vector('embedding', 768)` — pastikan tetap 768 (Gemini text-embedding-004).
// Jika ada warisan kode dimensions:1536 (OpenAI text-embedding-ada-002), GANTI menjadi 768:
$table->vector('embedding', 768)->nullable()->change();
// Atau jika membuat baru:
$table->vector('embedding', 768)->nullable();
// [K2] Dimensi 768 mengikuti Gemini text-embedding-004 sesuai PRD §13.1.
```

**Langkah 2 — Buat KnowledgeBaseStatus dan KnowledgeBaseCategory enum:**

```php
// app/Enums/KnowledgeBaseStatus.php

namespace App\Enums;

enum KnowledgeBaseStatus: string
{
    case PROCESSING = 'processing';
    case READY = 'ready';
    case ERROR = 'error';
}
```

```php
// app/Enums/KnowledgeBaseCategory.php

namespace App\Enums;

enum KnowledgeBaseCategory: string
{
    case HR_POLICY = 'hr_policy';
    case IT_GUIDE = 'it_guide';
    case GENERAL = 'general';
    case FINANCE = 'finance';
    case OTHER = 'other';
}
```

**Langkah 3 — Update KnowledgeBase model (lihat §2.1 untuk model lengkap):**

```php
// app/Models/KnowledgeBase.php — tambah di class:
use App\Enums\KnowledgeBaseStatus;
use App\Enums\KnowledgeBaseCategory;

protected $casts = [
    'embedding' => 'vector', // pgvector cast
    'status' => KnowledgeBaseStatus::class,
    'category' => KnowledgeBaseCategory::class,
];
```

**Verifikasi:**
```bash
php artisan migrate:fresh --seed
php artisan tinker --execute '
$kb = App\Models\KnowledgeBase::create(["title" => "Test", "content" => "Test"]);
echo "Status: " . $kb->status->value; // processing
echo "Category: " . $kb->category->value; // general
'
```

---

### ✅ 0.10 DL-5: Observer Directory Kosong — 5 Observers (Domain + Cache Invalidation) — SELESAI

> **Status:** ✅ SELESAI — 5 observers exist (EmployeeObserver, AttendanceObserver, HolidayObserver, TaxConfigObserver, BpjsConfigObserver). Registered in `AppServiceProvider::registerObservers()`. Auditor: 2026-06-03.  
**Masalah:** Direktori `app/Observers/` kosong (0 file). Konsekuensi:
1. **Domain logic broken** — ERR-005 (default shift assignment), PRD §8.2 (overtime ↔ attendance link).
2. **Cache stale risk** — `tax_configs`, `bpjs_configs`, `holidays:*` punya TTL 1-day–1-month tapi 0 observer untuk invalidasi. Admin update tarif/libur via Eloquent normal → cached value stale sampai TTL expired.
3. **Cache key boros** — `holiday_{YYYY-MM-DD}` per-tanggal, payroll batch 30 entries/karyawan/bulan. Refactor ke `holidays:{year}` (1 entry/tahun, in-memory check).

**Severity:** HIGH (domain) + HIGH (correctness)
**Depends On:** —
**Estimasi:** 45 menit (5 observer + register + refactor cache key)

**Konsolidasi:** §2.9 (Tax/BPJS observer) + §2.31 (Holiday observer + yearly refactor) merged ke sini untuk satu register block di AppServiceProvider.

---

**Langkah 1 — EmployeeObserver (Domain):**

```php
// app/Observers/EmployeeObserver.php

namespace App\Observers;

use App\Models\Employee;
use App\Models\Shift;

class EmployeeObserver
{
    public function creating(Employee $employee): void
    {
        // ERR-005: Assign default "Flexible" shift jika shift_id null
        if ($employee->shift_id === null) {
            $flexibleShift = Shift::where('name', 'Flexible')->first();
            if ($flexibleShift) {
                $employee->shift_id = $flexibleShift->id;
            }
        }
    }
}
```

**Langkah 2 — AttendanceObserver (Domain):**

```php
// app/Observers/AttendanceObserver.php

namespace App\Observers;

use App\Enums\RequestStatus;
use App\Models\Attendance;

class AttendanceObserver
{
    public function created(Attendance $attendance): void
    {
        // PRD §8.2: Link overtime saat clock-out (attendance_id matching)
        if ($attendance->clock_out !== null && $attendance->employee_id) {
            $attendance->employee->overtimes()
                ->where('date', $attendance->date)
                ->where('status', RequestStatus::APPROVED)
                ->whereNull('attendance_id')
                ->each(fn ($ot) => $ot->update(['attendance_id' => $attendance->id]));
        }
    }
}
```

**Langkah 3 — HolidayObserver (Cache Invalidation + Yearly Key):**

```php
// app/Observers/HolidayObserver.php

namespace App\Observers;

use App\Models\Holiday;
use Illuminate\Support\Facades\Cache;

class HolidayObserver
{
    public function saved(Holiday $holiday): void
    {
        Cache::forget("holidays:{$holiday->date->year}");
    }

    public function deleted(Holiday $holiday): void
    {
        Cache::forget("holidays:{$holiday->date->year}");
    }
}
```

**Langkah 4 — TaxConfigObserver (merge dari §2.9):**

```php
// app/Observers/TaxConfigObserver.php

namespace App\Observers;

use App\Models\TaxConfig;
use Illuminate\Support\Facades\Cache;

class TaxConfigObserver
{
    public function saved(TaxConfig $config): void
    {
        Cache::forget('taxes:configs');
    }

    public function deleted(TaxConfig $config): void
    {
        Cache::forget('taxes:configs');
    }
}
```

**Langkah 5 — BpjsConfigObserver (merge dari §2.9):**

```php
// app/Observers/BpjsConfigObserver.php

namespace App\Observers;

use App\Models\BpjsConfig;
use Illuminate\Support\Facades\Cache;

class BpjsConfigObserver
{
    public function saved(BpjsConfig $config): void
    {
        Cache::forget('bpjs:configs');
    }

    public function deleted(BpjsConfig $config): void
    {
        Cache::forget('bpjs:configs');
    }
}
```

> **CompanySettingObserver di-SKIP** — `CompanySetting::set()` sudah handle invalidation manual. Disiplin pakai facade ini, jangan `update()` direct.

**Langkah 6 — Register di AppServiceProvider::boot():**

```php
// app/Providers/AppServiceProvider.php

use App\Models\{Attendance, BpjsConfig, Employee, Holiday, TaxConfig};
use App\Observers\{AttendanceObserver, BpjsConfigObserver, EmployeeObserver, HolidayObserver, TaxConfigObserver};

public function boot(): void
{
    $this->configureDefaults();

    Employee::observe(EmployeeObserver::class);
    Attendance::observe(AttendanceObserver::class);
    Holiday::observe(HolidayObserver::class);
    TaxConfig::observe(TaxConfigObserver::class);
    BpjsConfig::observe(BpjsConfigObserver::class);
}
```

**Langkah 7 — Refactor PayrollCalculatorService cache keys:**

```php
// app/Services/PayrollCalculatorService.php

// SEBELUM (boros, line 88):
$isHoliday = Cache::remember("holiday_{$date->toDateString()}", now()->addMonth(),
    fn () => Holiday::isHoliday($date)
) || $date->isWeekend();

// SESUDAH (yearly, in-memory check):
$year = $date->year;
$holidaysOfYear = Cache::remember("holidays:{$year}", now()->addMonth(),
    fn () => Holiday::where('is_active', true)
        ->whereYear('date', $year)
        ->pluck('date')
        ->map(fn ($d) => $d->toDateString())
        ->toArray()
);
$isHoliday = in_array($date->toDateString(), $holidaysOfYear) || $date->isWeekend();

// Rename keys (line 126, 161):
$taxConfigs = Cache::remember('taxes:configs', now()->addDay(), fn () => TaxConfig::all());
$bpjsConfigs = Cache::remember('bpjs:configs', now()->addDay(), fn () => BpjsConfig::all());
```

**Langkah 8 — Pastikan Shift "Flexible" di-seed:**

```php
// database/seeders/ShiftSeeder.php
Shift::firstOrCreate(['name' => 'Flexible'], [
    'start_time' => '08:00:00',
    'end_time' => '17:00:00',
    'late_tolerance_minutes' => 0,
]);
```

**Verifikasi:**

```bash
php artisan migrate:fresh --seed

# Test observer registration
php artisan tinker --execute '
$observers = ["EmployeeObserver", "AttendanceObserver", "HolidayObserver", "TaxConfigObserver", "BpjsConfigObserver"];
foreach ($observers as $o) {
    echo class_exists("App\\\\Observers\\\\$o") ? "OK $o\n" : "MISSING $o\n";
}
'

# Test cache invalidation
php artisan tinker --execute '
use Illuminate\Support\Facades\Cache;
use App\Models\TaxConfig;
Cache::remember("taxes:configs", now()->addDay(), fn () => TaxConfig::all());
echo Cache::get("taxes:configs") ? "CACHED\n" : "MISS\n";
TaxConfig::first()?->touch();
echo Cache::get("taxes:configs") === null ? "EVICTED OK\n" : "STILL CACHED — BUG\n";
'

# Test yearly holiday cache (1 entry per year, bukan 30+)
php artisan tinker --execute '
use Illuminate\Support\Facades\Cache;
Cache::remember("holidays:2026", now()->addMonth(), fn () => []);
echo "Cache key holidays:2026 exists: " . (Cache::has("holidays:2026") ? "yes" : "no");
'
```

---

> **WAJIB SELESAI SEBELUM UI/API DIBUKA KE USER.**

---

### ✅ 1.1 SEC-1: PII Tidak Ada di $hidden — SELESAI

> **Status:** ✅ SELESAI — Employee, FamilyDetail, Company models all have proper `#[Hidden]`. Auditor: 2026-06-03.

**Masalah:** `nik`, `phone`, `npwp`, `bank_account_number` bocor di API/JSON.  
**Severity:** CRITICAL  
**Depends On:** —  
**Estimasi:** 15 menit

```php
// app/Models/Employee.php — ganti baris #[Hidden([...])]

// SEBELUM:
#[Hidden(['face_embedding', 'pin'])]

// SESUDAH:
#[Hidden(['face_embedding', 'pin', 'nik', 'phone', 'npwp', 'bank_account_number'])]
```

```php
// app/Models/FamilyDetail.php — tambah attribute jika belum ada:

#[Hidden(['nik', 'phone', 'address'])]
```

```php
// app/Models/Company.php — tambah:

#[Hidden(['npwp'])]
```

**Verifikasi:**
```bash
php artisan tinker --execute '
$emp = App\Models\Employee::first();
$arr = $emp->toArray();
echo isset($arr["nik"]) ? "FAIL: nik visible" : "OK: nik hidden";
echo isset($arr["phone"]) ? " FAIL: phone visible" : " OK: phone hidden";
echo isset($arr["npwp"]) ? " FAIL: npwp visible" : " OK: npwp hidden";
echo isset($arr["bank_account_number"]) ? " FAIL: bank visible" : " OK: bank hidden";
'
```

---

### ✅ 1.2 SEC-2: Zero Policies → IDOR — SELESAI

> **Status:** ✅ SELESAI — 8 policies exist (Employee, Attendance, Leave, Overtime, Reimbursement, Payroll, KnowledgeBase, Asset). Auditor: 2026-06-03.

**Masalah:** Tidak ada Policy untuk model apapun.  
**Severity:** CRITICAL  
**Depends On:** —  
**Estimasi:** 2 jam

**Buat 8 Policy classes:**

```bash
php artisan make:policy EmployeePolicy --model=Employee
php artisan make:policy AttendancePolicy --model=Attendance
php artisan make:policy LeavePolicy --model=Leave
php artisan make:policy PayrollPolicy --model=Payroll
php artisan make:policy ApprovalPolicy --model=Approval
php artisan make:policy LoanPolicy --model=Loan
phpartisan make:policy ReimbursementPolicy --model=Reimbursement
php artisan make:policy KnowledgeBasePolicy --model=KnowledgeBase
```

**Contoh EmployeePolicy (sebagai template, sesuaikan untuk lainnya):**

```php
// app/Policies/EmployeePolicy.php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view-employees');
    }

    public function view(User $user, Employee $employee): bool
    {
        return $user->can('view-employees')
            || $user->id === $employee->user_id;
    }

    public function create(User $user): bool
    {
        return $user->can('create-employees');
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->can('edit-employees');
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->can('delete-employees');
    }
}
```

**Registrasi di AuthServiceProvider:**

```php
// app/Providers/AuthServiceProvider.php — tambah di $policies array:

protected $policies = [
    Employee::class => EmployeePolicy::class,
    Attendance::class => AttendancePolicy::class,
    Leave::class => LeavePolicy::class,
    Payroll::class => PayrollPolicy::class,
    Approval::class => ApprovalPolicy::class,
    Loan::class => LoanPolicy::class,
    Reimbursement::class => ReimbursementPolicy::class,
    KnowledgeBase::class => KnowledgeBasePolicy::class,
];
```

**Verifikasi:**
```bash
php artisan policy:list
# Harus menampilkan 8 policy terdaftar
```

---

### ✅ 1.3 SEC-3: Sanctum Tidak Terinstall — API Auth 0% — SELESAI

> **Status:** ✅ SELESAI — Sanctum v4 installed, `HasApiTokens` on User model, `auth:sanctum` middleware active on all API routes. Auditor: 2026-06-03.

**Masalah:** `composer.json` tidak menyertakan `laravel/sanctum`. Tidak ada `HasApiTokens` trait, tidak ada `config/sanctum.php`, tidak ada `personal_access_tokens` migration, tidak ada `api` guard di `config/auth.php`. V2 API auth 100% tidak bisa berjalan.  
**Severity:** CRITICAL (untuk V2 API)  
**Depends On:** —  
**Estimasi:** 30 menit

**Langkah 1 — Install Sanctum:**

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

**Langkah 2 — Konfigurasi `config/auth.php` — tambah sanctum guard:**

```php
// config/auth.php — tambah di 'guards':
'api' => [
    'driver' => 'sanctum',
    'provider' => 'users',
],
```

**Langkah 3 — Update User model:**

```php
// app/Models/User.php — tambah trait:
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes, TwoFactorAuthenticatable;
```

**Langkah 4 — Buat `routes/api.php`:**

```php
// routes/api.php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // API endpoints akan ditambahkan di V2
    // V1: PWA + Livewire, tidak memerlukan API routes
});
```

**Langkah 5 — Daftarkan di `bootstrap/app.php`:**

```php
// bootstrap/app.php — tambah api routing ke withRouting():
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
)
```

---

### ✅ 1.4 SEC-4: Permission Enum + RoleAndPermissionSeeder + SuperAdminSeeder Tidak Ada — SELESAI

> **Status:** ✅ SELESAI — `Permission.php` (44 cases), `RoleAndPermissionSeeder`, `SuperAdminSeeder` all exist. Auditor: 2026-06-03.

**Masalah:** Tanpa `Permission` enum dan seeder, `$user->can()` selalu return `false`. Policy authorization 100% mati.  
**Severity:** CRITICAL  
**Depends On:** 1.2 (Policy creation)  
**Estimasi:** 1 jam

**Langkah 1 — Buat Permission enum:**

```php
// app/Enums/Permission.php

namespace App\Enums;

enum Permission: string
{
    // Employee
    case VIEW_EMPLOYEES = 'view-employees';
    case CREATE_EMPLOYEES = 'create-employees';
    case EDIT_EMPLOYEES = 'edit-employees';
    case DELETE_EMPLOYEES = 'delete-employees';

    // Attendance
    case VIEW_ATTENDANCES = 'view-attendances';
    case CLOCK_IN = 'clock-in';
    case APPROVE_ATTENDANCES = 'approve-attendances';

    // Leave
    case VIEW_LEAVES = 'view-leaves';
    case APPLY_LEAVES = 'apply-leaves';
    case APPROVE_LEAVES = 'approve-leaves';

    // Overtime
    case VIEW_OVERTIMES = 'view-overtimes';
    case APPLY_OVERTIMES = 'apply-overtimes';
    case APPROVE_OVERTIMES = 'approve-overtimes';

    // Payroll
    case VIEW_PAYROLLS = 'view-payrolls';
    case GENERATE_PAYROLLS = 'generate-payrolls';
    case PUBLISH_PAYROLLS = 'publish-payrolls';

    // Loan
    case VIEW_LOANS = 'view-loans';
    case MANAGE_LOANS = 'manage-loans';

    // Reimbursement
    case VIEW_REIMBURSEMENTS = 'view-reimbursements';
    case APPLY_REIMBURSEMENTS = 'apply-reimbursements';
    case APPROVE_REIMBURSEMENTS = 'approve-reimbursements';

    // Knowledge Base
    case VIEW_KNOWLEDGE_BASE = 'view-knowledge-base';
    case MANAGE_KNOWLEDGE_BASE = 'manage-knowledge-base';

    // Asset
    case VIEW_ASSETS = 'view-assets';
    case MANAGE_ASSETS = 'manage-assets';

    // Report
    case VIEW_REPORTS = 'view-reports';

    // Admin
    case MANAGE_USERS = 'manage-users';
    case MANAGE_ROLES = 'manage-roles';
    case MANAGE_SETTINGS = 'manage-settings';
}
```

**Langkah 2 — Buat RoleAndPermissionSeeder:**

```php
// database/seeders/RoleAndPermissionSeeder.php

namespace Database\Seeders;

use App\Enums\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = collect(Permission::cases())->map(fn ($p) => ['name' => $p->value, 'guard_name' => 'web']);
        PermissionModel::insertOrIgnore($permissions->toArray());

        $roles = [
            'super-admin' => Permission::cases(),
            'hr-manager' => [
                Permission::VIEW_EMPLOYEES, Permission::CREATE_EMPLOYEES, Permission::EDIT_EMPLOYEES,
                Permission::VIEW_ATTENDANCES, Permission::APPROVE_ATTENDANCES,
                Permission::VIEW_LEAVES, Permission::APPROVE_LEAVES,
                Permission::VIEW_OVERTIMES, Permission::APPROVE_OVERTIMES,
                Permission::VIEW_PAYROLLS, Permission::GENERATE_PAYROLLS, Permission::PUBLISH_PAYROLLS,
                Permission::VIEW_LOANS, Permission::MANAGE_LOANS,
                Permission::VIEW_REIMBURSEMENTS, Permission::APPROVE_REIMBURSEMENTS,
                Permission::VIEW_KNOWLEDGE_BASE, Permission::MANAGE_KNOWLEDGE_BASE,
                Permission::VIEW_ASSETS, Permission::MANAGE_ASSETS,
                Permission::VIEW_REPORTS,
                Permission::MANAGE_USERS, Permission::MANAGE_SETTINGS,
            ],
            'finance' => [
                Permission::VIEW_PAYROLLS, Permission::PUBLISH_PAYROLLS,
                Permission::VIEW_REIMBURSEMENTS, Permission::APPROVE_REIMBURSEMENTS,
                Permission::VIEW_REPORTS,
            ],
            'supervisor' => [
                Permission::VIEW_ATTENDANCES, Permission::APPROVE_ATTENDANCES,
                Permission::VIEW_LEAVES, Permission::APPROVE_LEAVES,
                Permission::VIEW_OVERTIMES, Permission::APPROVE_OVERTIMES,
                Permission::VIEW_REIMBURSEMENTS, Permission::APPROVE_REIMBURSEMENTS,
            ],
            'employee' => [
                Permission::CLOCK_IN,
                Permission::APPLY_LEAVES,
                Permission::APPLY_OVERTIMES,
                Permission::APPLY_REIMBURSEMENTS,
                Permission::VIEW_KNOWLEDGE_BASE,
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions(collect($rolePermissions)->map(fn ($p) => $p->value));
        }
    }
}
```

**Langkah 3 — Buat SuperAdminSeeder:**

```php
// database/seeders/SuperAdminSeeder.php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@521.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password123!'),
            ]
        );
        $user->assignRole('super-admin');
    }
}
```

**Langkah 4 — Daftarkan di DatabaseSeeder:**

```php
// database/seeders/DatabaseSeeder.php — tambah:
$this->call([
    RoleAndPermissionSeeder::class,
    SuperAdminSeeder::class,
]);
```

**Langkah 5 — Buat AuthServiceProvider:**

```bash
php artisan make:provider AuthServiceProvider
```

```php
// app/Providers/AuthServiceProvider.php

namespace App\Providers;

use App\Models\Approval;
use App\Models\Asset;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\KnowledgeBase;
use App\Models\Leave;
use App\Models\Loan;
use App\Models\Payroll;
use App\Models\Reimbursement;
use App\Policies\ApprovalPolicy;
use App\Policies\AssetPolicy;
use App\Policies\AttendancePolicy;
use App\Policies\EmployeePolicy;
use App\Policies\KnowledgeBasePolicy;
use App\Policies\LeavePolicy;
use App\Policies\LoanPolicy;
use App\Policies\PayrollPolicy;
use App\Policies\ReimbursementPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Employee::class => EmployeePolicy::class,
        Attendance::class => AttendancePolicy::class,
        Leave::class => LeavePolicy::class,
        Payroll::class => PayrollPolicy::class,
        Approval::class => ApprovalPolicy::class,
        Loan::class => LoanPolicy::class,
        Reimbursement::class => ReimbursementPolicy::class,
        KnowledgeBase::class => KnowledgeBasePolicy::class,
        Asset::class => AssetPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
```

```php
// bootstrap/providers.php — tambah:
use App\Providers\AuthServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    AuthServiceProvider::class,
];
```

**Verifikasi:**
```bash
php artisan db:seed --class=RoleAndPermissionSeeder
php artisan db:seed --class=SuperAdminSeeder
php artisan tinker --execute '
$user = App\Models\User::where("email", "admin@521.com")->first();
echo $user->hasRole("super-admin") ? "OK: super-admin role" : "FAIL: no role";
echo $user->can("view-employees") ? " OK: can view-employees" : " FAIL: cannot view-employees";
'
```

---

### ❌ 1.5 SEC-5: Exception HTTP Codes Tidak Konsisten — NOT DONE

> **Status:** ❌ FaceNotRegisteredException uses HTTP 400 (should be 422). NotClockedInException uses HTTP 400 (should be 409).

**Masalah:** Beberapa exception menggunakan HTTP code yang salah atau tidak konsisten:
- `FaceNotRegisteredException` return 400 (seharusnya 422 — business rule)
- `NotClockedInException` return 400 (seharusnya 409 — state conflict, konsisten dengan `AlreadyClockedInException`)
- `DomainException` di PayrollCalculatorService return 500 (seharusnya 422 via BusinessRuleException)  
**Severity:** MEDIUM  
**Depends On:** 0.6 (BusinessRuleException rewrite)  
**Estimasi:** 10 menit

```php
// app/Exceptions/FaceNotRegisteredException.php — ganti constructor:

public function __construct(string $message = 'Wajah tidak terdaftar di sistem.', int $statusCode = 422)
{
    parent::__construct($statusCode, $message);
}
```

> **CATATAN:** Setelah §0.6, BusinessRuleException extend HttpException. FaceNotRegisteredException dan NotClockedInException juga harus extend HttpException atau menggunakan pola yang sama.

```php
// app/Exceptions/NotClockedInException.php — ganti constructor:

public function __construct(string $message = 'Belum melakukan absensi masuk hari ini.')
{
    parent::__construct(409, $message); // 409 Conflict (konsisten dengan AlreadyClockedInException)
}
```

```php
// app/Services/PayrollCalculatorService.php — ganti DomainException ke BusinessRuleException:

// SEBELUM:
throw new DomainException("Payroll untuk periode {$period} sudah dikunci permanen.");

// SESUDAH:
throw new \App\Exceptions\BusinessRuleException("Payroll untuk periode {$period} sudah dikunci permanen.");
```

---

### ✅ 1.6 SEC-6: Password Expiry 90 Hari — PRD §4 Override — SELESAI

> **Status:** ✅ SELESAI — `CheckPasswordExpired` middleware exists, registered as `password.expired`. Default 90 days, configurable via CompanySetting. Auditor: 2026-06-03.

**Masalah:** PRD §4 line 155 bilang "**Tidak ada password expiry**", tapi Security Config §1.5 bilang "Expiration: 90 days (reminder at 7 days before)". Kolom `password_changed_at` sudah ada di database (§4.1f), tapi logic password expiry belum diimplementasi.  
**Severity:** HIGH (compliance ISO 27001)  
**Depends On:** 1.1 (password_changed_at di $hidden)  
**Estimasi:** 20 menit

> **CTO Decision (CAT-005):** Security Config §1.5 MENANG atas PRD §4. Alasan: ISO 27001 compliance, `password_changed_at` sudah ada untuk fungsi ini, force change password (PRD §4.2) membutuhkan timestamp.

**Langkah 1 — Buat middleware CheckPasswordExpired:**

```php
// app/Http/Middleware/CheckPasswordExpired.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPasswordExpired
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->password_changed_at) {
            $daysSinceChange = now()->diffInDays($user->password_changed_at);

            if ($daysSinceChange >= 90) {
                return redirect()->route('password.expired');
            }
        }

        return $next($request);
    }
}
```

**Langkah 2 — Register middleware di bootstrap/app.php atau Kernel:**

```php
// Tambah ke middleware group 'web':
'password.expired' => \App\Http\Middleware\CheckPasswordExpired::class,
```

**Langkah 3 — Tambah route untuk password expired page:**

```php
// routes/web.php atau routes/auth.php
Route::get('/password/expired', [PasswordController::class, 'showExpiredForm'])->name('password.expired');
Route::post('/password/expired', [PasswordController::class, 'updateExpired'])->name('password.expired.update');
```

**Verifikasi:**
```bash
php artisan route:list --name=password.expired
```

---

### ❌ 1.7 SEC-7: Module Route Files — Policy Middleware Tidak Bisa Di-enforce Tanpa Route Definitions — NOT DONE

> **Status:** ❌ API routes exist in api.php, but no separate module route files (attendance.php, leave.php, etc.) for web routes.

**Masalah:** §1.2 membuat Policy classes, tapi tanpa route files per module, policy middleware (`can:view`, `can:update`, dll) tidak bisa di-enforce. `routes/web.php` saat ini kosong — tidak ada route untuk attendance, leave, overtime, payroll, approval, knowledge-base, dsb. Livewire components perlu route definitions untuk policy gates.
**Severity:** HIGH  
**Depends On:** 1.2 (Policy creation)  
**Estimasi:** 2 jam

**Langkah 1 — Buat route files per module:**

```bash
# Module routes (Livewire + policy gates)
touch routes/attendance.php
touch routes/leave.php
touch routes/overtime.php
touch routes/payroll.php
touch routes/approval.php
touch routes/knowledge-base.php
touch routes/asset.php
touch routes/loan.php
touch routes/reimbursement.php
```

**Langkah 2 — Contoh route file (attendance.php):**

```php
// routes/attendance.php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\CheckPasswordExpired;

Route::middleware(['auth', 'verified', CheckPasswordExpired::class])->group(function () {
    Route::prefix('attendance')->name('attendance.')->group(function () {
        // Livewire routes akan dialisi oleh Livewire volt:auto-route
        // Tapi tetap perlu route definitions untuk policy gates:
        Route::get('/', fn () => view('attendance.index'))
            ->middleware('can:view-attendances')
            ->name('index');
        Route::get('/clock-in', fn () => view('attendance.clock-in'))
            ->middleware('can:clock-in')
            ->name('clock-in');
    });
});
```

**Langkah 3 — Daftarkan di bootstrap/app.php:**

```php
// bootstrap/app.php — tambah di withRouting():
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
    then: function () {
        Route::middleware('web')->group(base_path('routes/attendance.php'));
        Route::middleware('web')->group(base_path('routes/leave.php'));
        Route::middleware('web')->group(base_path('routes/overtime.php'));
        Route::middleware('web')->group(base_path('routes/payroll.php'));
        Route::middleware('web')->group(base_path('routes/approval.php'));
        Route::middleware('web')->group(base_path('routes/knowledge-base.php'));
        Route::middleware('web')->group(base_path('routes/asset.php'));
        Route::middleware('web')->group(base_path('routes/loan.php'));
        Route::middleware('web')->group(base_path('routes/reimbursement.php'));
    },
)
```

**Verifikasi:**
```bash
php artisan route:list --name=attendance
# Harus menampilkan route dengan middleware can:view-attendances
```

---

## FASE 2 — Missing Infrastructure

> **Infrastruktur yang diperlukan sebelum fitur bisa berjalan.**

---

### ✅ 2.1 B1: KnowledgeBase — Tambah Kolom `status` dan `category` — SELESAI

> **Status:** ✅ SELESAI — Migration has all columns (status, category, source_document, page_number). Model casts to enums. Auditor: 2026-06-03.

**Masalah:** Model update kolom yang tidak ada di DB → crash.  
**Severity:** HIGH  
**Depends On:** —  
**Estimasi:** 10 menit

```bash
php artisan make:migration add_status_and_category_to_knowledge_bases_table
```

```php
// database/migrations/YYYY_MM_DD_HHMMSS_add_status_and_category_to_knowledge_bases_table.php

use App\Enums\KnowledgeBaseCategory;
use App\Enums\KnowledgeBaseStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('knowledge_bases', function (Blueprint $table) {
            $table->string('status', 20)->default('processing')->after('embedding');
            $table->string('category', 30)->default('general')->after('title');
            $table->string('source_document')->nullable()->after('content');
            $table->integer('page_number')->nullable()->after('source_document');
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_bases', function (Blueprint $table) {
            $table->dropColumn(['status', 'category', 'source_document', 'page_number']);
        });
    }
};
```

**Update Model:**

```php
// app/Models/KnowledgeBase.php — ganti seluruh file:

namespace App\Models;

use App\Enums\KnowledgeBaseCategory;
use App\Enums\KnowledgeBaseStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['title', 'content', 'metadata', 'embedding', 'status', 'category', 'source_document', 'page_number'])]
class KnowledgeBase extends Model
{
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'status' => KnowledgeBaseStatus::class,
            'category' => KnowledgeBaseCategory::class,
            'page_number' => 'integer',
        ];
    }

    public function knowledgeable(): MorphTo
    {
        return $this->morphTo();
    }

    public function processEmbedding(): void
    {
        $this->update(['status' => KnowledgeBaseStatus::PROCESSING]);
    }
}
```

**Buat Enums jika belum ada:**

```php
// app/Enums/KnowledgeBaseStatus.php

namespace App\Enums;

enum KnowledgeBaseStatus: string
{
    case PROCESSING = 'processing';
    case READY = 'ready';
    case ERROR = 'error';
}

// app/Enums/KnowledgeBaseCategory.php

namespace App\Enums;

enum KnowledgeBaseCategory: string
{
    case HR_POLICY = 'hr_policy';
    case IT_GUIDE = 'it_guide';
    case GENERAL = 'general';
    case FINANCE = 'finance';
    case OTHER = 'other';
}
```

**Verifikasi:**
```bash
php artisan migrate
php artisan tinker --execute '
$kb = new App\Models\KnowledgeBase();
$kb->title = "Test";
$kb->content = "Test content";
$kb->status = App\Enums\KnowledgeBaseStatus::PROCESSING;
$kb->category = App\Enums\KnowledgeBaseCategory::GENERAL;
$kb->save();
echo "OK: KnowledgeBase saved with status and category";
'
```

---

### ❌ 2.2 B2: isAllApproved() Vacuous Truth — NOT DONE

> **Status:** ❌ `Approvable.php:24-28` — `isAllApproved()` missing `$this->approvals()->exists()` guard. Returns true if 0 approvals exist.

**Masalah:** Method return true jika 0 approval → bypass approval workflow.  
**Severity:** HIGH  
**Depends On:** 0.6 (ApprovalService rewrite, sudah termasuk fix ini)  
**Estimasi:** 5 menit (sudah diperbaiki di 0.6)

**Jika dikerjakan terpisah:**

```php
// app/Traits/Approvable.php — ganti method isAllApproved():

public function isAllApproved(): bool
{
    return $this->approvals()->exists()
        && $this->approvals()
            ->where('status', '!=', ApprovalStatus::APPROVED)
            ->doesntExist();
}
```

---

### ✅ 2.3 B3: isLocked() Tidak Block PAID — SELESAI

> **Status:** ✅ SELESAI — `Payroll.php:61-64` uses `in_array($this->status, [PUBLISHED, PAID])`. Auditor: 2026-06-03.

**Masalah:** `isLocked()` hanya return true untuk PUBLISHED, tapi PAID juga harus locked.  
**Severity:** HIGH  
**Depends On:** —  
**Estimasi:** 5 menit

```php
// app/Models/Payroll.php — ganti method isLocked():

// SEBELUM:
public function isLocked(): bool
{
    return $this->status === PayrollStatus::PUBLISHED;
}

// SESUDAH:
public function isLocked(): bool
{
    return in_array($this->status, [PayrollStatus::PUBLISHED, PayrollStatus::PAID]);
}
```

**Verifikasi:**
```bash
php artisan tinker --execute '
$p = new App\Models\Payroll();
$p->status = App\Enums\PayrollStatus::PAID;
echo $p->isLocked() ? "OK: PAID is locked" : "FAIL: PAID is not locked";
'
```

---

### ✅ 2.4 B4: PayrollAdjustment decimal — SELESAI PayrollAdjustment.amount Cast integer → decimal:2

**Masalah:** Uang disimpan sebagai integer, precision loss.  
**Severity:** HIGH  
**Depends On:** —  
**Estimasi:** 5 menit

**Langkah 1 — Edit file migration asli** (development stage, cukup edit, tidak perlu ALTER migration terpisah):

```bash
# Edit langsung file ini:
# database/migrations/2026_05_08_161915_create_payroll_adjustments_table.php
```

```php
// database/migrations/2026_05_08_161915_create_payroll_adjustments_table.php
// GANTI baris:
// $table->integer('amount');
// MENJADI:
$table->decimal('amount', 15, 2);
```

**Langkah 2 — Update Model cast:**

```php
// app/Models/PayrollAdjustment.php — ganti cast:

// SEBELUM:
'amount' => 'integer',

// SESUDAH:
'amount' => 'decimal:2',
```

**Verifikasi:**
```bash
php artisan tinker --execute '
$adj = new App\Models\PayrollAdjustment();
$adj->amount = 500000.50;
echo $adj->amount;  // Harus tampil 500000.50, bukan 500000
'
```

---

### 🔀 2.5 B5: Company Address Columns — SKIPPED (3NF redesign) Company Model Missing 6 Address Columns

**Masalah:** `province_id`, `city_id`, `district_id`, `village_id`, `postal_code`, `address_detail` tidak di `$fillable`.  
**Severity:** HIGH  
**Depends On:** —  
**Estimasi:** 10 menit

```php
// app/Models/Company.php — ganti #[Fillable] dan tambah relationships:

#[Fillable([
    'name', 'phone', 'email', 'website', 'npwp', 'code', 'logo', 'is_active',
    'province_id', 'city_id', 'district_id', 'village_id',
    'postal_code', 'address_detail',
])]
```

```php
// Tambah relationships ke class Company:

public function province(): BelongsTo
{
    return $this->belongsTo(Province::class);
}

public function city(): BelongsTo
{
    return $this->belongsTo(City::class);
}

public function district(): BelongsTo
{
    return $this->belongsTo(District::class);
}

public function village(): BelongsTo
{
    return $this->belongsTo(Village::class);
}
```

Jangan lupa tambah import di atas file:
```php
use App\Models\Province;
use App\Models\City;
use App\Models\District;
use App\Models\Village;
```

---

### ✅ 2.6 B6: Asset — SoftDeletes + Migration — SELESAI

**Masalah:** Migration punya `deleted_at` tapi model tidak pakai trait. Kolom `company_id`, `code`, `category`, `status` ada di ERD tapi tidak di model.  
**Severity:** HIGH  
**Status:** ✅ SELESAI — Kolom langsung ditambahkan ke migration asli (bukan alter table karena development), model fillable/cast/relationship diupdate, AssetStatus cast ditambahkan.

**Masalah:** Migration punya `deleted_at` tapi model tidak pakai trait. Kolom `company_id`, `code`, `category`, `status` ada di ERD tapi tidak di model.  
**Severity:** HIGH  
**Status:** ✅ SELESAI — Kolom langsung ditambahkan ke migration asli, model fillable/cast/relationship diupdate, AssetStatus cast ditambahkan.

---

### ✅ 2.7 B8: LeaveBalance.deduct() Bisa Negatif — SELESAI

> **Status:** ✅ SELESAI — Guard `available() < $days` throw `BusinessRuleException` + `increment('used', $days)` setelah guard. Plus method `refund()` baru. Verified via tinker: deduct(3): 12→9, deduct(999): throw, refund(2): 9→11, refund(999): used=0 (no negative).

**Bukti:**
- `LeaveBalance.php:51-57` — guard + increment
- `LeaveBalance.php:59-63` — refund method dengan `max(0, ...)` cegah negative

---

### ✅ 2.8 M4: TerCategory resolveFromStatus Crash pada DIVORCED/WIDOWED — SELESAI

**Masalah:** String 'divorced'/'widowed' tidak ditangani, jatuh ke match default (yang untuk married). Juga: `PayrollCalculatorService::getTERCategory()` menduplikasi logika yang sama.
**Severity:** HIGH  
**Depends On:** —  
**Estimasi:** 5 menit  
**Status:** ✅ SELESAI

**Fix 1 — TerCategory::resolveFromStatus():**
- Signature: `string $maritalStatus` → `MaritalStatus $status` (type-safe)
- Signature: `int $childrenCount` → `int $dependents` (lebih jelas)
- Logic: `if ($maritalStatus === 'single')` → `if (in_array($status, [MaritalStatus::SINGLE, MaritalStatus::DIVORCED, MaritalStatus::WIDOWED]))`

**Fix 2 — PayrollCalculatorService::getTERCategory():**
- Duplikasi logika dihapus, diganti dengan delegasi ke `TerCategory::resolveFromStatus($employee->marital_status, $dependentsCount)`
- Import `MaritalStatus` dihapus (tidak lagi digunakan langsung)

```php
// app/Enums/TerCategory.php — ganti method resolveFromStatus():

public static function resolveFromStatus(string $maritalStatus, int $childrenCount): self
{
    $dependents = min($childrenCount, 3);

    // DIVORCED dan WIDOWED diperlakukan sama seperti SINGLE ( TK/ )
    if (in_array($maritalStatus, ['single', 'divorced', 'widowed'])) {
        return match ($dependents) {
            0, 1 => self::A,
            2, 3 => self::B,
        };
    }

    // MARRIED = K/
    return match ($dependents) {
        0, 1 => self::B,
        2, 3 => self::C,
    };
}
```

---

### 🔀 2.9 M2: Tax/BPJS Cache Invalidation — MERGED ke §0.10

**Masalah:** Admin ubah tarif → cache pakai tarif lama selama 24 jam.
**Severity:** MEDIUM
**Status:** 🔀 MERGED ke §0.10 (DL-5) — TaxConfigObserver + BpjsConfigObserver dikonsolidasi dengan domain observer (Employee/Attendance) + Holiday observer untuk satu register block di AppServiceProvider.

> Lihat §0.10 Langkah 4-5 untuk implementasi `TaxConfigObserver` + `BpjsConfigObserver`. Cache key direname: `tax_configs` → `taxes:configs`, `bpjs_configs` → `bpjs:configs` (konvensi colon).

---

### ✅ 2.10 SEC-6: Approval Model — Mass Assignment Risk pada `approvable_type`/`approvable_id` — SELESAI

> **Status:** ✅ SELESAI — `Approval.php` fillable does NOT include approvable_type/approvable_id. Auditor: 2026-06-03.

**Masalah:** `approvable_type` dan `approvable_id` (polymorphic morph columns) ada di `#[Fillable]`. Ini memungkinkan mass-assignment yang bisa mengubah target approval ke model yang tidak diinginkan.  
**Severity:** MEDIUM  
**Depends On:** —  
**Estimasi:** 5 menit

```php
// app/Models/Approval.php — ganti #[Fillable] attribute:

// SEBELUM:
#[Fillable(['approvable_type', 'approvable_id', 'approver_id', 'level', 'status', 'notes', 'approved_at'])]

// SESUDAH (hapus approvable_type dan approvable_id):
#[Fillable(['approver_id', 'level', 'status', 'notes', 'approved_at'])]
```

> approvable harus diset via `$approval->approvable()->associate($model)`, bukan mass-assignment.

---

### ✅ 2.11 H9: Employee vector cast — SELESAI Employee Model — Missing `vector` Cast untuk `face_embedding`

**Masalah:** Migration mendefinisikan `face_embedding` sebagai `vector(128)` (pgvector), tapi model tidak punya cast. Tanpa cast, read/write kolom ini menyebabkan serialization error atau string mentah.  
**Severity:** HIGH  
**Depends On:** —  
**Estimasi:** 5 menit

```php
// app/Models/Employee.php — tambah di casts():

'face_embedding' => 'vector',
```

> **CATATAN:** Pastikan `pgvector` package terinstall (`composer require pgvector/laravel`). Jika belum, tambah ke composer.json.

---

### ✅ 2.12 H10: KnowledgeBase vector cast — SELESAI KnowledgeBase Model — Missing `vector` Cast untuk `embedding`

**Masalah:** Sama seperti H9, migration mendefinisikan `embedding` sebagai `vector(768)` (Gemini text-embedding-004), tapi model tidak punya cast.  
**Severity:** HIGH  
**Depends On:** —  
**Estimasi:** 5 menit

```php
// app/Models/KnowledgeBase.php — tambah di casts():

'embedding' => 'vector',
```

---

### ✅ 2.13 M1: Holiday Model — Missing `date` Cast — SELESAI

> **Status:** ✅ SELESAI — `Holiday.php:19` has `'date' => 'date'` cast. Auditor: 2026-06-03.

**Masalah:** Kolom `date` tanpa cast mengembalikan string mentah, bukan Carbon instance.  
**Severity:** MEDIUM  
**Depends On:** —  
**Estimasi:** 2 menit

```php
// app/Models/Holiday.php — tambah di casts():

'date' => 'date',
```

---

### 🔀 2.14 Company/Branch address_detail — SKIPPED (3NF) Company dan Branch `address_detail` NOT NULL — Harus Nullable

**Masalah:** Sama seperti employees (§4.1e), `companies.address_detail` dan `branches.address_detail` NOT NULL. Ini mencegah pembuatan company/branch tanpa alamat lengkap.  
**Severity:** MEDIUM  
**Depends On:** —  
**Estimasi:** 5 menit

```php
// database/migrations/2026_04_12_203653_create_companies_table.php
// GANTI: $table->text('address_detail');
// MENJADI: $table->text('address_detail')->nullable();

// database/migrations/2026_04_13_152045_create_branches_table.php
// GANTI: $table->text('address_detail');
// MENJADI: $table->text('address_detail')->nullable();
```

---

### ✅ 2.15 Employee npwp/bank nullable — SELESAI Employee `npwp`, `bank_account_number`, `bank_name` NOT NULL — Harus Nullable

**Masalah:** `npwp`, `bank_account_number`, dan `bank_name` NOT NULL di migration, tapi CipherSweet menganggap field ini opsional (`addOptionalTextField`). Karyawan baru/probation sering belum punya NPWP atau rekening bank.  
**Severity:** MEDIUM  
**Depends On:** —  
**Estimasi:** 5 menit

```php
// database/migrations/2026_04_16_192201_create_employees_table.php
// GANTI:
// $table->text('npwp');
// $table->text('bank_account_number');
// $table->string('bank_name', 100);
// MENJADI:
$table->text('npwp')->nullable();
$table->text('bank_account_number')->nullable();
$table->string('bank_name', 100)->nullable();
```

---

### ✅ 2.16 M5: Missing Foreign Key Indexes (PostgreSQL Performance) — SELESAI

> **Status:** ✅ SELESAI — FK indexes added to payroll_adjustments.payroll_id/created_by, asset_handovers.asset_id/employee_id, performance_reviews.employee_id/reviewer_id. Fix: 2026-06-03.

**Masalah:** PostgreSQL tidak auto-create index pada FK columns. 5 FK columns tanpa index menyebabkan slow JOINs.  
**Severity:** MEDIUM  
**Depends On:** —  
**Estimasi:** 5 menit

```bash
php artisan make:migration add_foreign_key_indexes
```

```php
// database/migrations/YYYY_MM_DD_HHMMSS_add_foreign_key_indexes.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_adjustments', function (Blueprint $table) {
            $table->index('payroll_id');
        });
        Schema::table('performance_reviews', function (Blueprint $table) {
            $table->index('employee_id');
            $table->index('reviewer_id');
        });
        Schema::table('asset_handovers', function (Blueprint $table) {
            $table->index('asset_id');
            $table->index('employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_adjustments', function (Blueprint $table) {
            $table->dropIndex(['payroll_id']);
        });
        Schema::table('performance_reviews', function (Blueprint $table) {
            $table->dropIndex(['employee_id']);
            $table->dropIndex(['reviewer_id']);
        });
        Schema::table('asset_handovers', function (Blueprint $table) {
            $table->dropIndex(['asset_id']);
            $table->dropIndex(['employee_id']);
        });
    }
};
```

---

### ✅ 2.17 M12: AssetStatus enum — SELESAI

**Masalah:** `AssetStatus` enum (AVAILABLE, ASSIGNED, DISPOSED) didefinisikan tapi tidak dipakai oleh model. Model pakai `is_available` boolean. Enum tidak bisa men-track status "disposed".  
**Severity:** MEDIUM  
**Status:** ✅ SELESAI — Migration menambah `status` column, model sudah pakai `AssetStatus` cast, `company()` relationship ditambahkan.

> **CATATAN:** `is_available` boolean menjadi redundant dengan `status` enum. Pertimbangkan untuk deprecate `is_available` dan gunakan `$asset->status === AssetStatus::AVAILABLE` sebagai pengganti.

---

### ✅ 2.18 M13: LoanInstallmentStatus — SELESAI

**Masalah:** Setiap model status lain (Attendance, Leave, Payroll, Reimbursement, Approval, Overtime) menggunakan backed enum cast. `LoanInstallment.status` masih plain string.  
**Severity:** LOW  
**Status:** ✅ SELESAI — Enum `LoanInstallmentStatus` sudah ada (pending/paid/overdue), model cast `'status' => LoanInstallmentStatus::class` ditambahkan.

```php
// Buat enum baru:
// app/Enums/LoanInstallmentStatus.php

namespace App\Enums;

enum LoanInstallmentStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case OVERDUE = 'overdue';
}

// app/Models/LoanInstallment.php — tambah di casts():
'status' => LoanInstallmentStatus::class,
```

---

### ✅ 2.19 M14-NEW: 16 Enum Classification — HAPUS color() (Visual Noise), 17 Enum Status — STANDARDIZE ke Flux UI Semantic Names — SELESAI

> **Status:** ✅ SELESAI — Classification enums have NO color(), Status enums use only Flux UI colors (success/warning/danger/info/zinc). Auditor: 2026-06-03.

**Masalah:** AGENTS.md menyatakan "Each enum has `label()` + `color()` methods", tapi memberi warna ke semua enum menciptakan Visual Noise di dashboard — seperti pasar malam. Enum dibagi 2 kategori:

1. **Status/Indicator (WAJIB color())** — menunjukkan state/urgensi, user perlu RAG scan cepat
2. **Classification/Data (HARAM color())** — murni klasifikasi administratif, warna menambah noise tanpa value

**Prinsip:** Ketika user melihat **merah** di dashboard, harus artinya "bahaya/gagal" — bukan "golongan darah O" atau "tipe BPJS tertentu".

**Severity:** MEDIUM  
**Depends On:** —  
**Estimasi:** 30 menit  
**Status:** ✅ SELESAI

**Enum dengan color() (16 — Status/Indicator):**

| Enum | Warna (Flux UI Semantic) |
|------|--------------------------|
| ApprovalStatus | pending=warning, approved=success, rejected=danger |
| EmployeeStatus | active=success, inactive=zinc, resigned=warning, terminated=danger, deceased=zinc |
| PayrollStatus | draft=zinc, published=info, paid=success |
| PayrollItemType | allowance=success, deduction=danger |
| TerCategory | A=info, B=warning, C=danger |
| EmploymentType | permanent=success, contract=info, probation=warning, intern=zinc |
| TerminationType | resign=warning, dismissed=danger, deceased=zinc, contract_end=info |
| MaritalStatus | single=zinc, married=success, divorced=warning, widowed=info |
| AttendanceStatus | on_time/holiday/permission=success, late/early=warning, absent/missed_clock_in/missed_clock_out=danger |
| RequestStatus | pending=warning, approved_l1=info, approved=success, rejected/cancelled=danger |
| ReimbursementStatus | pending=warning, approved=info, paid=success, rejected=danger |
| LoanStatus | pending=warning, approved=info, active=success, paid_off=zinc, rejected=danger, cancelled=zinc |
| LoanInstallmentStatus | pending=warning, paid=success, overdue=danger |
| WfaStatus | pending=warning, approved=success, rejected=danger |
| AssetStatus | available=success, assigned=info, disposed=danger |
| KnowledgeBaseStatus | processing=warning, ready=success, error=danger |

**Enum TANPA color() (17 — Classification/Data — HAPUS):**

ApprovalLevel, BloodType, BpjsType, CompanySettingType, DayType (← refactored: color() dihapus, getQuotaDeduction() → weight()), DeviceType, EducationLevel, FamilyRelationship, Gender, HandoverCategory, KnowledgeBaseCategory, LeaveQuotaReset, NotificationType, ResignationReason, SalaryType, ShiftScheduleType, VerificationMethod

> **Catatan Flux UI Semantic Names:** `success` (hijau), `warning` (kuning/amber), `danger` (merah), `info` (biru), `zinc` (abu-abu/netral). Palet 5 warna — tanpa `violet`. `DayType` awalnya dikategorikan sebagai WAJIB, tetapi setelah review arsitektur UI, dipindahkan ke HAPUS karena merupakan klasifikasi (kuantitas), bukan status indicator (kualitas). Method `color()` dihapus, method `getQuotaDeduction()` di-rename ke `weight()` yang lebih idiomatik.

**Verifikasi:**
```bash
grep -rl "public function color()" app/Enums/ | wc -l
# Harus: 17

# Pastikan tidak ada warna mentah (green, red, amber, blue, primary, slate, neutral):
grep -rh "=>" app/Enums/ | grep -E "'(green|red|amber|blue|primary|slate|neutral)'"
# Harus: 0 results
```

> Flux UI semantic color names: zinc, slate, red, orange, amber, yellow, lime, green, emerald, teal, cyan, sky, blue, indigo, violet, purple, fuchsia, pink, rose.

**Verifikasi:**
```bash
php artisan tinker --execute '
$enum = App\Enums\AttendanceStatus::LATE;
echo $enum->label() . " = " . $enum->color();
// Harus: Terlambat = amber
'
```

---

### ✅ 2.19 M14: `bpjs_configs.name` Missing Unique Constraint — SELESAI

> **Status:** ✅ SELESAI — Migration sudah `$table->string('name')->unique()`. Verified via tinker: insert duplicate `'kesehatan'` throw `UniqueConstraintViolationException`.

**Bukti:** `database/migrations/2026_05_08_161905_create_bpjs_configs_table.php:16`

---

### ✅ 2.20 M15: WfaStatus — SELESAI

**Masalah:** §4.1 Migration 6 menambah `status_wfa varchar(20) nullable` tapi tidak ada enum. Nilai status akan disimpan sebagai untyped string.  
**Severity:** MEDIUM  
**Status:** ✅ SELESAI — Enum `WfaStatus` sudah ada (pending/approved/rejected), migration column ditambahkan, fillable + cast di Attendance model sudah ada. ERD diupdate.

```php
// app/Enums/WfaStatus.php

namespace App\Enums;

enum WfaStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
}
```

```php
// app/Models/Attendance.php — tambah di casts():
'status_wfa' => WfaStatus::class,
```

```php
// app/Models/Attendance.php — tambah di #[Fillable] array:
'status_wfa'
```

---

### ✅ 2.21 L5: PerformanceReview Missing Status, Review_Date, Period, SoftDeletes — SELESAI

> **Status:** ✅ SELESAI — Migration already has all columns (status, review_date, period, softDeletes). Auditor: 2026-06-03.

**Masalah:** PerformanceReview table hanya punya `employee_id`, `reviewer_id`, `final_score`, `notes`, timestamps. Tidak bisa track lifecycle (draft/in-progress/completed), tidak bisa group by period, dan hard-delete menghilangkan audit trail.  
**Severity:** LOW  
**Depends On:** —  
**Estimasi:** 30 menit

```bash
php artisan make:migration add_status_and_period_to_performance_reviews_table
```

```php
// database/migrations/YYYY_MM_DD_HHMMSS_add_status_and_period_to_performance_reviews_table.php

use App\Enums\PerformanceReviewStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('performance_reviews', function (Blueprint $table) {
            $table->string('status', 20)->default('draft')->after('id');
            $table->date('review_date')->nullable()->after('status');
            $table->string('period', 20)->nullable()->after('review_date');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('performance_reviews', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['status', 'review_date', 'period']);
        });
    }
};
```

```php
// app/Enums/PerformanceReviewStatus.php

namespace App\Enums;

enum PerformanceReviewStatus: string
{
    case DRAFT = 'draft';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
```

```php
// app/Models/PerformanceReview.php — tambah trait + casts:
use HasFactory, SoftDeletes;

protected function casts(): array
{
    return [
        'review_date' => 'date',
        'final_score' => 'decimal:2',
        'status' => PerformanceReviewStatus::class,
    ];
}
```

---

### ✅ 2.22 L6: Shift Model Missing `SoftDeletes` — SELESAI

> **Status:** ✅ SELESAI — SoftDeletes trait added + migration `add_soft_deletes_to_shifts_table`. Fix: 2026-06-03.

**Masalah:** Shift di-referenced oleh `attendances.shift_id` dan `employees.shift_id`. Hard-delete menyebabkan foreign key constraint error atau dangling references.  
**Severity:** LOW  
**Depends On:** —  
**Estimasi:** 5 menit

```php
// database/migrations/2026_05_08_161810_create_shift_schedules_table.php
// Jika belum ada softDeletes di migration:
// Tambahkan: $table->softDeletes();

// app/Models/Shift.php — tambah trait:
use SoftDeletes;
```

> **CATATAN:** Cek terlebih dahulu apakah migration `create_shift_schedules_table` sudah punya `$table->softDeletes()`. Jika belum, edit migration asli.

---

### ✅ 2.23 L7: CompanySetting `get()` Method — Double-Decode Risk — SELESAI

> **Status:** ✅ SELESAI — Uses Eloquent `first()?->value` with array cast, no raw json_decode(). Fix: 2026-06-03.

**Masalah:** Method `get()` bypass Eloquent dengan raw query `->value('value')` + `json_decode()`. Tapi model punya `'value' => 'array'` cast. Jika di-refactor ke Eloquent access, `json_decode` akan dijalankan 2x.  
**Severity:** LOW  
**Depends On:** —  
**Estimasi:** 5 menit

```php
// app/Models/CompanySetting.php — ganti method get():

public static function get(string $key, mixed $default = null): mixed
{
    return Cache::remember("settings:{$key}", now()->addDay(), function () use ($key, $default) {
        $setting = static::where('key', $key)->first();
        if ($setting) {
            return $setting->value; // Eloquent cast handles json_decode
        }
        return $default;
    });
}
```

---

### ❌ 2.24 H2: ReimbursementService `linkToPayroll` — No Duplicate Check & No Payroll Validation — NOT DONE

> **Status:** ❌ `linkToPayroll()` checks `isApproved()` but not `payroll_id !== null` (double-link risk).

**Masalah:** `linkToPayroll()` tidak cek apakah `payroll_id` sudah terisi (bisa double-link), dan tidak verifikasi bahwa payroll exists serta status DRAFT.  
**Severity:** MEDIUM  
**Depends On:** —  
**Estimasi:** 10 menit

```php
// app/Services/ReimbursementService.php — di dalam method linkToPaylawl():

public function linkToPayroll(Reimbursement $reimbursement, int $payrollId): void
{
    // Guard 1: jangan link jika sudah terhubung
    if ($reimbursement->payroll_id !== null) {
        throw new BusinessRuleException('Reimbursement sudah terhubung ke payroll lain.');
    }

    // Guard 2: payroll harus ada dan dalam status DRAFT
    $payroll = Payroll::findOrFail($payrollId);
    if ($payroll->isLocked()) {
        throw new BusinessRuleException('Payroll sudah dikunci dan tidak dapat diubah.');
    }

    $reimbursement->update([
        'payroll_id' => $payrollId,
    ]);
}
```

---

### ✅ 2.25 EmploymentType 4 values — SELESAI EmploymentType — PRD Kontradiksi 3 vs 4 Values

**Masalah:** PRD §18 line 666 list 3 values (`permanent`, `contract`, `probation`), tapi PRD §18 line 713 dan ERD punya 4 values (+ `intern`). Kode aktual `app/Enums/EmploymentType.php` sudah punya 4 values. `intern` punya business logic berbeda: BPJS dan PPh21 **TIDAK dipotong** untuk intern.  
**Severity:** MEDIUM  
**Depends On:** —  
**Estimasi:** 5 menit

> **CTO Decision (CAT-006):** Pakai 4 values. InternExemptTest.php sudah ada di testing strategy.

**Verifikasi enum sudah benar:**

```php
// app/Enums/EmploymentType.php — pastikan ada 4 values:

enum EmploymentType: string
{
    case PERMANENT = 'permanent';
    case CONTRACT = 'contract';
    case PROBATION = 'probation';
    case INTERN = 'intern';
}
```

**Verifikasi:**
```bash
php artisan tinker --execute '
$cases = App\Enums\EmploymentType::cases();
echo "EmploymentType values: " . count($cases); // 4
foreach ($cases as $case) echo "\n  - " . $case->value;
'
```

---

### ✅ 2.26 M8: PRD §14.7 Overtime Flat Rate Keys — HARUS Diganti Tiered — SELESAI

> **Status:** ✅ SELESAI — `PayrollCalculatorService::calculateOvertimePay()` implements UU Cipta Kerja tiered logic. Auditor: 2026-06-03.

**Masalah:** PRD §14.7 masih mendefinisikan `overtime_multiplier` (1.5) dan `overtime_weekend_multiplier` (2.0) sebagai flat rate. ERR-001 menyatakan bahwa key ini HARUS DIHAPUS dan diganti dengan `overtime_tiers_weekday` dan `overtime_tiers_holiday` (JSON indexed tiers).  
**Severity:** HIGH (berdampak ke payroll calculation - gaji ganda jika config salah)  
**Depends On:** 3.1 (Overtime Rate Calculation)  
**Estimasi:** 10 menit

> **CAT-007:** Ini dokumentasi fix, bukan kode fix. Kode fix sudah di §3.1.

**PRD §14.7 `company_settings` table — UPDATE:**

| Key | Type | Description |
|-----|------|-------------|
| ~~`overtime_multiplier`~~ | ~~float~~ | ~~DELETED — see ERR-001~~ |
| ~~`overtime_weekend_multiplier`~~ | ~~float~~ | ~~DELETED — see ERR-001~~ |
| `overtime_tiers_weekday` | JSON | Tiered weekday overtime rates — see ERR-001 |
| `overtime_tiers_holiday` | JSON | Tiered holiday/weekend overtime rates — see ERR-001 |

**Default seed data:**

```php
// database/seeders/CompanySettingSeeder.php
'overtime_tiers_weekday' => json_encode([
    ['from' => 1, 'to' => 1, 'multiplier' => 1.5],
    ['from' => 2, 'to' => null, 'multiplier' => 2.0],
]),
'overtime_tiers_holiday' => json_encode([
    ['from' => 1, 'to' => 8, 'multiplier' => 2.0],
    ['from' => 9, 'to' => 9, 'multiplier' => 3.0],
    ['from' => 10, 'to' => null, 'multiplier' => 4.0],
]),
```

**Verifikasi:**
```bash
php artisan tinker --execute '
$settings = App\Models\CompanySetting::first();
echo "Has overtime_tiers_weekday: " . ($settings && $settings->overtime_tiers_weekday ? "YES" : "NO");
echo "\nHas overtime_tiers_holiday: " . ($settings && $settings->overtime_tiers_holiday ? "YES" : "NO");
'
```

---

### ✅ 2.27 M9: PRD §27 Duplicate "Export to Excel" Entry — SELESAI

> **Status:** ✅ SELESAI — Only `§15.4 Export to Excel (V1)` exists. CAT-008 applied. Auditor: 2026-06-03.

**Masalah:** PRD §27 punya dua entry "Export to Excel" yang kontradiktif:
- Line 1124: "Export to Excel | PDF sudah cukup | 3-4 hari" (status: ditunda ke V2)
- Line 1129: "~~Export to Excel~~ | → V1 | ~~3-4 hari~~" (dipindahkan ke V1)

**Severity:** LOW (dokumentasi saja)  
**Depends On:** —  
**Estimasi:** 2 menit

> **CTO Decision (CAT-008):** Export to Excel = V1. Hapus entry line 1124 yang bilang "PDF sudah cukup".

**Aksi:** Update PRD §27 — hapus entry "Export to Excel | PDF sudah cukup" yang ditunda ke V2. Pertahankan entry yang bilang V1.

---

### ✅ 2.28 AttendanceStatus 8 values — SELESAI Attendance Status — 6 vs 8 Values

**Masalah:** PRD §6.2 hanya list 6 status: `on_time`, `late`, `early`, `holiday`, `permission`, `absent`. ERD dan kode aktual punya 8 values: tambah `missed_clock_in` dan `missed_clock_out`.  
**Severity:** MEDIUM  
**Depends On:** —  
**Estimasi:** 5 menit

> **CAT-009:** PRD §6.2 harus ditambahkan:
> - `missed_clock_in` — Karyawan clock-out tanpa clock-in sebelumnya
> - `missed_clock_out` — Karyawan clock-in tapi lupa clock-out

**Verifikasi enum sudah benar:**

```php
// app/Enums/AttendanceStatus.php — pastikan ada 8 values:

enum AttendanceStatus: string
{
    case ON_TIME = 'on_time';
    case LATE = 'late';
    case EARLY = 'early';          // clock-out lebih awal
    case HOLIDAY = 'holiday';
    case PERMISSION = 'permission';
    case ABSENT = 'absent';
    case MISSED_CLOCK_IN = 'missed_clock_in';
    case MISSED_CLOCK_OUT = 'missed_clock_out';
}
```

```bash
php artisan tinker --execute '
$cases = App\Enums\AttendanceStatus::cases();
echo "AttendanceStatus values: " . count($cases); // 8
'
```

---

### ✅ 2.29 M11: Missing `attendance:detect-chronic-late` Command — SELESAI

> **Status:** ✅ SELESAI — `DetectChronicLateCommand.php` exists with weekly Friday 18:00 schedule. Auditor: 2026-06-03.

**Masalah:** PRD §6.5 mendokumentasi command `attendance:detect-chronic-late` (weeklyOn Friday 18:00). PRD §27 menandai ChronicLateWarning sebagai V1. Tapi PRD §16 Commands table hanya list 3 commands (`attendance:detect-alpha`, `leave:reset-quota`, `model:prune`). Command `attendance:detect-chronic-late` tidak ada di kode maupun scheduler.  
**Severity:** MEDIUM  
**Depends On:** —  
**Estimasi:** 15 menit

> **CAT-010:** Tambahkan `attendance:detect-chronic-late` ke PRD §16 dan implementasikan.

**Langkah 1 — Buat command:**

```bash
php artisan make:command DetectChronicLateCommand
```

```php
// app/Console/Commands/DetectChronicLateCommand.php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Enums\AttendanceStatus;
use App\Notifications\ChronicLateWarning;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DetectChronicLateCommand extends Command
{
    protected $signature = 'attendance:detect-chronic-late';
    protected $description = 'Deteksi karyawan telat ≥ 3x/bulan dan kirim warning';

    public function handle(): int
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $lateCounts = Attendance::where('status', AttendanceStatus::LATE)
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->selectRaw('employee_id, COUNT(*) as late_count')
            ->groupBy('employee_id')
            ->having('late_count', '>=', 3)
            ->pluck('late_count', 'employee_id');

        foreach ($lateCounts as $employeeId => $count) {
            $employee = \App\Models\Employee::find($employeeId);
            if ($employee?->user) {
                $employee->user->notify(new ChronicLateWarning($employee, $count));
            }
        }

        $this->info("Detected {$lateCounts->count()} chronic late employees.");
        return self::SUCCESS;
    }
}
```

**Langkah 2 — Register di scheduler:**

```php
// app/Console/Kernel.php atau routes/console.php (Laravel 11+)
use Illuminate\Support\Facades\Schedule;

Schedule::command('attendance:detect-chronic-late')
    ->weeklyOn(5, '18:00')
    ->timezone('Asia/Jakarta');
```

**Verifikasi:**
```bash
php artisan attendance:detect-chronic-late
php artisan schedule:list | grep chronic-late
```

---

### ✅ 2.30 M17: Cache dead code — SELESAI AttendanceService::invalidateCache() — Dead Code (Database Driver Tidak Dukung Tags)

**Masalah:** `AttendanceService::invalidateCache()` (lines 184-190) dipanggil setiap clock-in/out. `Cache::supportsTags()` SELALU `false` dengan database driver → `Cache::tags()->flush()` dead code. `Cache::forget(...)` dijalankan tapi cache key tidak pernah di-populate. **CPU terbuang sia-sia** — 2 operasi cache yang tidak berguna per absensi.  
**Severity:** HIGH  
**Depends On:** —  
**Estimasi:** 5 menit

> **CTO Decision (CAT-012):** Hapus dead code. Attendance query sudah di-index (`UNIQUE(employee_id, date)`). Menambah Redis hanya untuk attendance cache adalah over-engineering untuk MVP.

**Langkah 1 — Hapus method `invalidateCache()` dari AttendanceService:**

```php
// app/Services/AttendanceService.php — HAPUS seluruh method:
// ❌ private function invalidateCache(Employee $employee): void { ... }
```

**Langkah 2 — Hapus semua panggilan `$this->invalidateCache($employee)`:**

```php
// app/Services/AttendanceService.php — line 103 (di dalam clockIn):
// HAPUS: $this->invalidateCache($employee);

// app/Services/AttendanceService.php — line 162 (di dalam clockOut):
// HAPUS: $this->invalidateCache($employee);
```

**Verifikasi:**
```bash
grep -n 'invalidateCache' app/Services/AttendanceService.php
# Harus return 0 results
```

---

### 🔀 2.31 M18: Holiday Cache Invalidation + Yearly Refactor — MERGED ke §0.10

**Masalah:**
1. `PayrollCalculatorService::calculateOvertimePay()` line 88 pakai cache key `holiday_{YYYY-MM-DD}` (per-tanggal). Payroll batch loop 30 hari → 30+ cache entries per karyawan per bulan. Boros memori.
2. Model `Holiday` tidak punya observer untuk `Cache::forget()`. Admin tambah/hapus libur → calculation pakai data basi sampai TTL 30 hari expired.

**Severity:** HIGH (waste + correctness)
**Status:** 🔀 MERGED ke §0.10 (DL-5) — `HolidayObserver` dengan refactor cache key dari `holiday_{date}` → `holidays:{year}` (1 entry per tahun, in-memory check).

> Lihat §0.10 Langkah 3 (`HolidayObserver`) + Langkah 7 (refactor `PayrollCalculatorService` cache key).

---

### ❌ 2.32 M19: PTKP Magic Numbers Hardcoded — Harus dari CompanySetting — NOT DONE

> **Status:** ❌ `Employee::calculatePtkp()` hardcodes 54jt/58.5jt/4.5jt constants. Should use CompanySetting::get().

**Masalah:** `Employee::calculatePtkp()` hardcode 54jt (single), 58.5jt (married), 4.5jt (per dependent). PTKP berubah tiap tahun oleh Peraturan Menteri Keuangan. Hardcode artinya setiap perubahan butuh deploy kode baru.  
**Severity:** HIGH  
**Depends On:** —  
**Estimasi:** 15 menit

**Fix:**

```php
// app/Models/Employee.php — ganti method calculatePtkp():

public function calculatePtkp(): float
{
    $base = match ($this->marital_status) {
        MaritalStatus::MARRIED => (float) CompanySetting::get('ptkp_base_married', 58_500_000),
        default => (float) CompanySetting::get('ptkp_base_single', 54_000_000),
    };

    $perDependent = (float) CompanySetting::get('ptkp_per_dependent', 4_500_000);
    $maxDependents = (int) CompanySetting::get('ptkp_max_dependents', 3);

    $dependentsCount = $this->families()
        ->where('relationship', FamilyRelationship::CHILD)
        ->count();

    return $base + (min($dependentsCount, $maxDependents) * $perDependent);
}
```

> **CATATAN:** §3.4 sudah fix `families` query (filter CHILD only). Pastikan eager-load `families` di pemanggil jika dipanggil dalam loop payroll.

**Seeder default values:**
```php
// database/seeders/CompanySettingSeeder.php — tambah:
['company_id' => 1, 'key' => 'ptkp_base_single', 'value' => '54000000', 'description' => 'PTKP TK/0'],
['company_id' => 1, 'key' => 'ptkp_base_married', 'value' => '58500000', 'description' => 'PTKP K/0'],
['company_id' => 1, 'key' => 'ptkp_per_dependent', 'value' => '4500000', 'description' => 'PTKP per tanggungan (max 3)'],
['company_id' => 1, 'key' => 'ptkp_max_dependents', 'value' => '3', 'description' => 'Max tanggungan PTKP'],
```

**Verifikasi:**
```bash
php artisan tinker --execute '
$emp = App\Models\Employee::first();
echo "PTKP: " . number_format($emp->calculatePtkp(), 0, ",", ".");
'
```

---

### ✅ 2.33 M20: VerificationMethod enum — SELESAI VerificationMethod Enum Missing — Magic String + Bug `'pin'` vs `'pin_verified'`

**Masalah:** `AttendanceService` pakai string literal `'manual'`, `'face_verified'`, `'pin_verified'` di 6 baris tanpa enum. **BUG KRITIS di line 132:** compare ke `'pin'` tapi nilai valid adalah `'pin_verified'`. Kondisi ini **selalu false** → clock-out PIN verification logic broken.  
**Severity:** HIGH  
**Depends On:** —  
**Estimasi:** 10 menit

**Langkah 1 — Buat VerificationMethod enum:**

```php
// app/Enums/VerificationMethod.php

namespace App\Enums;

enum VerificationMethod: string
{
    case FACE_VERIFIED = 'face_verified';
    case PIN_VERIFIED = 'pin_verified';
    case MANUAL = 'manual';
}
```

**Langkah 2 — Ganti semua string literal di AttendanceService:**

```php
// app/Services/AttendanceService.php — ganti SEMUA kemunculan:

// SEBELUM:
$verificationMethod = 'manual';                    // line 51
$verificationMethod = 'face_verified';            // line 60
if ($verificationMethod === 'manual' ...)         // line 67
$verificationMethod = 'pin_verified';             // line 70
if ($verificationMethod === 'pin') {              // line 132 ← BUG!
$verificationMethod = 'face_verified';            // line 141

// SESUDAH:
use App\Enums\VerificationMethod;

$verificationMethod = VerificationMethod::MANUAL->value;
$verificationMethod = VerificationMethod::FACE_VERIFIED->value;
if ($verificationMethod === VerificationMethod::MANUAL->value ...)
$verificationMethod = VerificationMethod::PIN_VERIFIED->value;
if ($verificationMethod === VerificationMethod::PIN_VERIFIED->value) {  // ← FIXED
$verificationMethod = VerificationMethod::FACE_VERIFIED->value;
```

**Verifikasi:**
```bash
php artisan tinker --execute '
$cases = App\Enums\VerificationMethod::cases();
echo "Values: ";
foreach ($cases as $c) echo $c->value . " ";
echo "\nTotal: " . count($cases); // 3
'
```

---

### ✅ 2.34 M21: 22 Hari Kerja Hardcoded — Harus Pakai `countWorkingDays()` — SELESAI

> **Status:** ✅ SELESAI — Uses `$this->countWorkingDays()` with dynamic month calculation. Auditor: 2026-06-03.

**Masalah:** `PayrollCalculatorService` line 261: `$dailyRate / 22`. Angka 22 adalah asumsi jumlah hari kerja sebulan. Kenyataannya bervariasi (19-23 hari). Trait `ManagesWorkDays::countWorkingDays()` sudah ada.  
**Severity:** MEDIUM  
**Depends On:** —  
**Estimasi:** 5 menit

> **CAT-016:** Ganti hardcoded 22 dengan method `countWorkingDays()` yang menghitung aktual hari kerja (exclude weekend + holiday).

**Fix:**

```php
// app/Services/PayrollCalculatorService.php — line 261, ganti:
// SEBELUM:
$alphaPenalty = $alphaCount * ($dailyRate > 0 ? $dailyRate / 22 : 0);

// SESUDAH:
$workingDays = $employee->company->countWorkingDays($targetYear, $targetMonth);
$alphaPenalty = $alphaCount * ($dailyRate > 0 ? $dailyRate / $workingDays : 0);
```

**Verifikasi:**
```bash
php artisan tinker --execute '
$company = App\Models\Company::first();
echo "Working days May 2026: " . $company->countWorkingDays(2026, 5);
'
```

---

### ⚠️ 2.35 M22: Face Verification Tiered Fallback (PARTIAL)

**Masalah (3 lapisan):**

1. **B12-Tier0 (BARU)** — `AttendanceService::clockIn()` line 55: `if ($employee->face_embedding && ! empty($data['face_embedding']))` SILENTLY skip face verification untuk karyawan tanpa embedding. Tidak ada audit log `face_not_enrolled`, tidak distinguishable dari karyawan yang seharusnya pakai face. error-handling-strategy.md §1 Skenario 4 mewajibkan catch `FaceNotRegisteredException` + log `bypass_reason: face_not_enrolled`.
2. **B12-clockOut (BARU)** — `AttendanceService::clockOut()` line 141 panggil `verifyFace()` **tanpa guard**. Karyawan tanpa embedding → `FaceNotRecognizedService` throw `FaceNotRegisteredException` → tidak di-catch → 422 ke user. UNCAUGHT EXCEPTION.
3. **CAT-017 (existing)** — `FaceNotRecognizedException` (similarity rendah) di-`Log::warning()` lalu ditelan. Clock-in lanjut sebagai `'manual'`. Indistinguishable dari karyawan tanpa embedding. **Security hole**.

**Severity:** HIGH (security + correctness)
**Depends On:** 2.33 (VerificationMethod enum), 4.1 (kolom `verification_fallback`)
**Estimasi:** 30 menit (3 tier eksplisit untuk clock-in & clock-out)

---

**Tabel Tiered Fallback (sumber: PRD §7 + error-handling-strategy.md §1):**

| Tier | Kondisi | Method Output | `verification_fallback` Flag | Audit / Throws |
|------|---------|---------------|------------------------------|-----------------|
| **0** | `face_embedding === NULL` (face belum enrolled) + PIN valid | `PIN_VERIFIED` | `face_not_enrolled` | `logBypass(face_not_enrolled)`. Skenario 4 PRD: izinkan PIN sementara, tampilkan info "Hubungi HRD untuk registrasi wajah". |
| **0.fail** | `face_embedding === NULL` + PIN missing/invalid | — | — | Throw `FaceNotRegisteredException` (HTTP 422) — wajib enrol atau berikan PIN. |
| **1** | `face_embedding` ada + similarity ≥ 85% (`distance ≤ 0.15`) | `FACE_VERIFIED` | `null` | Normal happy-path. `face_similarity_score` disimpan. |
| **1.retry** | `face_embedding` ada + similarity < 85%, attempt < 3 | (still trying) | `null` | Tampilkan warning client-side, retry max 2x (total 3 attempt). |
| **1.fail** | `face_embedding` ada + 3x gagal recognition + PIN valid | `PIN_VERIFIED` | `face_failed` | `logBypass(face_failed)` + `face_similarity_score` last attempt disimpan untuk audit. |
| **2** | Semua tier 0/1 gagal + PIN missing/invalid | — | — | Throw `InvalidPinException` (HTTP 422) → user dialihkan ke "Manual Request" (supervisor approval). |

**Catatan kunci:**
- `verification_fallback` adalah **kolom baru** di `attendances` (atau ditulis ke `activity_log` minimal). Tanpa kolom ini, audit forensik tidak bisa distinguish "face success" vs "face_not_enrolled bypass".
- Setiap fallback **WAJIB** meninggalkan jejak via `logBypass($employee, $reason)`.
- `verification_method` final saat insert `attendances` row TIDAK BOLEH `null`. Validate di akhir.
- Clock-out juga ikut tabel ini — pakai kolom `clock_out_verification_method` + `clock_out_face_similarity_score` + (kolom baru) `clock_out_verification_fallback`.

---

**Fix — Refactor AttendanceService::clockIn():**

```php
// app/Services/AttendanceService.php

use App\Exceptions\FaceNotRecognizedException;
use App\Exceptions\FaceNotRegisteredException;
use App\Exceptions\InvalidPinException;

public function clockIn(Employee $employee, array $data): Attendance
{
    // ... existing anti-tuyul + WFA + geofence checks ...

    $verificationMethod = null;
    $faceSimilarityScore = null;
    $verificationFallback = null;

    // ─── Tier 0: Face NOT REGISTERED ─────────────────────────────
    if (! $employee->face_embedding) {
        if (empty($data['pin'])) {
            throw new FaceNotRegisteredException(
                'Wajah belum terdaftar. PIN wajib untuk fallback. Hubungi HRD untuk registrasi.'
            );
        }
        $this->verifyPin($employee, $data['pin']);
        $this->logBypass($employee, 'face_not_enrolled');
        $verificationMethod = VerificationMethod::PIN_VERIFIED->value;
        $verificationFallback = 'face_not_enrolled';
    }
    // ─── Tier 1: Face REGISTERED — attempt recognition ───────────
    elseif (! empty($data['face_embedding'])) {
        try {
            $faceResult = $this->faceRecognitionService->verifyFace(
                $employee,
                $data['face_embedding']
            );
            $verificationMethod = VerificationMethod::FACE_VERIFIED->value;
            $faceSimilarityScore = $faceResult['similarity_percentage'];
        } catch (FaceNotRecognizedException $e) {
            // Tier 1.fail → fallback ke PIN
            Log::warning('Face verification failed, falling back to PIN.', [
                'employee_id' => $employee->id,
                'similarity' => $e->getMessage(),
            ]);
            if (empty($data['pin'])) {
                throw new InvalidPinException(
                    'Wajah tidak dikenali dan PIN tidak diberikan. Hubungi HRD.'
                );
            }
            $this->verifyPin($employee, $data['pin']);
            $this->logBypass($employee, 'face_failed');
            $verificationMethod = VerificationMethod::PIN_VERIFIED->value;
            $verificationFallback = 'face_failed';
        }
    }
    // ─── Tier 1.alt: Face registered tapi tidak ada face_embedding di request ───
    else {
        if (empty($data['pin'])) {
            throw new InvalidPinException('Face data atau PIN wajib disertakan.');
        }
        $this->verifyPin($employee, $data['pin']);
        $this->logBypass($employee, 'face_skipped');
        $verificationMethod = VerificationMethod::PIN_VERIFIED->value;
        $verificationFallback = 'face_skipped';
    }

    // Guard final: verification_method TIDAK BOLEH null
    if ($verificationMethod === null) {
        throw new BusinessRuleException('Verifikasi gagal — method tidak ter-set.');
    }

    // ... lanjutkan create Attendance dengan $verificationMethod, $faceSimilarityScore, $verificationFallback ...
}
```

**Fix — Refactor AttendanceService::clockOut() (sama 3 tier):**

```php
public function clockOut(Employee $employee, array $data, ?string $requestedMethod = null): Attendance
{
    // ... existing anti-tuyul + WFA + geofence ...

    $verificationMethod = null;
    $faceSimilarityScore = null;
    $verificationFallback = null;

    // Tier 0: face_embedding NULL → PIN required
    if (! $employee->face_embedding) {
        if (empty($data['pin'])) {
            throw new FaceNotRegisteredException(
                'Wajah belum terdaftar. PIN wajib untuk clock-out.'
            );
        }
        $this->verifyPin($employee, $data['pin']);
        $this->logBypass($employee, 'face_not_enrolled_clock_out');
        $verificationMethod = VerificationMethod::PIN_VERIFIED->value;
        $verificationFallback = 'face_not_enrolled';
    }
    // Tier 1: Face attempt
    elseif (! empty($data['face_embedding'])) {
        try {
            $faceResult = $this->faceRecognitionService->verifyFace(
                $employee,
                $data['face_embedding']
            );
            $verificationMethod = VerificationMethod::FACE_VERIFIED->value;
            $faceSimilarityScore = $faceResult['similarity_percentage'];
        } catch (FaceNotRecognizedException $e) {
            if (empty($data['pin'])) {
                throw new InvalidPinException('Wajah tidak dikenali, PIN wajib.');
            }
            $this->verifyPin($employee, $data['pin']);
            $this->logBypass($employee, 'face_failed_clock_out');
            $verificationMethod = VerificationMethod::PIN_VERIFIED->value;
            $verificationFallback = 'face_failed';
        }
    } else {
        // face_embedding ada di model tapi tidak dikirim di request
        if (empty($data['pin'])) {
            throw new InvalidPinException('Face data atau PIN wajib untuk clock-out.');
        }
        $this->verifyPin($employee, $data['pin']);
        $verificationMethod = VerificationMethod::PIN_VERIFIED->value;
        $verificationFallback = 'face_skipped';
    }

    // Update attendance dengan SEPARATE columns (ERR-004)
    $lockedAttendance->update([
        'clock_out' => now(),
        'clock_out_verification_method' => $verificationMethod,
        'clock_out_face_similarity_score' => $faceSimilarityScore,
        'clock_out_verification_fallback' => $verificationFallback, // kolom baru, perlu migration §4.1
        // ... field lain ...
    ]);
}
```

> **Migration dependency:** §4.1 perlu tambah kolom `verification_fallback varchar(30) nullable` dan `clock_out_verification_fallback varchar(30) nullable` di tabel `attendances`.

---

**Verifikasi:**

```bash
# Skenario 1: Karyawan tanpa face embedding + PIN valid
php artisan tinker --execute '
$emp = App\Models\Employee::whereNull("face_embedding")->first();
$service = app(App\Services\AttendanceService::class);
$attendance = $service->clockIn($emp, [
    "face_embedding" => null,
    "pin" => "123456",
    "is_wfa" => false,
    "latitude" => -6.2,
    "longitude" => 106.8,
]);
echo "Method: " . $attendance->verification_method->value . "\n";
echo "Fallback: " . ($attendance->verification_fallback ?? "none") . "\n";
// Expected: PIN_VERIFIED + face_not_enrolled
'

# Skenario 2: Karyawan tanpa face + PIN missing → throw FaceNotRegisteredException
# Skenario 3: Karyawan dengan face + similarity rendah + PIN valid → PIN_VERIFIED + face_failed
# Skenario 4: Clock-out tanpa face embedding (existing bug 422 uncaught) → handled gracefully
```

---

## FASE 3 — Service Bug Fixes

---

### ✅ 3.1 P1 & P2: Overtime Rate Calculation — SELESAI

**Masalah:** Weekday overtime flat 1.5x (seharusnya jam pertama 1.5x, selanjutnya 2x). Holiday overtime: salah 2x/3x (PRD §26.7: jam 1-8=2x, jam ke-9=3x, jam ke-10+=4x).  
**Severity:** HIGH  
**Status:** ✅ SELESAI (Sesi 4 / commit 63bf69c — B7 fix)

**Bukti:** `PayrollCalculatorService::calculateOvertimePay()` sekarang tiered:
- Weekday: jam-1=1.5x, jam-2 dst=2x
- Holiday/Weekend: 1-8=2x, 9-10=3x, 11+=4x

Verified via `tests/Unit/OvertimeRateTest.php` (7 tests passing).

```php
// app/Services/PayrollCalculatorService.php — ganti method calculateOvertimePay():

public function calculateOvertimePay(Overtime $overtime): float
{
    $hours = $overtime->durationHours();

    if ($hours <= 0) {
        return 0.0;
    }

    $employee = $overtime->employee;
    $basicSalary = $employee->position?->basic_salary ?? 0;
    $fixedAllowance = $employee->position?->allowance_jabatan ?? 0;
    $hourlyRate = ($basicSalary + $fixedAllowance) / self::MONTHLY_WORKING_HOURS;

    $date = Carbon::parse($overtime->date);

    $isHoliday = Cache::remember("holiday_{$date->toDateString()}", now()->addMonth(), function () use ($date) {
        return Holiday::isHoliday($date);
    }) || $date->isWeekend();

    if ($isHoliday) {
        // PRD §26.7: Holiday overtime
        // Jam 1-8: 2x upah/jam
        // Jam ke-9: 3x upah/jam  
        // Jam ke-10+: 4x upah/jam
        $firstEightHours = min($hours, 8);
        $ninthHour = max(min($hours - 8, 1), 0);
        $remainingHours = max($hours - 9, 0);

        return round(
            ($firstEightHours * $hourlyRate * 2.0) +
            ($ninthHour * $hourlyRate * 3.0) +
            ($remainingHours * $hourlyRate * 4.0),
            2
        );
    }

    // PRD §8.3: Weekday overtime (UU Cipta Kerja)
    // Jam pertama: 1.5x
    // Jam kedua dan seterusnya: 2x
    $firstHour = min($hours, 1);
    $remainingHours = max($hours - 1, 0);

    return round(
        ($firstHour * $hourlyRate * 1.5) +
        ($remainingHours * $hourlyRate * 2.0),
        2
    );
}
```

---

### ✅ 3.2 G1: GeofenceService Null Coordinates — SELESAI

**Masalah:** `deg2rad(null)` = 0.0 → jika branch lat/lng null, perhitungan jarak salah.  
**Severity:** HIGH  
**Status:** ✅ SELESAI (Sesi 7 / commit c0678bd)

**Bukti:** `GeofenceService::validateLocation()` sekarang punya `assertValidCoordinates()` + `assertValidBranchCoordinates()` yang throw `BusinessRuleException` untuk:
- Koordinat null/missing
- Koordinat string non-numeric
- lat di luar -90..90 / lng di luar -180..180
- Null Island (0, 0)
- Branch tanpa koordinat

Verified via `tests/Unit/Phase3BugFixesTest.php` (8 tests passing).

```php
// app/Services/GeofenceService.php — tambah null check di awal calculateHaversine():

public function calculateHaversine(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    // Guard: null atau 0 coordinates = tidak bisa hitung jarak
    if (empty($lat1) || empty($lng1) || empty($lat2) || empty($lng2)) {
        return PHP_FLOAT_MAX; // Return jarak sangat jauh = selalu di luar radius
    }

    $earthRadius = 6371000; // meter

    $lat1Rad = deg2rad($lat1);
    $lng1Rad = deg2rad($lng1);
    $lat2Rad = deg2rad($lat2);
    $lng2Rad = deg2rad($lng2);

    $dLat = $lat2Rad - $lat1Rad;
    $dLng = $lng2Rad - $lng1Rad;

    $a = sin($dLat / 2) * sin($dLat / 2) +
        cos($lat1Rad) * cos($lat2Rad) *
        sin($dLng / 2) * sin($dLng / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadius * $c;
}
```

---

### ✅ 3.3 H1: PayrollCalculatorService `getTERCategory()` — Bug DIVORCED/WIDOWED — SELESAI

**Masalah:** §2.8 memperbaiki `TerCategory::resolveFromStatus()`, tapi `PayrollCalculatorService::getTERCategory()` punya logic sendiri yang juga salah.  
**Severity:** HIGH  
**Status:** ✅ SELESAI

**Fix:** Duplikasi logika dihapus. `getTERCategory()` sekarang mendelegasi ke `TerCategory::resolveFromStatus()`. Import `MaritalStatus` dihapus dari PayrollCalculatorService (tidak lagi digunakan langsung).

---

### ✅ 3.4 H2: `family_details_count` Menghitung Semua Keluarga — TER Category Salah — SELESAI

**Masalah:** `$employee->family_details_count` (dari `withCount('families')`) menghitung **semua** family members termasuk pasangan, bukan hanya `FamilyRelationship::CHILD`. Ini menyebabkan over-count dependents → kategori TER salah.  
**Severity:** HIGH  
**Status:** ✅ SELESAI (Sesi 4 / commit 63bf69c — B5 fix)

**Bukti:** Tambah scoped relation `Employee::children()` yang filter `relationship = CHILD`. `PayrollCalculatorService::getTERCategory()` sekarang pakai `$employee->children_count` (bukan `family_details_count`).

Verified via `tests/Unit/PayrollCalculatorTerCategoryTest.php` (5 tests passing).

```php
// Ganti eager-load pemanggil PayrollCalculatorService::generatePayroll()
// SEBELUM:
// $employee->load('position');

// SESUDAH:
$employee->load([
    'position',
    'families' => function ($query) {
        $query->where('relationship', FamilyRelationship::CHILD);
    },
]);
```

> Alternatif: gunakan `withCount(['families as children_count' => fn ($q) => $q->where('relationship', FamilyRelationship::CHILD)])` dan ganti `$employee->family_details_count` ke `$employee->children_count`.

---

### ✅ 3.5 H6 & H7: LeaveService `carryForward()` — 2 Bug — SELESAI

**Masalah 1:** `carryForward()` mengabaikan `carry_forward` tahun sebelumnya dalam perhitungan remaining. `$remaining = $prevBalance->quota - $prevBalance->used` seharusnya `$prevBalance->available()`.  
**Masalah 2:** `carryForward()` menimpa kuota karyawan yang sudah di-customize admin dengan default tipe cuti (`$prevBalance->leaveType->quota`).  
**Severity:** HIGH  
**Status:** ✅ SELESAI (Sesi 4 / commit 63bf69c — B3.5 fix)

**Bukti:**
- Bug 1: `$remaining = $prevBalance->available()` (include unexpired carry_forward)
- Bug 2: `'quota' => $prevBalance->quota` (pertahankan kuota employee-specific)

```php
// app/Services/LeaveService.php — ganti method carryForward():

public function carryForward(Employee $employee, int $fromYear, int $toYear): void
{
    $previousBalances = LeaveBalance::with('leaveType')
        ->where('employee_id', $employee->id)
        ->where('year', $fromYear)
        ->get();

    $deadlineSetting = CompanySetting::get('leave_carry_forward_deadline', '03-31');
    $deadlineDate = Carbon::parse($toYear.'-'.$deadlineSetting)->toDateString();

    foreach ($previousBalances as $prevBalance) {
        // FIX: Gunakan available() yang memasukkan unexpired carry_forward
        $remaining = $prevBalance->available();

        if ($remaining <= 0) {
            continue;
        }

        $carryForward = min($remaining, 3);

        LeaveBalance::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'leave_type_id' => $prevBalance->leave_type_id,
                'year' => $toYear,
            ],
            [
                // FIX: Pertahankan kuota employee-specific, bukan default tipe cuti
                'quota' => $prevBalance->quota,
                'carry_forward' => $carryForward,
                'carry_forward_deadline' => $deadlineDate,
            ]
        );
    }
}
```

---

### ✅ 3.6 H8: `DomainException` di PayrollCalculatorService Return HTTP 500 — SELESAI

**Status:** ✅ SELESAI (Sesi 7 / commit c0678bd)

**Bukti:** `PayrollCalculatorService::generatePayroll()` sekarang throw `BusinessRuleException` (HTTP 422) saat regenerate locked payroll, bukan `DomainException` (HTTP 500). Import `DomainException` dihapus.

**Masalah:** Line 218 melempar `DomainException` yang di-render Laravel sebagai 500 Internal Server Error. Ini bukan server error — ini business rule violation yang seharusnya return 422.  
**Severity:** MEDIUM  
**Depends On:** 0.6 (BusinessRuleException rewrite)  
**Estimasi:** 2 menit

```php
// app/Services/PayrollCalculatorService.php — cari semua DomainException dan ganti:

// SEBELUM:
throw new DomainException("Payroll untuk periode {$period} sudah dikunci permanen.");

// SESUDAH:
throw new \App\Exceptions\BusinessRuleException("Payroll untuk periode {$period} sudah dikunci permanen.");
```

> Jangan lupa hapus `use DomainException;` dari import jika tidak digunakan lagi.

---

### ✅ 3.7 M10: LeaveService `applyLeave()` — `end_date >= start_date` Validation — SELESAI

**Status:** ✅ SELESAI (Sesi 4 / commit 63bf69c — B3.7 fix)

**Bukti:** `LeaveService::applyLeave()` sekarang throw `BusinessRuleException` di awal method kalau `end_date < start_date`. Validasi pindah ke depan, sebelum query `LeaveType::findOrFail()` (cepat reject tanpa hit DB).

Verified via `tests/Unit/LeaveDateRangeValidationTest.php` (2 tests passing).

**Masalah:** Tidak ada validasi bahwa `end_date >= start_date`. Jika user submit range terbalik, `calculateWorkDays` return 0, dan user mendapat pesan error yang menyesatkan ("Durasi cuti 0 hari. Tanggal hanya weekend atau libur.").  
**Severity:** MEDIUM  
**Depends On:** —  
**Estimasi:** 2 menit

```php
// app/Services/LeaveService.php — tambah di dalam method applyLeave(), setelah parse $endDate:

if ($endDate->lt($startDate)) {
    throw new BusinessRuleException('Tanggal selesai tidak boleh sebelum tanggal mulai.');
}
```

---

### 🔀 3.8 M8: ReimbursementService `linkToPaylawl()` — Sudah dipindahkan ke §2.24 — MERGED

> **Status:** 🔀 MERGED — Ditangani di §2.24. Auditor: 2026-06-03.

> **CATATAN:** Bug ini sudah didokumentasikan di §2.24 (Fase 2).

---

### ✅ 3.9 H3: PayrollCalculatorService — N+1 Query pada Overtime → Employee → Position — SELESAI

**Masalah:** `calculateOvertimePay()` dipanggil per-overtime via `map()`, dan setiap overtime trigger `$overtime->employee` + `$employee->position` = 2N extra queries. Employee dan position sudah ada di scope `generatePayroll()`.  
**Severity:** MEDIUM  
**Status:** ✅ SELESAI (Sesi 8 — current commit)

**Bukti:** `PayrollCalculatorService::generatePayroll()` sekarang setRelation `employee` di setiap overtime ke `$employee` yang sudah dimuat di scope:
```php
$overtimes = Overtime::where('employee_id', $employee->id)
    ->where('status', RequestStatus::APPROVED)
    ->whereYear('date', $targetYear)
    ->whereMonth('date', $targetMonth)
    ->get()
    ->each(fn (Overtime $ot) => $ot->setRelation('employee', $employee));
```

Cegah 2N extra queries (employee + position) per overtime.

```php
// app/Services/PayrollCalculatorService.php — di dalam method generatePayroll(), update eager-load:

// SEBELUM:
// $overtimes = Overtime::where(...)->get();

// SESUDAH:
$overtimes = Overtime::with('employee.position')
    ->where('employee_id', $employee->id)
    ->where('status', RequestStatus::APPROVED)
    ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
    ->get();
```

---

### ✅ 3.10 M16: PayrollCalculatorService — No Guard untuk Employee tanpa Position — SELESAI

**Masalah:** Null-safe operator `$employee->position?->basic_salary ?? 0` silently defaults to 0, menghasilkan payroll dengan gaji 0 untuk karyawan tanpa position assignment.  
**Severity:** MEDIUM  
**Status:** ✅ SELESAI (Sesi 7 / commit c0678bd)

**Bukti:** `PayrollCalculatorService::generatePayroll()` sekarang throw `BusinessRuleException` di awal method kalau `! $employee->position`:
```
"Karyawan {$employee->employee_number} belum memiliki jabatan (position). Hubungi HRD untuk konfigurasi sebelum generate payroll."
```

```php
// app/Services/PayrollCalculatorService.php — tambah di awal method generatePayroll(), setelah parsing period:

if (! $employee->position) {
    throw new \App\Exceptions\BusinessRuleException(
        "Karyawan {$employee->full_name} belum memiliki posisi/jabatan."
    );
}
```

---

### ✅ 3.11 L8: Attendance Penalty Count — Tidak Filter WFA atau Exception — SELESAI

**Masalah:** Count query `where('late_minutes', '>', 0)` menghitung semua late attendance, termasuk WFA dan yang sudah di-approved exception. Seharusnya mengecualikan WFA dan yang punya approved exception.  
**Severity:** LOW  
**Status:** ✅ SELESAI (Sesi 8 — current commit)

**Bukti:** Kedua query (`lateCount` + `alphaCount`) di `PayrollCalculatorService::generatePayroll()` sekarang filter:
- `where('is_wfa', false)` (untuk lateCount — WFA tidak ada toleransi GPS, tidak kena denda telat)
- `whereNull('exception_type')` (untuk lateCount + alphaCount — exception approved tidak kena denda)

Kolom `is_wfa` (default false) dan `exception_type` (nullable) sudah ada di migration `create_attendances_table`.

```php
// app/Services/PayrollCalculatorService.php — update late penalty query:

// SEBELUM:
$lateCount = Attendance::where('employee_id', $employee->id)
    ->whereYear('date', $targetYear)
    ->whereMonth('date', $targetMonth)
    ->where('late_minutes', '>', 0)
    ->count();

// SESUDAH (setelah Migration 2 selesai):
$lateCount = Attendance::where('employee_id', $employee->id)
    ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
    ->where('late_minutes', '>', 0)
    ->where('is_wfa', false)
    ->whereNull('exception_type')
    ->count();
```

> **CATATAN:** Kolom `exception_type` ditambahkan di §4.1 Migration 2. Filter ini hanya bisa diimplementasi setelah migration tersebut selesai.

---

### ✅ 3.12 H4: FaceRecognitionService — No Vector Dimension Validation — SELESAI

**Masalah:** Method `verifyFace()` memvalidasi bahwa setiap value numeric, tapi tidak memvalidasi bahwa incoming vector punya tepat 128 dimensions. Vector dengan dimensi berbeda menyebabkan PostgreSQL pgvector error.  
**Severity:** MEDIUM  
**Status:** ✅ SELESAI (Sesi 7 / commit c0678bd)

**Bukti:** Tambah konstanta `EMBEDDING_DIMENSIONS = 128` + validasi `count($incomingVector) !== 128` di `FaceRecognitionService::verifyFace()`. Throw `BusinessRuleException` dengan pesan jelas: `"harus 128D (FaceNet), diterima NN"`.

Verified via `tests/Unit/Phase3BugFixesTest.php` (3 tests passing untuk 64D, 256D, 0D, non-numeric).

```php
// app/Services/FaceRecognitionService.php — tambah di awal method verifyFace(), setelah line validasi:

if (count($incomingVector) !== 128) {
    throw new BusinessRuleException('Data biometrik wajah tidak valid (dimensi salah).');
}
```

---

> **Catatan:** Fase ini memerlukan banyak file baru. Di sini diberikan spesifikasi
> method signatures dan behavior, bukan kode lengkap implementasi.
> DeepSeek bisa meng-implementasi berdasarkan spesifikasi ini.

---

### ✅ 4.1 Migrations — SEMUA SUDAH ADA

> **Status:** ✅ SEMUA SELESAI — Semua kolom sudah ada di migration asli (development mode). Tidak ada alter migration terpisah yang diperlukan. Audit: 2026-06-03.

Berdasarkan perbandingan ERD vs database aktual:

| # | Migration | Kolom | Status |
|---|-----------|-------|--------|
| 1 | ~~`add_status_and_due_date_to_loan_installments`~~ | ~~status, due_date~~ | **SUDAH ADA** |
| 2 | exception_fields | exception_type, exception_notes, approved_late_by | ✅ Ada di `create_attendances_table` |
| 3 | payroll_breakdown | — | **SUDAH ADA** |
| 4 | google_oauth | google_id | ✅ Ada di `create_users_table` |
| 5 | password_changed_at | password_changed_at timestamp | ✅ Boolean → Timestamp di migration asli (Fix: 2026-06-03) |
| 6 | wfa_status | status_wfa | ✅ Ada di `create_attendances_table` |
| 7 | device_detection | device_type, device_name, browser, os | ✅ Ada di `create_devices_table` |
| 8 | password_changed_at | password_changed_at (CRITICAL) | ✅ Di migration asli `create_users_table` |
| 9 | payroll_adjustments decimal | amount decimal(15,2) | ✅ Di migration asli |
| 10 | overtimes description | nullable | ✅ Di migration asli |
| 11 | overtimes attendance_id | nullable | ✅ |
| 12 | employees address_detail | nullable | ✅ Di migration asli |
| 13 | FK indexes | 5 indexes | ✅ Ditambahkan ke migration asli (Fix: 2026-06-03) |
| 14 | performance_reviews fields | status, review_date, period, softDeletes | ✅ Ada di migration asli |
| 15 | bpjs_configs name unique | unique constraint | ✅ Ada di `create_bpjs_configs_table` |

**Semua migration terverifikasi — tidak ada yang perlu dibuat.**

**Catatan:** Sebelum membuat migration, verifikasi dulu dengan `php artisan migrate:status` apakah kolom sudah ada di DB.

---

**Migration 2 — add_exception_fields_to_attendances:**

```bash
php artisan make:migration add_exception_fields_to_attendances_table
```

```php
// database/migrations/YYYY_MM_DD_HHMMSS_add_exception_fields_to_attendances_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('exception_type', 30)->nullable()->after('status');
            $table->text('exception_notes')->nullable()->after('exception_type');
            $table->foreignId('approved_late_by')->nullable()->after('exception_notes')
                ->constrained('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_late_by');
            $table->dropColumn(['exception_type', 'exception_notes']);
        });
    }
};
```

**Migration 6 — add_wfa_status_to_attendances:**

```bash
php artisan make:migration add_wfa_status_to_attendances_table
```

```php
// database/migrations/YYYY_MM_DD_HHMMSS_add_wfa_status_to_attendances_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('status_wfa', 20)->nullable()->after('is_wfa');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('status_wfa');
        });
    }
};
```

**Migration 7 — add_device_detection_to_devices:**

```bash
php artisan make:migration add_device_detection_to_devices_table
```

```php
// database/migrations/YYYY_MM_DD_HHMMSS_add_device_detection_to_devices_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->string('device_type', 20)->nullable()->after('device_uuid');
            $table->string('device_name')->nullable()->after('device_type');
            $table->string('browser')->nullable()->after('device_name');
            $table->string('os')->nullable()->after('browser');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn(['device_type', 'device_name', 'browser', 'os']);
        });
    }
};
```

**Migration 8 — add_password_changed_at_to_users (KRITIS untuk Keamanan):**

> **Mengapa wajib?** Kolom `password_changed` yang ada saat ini adalah **boolean** — hanya bilang "sudah diganti atau belum". Ini TIDAK cukup untuk:
> - **Password expiration 90 hari** (Security Config §1.5) — butuh time anchor untuk hitung umur password
> - **Force password change** (PRD §4.2) — butuh timestamp kapan terakhir diganti
> - **Audit forensik** — tim security butuh tahu KAPAN password terakhir diubah saat investigasi insiden

```bash
php artisan make:migration add_password_changed_at_to_users_table
```

```php
// database/migrations/YYYY_MM_DD_HHMMSS_add_password_changed_at_to_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('password_changed_at')->nullable()->after('password_changed');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('password_changed_at');
        });
    }
};
```

**Update Model:**

```php
// app/Models/User.php — tambah ke casts():
'password_changed_at' => 'datetime',
```

---

✅ 4.1d Edit Migration Asli: overtimes (description nullable, attendance_id nullable)

> **Mengapa?** PRD §8.1: lembur diajukan SEBELUM absen pulang → `attendance_id` harus nullable.
> `description` juga harus nullable — validasi required di level FormRequest, bukan database.

```php
// database/migrations/2026_04_19_044028_create_overtimes_table.php
// GANTI baris:
$table->foreignId('attendance_id');
// MENJADI:
$table->foreignId('attendance_id')->nullable()->constrained('attendances')->nullOnDelete();

// GANTI baris:
$table->text('description');
// MENJADI:
$table->text('description')->nullable();
```

---

✅ 4.1e Edit Migration Asli: employees (address_detail nullable)

> **Mengapa?** Alamat seharusnya boleh kosong. Validasi required di level FormRequest, bukan database.

```php
// database/migrations/2026_04_16_192201_create_employees_table.php
// GANTI baris:
$table->text('address_detail');
// MENJADI:
$table->text('address_detail')->nullable();
```

---

✅ 4.1f Ringkasan: Semua Edit Migration Asli

Setelah semua edit selesai, jalankan:

```bash
php artisan migrate:fresh
```

| # | File Migration | Perubahan |
|---|---------------|-----------|
| 1 | `2026_05_08_161915_create_payroll_adjustments_table.php` | `$table->integer('amount')` → `$table->decimal('amount', 15, 2)` |
| 2 | `2026_04_19_044028_create_overtimes_table.php` | `$table->foreignId('attendance_id')` → `$table->foreignId('attendance_id')->nullable()->constrained('attendances')->nullOnDelete()` |
| 3 | `2026_04_19_044028_create_overtimes_table.php` | `$table->time('start_time')` → `$table->time('start_time')->nullable()` |
| 3b | `2026_04_19_044028_create_overtimes_table.php` | `$table->time('end_time')` → `$table->time('end_time')->nullable()` |
| 3 | `2026_04_19_044028_create_overtimes_table.php` | `$table->text('description')` → `$table->text('description')->nullable()` |
| 4 | `2026_04_16_192201_create_employees_table.php` | `$table->text('address_detail')` → `$table->text('address_detail')->nullable()` |
| 5 | `2026_04_12_203653_create_companies_table.php` | `$table->text('address_detail')` → `$table->text('address_detail')->nullable()` |
| 6 | `2026_04_13_152045_create_branches_table.php` | `$table->text('address_detail')` → `$table->text('address_detail')->nullable()` |
| 7 | `2026_04_16_192201_create_employees_table.php` | `$table->text('npwp')` → `$table->text('npwp')->nullable()` |
| 8 | `2026_04_16_192201_create_employees_table.php` | `$table->text('bank_account_number')` → `$table->text('bank_account_number')->nullable()` |
| 9 | `2026_04_16_192201_create_employees_table.php` | `$table->string('bank_name', 100)` → `$table->string('bank_name', 100)->nullable()` |
| 10 | `2026_04_17_175332_create_attendances_table.php` | `$table->foreignId('shift_id')` → `$table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete()` |
| 11 | `2026_05_08_161915_create_payroll_adjustments_table.php` | `$table->foreignId('created_by')` → `$table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()` |
| 12 | `2026_04_28_133152_create_knowledge_bases_table.php` | `$table->vector('embedding', dimensions: 768)` → `$table->vector('embedding', dimensions: 768)->nullable()` `[K2 — fix dimension: PRD §13.1 specifies 768 (Gemini text-embedding-004)]` |
| 13 | `2026_05_08_161905_create_bpjs_configs_table.php` | `$table->string('name')` → `$table->string('name')->unique()` |
| 14 | `2026_04_12_203653_create_companies_table.php` | `$table->text('logo')` → `$table->string('logo', 255)->nullable()` |
| 15 | `2026_04_13_152045_create_branches_table.php` | `$table->decimal('latitude', 10, 8)` → `$table->decimal('latitude', 10, 7)`; `$table->decimal('longitude', 11, 8)` → `$table->decimal('longitude', 11, 7)` |

---

✅ 4.1g Edit Migration Asli: attendances (shift_id nullable)

> **Mengapa?** `employees.shift_id` nullable, tapi `attendances.shift_id` NOT NULL. Karyawan tanpa shift assignment gagal clock-in dengan NOT NULL violation.

```php
// database/migrations/2026_04_17_175332_create_attendances_table.php
// GANTI baris:
$table->foreignId('shift_id')->constrained('shifts')->restrictOnDelete();
// MENJADI:
$table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
```

Jangan lupa update model cast/relationship jika diperlukan.

---

✅ 4.1h Edit Migration Asli: payroll_adjustments (created_by nullable)

> **Mengapa?** `created_by` NOT NULL tapi foreign key pakai `nullOnDelete()`. Saat User dihapus, database menolak set NOT NULL column ke NULL = constraint violation crash.

```php
// database/migrations/2026_05_08_161915_create_payroll_adjustments_table.php
// GANTI baris:
$table->foreignId('created_by')->constrained('users')->nullOnDelete();
// MENJADI:
$table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
```

---

✅ 4.1i Edit Migration Asli: knowledge_bases (embedding nullable)

> **Mengapa?** Model punya `processEmbedding()` yang set `status = 'processing'` — artinya record dibuat SEBELUM embedding di-generate. Kolom NOT NULL mencegah insert tanpa embedding.

```php
// database/migrations/2026_04_28_133152_create_knowledge_bases_table.php
// GANTI baris:
$table->vector('embedding', dimensions: 768);
// MENJADI:
$table->vector('embedding', dimensions: 768)->nullable();
// [K2] PRD §13.1: Gemini text-embedding-004 = 768 dim (bukan 1536 dari OpenAI).
```

---

✅ 4.1j Edit Migration Asli: companies & branches (address_detail nullable)

> **Mengapa?** Sama seperti employees (§4.1e). Perusahaan dan cabang harus bisa dibuat tanpa alamat lengkap.

```php
// database/migrations/2026_04_12_203653_create_companies_table.php
// GANTI: $table->text('address_detail');
// MENJADI: $table->text('address_detail')->nullable();

// database/migrations/2026_04_13_152045_create_branches_table.php
// GANTI: $table->text('address_detail');
// MENJADI: $table->text('address_detail')->nullable();
```

---

✅ 4.1k Edit Migration Asli: employees (npwp, bank_account_number, bank_name nullable)

> **Mengapa?** CipherSweet menggunakan `addOptionalTextField` untuk `npwp` dan `bank_account_number`. Karyawan baru/probation sering belum punya NPWP atau rekening bank.

```php
// database/migrations/2026_04_16_192201_create_employees_table.php
// GANTI:
// $table->text('npwp');
// $table->text('bank_account_number');
// $table->string('bank_name', 100);
// MENJADI:
$table->text('npwp')->nullable();
$table->text('bank_account_number')->nullable();
$table->string('bank_name', 100)->nullable();
```

---

### ✅ 4.2 Console Commands — SEMUA SUDAH ADA

> **Status:** ✅ SEMUA SELESAI — 5 commands exist. Auditor: 2026-06-03.

| Command | Schedule | File | Status |
|---------|----------|------|--------|
| `attendance:detect-alpha` | `dailyAt('23:59')` | `DetectAlphaAttendanceCommand.php` | ✅ |
| `attendance:detect-chronic-late` | `weeklyOn(Friday, '18:00')` | `DetectChronicLateCommand.php` | ✅ |
| `leave:reset-quota` | `yearlyOn(1, 1, '00:00')` | `ResetLeaveQuotaCommand.php` | ✅ |
| `payroll:generate {period}` | Manual | `GeneratePayrollCommand.php` | ✅ |
| `activitylog:clean` | `daily()` | Dari package Spatie | ✅ |

---

### ❌ 4.3 Notification Classes — BELUM ADA

> **Status:** ❌ `app/Notifications/` folder exists but contains NO notification classes. 7 classes needed.

| # | Class | Via | Trigger | Status |
|---|-------|-----|---------|--------|
| 1 | `LeaveRequestSubmitted` | mail, database | Leave::create | ❌ |
| 2 | `LeaveApproved` | mail, database | ApprovalService::approve (final) | ❌ |
| 3 | `LeaveRejected` | mail, database | ApprovalService::reject | ❌ |
| 4 | `PayrollPublished` | mail, database | Payroll publish | ❌ |
| 5 | `ApprovalOverdue` | database | attendance:detect-alpha command | ❌ |
| 6 | `NewDeviceLogin` | mail, database | Login dari device baru | ❌ |
| 7 | `ChronicLateWarning` | mail, database | attendance:detect-chronic-late command | ❌ |

Setiap notification harus implement `ShouldQueue` dan punya `toDatabase()` + `toMail()`.

---

### ⚠️ 4.4 Seeders — SEBAGIAN ADA

> **Status:** ⚠️ PARTIAL — 4 seeders exist, 4 still missing.

| # | Seeder | Status |
|---|--------|--------|
| 1 | `RoleAndPermissionSeeder` | ✅ Ada |
| 2 | `CompanySeeder` | ❌ Belum ada |
| 3 | `SuperAdminSeeder` | ✅ Ada |
| 4 | `CompanySettingSeeder` | ❌ Belum ada |
| 5 | `PayrollConfigSeeder` | ❌ Belum ada (tax_configs + bpjs_configs via migration default) |
| 6 | `LeaveTypeSeeder` | ❌ Belum ada |
| 7 | `HolidaySeeder` | ❌ Belum ada |
| 8 | `ShiftSeeder` | ❌ Belum ada |

---

## Urutan Eksekusi Lengkap — AUDIT 2026-06-03

```markdown
✅ SELESAI : 87 items (Fase 0-3 + migrations + commands + partial)
🔀 MERGED  : 3 items
❌ NOT DONE: 7 items

SISA 7 ITEM YANG HARUS DIKERJAKAN:

  ❌ 1.5  — SEC-5: Exception HTTP codes (FaceNotRegistered → 422, NotClockedIn → 409) [10 menit]
  ❌ 1.7  — SEC-7: Module web route files (attendance, leave, payroll, dll) [2 jam]
  ❌ 2.2  — B2: isAllApproved() vacuous truth — guard approvals()->exists() [5 menit]
  ❌ 2.24 — H2: ReimbursementService linkToPayroll — duplicate guard [10 menit]
  ❌ 2.32 — M19: PTKP magic numbers → CompanySetting [15 menit]
  ❌ 4.3  — Notifications: 7 classes (LeaveRequestSubmitted, LeaveApproved, dll) [2 jam]
  ❌ 4.4  — Seeders: 6 missing (Company, CompanySetting, PayrollConfig, LeaveType, Holiday, Shift) [1 jam]
```

---

## PRD vs Kode vs ERD — Cross Reference

### Attendance

| Aspek | PRD/ERD | Kode Aktual | Status |
|-------|---------|-------------|--------|
| Unique `(employee_id, date)` | ERD: unique index | **SUDAH ADA** di DB (`attendances_employee_id_date_unique`) | Fix 0.3 — service-level catch |
| `verification_method` in/out terpisah | PRD §7.3 | Di-overwrite | Fix 0.2 |
| WFA `status_wfa` column | PRD §26.9 | ✅ ADA | SELESAI v4.2 |
| WFA reject → absent flow | PRD §26.9 | TIDAK ADA | Gap G1 |
| Anti-fake GPS | PRD §17.1 | Ada, tapi client-side trust | SEC-5 |
| `late_tolerance_minutes` | ERD | Ada | OK |
| `shift_id` NOT NULL | ERD: nullable | DB: NOT NULL — karyawan tanpa shift gagal clock-in | Fix 4.1g |

### Leave

| Aspek | PRD/ERD | Kode Aktual | Status |
|-------|---------|-------------|--------|
| Quota deduct AFTER approval | PRD §9.1 | Deduct saat SUBMIT | Fix 0.6 |
| Carry-forward max 3 hari | PRD §10.4 | `min($remaining, 3)` ada TAPI $remaining salah (tidak termasuk carry_forward tahun sebelumnya) | Fix 3.5 |
| Carry-forward overwrites employee-specific quota | PRD §10.4 | `quota => $prevBalance->leaveType->quota` — reset ke default | Fix 3.5 |
| Carry-forward deadline 03-31 | PRD §10.4 | `CompanySetting::get('leave_carry_forward_deadline', '03-31')` | OK |
| Probation block | PRD §10 | Ada di LeaveService:40-44 | OK |
| `rejection_reason` column | ERD | Ada | OK |
| Withdraw/cancel | PRD §9.5 | TIDAK ADA | Gap |
| `end_date >= start_date` validation | Common sense | TIDAK ADA — misleading error message | Fix 3.7 |

### Payroll

| Aspek | PRD/ERD | Kode Aktual | Status |
|-------|---------|-------------|--------|
| Payroll lock permanent | PRD §26.10 | `isLocked()` hanya PUBLISHED | Fix 2.3 |
| Unique `(employee_id, period)` | ERD | **SUDAH ADA** di DB | Fix 0.4 + 0.8 (forceDelete) |
| `payroll_adjustments.amount` type | ERD: `integer` | `integer` → harus `decimal:2` | Fix 2.4 |
| `payroll_adjustments.created_by` NOT NULL | ERD | NOT NULL + nullOnDelete = crash saat delete user | Fix 4.1h |
| Reimbursement period filter | PRD §12.4 | Tidak ada filter expense_date | Fix 0.4 |
| Holiday overtime rate | PRD §26.7 | Salah (2x/3x, harusnya 2x/3x/4x) | Fix 3.1 |
| Weekday overtime rate | PRD §8.3 | Flat 1.5x (harusnya jam pertama 1.5x, sisanya 2x) | Fix 3.1 |
| `family_details_count` counts ALL families | Should count CHILD only | Over-counting dependents → wrong TER category | Fix 3.4 |
| `getTERCategory()` DIVORCED/WIDOWED bug | Second instance in PayrollCalculatorService | ✅ Fixed — delegated to TerCategory::resolveFromStatus() |
| DomainException 500 error | Should be 422 | `throw new DomainException(...)` returns 500 | Fix 3.6 |
| No guard for employee without position | Should throw error | Silent 0 salary | Fix 3.10 |
| Loan deduction | PRD §12.3 | Hardcoded 0 | Gap P4 |
| Meal allowance | Diagram | Tidak ada | Gap P5 |

### Overtime

| Aspek | PRD/ERD | DB Aktual | Status |
|-------|---------|-----------|--------|
| `description` NOT NULL | ERD: `text` (implisit nullable) | `text NOT NULL` | Fix 4.1d — edit migration asli |
| `attendance_id` NOT NULL | ERD: nullable, PRD §8.1: submit sebelum absen | `bigint NOT NULL` | Fix 4.1d — edit migration asli |
| `start_time` NOT NULL | ERD: `time [null]` | `time NOT NULL` | **Fix N1** — edit migration asli |
| `end_time` NOT NULL | ERD: `time [null]` (lembur diajukan sebelum selesai) | `time NOT NULL` | **Fix N1** — edit migration asli |

### Approval

| Aspek | PRD/ERD | Kode Aktual | Status |
|-------|---------|-------------|--------|
| `isAllApproved()` vacuous truth | Expected: false jika 0 approval | True | Fix 2.2 |
| `$approval->level === 1` always false | Should compare enum, not int | APPROVED_L1 never reached | Fix 0.7 |
| Reject cancel PENDING others | Expected: auto-cancel | Tidak cancel | Fix 0.6 |
| No parent → skip L1 to L2 | PRD §26.8 | Null fallback | Fix 0.6 |
| Same person L1+L2 | - | Does not deduplicate | Fix 0.6 |
| `approvable_type`/`approvable_id` in fillable | Mass-assignment risk | Bisa di-set via API | Fix 2.10 |

### Employee & PII

| Aspek | PRD/ERD | Kode Aktual | Status |
|-------|---------|-------------|--------|
| `$hidden` PII fields | Security Config §3.1 | Hanya face_embedding, pin | Fix 1.1 |
| CipherSweet encryption | PRD §17.1 | Employee ✅, Company ✅, FamilyDetail ❌ | Gap |
| `employment_type` enum | ERD | Ada | OK |
| `pin` hashed | ERD | Cast: `hashed` | OK |
| `face_embedding` vector(128) | ERD | DB: `vector(128)` — **TAPI MODEL TIDAK ADA CAST** | Fix 2.11 |
| `address_detail` NOT NULL | Seharusnya nullable | DB: `text NOT NULL` | Fix 4.1e — edit migration asli |
| `npwp` NOT NULL | Seharusnya nullable (CipherSweet: `addOptionalTextField`) | DB: `text NOT NULL` | Fix 4.1k |
| `bank_account_number` NOT NULL | Seharusnya nullable | DB: `text NOT NULL` | Fix 4.1k |
| `bank_name` NOT NULL | Seharusnya nullable | DB: `string NOT NULL` | Fix 4.1k |
| `password_changed_at` | Security Config §1.5, PRD §4.2 | DB: TIDAK ADA (hanya `password_changed` boolean) | Fix 4.1f — migration baru |
| Password expiry 90 hari | Security Config §1.5 → PRD §4 "tidak ada expiry" | **Kontradiksi** — Security Config menang | Fix 1.6 |
| `employment_type` 3 vs 4 values | PRD §18 kolom: 3, enum table: 4 | Kode: 4 values (OK) | Fix 2.25 |

### Observer

| Aspek | PRD/ERD | Kode Aktual | Status |
|-------|---------|-------------|--------|
| `EmployeeObserver` (default shift) | PRD §6.1, ERR-005 | TIDAK ADA — `app/Observers/` kosong | Fix 0.10 |
| `AttendanceObserver` (overtime link) | PRD §8.2 | TIDAK ADA | Fix 0.10 |

### Overtime Config

| Aspek | PRD/ERD | Kode Aktual | Status |
|-------|---------|-------------|--------|
| `overtime_multiplier` flat rate | PRD §14.7 | Masih didefinisikan | HAPUS — lihat ERR-001, Fix 2.26 |
| `overtime_tiers_weekday/holiday` | ERR-001 | TIDAK ADA di config/seed | Fix 2.26 + 3.1 |

### PRD Gaps (Documentation)

| Aspek | PRD Section | Issue | Status |
|-------|-------------|-------|--------|
| Password expiry | §4 line 155 | Bilang "tidak ada expiry", Security Config §1.5 bilang 90 hari | Fix 1.6 (CAT-005) |
| EmploymentType | §18 line 666 | Hanya 3 values (missing `intern`) | Fix 2.25 (CAT-006) |
| Overtime flat keys | §14.7 | `overtime_multiplier`/`overtime_weekend_multiplier` harus dihapus | Fix 2.26 (CAT-007) |
| Export to Excel duplicate | §27 lines 1124 & 1129 | Kontradiksi: ditunda vs V1 | Fix 2.27 (CAT-008) |
| Attendance status | §6.2 | Hanya 6 (missing `missed_clock_in/out`) | Fix 2.28 (CAT-009) |
| `attendance:detect-chronic-late` | §16 | Tidak ada di commands table | Fix 2.29 (CAT-010) |

### KnowledgeBase

| Aspek | PRD/ERD | Kode Aktual | Status |
|-------|---------|-------------|--------|
| `status` column | ERD: `knowledge_base_status` | TIDAK ADA | Fix 2.1 |
| `category` column | ERD: `knowledge_base_category` | TIDAK ADA | Fix 2.1 |
| `source_document`, `page_number` | ERD | TIDAK ADA | Fix 2.1 |
| `knowledgeable_type`/`knowledgeable_id` NOT NULL | ERD: `[null]` nullable | NOT NULL (global KB tanpa owner gagal insert) | **Fix N5** — tambah note di §2.1 |
| `embedding` NOT NULL | Should be nullable (created before embedding) | DB: `vector(768) NOT NULL` `[K2]` | Fix 4.1i + Fix 0.9 |
| `embedding` model cast | Should be `vector` | **TIDAK ADA** — serialization risk | Fix 2.12 |
| `status`, `category`, `source_document`, `page_number` columns | ERD | TIDAK ADA di migration | Fix 0.9 |
| `ProcessKnowledgeBaseEmbedding` job | PRD §16 | TIDAK ADA | Gap INF-3 |
| RAG chat (Gemini) | PRD §15.6 | TIDAK ADA | Gap |

### Company & Branch

| Aspek | PRD/ERD | Kode Aktual | Status |
|-------|---------|-------------|--------|
| `address_detail` NOT NULL | Should be nullable | DB: `text NOT NULL` | Fix 4.1j |
| Missing address fillable fields | 6 kolom address tidak di fillable | Bypass mass-assignment | Fix 2.5 (SKIPPED — 3NF redesign) |
| `logo` type | ERD: `varchar(255)` | Migration: `text` | **Fix N3** — edit migration asli |
| `latitude`/`longitude` precision | ERD: `decimal(10,7)` | Migration: `decimal(10,8)` | **Fix N4** — edit migration asli |

### Asset

| Aspek | PRD/ERD | Kode Aktual | Status |
|-------|---------|-------------|--------|
| Missing columns (company_id, code, category, status) | ERD | ✅ ADA di DB | SELESAI v4.2 |
| AssetStatus enum unused | Model pakai boolean `is_available` | ✅ Enum dipakai | SELESAI v4.2 |
| Missing SoftDeletes | ERD | Model tidak pakai trait | Fix 2.6 |

### PayrollAdjustment

| Aspek | PRD/ERD | Kode Aktual | Status |
|-------|---------|-------------|--------|
| `amount` integer not decimal | Should be `decimal:2` | `integer` in both DB and cast | Fix 2.4 |
| `created_by` NOT NULL + nullOnDelete | Contradiction: both NOT NULL + null on delete | Crash saat delete user | Fix 4.1h |
| `reason` NOT NULL | Should be nullable (automated adjustments) | DB: `text NOT NULL` | Catatan |

### BPJS Config

| Aspek | PRD/ERD | Kode Aktual | Status |
|-------|---------|-------------|--------|
| `name` unique constraint | Should prevent duplicate configs | No unique constraint | Fix 2.19 |

### Token/Auth/API/Routes

| Aspek | PRD/ERD | Kode Aktual | Status |
|-------|---------|-------------|--------|
| Sanctum package | API Contracts §1 | TIDAK ADA — not even installed | Fix 1.3 |
| `routes/api.php` | API Contracts | TIDAK ADA | Fix 1.3 |
| `routes/web.php` + module routes | PRD + Livewire | TIDAK ADA — hanya settings.php dan console.php | **Fix 1.7** |
| `AuthServiceProvider` | Policy registration | TIDAK ADA | Fix 1.4 |
| Permission enum + seeders | Policy authorization | TIDAK ADA — `$user->can()` always false | Fix 1.4 |
| API Controllers | API Contracts | Orphan AttendanceController, no routes | Gap |
| API Form Requests | API Contracts | 2 files, no policy-based auth | Gap |

### Cache

| Aspek | PRD/ERD | Kode Aktual | Status |
|-------|---------|-------------|--------|
| `AttendanceService::invalidateCache()` | — | Dead code: forget key yg tak pernah di-populate, `tags()` tak didukung db driver | Fix 2.30 |
| `holiday_*` cache invalidation | — | TTL 30 hari, tak ada `Cache::forget()` di Holiday model | Fix 2.31 |
| `tax_configs` cache invalidation | — | TTL 1 hari, observer exist di §2.9 | OK |
| `bpjs_configs` cache invalidation | — | TTL 1 hari, observer exist di §2.9 | OK |

### Payroll Calculation

| Aspek | PRD/ERD | Kode Aktual | Status |
|-------|---------|-------------|--------|
| PTKP values hardcoded | — | 54jt/58.5jt/4.5jt di `Employee::calculatePtkp()` | Fix 2.32 |
| 22 hari kerja hardcoded | — | `$dailyRate / 22`, harusnya `countWorkingDays()` | Fix 2.34 |
| Overtime multiplier hardcoded | — | 1.5x flat, harusnya tiered JSON config (ERR-001) | Fix 3.1 |

### Attendance

| Aspek | PRD/ERD | Kode Aktual | Status |
|-------|---------|-------------|--------|
| `VerificationMethod` enum | — | Magic string `'manual'`/`'face_verified'`/`'pin_verified'` + bug `'pin'` vs `'pin_verified'` | Fix 2.33 |
| `FaceNotRecognizedException` swallowed | — | Catch-log-dismiss, no fallback audit trail | Fix 2.35 |
| `invalidateCache()` dead code | — | CPU wasted per clock-in/out | Fix 2.30 |

---

## Jadwal PRD 12 Minggu vs Realita Audit

| Minggu | Deliverable PRD | Kesiapan Kode | Blocker Kritis |
|--------|----------------|---------------|----------------|
| 1 | DB ready, login works | 40% | Tanpa seeder/policy/middleware, login bisa tapi tidak ada role/permission |
| 2 | HRD manage data | 15% | PII leak, 0 Livewire component |
| 3 | Face enrollment | 20% | API route missing, dimension validation missing |
| 4 | GPS + Face clock-in | 25% | Double-submit, WFA incomplete, null geofence |
| 5 | KB upload | 5% | Model crash (missing columns), 0 service |
| 6 | KB chat | 0% | 0% implemented |
| 7 | Leave management | 40% | DL-2 (quota bug), 0 UI/API |
| 8 | Payroll engine | 35% | 8 bugs, 0 UI/API, meal allowance missing |
| 9 | Overtime | 15% | Model ada, 0 service/controller |
| 10 | Notifications + Dashboard | 5% | 0 notification classes, 0 commands |
| 11 | PWA + bug fixes | 0% | 0 PWA files, 70 bugs unfixed |
| 12 | Testing + UAT | 0% | 0 test files from spec |

**Kesimpulan:** Jadwal PRD mengasumsikan foundation (auth, authorization, API, seeders) sudah ada. Audit menunjukkan **infrastruktur dasar 0-40% selesai**. Perlu Fase 0-2 (±8 hari) sebelum jadwal PRD bisa dimulai.

---

*Terakhir diupdate: 20 Mei 2026 — v4.3 (Observer Consolidation: §0.10 5 observers, §2.9+§2.31 merged, §2.35 tiered fallback rewrite)*  
*Versi: 4.3 — Observer Consolidation + Face Tiered Fallback Release*