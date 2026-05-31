# PRD ERRATA — Historical Changelog

**Versi:** 2.0 (READ-ONLY)  
**Tanggal:** 20 Mei 2026  
**PRD Referensi:** PRD v3.1 (2026-05-21)  
**Status:** HISTORICAL CHANGELOG — Semua koreksi (9 ERR + 19 CAT) sudah di-merge ke PRD.md v3.0 dan diperluas pada PRD.md v3.1 dengan 35 koreksi konsolidasi tambahan (K1-K5 + M1-M8 + S1-S18 + N1-N12 + Glossary). Dokumen ini hanya untuk referensi historis. Untuk versi terbaru, lihat PRD.md v3.1.

---

## Cara Pakai

Dokumen ini adalah **read-only changelog**. Semua koreksi di bawah sudah di-merge langsung ke PRD.md v3.0 (dan diperluas di v3.1) pada section yang relevan. Jika ada konflik antara dokumen ini dan PRD.md v3.1, **PRD.md v3.1 yang benar**.

---

## ERR-001: Perhitungan Lembur Berjenjang (UU Cipta Kerja PP 35/2021)

**Koreksi PRD:** §8.3, §14.7 (`overtime_multiplier`/`overtime_weekend_multiplier`), §26.7

**PRD bilang:**
- Weekday: flat 1.5x
- Holiday/Weekend: 2x (8 jam pertama), 3x (jam ke-9+)

**Yang benar (UU Cipta Kerja PP 35/2021 Pasal 28):**
- Weekday: jam pertama 1.5x, jam kedua dan seterusnya 2x
- Holiday (5 hari kerja/minggu): jam 1-7 = 2x, jam 8 = 3x, jam 9+ = 4x
- Holiday (6 hari kerja/minggu): jam 1-8 = 2x, jam 9 = 3x, jam 10+ = 4x

**Perubahan config `company_settings`:**
- **HAPUS** key `overtime_multiplier` (flat rate, tidak bisa merepresentasikan tiered)
- **HAPUS** key `overtime_weekend_multiplier` (flat rate, tidak bisa merepresentasikan tiered)
- **TAMBAH** key `overtime_tiers_weekday` → JSON: `[{"from":1,"to":1,"multiplier":1.5},{"from":2,"to":null,"multiplier":2.0}]`
- **TAMBAH** key `overtime_tiers_holiday` → JSON: `[{"from":1,"to":8,"multiplier":2.0},{"from":9,"to":9,"multiplier":3.0},{"from":10,"to":null,"multiplier":4.0}]`

**Perubahan kode:** `PayrollCalculatorService::calculateOvertimePay()` harus baca dari config tiers, bukan flat multiplier. Lihat task.md §3.1 untuk implementasi lengkap.

**Status kode:** Masih flat 1.5x (belum diimplementasi). Lihat task.md §3.1.

---

## ERR-002: Quota Cuti — Deduct Saat Approved, Bukan Submit

**Koreksi PRD:** §7.2, §7.4

**PRD §7.2 bilang:** "Sisa kuota cuti tidak boleh minus"  
**PRD §7.4 bilang:** "Jika approved → otomatis kurangi `leave_balances.used`"

**Yang benar (Keputusan CTO):** Quota di-VALIDASI saat submit (cek available quota), tapi di-DEDUCT hanya setelah SEMUA approval selesai (status = APPROVED).

**Lifecycle lengkap:**

| Tahap | Status | Aksi Quota |
|-------|--------|-----------|
| Submit | `pending` | VALIDASI: `quota + carry_forward(unexpired) - used >= total_days`. **JANGAN deduct.** |
| Approve L1 | `approved_l1` | Tidak ada perubahan quota |
| Approve L2 (final) | `approved` | **DEDUCT:** `used += total_days` |
| Reject | `rejected` | Tidak ada perubahan (karena belum deduct) |
| Withdraw/Cancel (setelah approved) | `cancelled` | **REFUND:** `used -= total_days` |

**Implementasi:** Lihat task.md §0.6 untuk kode lengkap ApprovalService::approve() dan deductLeaveQuotaIfApplicable().

---

## ERR-003: Approval Matriks — 3 Jenis Pengajuan, Bukan 2

**Koreksi PRD:** §12.1 (tambah baris Reimbursement)

**PRD §12.1 hanya punya 2 baris. Yang benar ada 3 baris:**

| Pengajuan | Level 1 | Level 2 |
|-----------|---------|---------|
| Cuti | Manager (`parent_id`) | HR Manager |
| Lembur | Manager (`parent_id`) | HR Manager |
| Reimbursement | Manager (`parent_id`) | **Finance** |

**The WHY:** Reimbursement adalah urusan arus kas perusahaan. HRD tidak punya kompetensi menilai bon makan atau struk bensin — Finance yang harus approve L2.

**Enum ApprovalLevel:** Memiliki 4 level (L1_SUPERVISOR, L2_MANAGER, L3_HRD, L4_DIRECTOR), tapi MVP hanya pakai L1 dan L2. L3 dan L4 disiapkan untuk V2.

---

## ERR-004: Verification Method Clock-In dan Clock-Out HARUS Terpisah

**Koreksi PRD:** §6, §19

**PRD bilang:** hanya 1 kolom `verification_method`

**Yang benar (Keputusan CTO):** 4 kolom terpisah untuk audit trail:

| Kolom | Kegunaan |
|-------|----------|
| `verification_method` | Metode verifikasi clock-in (`face_verified`, `pin_verified`, `manual`) |
| `clock_out_verification_method` | Metode verifikasi clock-out |
| `face_similarity_score` | Skor kesamaan wajah clock-in (decimal 5,2) |
| `clock_out_face_similarity_score` | Skor kesamaan wajah clock-out (decimal 5,2) |

**The WHY:** Karyawan bisa clock-in pakai face recognition, clock-out pakai PIN. Kalau 1 kolom ditimpa, data forensik clock-in hilang. Ini prinsip dasar audit trail.

**Implementasi:** Lihat task.md §0.2 untuk migration dan AttendanceService fix.

---

## ERR-005: shift_id Nullable di Database, Wajib di Level Aplikasi

**Koreksi PRD:** §6.1

**PRD §6.1 bilang:** "Semua karyawan wajib punya shift"

**Yang benar (Keputusan CTO):** Database nullable (fleksibilitas onboarding), tapi aplikasi wajibisi default shift "Flexible" via EmployeeObserver.

| Level | Aturan |
|-------|--------|
| Database (`employees.shift_id`) | **Nullable** — mencegah crash saat HRD input karyawan baru tanpa shift |
| Database (`attendances.shift_id`) | **Nullable** — mencegah crash clock-in tanpa shift assignment |
| Aplikasi | `EmployeeObserver::creating()` → jika `shift_id` null, assign shift "Flexible" (default) |
| Shift default | "Flexible" (08:00-17:00, toleransi 0 menit) — di-seed otomatis |

**Implementasi:** Lihat task.md §4.1g untuk migration (shift_id nullable).

**Status kode:** `EmployeeObserver` **BELUM DIBUAT** — lihat ERR-009.

---

## ERR-006: Queue retry_after WAJIB Lebih Besar dari Timeout

**Koreksi PRD:** §16

**PRD §16 bilang:** Hanya `tries=3` dan `timeout=120s`. Tidak menyebutkan `retry_after`.

**Yang benar (Keputusan CTO):** `retry_after` HARUS lebih besar dari job timeout terlama.

| Config | Nilai | Alasan |
|--------|-------|--------|
| `retry_after` | **180s** (minimum) | Harus > `GenerateEmployeePayrollJob.timeout` (120s) |
| `GenerateEmployeePayrollJob.timeout` | 120s | PRD §16 |
| `ProcessKnowledgeBaseEmbedding.timeout` | 300s | Embedding generation bisa lambat |

**Perubahan `config/queue.php`:**
```php
'retry_after' => (int) env('DB_QUEUE_RETRY_AFTER', 180),
```

**The WHY (KRUSIAL):** Jika `retry_after` (90s default) < `timeout` (120s), Laravel melepaskan job ke queue ulang SEBELUM job selesai. **Payroll diproses 2x = gaji ganda.** Bencana finansial.

**Implementasi:** Lihat task.md §0.1 untuk kode lengkap.

---

## ERR-007: Weekend = Holiday Rate (Sama Multipliernya, Beda Cara Deteksi)

**Koreksi PRD:** §8.3, §26.7

**PRD §8.3 bilang:** "Rate hari libur / weekend" (disamakan)  
**PRD §26.7 bilang:** "Rate: 2x upah/jam (8 jam pertama), 3x (jam ke-9+)" (hanya holiday context)

**Yang benar (Keputusan CTO):** Weekend dan Holiday diperlakukan SAMA (pakai rate holiday tiers), tapi cara deteksinya BEDA.

**Implementasi:**
```php
function isHolidayOrWeekend(Carbon $date): bool
{
    return $date->isWeekend() || Holiday::isHoliday($date);
}
```

Jika `isHolidayOrWeekend()` return true → pakai `overtime_tiers_holiday` config.  
Jika false → pakai `overtime_tiers_weekday` config.

**Catatan:** Lihat ERR-001 untuk detail tiers JSON.

---

## ERR-008: KnowledgeBase::processEmbedding() Crash — Kolom `status` Tidak Ada

**Koreksi PRD:** §13.2, §19

**Masalah:** `KnowledgeBase::processEmbedding()` (line 26) menjalankan `$this->update(['status' => 'processing'])`, tapi migration `create_knowledge_bases_table` **tidak punya kolom `status`**. Juga tidak punya `category`, `source_document`, `page_number`.

**Impact:** Runtime SQL error saat `ProcessKnowledgeBaseEmbedding` job dijalankan. HRD upload PDF → job crash → status tidak pernah terupdate → user tidak tahu apakah dokumen sudah diproses atau error.

**Yang benar:**

| Kolom | Tipe | Nullable | Default | Catatan |
|-------|------|:---:|:---:|--------|
| `status` | `varchar(20)` | ✅ | `'processing'` | Enum: processing, ready, error |
| `category` | `varchar(255)` | ✅ | null | Enum: hr_policy, it_guide, general, finance, other |
| `source_document` | `varchar(255)` | ✅ | null | Nama file PDF asli |
| `page_number` | `integer` | ✅ | null | Halaman sumber di PDF |
| `embedding` | `vector(1536)` | ✅ | null | Nullable karena insert sebelum embedding di-generate |

**Implementasi:** Lihat task.md §0.9 untuk migration fix.

---

## ERR-009: Observer Directory Kosong — EmployeeObserver + AttendanceObserver Tidak Ada

**Koreksi PRD:** §6.1, §8.2

**Masalah:** Direktori `app/Observers/` kosong (0 file). Hal ini menyebabkan:

1. **ERR-005 tidak ada implementasi** — `EmployeeObserver::creating()` yang harus assign default shift_id "Flexible" tidak ada. Karyawan baru tanpa shift akan crash saat clock-in.
2. **PRD §8.2 tidak ada implementasi** — `AttendanceObserver` yang harus mendeteksi overtime saat clock-out dan menghubungkannya ke record overtime tidak ada. Overtime tetap `pending` tanpa terhubung ke attendance.

**Yang benar:**

```php
// app/Observers/EmployeeObserver.php
class EmployeeObserver
{
    public function creating(Employee $employee): void
    {
        if ($employee->shift_id === null) {
            $flexibleShift = Shift::where('name', 'Flexible')->first();
            if ($flexibleShift) {
                $employee->shift_id = $flexibleShift->id;
            }
        }
    }
}

// app/Observers/AttendanceObserver.php
class AttendanceObserver
{
    public function created(Attendance $attendance): void
    {
        if ($attendance->clock_out !== null) {
            // Link overtime jika ada overtime request approved untuk tanggal ini
            // PRD §8.2: Observer Saat Clock-Out
        }
    }
}
```

**Registrasi:** `AppServiceProvider::boot()` → `Employee::observe(EmployeeObserver::class)` dan `Attendance::observe(AttendanceObserver::class)`.

**Implementasi:** Lihat task.md §0.10 untuk kode lengkap.

---

## CATATAN TAMBAHAN

### CAT-001: password_changed_at Timestamp

**PRD §4** hanya punya `password_changed` (boolean). Tambah `password_changed_at` (timestamp) untuk:
- Password expiration 90 hari (Security Config §1.5)
- Force change password (PRD §4.2) — butuh tahu KAPAN terakhir diubah
- Audit forensik — tim security butuh timestamp

**Implementasi:** Lihat task.md §4.1f untuk migration.

### CAT-002: Reimbursement Filter by Period

**PRD §9.3** tidak menyebutkan bahwa reimbursement harus difilter oleh `expense_date` periode payroll. Tanpa filter, reimbursement dihitung ulang setiap regenerate payroll.

**Perubahan:** `PayrollCalculatorService::generatePayroll()` harus filter reimbursement by `expense_date` within payroll period.

### CAT-003: CipherSweet Encryption Gap

**PRD §17.1** dan ERD menyebutkan CipherSweet encryption untuk:
- `employees.nik`, `employees.phone`, `employees.npwp`, `employees.bank_account_number` — **SUDAH diimplementasi**
- `family_details.nik`, `family_details.phone`, `family_details.address` — **BELUM diimplementasi**
- `companies.npwp` — **BELUM diimplementasi**

**Status:** FamilyDetail dan Company encryption ditunda ke post-MVP.

### CAT-004: Kolom ERD yang Missing dari PRD

ERD (erd.dbml) menyebutkan kolom-kolom yang tidak ada di PRD description text:
- `employees`: `company_id`, `branch_id`, `department_id`, `employee_number`, `photo`, `pin`, `education_level`, `institution_name`, `major`, `graduation_year`, `salary_type`, province/city/district/village FK, `created_by`, `updated_by`
- `attendances`: `date`, `shift_id`, `device_fingerprint`, `verification_method`, `face_similarity_score`, `is_wfa`, `wfa_note`, `late_minutes`, `clock_in_is_mocked`, `clock_out_is_mocked`, `clock_in_accuracy`, `clock_out_accuracy`

Kolom-kolom ini ADA di migration dan model, hanya tidak dijelaskan secara eksplisit di PRD text. Implementasi mengikuti migration.

### CAT-005: Password Expiry — PRD §4 "Tidak Ada" vs Security Config §1.5 "90 Hari"

**PRD §4 line 155** bilang: "**Tidak ada password expiry**"  
**Security Config §1.5** bilang: "Expiration: 90 days (reminder at 7 days before)"

**Keputusan CTO:** **IKUT SECURITY CONFIG (90 HARI).** Alasan:

1. Kolom `password_changed_at` sudah dibuat (task.md §4.1f) untuk fungsi ini — kalau tidak dipakai, kolom tersebut sia-sia
2. Compliance ISO 27001 mengharuskan password rotation
3. Force password change saat pertama login (PRD §4.2) membutuhkan timestamp untuk menghitung umur password
4. PRD §4 bilang "tidak ada expiry" tapi Security Config (dokumen yang lebih spesifik) bilang "90 hari" — dokumen spesifik menang

**Aksi:** Update PRD §4 untuk mencabut kalimat "Tidak ada password expiry" dan ganti dengan "Password expiry 90 hari".

### CAT-006: EmploymentType — PRD Kontradiksi Internal (3 vs 4 Values)

**PRD §18 line 666** (column description): `employment_type` values: `permanent`, `contract`, `probation` (3 values)  
**PRD §18 line 713** (enum table): `EmploymentType`: `permanent`, `contract`, `probation`, `intern` (4 values)

**Keputusan CTO:** **IKUT 4 VALUES (include `intern`).** Alasan:

1. `intern` (magang) punya business logic yang berbeda di payroll: **BPJS dan PPh21 TIDAK dipotong** untuk intern
2. Testing strategy sudah merencanakan `InternExemptTest.php` untuk memastikan logic ini
3. ERD (erd.dbml) sudah mendefinisikan 4 values
4. Kode aktual (`app/Enums/EmploymentType.php`) sudah punya 4 values

**Aksi:** PRD §18 line 666 harus diupdate dari "3 values" ke "4 values: permanent, contract, probation, intern".

### CAT-007: PRD §14.7 Overtime Keys Masih Didefinisikan (Flat Rate)

**PRD §14.7** mendefinisikan `overtime_multiplier` (1.5) dan `overtime_weekend_multiplier` (2.0) sebagai konfigurasi flat rate.  
**ERR-001** sudah menyatakan bahwa key ini HARUS DIHAPUS dan diganti dengan `overtime_tiers_weekday` dan `overtime_tiers_holiday` (JSON indexed tiers).

**Aksi:** Hapus baris `overtime_multiplier` dan `overtime_weekend_multiplier` dari PRD §14.7 company_settings table, ganti dengan:
```
| `overtime_tiers_weekday` | `JSON (见 ERR-001)` | Tiered weekday overtime rates |
| `overtime_tiers_holiday` | `JSON (见 ERR-001)` | Tiered holiday/weekend overtime rates |
```

### CAT-008: PRD §27 Duplicate "Export to Excel" Entry

**PRD §27** memiliki dua entry "Export to Excel" yang bertentangan:
- Line 1124: "Export to Excel | PDF sudah cukup | 3-4 hari" (status: **ditunda ke V2**)
- Line 1129: "~~Export to Excel~~ | → **V1** | ~~3-4 hari~~" (status: **dipindahkan ke V1**, dicoret)

**Keputusan CTO:** Export to Excel adalah **V1** (sesuai line 1129 yang dipindahkan ke V1). Hapus entry pertama (line 1124) yang bilang "PDF sudah cukup".

### CAT-009: PRD §6.2 Attendance Status — 6 vs 8 Values

**PRD §6.2** hanya list 6 status: `on_time`, `late`, `early`, `holiday`, `permission`, `absent`  
**ERD dan kode aktual** punya 8 values: tambah `missed_clock_in`, `missed_clock_out`

**Aksi:** Update PRD §6.2 untuk menambahkan:
- `missed_clock_in` — Karyawan clock-out tanpa clock-in sebelumnya   
- `missed_clock_out` — Karyawan clock-in tapi lupa clock-out

### CAT-010: PRD §16 Missing `attendance:detect-chronic-late` Command

**PRD §6.5** mendokumentasi command `attendance:detect-chronic-late` (weeklyOn Friday 18:00).  
**PRD §16** hanya list 3 commands: `attendance:detect-alpha`, `leave:reset-quota`, `model:prune`.  
**PRD §27** menandai ChronicLateWarning sebagai **V1** (bukan V2).

**Aksi:** Tambah `attendance:detect-chronic-late` ke PRD §16 Commands table:
```
| `attendance:detect-chronic-late` | weeklyOn Friday 18:00 | Deteksi karyawan telat ≥ 3x/bulan |
```

### CAT-011: Asset Model — SoftDeletes Trait Missing + AssetStatus Enum Orphaned

**Masalah:** 
1. Migration `create_assets_table` punya `$table->softDeletes()`, tapi `Asset` model **tidak pakai `use SoftDeletes` trait**. Akibatnya, `$asset->delete()` melakukan hard delete, bukan soft delete.
2. `AssetStatus` enum (`available`, `assigned`, `disposed`) ada di `app/Enums/AssetStatus.php` tapi **tidak digunakan** oleh model — model pakai `is_available` boolean.

**Aksi:**
1. Tambah `use SoftDeletes` ke Asset model
2. Tambah kolom `company_id`, `code`, `category`, `status` ke migration (task.md §2.6)
3. Ganti `is_available` boolean → `status` enum cast di model

### CAT-012: AttendanceService::invalidateCache() — Dead Code (Database Driver Tidak Dukung Tags)

**Masalah:**
1. `AttendanceService::invalidateCache()` (lines 184-190) dipanggil setiap clock-in/out
2. `Cache::supportsTags()` SELALU return `false` dengan database driver → `Cache::tags()->flush()` dead code
3. `Cache::forget("attendance:employee:{$id}:date:{$date}")` dijalankan tapi cache key ini **tidak pernah di-populate** (`Cache::remember()` tidak ada di manapun)
4. **CPU terbuang sia-sia** — 2 operasi cache yang tidak berguna per absensi

**Aksi:**
1. **Pilihan A (jika ingin cache attendance):** Tambah `Cache::remember()` di `Employee::hasClockedInToday()` + ganti driver ke Redis (database driver tidak dukung tags)
2. **Pilihan B (jika tidak butuh cache):** Hapus seluruh method `invalidateCache()` dan semua panggilannya dari `clockIn()` + `clockOut()`

**Keputusan CTO:** Pilihan B (hapus dead code). Attendance query sudah di-index (`UNIQUE(employee_id, date)`) dan database driver tidak dukung tags. Menambahkan Redis hanya untuk attendance cache adalah over-engineering untuk MVP.

### CAT-013: `holiday_*` Cache Tanpa Invalidation — Stale 30 Hari

**Masalah:** `PayrollCalculatorService::calculateOvertimePay()` line 89:
```php
$isHoliday = Cache::remember("holiday_{$date->toDateString()}", now()->addMonth(), ...);
```
TTL 30 hari, tapi **tidak ada `Cache::forget("holiday_*")` di model `Holiday`**. Admin tambah/hapus libur nasional → cache tidak ter-refresh.

> **CATATAN:** `tax_configs` dan `bpjs_configs` sudah difix di task.md §2.9. Tapi `holiday_*` belum.

**Aksi:** Tambah cache invalidation di `Holiday` model (observer `saved`/`deleted`):
```php
// app/Models/Holiday.php — tambah di boot()
protected static function booted(): void
{
    static::saved(function (Holiday $holiday) {
        Cache::forget("holiday_{$holiday->date->toDateString()}");
    });
    static::deleted(function (Holiday $holiday) {
        Cache::forget("holiday_{$holiday->date->toDateString()}");
    });
}
```

### CAT-014: PTKP Magic Numbers Hardcoded — Tidak Dari TaxConfig/CompanySetting

**Masalah:** `Employee::calculatePtkp()` (lines 262-272):
```php
$base = match ($this->marital_status) {
    MaritalStatus::SINGLE => 54_000_000,
    MaritalStatus::MARRIED => 58_500_000,
    default => 54_000_000,
};
return $base + (min($dependentsCount, 3) * 4_500_000);
```
PTKP nilainya **berubah tiap tahun** oleh Peraturan Menteri Keuangan. Hardcode artinya setiap ada perubahan, developer harus deploy kode baru.

**Aksi:** Pindahkan PTKP values ke `CompanySetting` atau tabel `tax_configs`. Default fallback tetap values di atas.

```php
// Pindahkan ke CompanySetting
CompanySetting::get('ptkp_base_single', 54_000_000);         // TK/0
CompanySetting::get('ptkp_base_married', 58_500_000);        // K/0
CompanySetting::get('ptkp_per_dependent', 4_500_000);        // per tanggungan (max 3)
CompanySetting::get('ptkp_max_dependents', 3);
```

### CAT-015: VerificationMethod Enum Missing — Magic String Tersebar 6 Baris

**Masalah:** `AttendanceService` pakai string literal `'manual'`, `'face_verified'`, `'pin_verified'`, `'pin'` tanpa enum:
```php
// AttendanceService.php — magic strings:
$verificationMethod = 'manual';          // line 51
$verificationMethod = 'face_verified';   // line 60
if ($verificationMethod === 'manual')    // line 67
$verificationMethod = 'pin_verified';    // line 70
if ($verificationMethod === 'pin')       // line 132 ← TYPO: 'pin' vs 'pin_verified'!
$verificationMethod = 'face_verified';   // line 141
```
**Bug tambahan:** Line 132 compare ke `'pin'`, tapi nilai yang valid adalah `'pin_verified'`. Kondisi ini **selalu false** → clock-out PIN verification logic broken.

**Aksi:** Buat `App\Enums\VerificationMethod` enum dan ganti semua string literal:
```php
enum VerificationMethod: string
{
    case FACE_VERIFIED = 'face_verified';
    case PIN_VERIFIED = 'pin_verified';
    case MANUAL = 'manual';
}
```

### CAT-016: 22 Hari Kerja Hardcoded — Harus dari ManagesWorkDays Trait

**Masalah:** `PayrollCalculatorService` line 261:
```php
$alphaPenalty = $alphaCount * ($dailyRate > 0 ? $dailyRate / 22 : 0);
```
Angka 22 diasumsikan sebagai jumlah hari kerja sebulan. Kenyataannya bervariasi (19-23 hari) tergantung bulan dan tahun. Trait `ManagesWorkDays::countWorkingDays()` sudah ada tapi tidak dipakai.

**Aksi:** Ganti 22 dengan `countWorkingDays()`:
```php
$workingDays = $employee->company->countWorkingDays($targetYear, $targetMonth);
$alphaPenalty = $alphaCount * ($dailyRate > 0 ? $dailyRate / $workingDays : 0);
```

### CAT-017: FaceNotRecognizedException Ditelan — Fallback Tanpa Indikasi ke Pemanggil

**Masalah:** `AttendanceService::clockIn()` lines 62-64:
```php
} catch (FaceNotRecognizedException $e) {
    Log::warning('Verifikasi wajah gagal: '.$e->getMessage());
}
```
Exception ditangkap, di-log, lalu **ditelan tanpa fallback yang jelas**. Kode lanjut ke bawah seolah tidak terjadi apa-apa:
- `$verificationMethod` tetap `'manual'` (line 51) — tidak bisa dibedakan dari user yang memang tidak punya face embedding
- Jika user SUDAH punya face embedding tapi recognition gagal (mirip 72%), clock-in tetap sukses dengan method `'manual'` — ini security hole

**Aksi:** Refactor clock-in flow untuk tiered fallback yang eksplisit:
```php
// Tier 1: Face recognition (jika embedding ada)
if ($employee->face_embedding) {
    try {
        $faceResult = $this->faceRecognitionService->verifyFace($employee, $request->face_embedding);
        $response['verification_method'] = VerificationMethod::FACE_VERIFIED;
        $response['face_similarity_score'] = $faceResult['similarity_percentage'];
    } catch (FaceNotRecognizedException $e) {
        // Fallback ke Tier 2: PIN
        $response['verification_fallback'] = 'face_failed';
    }
}

// Tier 2: PIN (jika face gagal atau tidak ada embedding)
if ($response['verification_method'] === null) {
    $this->validatePin($employee, $request->pin);
    $response['verification_method'] = VerificationMethod::PIN_VERIFIED;
}
```

### CAT-018: Defensive Migration — pgvector Guard untuk SQLite Compatibility

**Masalah:** 3 file migration memanggil fitur PostgreSQL-specific tanpa guard:
1. `create_users_table.php:15` — `Schema::ensureVectorExtensionExists()` tanpa driver check
2. `create_employees_table.php:52` — `$table->vector('face_embedding', 128)` tanpa fallback
3. `create_knowledge_bases_table.php:20` — `$table->vector('embedding', 1536)` + `DB::statement('CREATE INDEX ... hnsw ...')` tanpa guard

PHPUnit (phpunit.xml) menggunakan SQLite in-memory, yang tidak mendukung `vector`, `pg_trgm`, `pgcrypto`, atau HNSW index. Hasilnya: **32 test failure**.

**Aksi:** Pasang `if (DB::getDriverName() === 'pgsql')` guard di semua PostgreSQL-specific code:
- `ensureVectorExtensionExists()` → guard dengan driver check
- `$table->vector()` → fallback ke `$table->text()` pada SQLite
- `DB::statement('CREATE INDEX ... USING hnsw ...')` → guard dengan driver check
- `DB::statement('CREATE EXTENSION IF NOT EXISTS ...')` → sudah di-guard di users migration

✅ **SUDAH DI-MERGE ke PRD v3.0 §19**

### CAT-019: Dilarang Hardcode SQL Error Code 23505

**Masalah:** `AttendanceService.php:107` menangkap `QueryException` dan mengecek `$e->getCode() === '23505'`. Error code `23505` adalah PostgreSQL-specific (Unique Constraint Violation). SQLite menggunakan error code `19` atau `23000`. Akibatnya, catch block ini tidak bekerja pada test environment (SQLite) dan menyebabkan duplicate clock-in tidak tertangkap.

**Aksi:** Ganti `QueryException` + hardcoded error code dengan `UniqueConstraintViolationException` dari Laravel, yang secara otomatis menangkap unique constraint violation di semua driver database:

```php
// SEBELUM (PostgreSQL-only):
} catch (QueryException $e) {
    if ($e->getCode() === '23505') { ... }
}

// SESUDAH (Cross-database compatible):
} catch (UniqueConstraintViolationException $e) {
    throw new AlreadyClockedInException(...);
} catch (QueryException $e) {
    throw $e;
}
```

✅ **SUDAH DI-MERGE ke PRD v3.0 §21.1**

---

*Terakhir diupdate: 20 Mei 2026 — v2.0 (CAT-018: Defensive Migration, CAT-019: Dilarang hardcode error code 23505. Semua koreksi sudah di-merge ke PRD v3.0)*