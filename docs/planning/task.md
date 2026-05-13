# HRConnect — Spesifikasi Eksekusi Perbaikan

> **Version:** 3.2 — Comprehensive Audit Update  
> **Tanggal:** 12 Mei 2026  
> **Errata v3.2:** 30 koreksi — 14 dari v3.1 + 16 temuan baru audit  
> **Cara Pakai:** Ikuti urutan Fase 0→1→2→3→4. Setiap item punya:  
> - **Masalah** — apa yang salah  
> - **File** — path file yang diubah  
> - **Kode Fix** — kode lengkap pengganti  
> - **Verifikasi** — command untuk cek fix  
> - **Depends On** — item yang harus selesai dulu

---

## ERRATA (Koreksi dari v2.1)

| # | Kesalahan | Koreksi | Lokasi |
|---|-----------|---------|--------|
| 1 | Typo `bpjsKesehetanDeduction` | Diperbaiki ke `bpjsKesehatanDeduction` | §0.4 |
| 2 | ApprovalService guard pakai `RequestStatus` hardcoded — gagal untuk Reimbursement yang pakai `ReimbursementStatus` | Diganti ke instance-based check | §0.6 |
| 3 | `refundLeaveQuotaIfApplicable()` di-reject padahal quota belum di-deduct | Dihapus — refund hanya diperlukan saat withdraw, bukan reject | §0.6 |
| 4 | PayrollAdjustment `decimal:2` perlu migration database | Karena development stage, cukup alter kolom langsung atau update migration asli, tanpa migration file baru | §2.4 |
| 5 | Unique index mungkin sudah ada | Ditambahkan langkah verifikasi sebelum buat migration | §0.3, §0.4 |
| 6 | **§0.3 & §0.4: Migration unique constraint TIDAK PERLU** — index sudah ada di database (`attendances_employee_id_date_unique` dan `payrolls_employee_id_period_unique`) | Hapus Langkah 1 (migration), pertahankan service fix (QueryException catch + lockForUpdate). Verifikasi command diperbaiki. | §0.3, §0.4 |
| 7 | **§2.6: Asset model fillable ditambah `company_id, code, category, status` tapi kolom ini TIDAK ADA di tabel `assets`** | Tambahkan migration alter table untuk add missing columns | §2.6 |
| 8 | **§0.6 Langkah 4: BusinessRuleException sudah ada** tapi extends `Exception` (HTTP 400), bukan `HttpException` (HTTP 422). API Contracts spec: validation errors return 422. | Ubah instruksi dari "buat jika belum ada" ke "ganti seluruh file" | §0.6 |
| 9 | **§4.1: `loan_installments` migration redundant** — kolom `status` dan `due_date` sudah ada di database | Hapus baris loan_installments dari §4.1 | §4.1 |
| 10 | **§4.1: 3 migration tanpa kode detail** (devices, attendances exception, attendances wfa) | Tambahkan kode migration lengkap untuk ketiga migration | §4.1 |
| 11 | **§3.1: Judul bilang "2x/3x" tapi PRD §26.7 bilang jam ke-10+ = 4x** | Update judul menjadi "2x/3x/4x" | §3.1 |
| 12 | **`password_changed_at` wajib untuk keamanan** — Password expiration (90 hari), force change password (PRD §4.2), dan audit forensik membutuhkan time anchor. Kolom `password_changed` (boolean) hanya bilang "sudah diganti atau belum", BUKAN "kapan terakhir diganti" | Tambah migration + model cast | §4.1 |
| 13 | **`overtimes.description` NOT NULL dan `overtimes.attendance_id` NOT NULL** — description harus nullable (FormRequest enforce required), attendance_id WAJIB nullable (PRD §8.1: lembur diajukan SEBELUM absen pulang) | Edit migration asli `create_overtimes_table` | §4.1d |
| 14 | **`employees.address_detail` NOT NULL** — seharusnya nullable (alamat boleh kosong, validasi di FormRequest) | Edit migration asli `create_employees_table` | §4.1e |
| 15 | **`$approval->level === 1` selalu false** — ApprovalLevel enum vs int strict comparison (`===`), status APPROVED_L1 tidak pernah tercapai | Ganti ke `$approval->level->value === 1` atau `$approval->level === ApprovalLevel::L1_SUPERVISOR`. Bug ini JUGA ada di §0.6 fix yang diusulkan! | §0.7 |
| 16 | **Payroll `SoftDeletes` memblokir regenerasi** — `$existingPayroll->delete()` hanya set `deleted_at`, record tetap ada, `Payroll::create()` crash karena unique constraint violation | Ganti ke `$existingPayroll->forceDelete()` atau gunakan partial unique index | §0.8 |
| 17 | **`laravel/sanctum` tidak terinstall** — Tidak ada package, no `HasApiTokens`, no API guard, no `personal_access_tokens` migration | Install Sanctum + konfigurasi lengkap untuk V2 API | §1.3 |
| 18 | **`Permission` enum + `RoleAndPermissionSeeder` + `SuperAdminSeeder` tidak ada** — `$user->can()` selalu return false, policy authorization 100% mati | Buat Permission enum + 2 seeder | §1.4 |
| 19 | **`PayrollCalculatorService::getTERCategory()` punya bug DIVORCED/WIDOWED yang sama dengan M4** — tapi di method berbeda, tidak difix di §2.8 | Tambah fix parallel di §3.3 | §3.3 |
| 20 | **`family_details_count` menghitung SEMUA keluarga (termasuk pasangan)**, bukan hanya anak — TER category salah | Ganti ke `withCount` yang filter `FamilyRelationship::CHILD` | §3.4 |
| 21 | **`attendances.shift_id` NOT NULL** tapi `employees.shift_id` nullable — karyawan tanpa shift gagal clock-in | Edit migration: `$table->foreignId('shift_id')->nullable()` | §4.1g |
| 22 | **`payroll_adjustments.created_by` NOT NULL + `nullOnDelete()`** — user deletion crash (constraint violation) | Ganti ke `->nullable()` atau `restrictOnDelete()` | §4.1h |
| 23 | **`knowledge_bases.embedding` NOT NULL** — mencegah insert sebelum embedding di-generate, `processEmbedding()` menjadi useless | Ganti ke `->nullable()` | §4.1i |
| 24 | **`companies.address_detail` NOT NULL** — sama seperti employees, seharusnya nullable | Edit migration asli `create_companies_table` | §4.1j |
| 25 | **`branches.address_detail` NOT NULL** — sama seperti employees | Edit migration asli `create_branches_table` | §4.1j |
| 26 | **`employees.npwp` NOT NULL** — CipherSweet pakai `addOptionalTextField`, seharusnya nullable | Edit migration asli `create_employees_table` | §4.1k |
| 27 | **`employees.bank_account_number` + `bank_name` NOT NULL** — seharusnya nullable (karyawan baru/probation mungkin belum punya rekening) | Edit migration asli `create_employees_table` | §4.1k |
| 28 | **`LeaveService::carryForward()` mengabaikan `carry_forward` tahun sebelumnya** + menimpa kuota karyawan dengan default tipe cuti** | Ganti `$remaining = $quota - $used` ke `$remaining = $balance->available()` dan `$quota = $prevBalance->quota` bukan `$prevBalance->leaveType->quota` | §3.5 |
| 29 | **`DomainException` di PayrollCalculatorService return HTTP 500** — seharusnya `BusinessRuleException` (422) | Ganti ke BusinessRuleException | §3.6 |
| 30 | **`Approval` model: `approvable_type`/`approvable_id` di `#[Fillable]`** — mass-assignment security risk | Hapus dari fillable | §2.10 |

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

### 0.1 RC-5: Queue retry_after < timeout

**Masalah:** `retry_after=90s` tapi `timeout=120s`. Job payroll yang jalan >90 detik akan diproses 2x.  
**Severity:** CRITICAL  
**File:** `config/queue.php`  
**Depends On:** —  
**Estimasi:** 1 menit

**Fix:**

```php
// config/queue.php — baris 43
// SEBELUM:
'retry_after' => (int) env('DB_QUEUE_RETRY_AFTER', 90),

// SESUDAH:
'retry_after' => (int) env('DB_QUEUE_RETRY_AFTER', 180),
```

Juga update `GenerateEmployeePayrollJob` agar queue-nya explicit:

```php
// app/Jobs/GenerateEmployeePayrollJob.php — tambah properti
// CATATAN: hanya berpengaruh jika queue connection mendukung named queues.
// Untuk database driver default, job akan masuk ke queue 'default'.
// Konfigurasi queue terpisah diperlukan jika ingin named queue 'payroll_high'.
public string $queue = 'payroll_high';
```

**Verifikasi:**
```bash
php artisan config:show queue.connections.database.retry_after
# Harus return 180
```

---

### 0.2 DL-3: face_similarity_score & verification_method Ditimpa Saat Clock-Out

**Masalah:** Clock-out menimpa `verification_method` dan `face_similarity_score` dari clock-in. Data biometrik clock-in hilang.  
**Severity:** CRITICAL  
**File:** `app/Services/AttendanceService.php`  
**Depends On:** —  
**Estimasi:** 15 menit

**Fix — opsi terbaik: Buat kolom terpisah di migration dulu, lalu update service.**

**Langkah 1 — Buat migration:**

```bash
php artisan make:migration add_clock_out_verification_to_attendances_table
```

```php
// database/migrations/YYYY_MM_DD_HHMMSS_add_clock_out_verification_to_attendances_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('clock_out_verification_method', 50)->nullable()->after('verification_method');
            $table->decimal('clock_out_face_similarity_score', 5, 2)->nullable()->after('face_similarity_score');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['clock_out_verification_method', 'clock_out_face_similarity_score']);
        });
    }
};
```

**Langkah 2 — Update Model:**

```php
// app/Models/Attendance.php — tambah ke #[Fillable] attribute
// Tambahkan: 'clock_out_verification_method', 'clock_out_face_similarity_score'

// Tambahkan ke casts():
'clock_out_verification_method' => 'string',
'clock_out_face_similarity_score' => 'decimal:2',
```

**Langkah 3 — Update AttendanceService clockOut:**

```php
// app/Services/AttendanceService.php — method clockOut()
// GANTI baris 151-160 (update.clock_out):

// SEBELUM:
$lockedAttendance->update([
    'clock_out' => now(),
    'lat_out' => $data['latitude'] ?? null,
    'long_out' => $data['longitude'] ?? null,
    'clock_out_is_mocked' => $data['is_mocked'] ?? false,
    'clock_out_accuracy' => $data['accuracy'] ?? null,
    'photo_selfie_out' => $data['photo_selfie'] ?? null,
    'verification_method' => $verificationMethod,        // ← OVERWRITE clock-in
    'face_similarity_score' => $faceSimilarityScore,      // ← OVERWRITE clock-in
]);

// SESUDAH:
$lockedAttendance->update([
    'clock_out' => now(),
    'lat_out' => $data['latitude'] ?? null,
    'long_out' => $data['longitude'] ?? null,
    'clock_out_is_mocked' => $data['is_mocked'] ?? false,
    'clock_out_accuracy' => $data['accuracy'] ?? null,
    'photo_selfie_out' => $data['photo_selfie'] ?? null,
    'clock_out_verification_method' => $verificationMethod,
    'clock_out_face_similarity_score' => $faceSimilarityScore,
]);
```

**Verifikasi:**
```bash
php artisan migrate
# Lalu test clock-in lalu clock-out, cek bahwa clock_in verification_method tetap utuh:
php artisan tinker --execute '
$att = App\Models\Attendance::latest()->first();
echo "In method: " . $att->verification_method . "\n";
echo "Out method: " . $att->clock_out_verification_method . "\n";
echo "In face: " . $att->face_similarity_score . "\n";
echo "Out face: " . $att->clock_out_face_similarity_score . "\n";
'
```

---

### 0.3 RC-1: Clock-In Double-Submit → 500 Error

**Masalah:** Double-tap clock-in bisa membuat 2 record attendance. Unique constraint `(employee_id, date)` **sudah ada di database** (`attendances_employee_id_date_unique`), tapi service tidak catch unique violation.  
**Severity:** CRITICAL  
**File:** `AttendanceService.php`  
**Depends On:** —  
**Estimasi:** 15 menit

> **CATATAN (v3.0):** Unique index `attendances_employee_id_date_unique` sudah ada di database.
> Migration TIDAK DIPERLUKAN. Fix hanya di service layer (catch QueryException).

**Update AttendanceService clockIn untuk catch unique violation:**

```php
// app/Services/AttendanceService.php — method clockIn()
// GANTI seluruh method clockIn:

public function clockIn(Employee $employee, array $data): Attendance
{
    if (isset($data['is_mocked']) && $data['is_mocked'] == true) {
        throw new AntiFakeGPSException('Peringatan: Aplikasi Fake GPS / Tuyul terdeteksi!');
    }

    if ($employee->hasClockedInToday()) {
        throw new AlreadyClockedInException('Anda sudah melakukan absensi masuk hari ini.');
    }

    $isWfa = $data['is_wfa'] ?? false;

    if ($isWfa) {
        if (empty($data['wfa_note']) || mb_strlen(trim($data['wfa_note'])) < 20) {
            throw new BusinessRuleException('Catatan WFA wajib diisi minimal 20 karakter.');
        }
    } else {
        if (! $employee->branch) {
            throw new BusinessRuleException('Data lokasi kerja Anda belum diatur. Hubungi HRD.');
        }
        $this->geofenceService->validateLocation($employee->branch, $data);
    }

    $verificationMethod = 'manual';
    $faceSimilarityScore = null;

    if (! empty($data['face_embedding'])) {
        try {
            $faceResult = $this->faceRecognitionService->verifyFace(
                $employee,
                $data['face_embedding']
            );
            $verificationMethod = 'face_verified';
            $faceSimilarityScore = $faceResult['similarity_percentage'];
        } catch (FaceNotRecognizedException $e) {
            Log::warning('Verifikasi wajah gagal: '.$e->getMessage());
        }
    }

    if ($verificationMethod === 'manual' && ! empty($data['pin'])) {
        $this->verifyPin($employee, $data['pin']);
        $this->logBypass($employee, 'pin_verified_clock_in');
        $verificationMethod = 'pin_verified';
    }

    try {
        return DB::transaction(function () use ($employee, $data, $isWfa, $verificationMethod, $faceSimilarityScore) {
            if ($employee->hasClockedInToday()) {
                throw new AlreadyClockedInException('Data absen masuk sudah tercatat.');
            }

            $now = now();
            $lateMinutes = $employee->shift ? $employee->shift->calculateLateMinutes($now) : 0;

            $status = $lateMinutes > 0
                ? AttendanceStatus::LATE
                : AttendanceStatus::ON_TIME;

            $attendance = Attendance::create([
                'employee_id' => $employee->id,
                'shift_id' => $employee->shift_id,
                'date' => $now->toDateString(),
                'clock_in' => $now,
                'lat_in' => $data['latitude'] ?? null,
                'long_in' => $data['longitude'] ?? null,
                'clock_in_is_mocked' => $data['is_mocked'] ?? false,
                'clock_in_accuracy' => $data['accuracy'] ?? null,
                'is_wfa' => $isWfa,
                'wfa_note' => $data['wfa_note'] ?? null,
                'late_minutes' => $lateMinutes,
                'verification_method' => $verificationMethod,
                'face_similarity_score' => $faceSimilarityScore,
                'photo_selfie_in' => $data['photo_selfie'] ?? null,
                'status' => $status,
            ]);

            $this->invalidateCache($employee);

            return $attendance;
        });
    } catch (\Illuminate\Database\QueryException $e) {
        if ($e->getCode() === '23505') {
            throw new AlreadyClockedInException('Anda sudah melakukan absensi masuk hari ini.');
        }
        throw $e;
    }
}
```

**Verifikasi:**
```bash
php artisan migrate
# Test double-submit: kirim clock-in 2x cepat, harus return friendly error bukan 500
```

---

### 0.4 RC-2: Payroll Double-Generation Race Condition

**Masalah:** Dua request payroll generate untuk employee+period yang sama bisa masuk bersamaan.  
**Severity:** CRITICAL  
**File:** `PayrollCalculatorService.php`  
**Depends On:** —  
**Estimasi:** 30 menit

> **CATATAN (v3.0):** Unique index `payrolls_employee_id_period_unique` **sudah ada di database**.
> Migration TIDAK DIPERLUKAN. Fix hanya di service layer (lockForUpdate + QueryException catch).

**Update PayrollCalculatorService::generatePayroll dengan lockForUpdate + QueryException catch:**

```php
// app/Services/PayrollCalculatorService.php — ganti method generatePayroll():

public function generatePayroll(Employee $employee, string $period): Payroll
{
    $parsedPeriod = Carbon::createFromFormat('Y-m', $period);
    $targetYear = $parsedPeriod->year;
    $targetMonth = $parsedPeriod->month;

    try {
        return DB::transaction(function () use ($employee, $period, $targetYear, $targetMonth) {
            $existingPayroll = Payroll::where('employee_id', $employee->id)
                ->where('period', $period)
                ->lockForUpdate()
                ->first();

            if ($existingPayroll && $existingPayroll->status === PayrollStatus::PUBLISHED) {
                throw new DomainException("Payroll untuk periode {$period} sudah dikunci permanen.");
            }

            // Hitung semua nilai sebelum create
            $grossSalary = $this->calculateProratedSalary($employee, $period);

            $overtimes = Overtime::where('employee_id', $employee->id)
                ->where('status', RequestStatus::APPROVED)
                ->whereBetween('date', [
                    Carbon::create($targetYear, $targetMonth, 1)->startOfMonth()->toDateString(),
                    Carbon::create($targetYear, $targetMonth, 1)->endOfMonth()->toDateString(),
                ])
                ->get();

            $totalOvertimePay = $overtimes
                ->map(fn(Overtime $ot) => $this->calculateOvertimePay($ot))
                ->sum();

            $taxableIncome = $grossSalary + $totalOvertimePay;

            $startOfMonth = Carbon::create($targetYear, $targetMonth, 1)->startOfMonth();
            $endOfMonth = Carbon::create($targetYear, $targetMonth, 1)->endOfMonth();

            $reimbursements = Reimbursement::where('employee_id', $employee->id)
                ->where('status', ReimbursementStatus::APPROVED)
                ->whereBetween('expense_date', [$startOfMonth, $endOfMonth])
                ->get();

            $totalReimbursement = $reimbursements->sum('amount');
            $totalGross = $taxableIncome + $totalReimbursement;

            $penaltyPerDay = (int) CompanySetting::get('attendance_penalty_per_day', 50000);

            $lateCount = Attendance::where('employee_id', $employee->id)
                ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
                ->where('late_minutes', '>', 0)
                ->count();
            $latePenalty = $lateCount * $penaltyPerDay;

            $alphaCount = Attendance::where('employee_id', $employee->id)
                ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
                ->where('status', AttendanceStatus::ABSENT)
                ->count();
            $dailyRate = $employee->position?->basic_salary ?? 0;
            $alphaPenalty = $alphaCount * ($dailyRate > 0 ? $dailyRate / 22 : 0);

            $attendancePenalty = $latePenalty + $alphaPenalty;

            $bpjsComponents = $this->calculateBPJS($employee, $taxableIncome);
            $bpjsKesehatanDeduction = $bpjsComponents['bpjs_kesehatan']['employee'];
            $bpjsEmploymentDeduction = $bpjsComponents['bpjs_jht']['employee'] + $bpjsComponents['bpjs_jp']['employee'];

            $terCategory = $this->getTERCategory($employee);
            $pph21Deduction = $this->calculatePPh21($employee, max(0, $taxableIncome - $attendancePenalty), $terCategory);

            $totalDeduction = $attendancePenalty + $bpjsKesehatanDeduction + $bpjsEmploymentDeduction + $pph21Deduction;
            $basicSalary = $employee->position?->basic_salary ?? 0;
            $totalAllowance = $employee->position?->allowance_jabatan ?? 0;
            $netSalary = $totalGross - $totalDeduction;

            if ($existingPayroll) {
                // Reset reimbursements yang terkait payroll lama
                Reimbursement::where('payroll_id', $existingPayroll->id)->update([
                    'payroll_id' => null,
                    'status' => ReimbursementStatus::APPROVED,
                ]);
                $existingPayroll->delete();
            }

            $payroll = Payroll::create([
                'employee_id' => $employee->id,
                'period' => $period,
                'basic_salary' => $basicSalary,
                'total_allowance' => $totalAllowance,
                'gross_salary' => $totalGross,
                'overtime_pay' => $totalOvertimePay,
                'pph21' => $pph21Deduction,
                'bpjs_health' => $bpjsKesehatanDeduction,
                'bpjs_employment' => $bpjsEmploymentDeduction,
                'loan_deduction' => 0,
                'attendance_penalty' => $attendancePenalty,
                'total_deduction' => $totalDeduction,
                'net_salary' => $netSalary,
                'status' => PayrollStatus::DRAFT,
            ]);

            if ($reimbursements->isNotEmpty()) {
                Reimbursement::whereIn('id', $reimbursements->pluck('id'))->update([
                    'payroll_id' => $payroll->id,
                    'status' => ReimbursementStatus::PAID,
                ]);
            }

            return $payroll;
        });
    } catch (\Illuminate\Database\QueryException $e) {
        if ($e->getCode() === '23505') {
            throw new DomainException("Payroll untuk periode {$period} sudah ada. Gunakan regenerate.");
        }
        throw $e;
    }
}
```

**Perubahan penting dalam method ini:**

1. **`lockForUpdate()`** — cegah race condition
2. **`whereBetween('date', [...])`** — ganti `whereYear/whereMonth` yang bypass B-tree index
3. **`whereBetween('expense_date', [...])`** — filter reimbursement per periode (fix P1)
4. **Reset reimbursements sebelum delete** — fix DL-1
5. **Catch `QueryException 23505`** — friendly error untuk unique violation (fix RC-2)
6. **`max(0, ...)`** — cegah negative taxable income untuk PPh21

**Verifikasi:**
```bash
php artisan migrate
# Test: generate payroll untuk employee Y, lalu generate lagi → harus return friendly error
```

---

### 0.5 DL-1: Reimbursement PAID Tanpa payroll_id Setelah Regenerate

**Masalah:** Saat payroll di-regenerate, reimbursement yang sudah PAID tetap PAID tapi payroll_id-nya orphan. Fix SUDAH termasuk di 0.4 di atas (lihat baris "Reset reimbursements").  
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

### 0.6 DL-2: Leave Quota Di-deduct Saat SUBMIT, Bukan Saat APPROVED

**Masalah:** `LeaveService::applyLeave()` langsung deduct quota saat submit. Jika di-reject, kuota tidak dikembalikan.  
**Severity:** CRITICAL  
**File:** `app/Services/LeaveService.php`, `app/Services/ApprovalService.php`  
**Depends On:** 0.3 (B2 fix — isAllApproved)  
**Estimasi:** 45 menit

**Langkah 1 — Hapus deduct dari LeaveService::applyLeave():**

```php
// app/Services/LeaveService.php — method applyLeave()
// GANTI seluruh method:

public function applyLeave(Employee $employee, array $data): Leave
{
    $leaveType = LeaveType::findOrFail($data['leave_type_id']);
    $startDate = Carbon::parse($data['start_date']);
    $endDate = Carbon::parse($data['end_date']);
    $dayType = DayType::from($data['day_type'] ?? 'full_day');

    if ($startDate->isBefore(now()->startOfDay()->subDays(3))) {
        throw new BusinessRuleException('Pengajuan cuti maksimal mundur H+3 dari hari ini.');
    }

    if ($employee->employment_type === EmploymentType::PROBATION
        && $leaveType->deductsFromQuota()) {
        throw new BusinessRuleException('Karyawan masa percobaan tidak dapat mengajukan cuti tahunan.');
    }

    $sickLeaveCode = CompanySetting::get('leave_sick_code', 'sick');
    if ($leaveType->code === $sickLeaveCode && empty($data['proof_file'])) {
        throw new BusinessRuleException('Cuti Sakit wajib menyertakan bukti (Surat Dokter).');
    }

    $totalDays = $this->calculateWorkDays($startDate, $endDate, $dayType);

    if ($totalDays <= 0) {
        throw new BusinessRuleException('Durasi cuti 0 hari. Tanggal hanya weekend atau libur.');
    }

    if (Leave::hasOverlap($employee->id, $startDate, $endDate)) {
        throw new BusinessRuleException('Tanggal bertabrakan dengan pengajuan cuti lain.');
    }

    // VALIDASI quota di sini, tapi JANGAN deduct dulu
    if ($leaveType->deductsFromQuota()) {
        $balance = LeaveBalance::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', now()->year)
            ->first();

        if (! $balance) {
            throw new BusinessRuleException('Saldo cuti Anda belum diinisialisasi.');
        }

        if (! $balance->hasEnoughQuota($totalDays)) {
            $remaining = $balance->available();
            throw new BusinessRuleException(
                "Kuota cuti tidak mencukupi. (Sisa: {$remaining} hari, Diminta: {$totalDays} hari)"
            );
        }
    }

    // BUAT LEAVE TANPA DEDUCT — deduct akan dilakukan di ApprovalService::approve()
    // setelah SEMUA approval selesai
    return DB::transaction(function () use ($employee, $leaveType, $data, $totalDays) {
        $leave = Leave::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'day_type' => $data['day_type'],
            'total_days' => $totalDays,
            'reason' => $data['reason'],
            'proof_file' => $data['proof_file'] ?? null,
            'status' => RequestStatus::PENDING,
        ]);

        $this->approvalService->createApprovalWorkflow($leave);

        return $leave;
    });
}
```

**Langkah 2 — Tambah deduct di ApprovalService::approve() dan refund di reject():**

```php
// app/Services/ApprovalService.php — ganti seluruh file:

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\RequestStatus;
use App\Models\Approval;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Reimbursement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LogicException;

class ApprovalService
{
    public function createApprovalWorkflow(Model $approvable): void
    {
        DB::transaction(function () use ($approvable) {
            $employee = $approvable->employee;
            $directApprover = $employee->getDirectApprover();
            $l2Approver = $this->resolveL2Approver($approvable);

            $approversCount = 0;

            if ($directApprover) {
                $approvable->approvals()->create([
                    'approver_id' => $directApprover->id,
                    'level' => 1,
                    'status' => ApprovalStatus::PENDING,
                ]);
                $approversCount++;
            }

            if ($l2Approver) {
                // Jangan buat L2 = L1 (deduplikasi)
                if (! $directApprover || $l2Approver->id !== $directApprover->id) {
                    $approvable->approvals()->create([
                        'approver_id' => $l2Approver->id,
                        'level' => 2,
                        'status' => ApprovalStatus::PENDING,
                    ]);
                    $approversCount++;
                }
            }

            // Fallback: jika tidak ada approver sama sekali, langsung ke Super Admin
            if ($approversCount === 0) {
                $superAdmin = User::role('super-admin')->first()?->employee;
                if ($superAdmin) {
                    $approvable->approvals()->create([
                        'approver_id' => $superAdmin->id,
                        'level' => 1,
                        'status' => ApprovalStatus::PENDING,
                    ]);
                    $approversCount++;
                }
            }

            if ($approversCount === 0) {
                throw new LogicException('Tidak ada Approver (Atasan/HR) yang tersedia.');
            }
        });
    }

    public function approve(Approval $approval, string $notes = ''): void
    {
        DB::transaction(function () use ($approval, $notes) {
            $approvable = $approval->approvable()->lockForUpdate()->first();

            // Guard: jangan approve yang sudah diproses
            if ($approval->status !== ApprovalStatus::PENDING) {
                throw new LogicException('Persetujuan ini sudah diproses.');
            }

            // Guard: jangan approve jika parent model sudah resolved
            // Gunakan instance-based check karena Leave/Overtime pakai RequestStatus
            // tapi Reimbursement pakai ReimbursementStatus
            $pendingStatuses = match (true) {
                $approvable instanceof \App\Models\Leave, $approvable instanceof \App\Models\Overtime
                    => [RequestStatus::PENDING->value, RequestStatus::APPROVED_L1->value],
                $approvable instanceof \App\Models\Reimbursement
                    => [\App\Enums\ReimbursementStatus::PENDING->value],
                $approvable instanceof \App\Models\Attendance
                    => [ApprovalStatus::PENDING->value],
                default => [],
            };

            if (! in_array($approvable->status instanceof \BackedEnum ? $approvable->status->value : $approvable->status, $pendingStatuses, true)) {
                throw new LogicException('Pengajuan ini sudah diproses.');
            }

            $approval->update([
                'status' => ApprovalStatus::APPROVED,
                'approved_at' => now(),
                'notes' => $notes ?: null,
            ]);

            if ($approvable->isAllApproved()) {
                // Instance-based check: Reimbursement pakai ReimbursementStatus,
                // Leave/Overtime pakai RequestStatus. Attendance tidak masuk sini.
                $finalStatus = $approvable instanceof \App\Models\Reimbursement
                    ? \App\Enums\ReimbursementStatus::APPROVED
                    : RequestStatus::APPROVED;
                $approvable->update(['status' => $finalStatus]);
                // DEDUCT QUOTA SETELAH SEMUA APPROVAL SELESAI
                $this->deductLeaveQuotaIfApplicable($approvable);
            } elseif ($approval->level === 1) {
                // Reimbursement tidak punya status APPROVED_L1 — tetap PENDING
                // sampai finance (L2) approve. Hanya Leave/Overtime yang pakai L1.
                if (! ($approvable instanceof \App\Models\Reimbursement)) {
                    $approvable->update(['status' => RequestStatus::APPROVED_L1]);
                }
            }
        });
    }

    public function reject(Approval $approval, string $reason): void
    {
        DB::transaction(function () use ($approval, $reason) {
            $approvable = $approval->approvable()->lockForUpdate()->first();

            if ($approval->status !== ApprovalStatus::PENDING) {
                throw new LogicException('Persetujuan ini sudah diproses.');
            }

            $approval->update([
                'status' => ApprovalStatus::REJECTED,
                'notes' => $reason,
            ]);

            // Cancel semua PENDING approvals lainnya
            $approvable->approvals()
                ->where('id', '!=', $approval->id)
                ->where('status', ApprovalStatus::PENDING)
                ->update(['status' => ApprovalStatus::REJECTED, 'notes' => 'Otomatis dibatalkan karena pengajuan ditolak.']);

            // Instance-based check: Reimbursement pakai ReimbursementStatus,
            // Leave/Overtime/Attendance pakai RequestStatus.
            $rejectedStatus = $approvable instanceof \App\Models\Reimbursement
                ? \App\Enums\ReimbursementStatus::REJECTED
                : RequestStatus::REJECTED;
            $approvable->update([
                'status' => $rejectedStatus,
                'rejection_reason' => $reason,
            ]);

            // CATATAN: TIDAK perlu refund quota di sini karena quota bara di-deduct
            // di approve() setelah SEMUA approval selesai. Jika reject terjadi sebelum
            // semua approval, quota belum pernah di-deduct.
            // Refund hanya diperlukan saat "withdraw" (cancel cuti yang sudah approved),
            // yang akan diimplementasi di fitur terpisah.
        });
    }

    protected function resolveL2Approver(Model $approvable): ?Employee
    {
        if ($approvable instanceof Reimbursement) {
            return User::role('finance')->first()?->employee;
        }

        return User::role('hr-manager')->first()?->employee;
    }

    protected function deductLeaveQuotaIfApplicable(Model $approvable): void
    {
        if ($approvable instanceof Leave) {
            $leaveType = $approvable->leaveType;

            if ($leaveType && $leaveType->deductsFromQuota()) {
                $balance = LeaveBalance::where('employee_id', $approvable->employee_id)
                    ->where('leave_type_id', $leaveType->id)
                    ->where('year', $approvable->start_date->year)
                    ->lockForUpdate()
                    ->first();

                if ($balance) {
                    $balance->deduct($approvable->total_days);
                }
            }
        }
    }

    // CATATAN: refundLeaveQuotaIfApplicable() dihapus dari reject() karena
    // quota hanya di-deduct di approve() setelah semua approval selesai.
    // Method ini akan dibutuhkan lagi saat fitur "withdraw leave" diimplementasi.
}
```

**Langkah 3 — Tambah `refund()` method di LeaveBalance:**

```php
// app/Models/LeaveBalance.php — tambah method setelah deduct():

public function refund(float $days): void
{
    if ($this->used < $days) {
        $this->used = 0;
    } else {
        $this->decrement('used', $days);
    }
}
```

**Juga update `deduct()` untuk cek minimum:**

```php
// app/Models/LeaveBalance.php — ganti method deduct():

public function deduct(float $days): void
{
    if ($this->available() < $days) {
        throw new \App\Exceptions\BusinessRuleException('Kuota cuti tidak mencukupi.');
    }
    $this->increment('used', $days);
}
```

**Langkah 4 — Ganti seluruh BusinessRuleException (sudah ada tapi salah HTTP code):**

> **CATATAN (v3.0):** File `app/Exceptions/BusinessRuleException.php` sudah ada, tapi
> menggunakan `extends Exception` dengan HTTP code **400**. API Contracts spec mengharuskan
> validation/business rule errors return **422**. Ganti seluruh file:

```php
// app/Exceptions/BusinessRuleException.php — GANTI SELURUH FILE:

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class BusinessRuleException extends HttpException
{
    public function __construct(string $message = '', int $statusCode = 422)
    {
        parent::__construct($statusCode, $message);
    }
}
```

**Verifikasi:**
```bash
# Test skenario:
# 1. Apply leave → quota TIDAK berkurang
# 2. Approve L1 → quota TIDAK berkurang
# 3. Approve L2 → quota BERKURANG
# 4. Reject leave → quota DIKEMBALIKAN
php artisan test --compact --filter=LeaveTest
```

---

### 0.7 C1: ApprovalLevel Enum vs Integer Strict Comparison — APPROVED_L1 Never Reached

**Masalah:** `Approval` model cast `level` ke `ApprovalLevel` enum, tapi `ApprovalService` membandingkan dengan integer `1` menggunakan `===`. PHP strict comparison `enum === int` **selalu false**. Akibatnya, status `APPROVED_L1` tidak pernah tercapai.  
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

### 0.8 C2: Payroll SoftDelete Memblokir Regenerasi — Unique Constraint Violation

**Masalah:** `Payroll` model menggunakan `SoftDeletes`. Saat `generatePayroll()` regenerasi, `$existingPayroll->delete()` hanya set `deleted_at` — record tetap ada di database. `Payroll::create()` berikutnya dengan `employee_id + period` yang sama **crash** karena `payrolls_employee_id_period_unique` constraint violation.  
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

> **WAJIB SELESAI SEBELUM UI/API DIBUKA KE USER.**

---

### 1.1 SEC-1: PII Tidak Ada di $hidden

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

### 1.2 SEC-2: Zero Policies → IDOR

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

### 1.3 SEC-3: Sanctum Tidak Terinstall — API Auth 0%

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

### 1.4 SEC-4: Permission Enum + RoleAndPermissionSeeder + SuperAdminSeeder Tidak Ada

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

### 1.5 SEC-5: Exception HTTP Codes Tidak Konsisten

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

## FASE 2 — Missing Infrastructure

> **Infrastruktur yang diperlukan sebelum fitur bisa berjalan.**

---

### 2.1 B1: KnowledgeBase — Tambah Kolom `status` dan `category`

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

### 2.2 B2: isAllApproved() Vacuous Truth

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

### 2.3 B3: isLocked() Tidak Block PAID

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

### 2.4 B4: PayrollAdjustment.amount Cast integer → decimal:2

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

### 2.5 B5: Company Model Missing 6 Address Columns

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

### 2.6 B6: Asset Model — Tambah SoftDeletes, Fillable, Casts, dan Migration

**Masalah:** Migration punya `deleted_at` tapi model tidak pakai trait. Kolom `company_id`, `code`, `category`, `status` ada di ERD tapi tidak di model.  
**Severity:** HIGH  
**Depends On:** —  
**Estimasi:** 15 menit

**Langkah 1 — Buat migration untuk add missing columns ke assets table:**

```bash
php artisan make:migration add_missing_columns_to_assets_table
```

```php
// database/migrations/YYYY_MM_DD_HHMMSS_add_missing_columns_to_assets_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('code')->nullable()->after('name');
            $table->string('category')->nullable()->after('code');
            $table->string('status', 20)->default('available')->after('serial_number');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn(['code', 'category', 'status']);
        });
    }
};
```

**Langkah 2 — Update Model:**

```php
// app/Models/Asset.php — ganti seluruh file:

namespace App\Models;

use App\Enums\AssetStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['company_id', 'name', 'code', 'category', 'status', 'serial_number'])]
class Asset extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'status' => AssetStatus::class,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function handovers(): HasMany
    {
        return $this->hasMany(AssetHandover::class);
    }
}
```

---

### 2.7 B8: LeaveBalance.deduct() Bisa Negatif

**Masalah:** Tidak ada pengecekan minimum sebelum deduct.  
**Severity:** HIGH  
**Depends On:** 0.6 (sudah diperbaiki di sana)  
**Estimasi:** 5 menit (sudah termasuk di 0.6 Langkah 3)

**Jika dikerjakan terpisah:**

```php
// app/Models/LeaveBalance.php — ganti deduct():

public function deduct(float $days): void
{
    if ($this->available() < $days) {
        throw new \App\Exceptions\BusinessRuleException('Kuota cuti tidak mencukupi.');
    }
    $this->increment('used', $days);
}
```

---

### 2.8 M4: TerCategory resolveFromStatus Crash pada DIVORCED/WIDOWED

**Masalah:** String 'divorced'/'widowed' tidak ditangani, jatuh ke match default (yang untuk married).  
**Severity:** MEDIUM  
**Depends On:** —  
**Estimasi:** 5 menit

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

### 2.9 M2: Tax/BPJS Cache 24 Jam Tanpa Invalidation

**Masalah:** Admin ubah tarif → cache masih pakai tarif lama selama 24 jam.  
**Severity:** MEDIUM  
**Depends On:** —  
**Estimasi:** 15 menit

```php
// app/Models/TaxConfig.php — tambah boot method:

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class TaxConfig extends Model
{
    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('tax_configs');
        });

        static::deleted(function () {
            Cache::forget('tax_configs');
        });
    }
}
```

```php
// app/Models/BpjsConfig.php — sama:

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class BpjsConfig extends Model
{
    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('bpjs_configs');
        });

        static::deleted(function () {
            Cache::forget('bpjs_configs');
        });
    }
}
```

---

### 2.10 SEC-6: Approval Model — Mass Assignment Risk pada `approvable_type`/`approvable_id`

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

### 2.11 H9: Employee Model — Missing `vector` Cast untuk `face_embedding`

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

### 2.12 H10: KnowledgeBase Model — Missing `vector` Cast untuk `embedding`

**Masalah:** Sama seperti H9, migration mendefinisikan `embedding` sebagai `vector(1536)`, tapi model tidak punya cast.  
**Severity:** HIGH  
**Depends On:** —  
**Estimasi:** 5 menit

```php
// app/Models/KnowledgeBase.php — tambah di casts():

'embedding' => 'vector',
```

---

### 2.13 M1: Holiday Model — Missing `date` Cast

**Masalah:** Kolom `date` tanpa cast mengembalikan string mentah, bukan Carbon instance.  
**Severity:** MEDIUM  
**Depends On:** —  
**Estimasi:** 2 menit

```php
// app/Models/Holiday.php — tambah di casts():

'date' => 'date',
```

---

### 2.14 M2 & M3: Company dan Branch `address_detail` NOT NULL — Harus Nullable

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

### 2.15 M4: Employee `npwp`, `bank_account_number`, `bank_name` NOT NULL — Harus Nullable

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

### 2.16 M5: Missing Foreign Key Indexes (PostgreSQL Performance)

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

### 2.17 M12: AssetStatus Enum Orphaned — `is_available` Boolean Harusnya `status` + Enum

**Masalah:** `AssetStatus` enum (AVAILABLE, ASSIGNED, DISPOSED) didefinisikan tapi tidak dipakai oleh model. Model pakai `is_available` boolean. Enum tidak bisa men-track status "disposed".  
**Severity:** MEDIUM  
**Depends On:** 2.6 (Asset model update)  
**Estimasi:** Sudah diperbaiki di §2.6 — migration menambah `status` column, model sudah pakai `AssetStatus` cast

> **CATATAN:** Setelah §2.6 diimplementasi, `is_available` boolean menjadi redundant dengan `status` enum. Pertimbangkan untuk deprecate `is_available` dan gunakan `$asset->status === AssetStatus::AVAILABLE` sebagai pengganti.

---

### 2.18 M13: LoanInstallment `status` Tanpa Enum Cast — Plain String, Tidak Type-Safe

**Masalah:** Setiap model status lain (Attendance, Leave, Payroll, Reimbursement, Approval, Overtime) menggunakan backed enum cast. `LoanInstallment.status` masih plain string.  
**Severity:** LOW  
**Depends On:** —  
**Estimasi:** 10 menit

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

### 2.19 M14: `bpjs_configs.name` Missing Unique Constraint

**Masalah:** Tanpa unique constraint, duplikat konfigurasi BPJS bisa dibuat, menyebabkan kalkulasi payroll salah (double-counting).  
**Severity:** LOW  
**Depends On:** —  
**Estimasi:** 2 menit

```php
// database/migrations/2026_05_08_161905_create_bpjs_configs_table.php
// GANTI: $table->string('name');
// MENJADI: $table->string('name')->unique();
```

---

### 2.20 M15: Missing `WfaStatus` Enum untuk `status_wfa` Column

**Masalah:** §4.1 Migration 6 menambah `status_wfa varchar(20) nullable` tapi tidak ada enum. Nilai status akan disimpan sebagai untyped string.  
**Severity:** MEDIUM  
**Depends On:** 4.1 (Migration 6)  
**Estimasi:** 5 menit

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

### 2.21 L5: PerformanceReview Missing Status, Review_Date, Period, SoftDeletes

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

### 2.22 L6: Shift Model Missing `SoftDeletes`

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

### 2.23 L7: CompanySetting `get()` Method — Double-Decode Risk

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

### 2.24 H2: ReimbursementService `linkToPayroll` — No Duplicate Check & No Payroll Validation

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

## FASE 3 — Service Bug Fixes

---

### 3.1 P1 & P2: Overtime Rate Calculation

**Masalah:** Weekday overtime flat 1.5x (seharusnya jam pertama 1.5x, selanjutnya 2x). Holiday overtime: salah 2x/3x (PRD §26.7: jam 1-8=2x, jam ke-9=3x, jam ke-10+=4x).  
**Severity:** HIGH  
**Depends On:** —  
**Estimasi:** 30 menit

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

### 3.2 G1: GeofenceService Null Coordinates

**Masalah:** `deg2rad(null)` = 0.0 → jika branch lat/lng null, perhitungan jarak salah.  
**Severity:** HIGH  
**Depends On:** —  
**Estimasi:** 15 menit

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

### 3.3 H1: PayrollCalculatorService `getTERCategory()` — Bug DIVORCED/WIDOWED (Parallel ke M4)

**Masalah:** §2.8 memperbaiki `TerCategory::resolveFromStatus()`, tapi `PayrollCalculatorService::getTERCategory()` (baris 107-128) punya logic sendiri yang juga salah: hanya `$maritalStatus === MaritalStatus::SINGLE` yang di-handle, DIVORCED dan WIDOWED jatuh ke branch MARRIED → TER category salah → PPh21 salah.  
**Severity:** HIGH  
**Depends On:** —  
**Estimasi:** 5 menit

```php
// app/Services/PayrollCalculatorService.php — ganti method getTERCategory():

public function getTERCategory(Employee $employee): TerCategory
{
    $maritalStatus = $employee->marital_status;

    $dependentsCount = $employee->families
        ->where('relationship', FamilyRelationship::CHILD)
        ->count();
    $dependents = min($dependentsCount, 3);

    // DIVORCED dan WIDOWED diperlakukan sama seperti SINGLE (TK/)
    if (in_array($maritalStatus->value ?? $maritalStatus, ['single', 'divorced', 'widowed'])) {
        return match (true) {
            $dependents <= 1 => TerCategory::A,
            default => TerCategory::B,
        };
    }

    // MARRIED (K/)
    return match (true) {
        $dependents <= 1 => TerCategory::B,
        default => TerCategory::C,
    };
}
```

> **CATATAN:** Juga perbaiki N+1 query dengan eager-load: ganti `$employee->families` ke eager-load relasi di pemanggil method. Atau gunakan `withCount` yang filter CHILD saja (lihat §3.4).

---

### 3.4 H2: `family_details_count` Menghitung Semua Keluarga — TER Category Salah

**Masalah:** `$employee->family_details_count` (dari `withCount('families')`) menghitung **semua** family members termasuk pasangan, bukan hanya `FamilyRelationship::CHILD`. Ini menyebabkan over-counting dependents → TER category terlalu rendah → PPh21 kurang dipotong.  
**Severity:** HIGH  
**Depends On:** —  
**Estimasi:** 5 menit

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

### 3.5 H6 & H7: LeaveService `carryForward()` — 2 Bug

**Masalah 1:** `carryForward()` mengabaikan `carry_forward` tahun sebelumnya dalam perhitungan remaining. `$remaining = $prevBalance->quota - $prevBalance->used` seharusnya `$prevBalance->available()` (yang termasuk carry_forward yang belum expired).  
**Masalah 2:** `carryForward()` menimpa kuota karyawan yang sudah di-customize admin dengan default tipe cuti (`$prevBalance->leaveType->quota`).  
**Severity:** HIGH  
**Depends On:** —  
**Estimasi:** 10 menit

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

### 3.6 H8: `DomainException` di PayrollCalculatorService Return HTTP 500

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

### 3.7 M10: LeaveService `applyLeave()` — No `end_date >= start_date` Validation

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

### 3.8 M8: ReimbursementService `linkToPaylawl()` — Sudah dipindahkan ke §2.24

> **CATATAN:** Bug ini sudah didokumentasikan di §2.24 (Fase 2).

---

### 3.9 H3: PayrollCalculatorService — N+1 Query pada Overtime → Employee → Position

**Masalah:** `calculateOvertimePay()` dipanggil per-overtime via `map()`, dan setiap overtime trigger `$overtime->employee` + `$employee->position` = 2N extra queries. Employee dan position sudah tersedia di `generatePayroll()` scope.  
**Severity:** MEDIUM  
**Depends On:** —  
**Estimasi:** 10 menit

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

### 3.10 M16: PayrollCalculatorService — No Guard untuk Employee tanpa Position

**Masalah:** Null-safe operator `$employee->position?->basic_salary ?? 0` silently defaults to 0, menghasilkan payroll dengan gaji 0 untuk karyawan tanpa position assignment.  
**Severity:** MEDIUM  
**Depends On:** —  
**Estimasi:** 5 menit

```php
// app/Services/PayrollCalculatorService.php — tambah di awal method generatePayroll(), setelah parsing period:

if (! $employee->position) {
    throw new \App\Exceptions\BusinessRuleException(
        "Karyawan {$employee->full_name} belum memiliki posisi/jabatan."
    );
}
```

---

### 3.11 L8: Attendance Penalty Count — Tidak Filter WFA atau Exception

**Masalah:** Count query `where('late_minutes', '>', 0)` menghitung semua late attendance, termasuk WFA dan yang sudah di-approved exception. Seharusnya mengecualikan WFA dan yang punya approved exception.  
**Severity:** LOW  
**Depends On:** 4.1 (Migration 2: add_exception_fields_to_attendances)  
**Estimasi:** 5 menit

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

### 3.12 H4: FaceRecognitionService — No Vector Dimension Validation

**Masalah:** Method `verifyFace()` memvalidasi bahwa setiap value numeric, tapi tidak memvalidasi bahwa incoming vector punya tepat 128 dimensions. Vector dengan dimensi berbeda menyebabkan PostgreSQL error saat operator `<=>` dijalankan.  
**Severity:** MEDIUM  
**Depends On:** —  
**Estimasi:** 5 menit

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

### 4.1 Migrations yang Masih Dibutuhkan

Berdasarkan perbandingan ERD vs database aktual:

| # | Migration | Kolom yang Ditambahkan | Status |
|---|-----------|----------------------|--------|
| 1 | ~~`add_status_and_due_date_to_loan_installments`~~ | ~~`status varchar(20) default 'pending'`, `due_date date`~~ | **SUDAH ADA** — skip |
| 2 | `add_exception_fields_to_attendances` | `exception_type varchar(30) nullable`, `exception_notes text nullable`, `approved_late_by bigint nullable` | Diperlukan |
| 3 | `add_breakdown_to_payrolls` | Kolom-kolom sudah ada di DB (sudah diverifikasi) | **SUDAH ADA** — skip |
| 4 | `add_google_oauth_to_users` | `google_id varchar(255) nullable` | **SUDAH ADA** — skip |
| 5 ~~`add_password_changed_at_to_users`~~ | ~~`password_changed_at timestamp nullable`~~ | ~~SUDAH ADA sebagai `password_changed`~~ — **TAPI FIELD SALAH: `password_changed` adalah boolean, bukan timestamp!** | **Ganti ke migration baru** — lihat #8 |
| 6 | `add_wfa_status_to_attendances` | `status_wfa varchar(20) nullable` | Diperlukan |
| 7 | `add_device_detection_to_devices` | `device_type varchar(20) nullable`, `device_name varchar(255) nullable`, `browser varchar(255) nullable`, `os varchar(255) nullable` | Diperlukan |
| 8 | `add_password_changed_at_to_users` | `password_changed_at timestamp nullable` — **KRITIS untuk keamanan** (password expiration 90 hari, force change, audit forensik) | **Diperlukan** — `password_changed` (boolean) tidak bisa jadi time anchor |
| 9 | ~~Edit migration asli~~ | ~~`payroll_adjustments.amount` integer → decimal(15,2)~~ | **Sudah di §2.4** |
| 10 | ~~Edit migration asli~~ | ~~`overtimes.description` NOT NULL → nullable~~ | **Lihat §4.1d** |
| 11 | ~~Edit migration asli~~ | ~~`overtimes.attendance_id` NOT NULL → nullable~~ | **Lihat §4.1d** — PRD §8.1: lembur diajukan SEBELUM absen pulang |
| 12 | ~~Edit migration asli~~ | ~~`employees.address_detail` NOT NULL → nullable~~ | **Lihat §4.1e** |
| 13 | `add_foreign_key_indexes` | Index pada `payroll_adjustments.payroll_id`, `performance_reviews.employee_id`, `performance_reviews.reviewer_id`, `asset_handovers.asset_id`, `asset_handovers.employee_id` | Diperlukan — §2.16 |
| 14 | `add_status_and_period_to_performance_reviews` | `status varchar(20) default 'draft'`, `review_date date nullable`, `period varchar(20) nullable`, `softDeletes` | Diperlukan — §2.21 |
| 15 | `add_unique_to_bpjs_configs_name` | Unique constraint pada `bpjs_configs.name` | Diperlukan — §2.19 |

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

### 4.1d Edit Migration Asli: overtimes (description nullable, attendance_id nullable)

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

### 4.1e Edit Migration Asli: employees (address_detail nullable)

> **Mengapa?** Alamat seharusnya boleh kosong. Validasi required di level FormRequest, bukan database.

```php
// database/migrations/2026_04_16_192201_create_employees_table.php
// GANTI baris:
$table->text('address_detail');
// MENJADI:
$table->text('address_detail')->nullable();
```

---

### 4.1f Ringkasan: Semua Edit Migration Asli

Setelah semua edit selesai, jalankan:

```bash
php artisan migrate:fresh
```

| # | File Migration | Perubahan |
|---|---------------|-----------|
| 1 | `2026_05_08_161915_create_payroll_adjustments_table.php` | `$table->integer('amount')` → `$table->decimal('amount', 15, 2)` |
| 2 | `2026_04_19_044028_create_overtimes_table.php` | `$table->foreignId('attendance_id')` → `$table->foreignId('attendance_id')->nullable()->constrained('attendances')->nullOnDelete()` |
| 3 | `2026_04_19_044028_create_overtimes_table.php` | `$table->text('description')` → `$table->text('description')->nullable()` |
| 4 | `2026_04_16_192201_create_employees_table.php` | `$table->text('address_detail')` → `$table->text('address_detail')->nullable()` |
| 5 | `2026_04_12_203653_create_companies_table.php` | `$table->text('address_detail')` → `$table->text('address_detail')->nullable()` |
| 6 | `2026_04_13_152045_create_branches_table.php` | `$table->text('address_detail')` → `$table->text('address_detail')->nullable()` |
| 7 | `2026_04_16_192201_create_employees_table.php` | `$table->text('npwp')` → `$table->text('npwp')->nullable()` |
| 8 | `2026_04_16_192201_create_employees_table.php` | `$table->text('bank_account_number')` → `$table->text('bank_account_number')->nullable()` |
| 9 | `2026_04_16_192201_create_employees_table.php` | `$table->string('bank_name', 100)` → `$table->string('bank_name', 100)->nullable()` |
| 10 | `2026_04_17_175332_create_attendances_table.php` | `$table->foreignId('shift_id')` → `$table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete()` |
| 11 | `2026_05_08_161915_create_payroll_adjustments_table.php` | `$table->foreignId('created_by')` → `$table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()` |
| 12 | `2026_04_28_133152_create_knowledge_bases_table.php` | `$table->vector('embedding', dimensions: 1536)` → `$table->vector('embedding', dimensions: 1536)->nullable()` |
| 13 | `2026_05_08_161905_create_bpjs_configs_table.php` | `$table->string('name')` → `$table->string('name')->unique()` |

---

### 4.1g Edit Migration Asli: attendances (shift_id nullable)

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

### 4.1h Edit Migration Asli: payroll_adjustments (created_by nullable)

> **Mengapa?** `created_by` NOT NULL tapi foreign key pakai `nullOnDelete()`. Saat User dihapus, database menolak set NOT NULL column ke NULL = constraint violation crash.

```php
// database/migrations/2026_05_08_161915_create_payroll_adjustments_table.php
// GANTI baris:
$table->foreignId('created_by')->constrained('users')->nullOnDelete();
// MENJADI:
$table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
```

---

### 4.1i Edit Migration Asli: knowledge_bases (embedding nullable)

> **Mengapa?** Model punya `processEmbedding()` yang set `status = 'processing'` — artinya record dibuat SEBELUM embedding di-generate. Kolom NOT NULL mencegah insert tanpa embedding.

```php
// database/migrations/2026_04_28_133152_create_knowledge_bases_table.php
// GANTI baris:
$table->vector('embedding', dimensions: 1536);
// MENJADI:
$table->vector('embedding', dimensions: 1536)->nullable();
```

---

### 4.1j Edit Migration Asli: companies & branches (address_detail nullable)

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

### 4.1k Edit Migration Asli: employees (npwp, bank_account_number, bank_name nullable)

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

### 4.2 Console Commands yang Dibutuhkan

| Command | Schedule | Spesifikasi |
|---------|----------|-------------|
| `attendance:detect-alpha` | `dailyAt('23:59')` | Cari employee yang tidak punya attendance record hari ini → buat record dengan `status=absent` |
| `attendance:detect-chronic-late` | `weeklyOn(Friday, '18:00')` | Cari employee dengan >3 late attendance minggu ini → kirim ChronicLateWarning notification |
| `leave:reset-quota` | `yearlyOn(1, 1, '00:00')` | Untuk setiap employee, create LeaveBalance untuk tahun baru. Panggil `LeaveService::carryForward()` untuk sisa tahun lalu. |
| `payroll:generate {period}` | Manual | Dispatch `GenerateEmployeePayrollJob` untuk setiap active employee |
| `activitylog:clean` | `daily()` | `php artisan activitylog:clean --days=365` (sudah dari Spatie) |

---

### 4.3 Notification Classes yang Dibutuhkan

| # | Class | Via | Trigger |
|---|-------|-----|---------|
| 1 | `LeaveRequestSubmitted` | mail, database | Leave::create |
| 2 | `LeaveApproved` | mail, database | ApprovalService::approve (final) |
| 3 | `LeaveRejected` | mail, database | ApprovalService::reject |
| 4 | `PayrollPublished` | mail, database | Payroll publish |
| 5 | `ApprovalOverdue` | database | attendance:detect-alpha command |
| 6 | `NewDeviceLogin` | mail, database | Login dari device baru |
| 7 | `ChronicLateWarning` | mail, database | attendance:detect-chronic-late command |

Setiap notification harus implement `ShouldQueue` dan punya `toDatabase()` + `toMail()`.

---

### 4.4 Seeders yang Dibutuhkan

| # | Seeder | Data |
|---|---------|------|
| 1 | `RoleAndPermissionSeeder` | 5 roles (super-admin, hr-manager, finance, supervisor, employee) + 50+ permissions |
| 2 | `CompanySeeder` | 1 company (521) + 1 branch (Jakarta Pusat) |
| 3 | `SuperAdminSeeder` | admin@521.com / password123! dengan role super-admin |
| 4 | `CompanySettingSeeder` | Default settings: geofence radius 100m, face threshold 0.85, attendance rules |
| 5 | `PayrollConfigSeeder` | Tax configs (TER A/B/C) + BPJS configs (5 jenis) |
| 6 | `LeaveTypeSeeder` | Tahunan(12 hari), Sakit(unlimited), Menstruasi(1 hari/bulan), Melahirkan(90 hari), Penting(aturan perusahaan), Unpaid |
| 7 | `HolidaySeeder` | Hari libur nasional Indonesia 2026 |
| 8 | `ShiftSeeder` | Office Hour (08:00-17:00), Morning (06:00-14:00), Night (14:00-22:00), Flexible |

---

## Urutan Eksekusi Lengkap

```markdown
HARI 1:
  ☐ 0.1 — RC-5: Queue retry_after (1 menit)
  ☐ 0.7 — C1: ApprovalLevel enum comparison (5 menit)
  ☐ 0.3 — RC-1: Clock-in QueryException catch (15 menit) ← MIGRATION TIDAK PERLU, index sudah ada
  ☐ 2.3 — B3: isLocked() block PAID (5 menit)
  ☐ 2.4 — B4: PayrollAdjustment amount decimal:2 (5 menit)
  ☐ 2.5 — B5: Company fillable (10 menit)
  ☐ 2.8 — M4: TerCategory DIVORCED/WIDOWED (5 menit)
  ☐ 2.9 — M2: Tax/BPJS cache invalidation (15 menit)
  ☐ 2.10 — SEC-6: Approval fillable mass-assignment (5 menit)
  ☐ 2.13 — M1: Holiday date cast (2 menit)
  ☐ 2.14 — M2/M3: Company & Branch address_detail nullable (5 menit)
  ☐ 2.15 — M4: Employee npwp/bank nullable (5 menit)
  ☐ 2.19 — M14: bpjs_configs.name unique (2 menit)
  ☐ 4.1d — Fix overtimes NOT NULL → nullable (5 menit) ← edit migration asli
  ☐ 4.1e — Fix employees.address_detail NOT NULL → nullable (5 menit) ← edit migration asli
  ☐ 4.1f — Add password_changed_at to users (5 menit) ← migration baru + model cast
  ☐ 4.1g — Fix attendances.shift_id nullable (2 menit) ← edit migration asli
  ☐ 4.1h — Fix payroll_adjustments.created_by nullable (2 menit) ← edit migration asli
  ☐ 4.1i — Fix knowledge_bases.embedding nullable (2 menit) ← edit migration asli
  ☐ 4.1j — Fix companies/branches.address_detail nullable (5 menit) ← edit migration asli
  ☐ 4.1k — Fix employees npwp/bank nullable (5 menit) ← edit migration asli
  ☐ 4.1 — Migration 2: add_exception_fields_to_attendances (10 menit)
  ☐ 4.1 — Migration 6: add_wfa_status_to_attendances (5 menit)
  ☐ 4.1 — Migration 7: add_device_detection_to_devices (5 menit)
  ☐ 4.1 — Migration 8: add_password_changed_at_to_users (5 menit)
  ☐ 4.1 — Migration 13: add_foreign_key_indexes (5 menit)
  ☐ 4.1 — Migration 14: add_status_and_period_to_performance_reviews (10 menit)
  ☐ 4.1 — Migration 15: add_unique_to_bpjs_configs_name (2 menit)
  ☐ Run: php artisan migrate:fresh

HARI 2:
  ☐ 0.2 — DL-3: face_similarity_score clock-out (15 menit)
  ☐ 0.4 + 0.8 — RC-2: Payroll race condition + forceDelete fix (30 menit) ← replace softDelete with forceDelete
  ☐ 0.6 — DL-2: Leave quota deduct timing (45 menit) ← includes B2, B8, refund() method
  ☐ 1.5 — SEC-5: Exception HTTP codes (10 menit)

HARI 3:
  ☐ 1.1 — SEC-1: PII hidden fields (15 menit)
  ☐ 1.2 — SEC-2: Zero policies → create 8 policies (2 jam)
  ☐ 1.3 — SEC-3: Sanctum install + config (30 menit)
  ☐ 1.4 — SEC-4: Permission enum + seeders (1 jam)
  ☐ 2.1 — B1: KnowledgeBase status/category migration + vector cast (15 menit)
  ☐ 2.6 — B6: Asset SoftDeletes + missing columns migration (15 menit)
  ☐ 2.11 — H9: Employee vector cast (5 menit)
  ☐ 2.12 — H10: KnowledgeBase vector cast (5 menit)
  ☐ 2.16 — M5: FK indexes migration (5 menit)
  ☐ 2.17 — M12: AssetStatus enum integration (included in B6)
  ☐ 2.18 — M13: LoanInstallmentStatus enum (10 menit)
  ☐ 2.20 — M15: WfaStatus enum (5 menit)

HARI 4-5:
  ☐ 2.2 — B2: isAllApproved() (sudah di 0.6)
  ☐ 2.7 — B8: LeaveBalance deduct minimum (sudah di 0.6)
  ☐ 2.21 — L5: PerformanceReview status/period/SoftDeletes (30 menit)
  ☐ 2.22 — L6: Shift SoftDeletes (5 menit)
  ☐ 2.23 — L7: CompanySetting double-decode fix (5 menit)
  ☐ 2.24 — H2: ReimbursementService linkToPayroll guards (10 menit)
  ☐ 3.1 — P1/P2: Overtime rate calculation (30 menit)
  ☐ 3.2 — G1: GeofenceService null coordinates (15 menit)
  ☐ 3.3 — H1: getTERCategory() DIVORCED/WIDOWED (5 menit)
  ☐ 3.4 — H2: family_details_count counts all (5 menit)
  ☐ 3.5 — H6/H7: carryForward bugs (10 menit)
  ☐ 3.6 — H8: DomainException → BusinessRuleException (2 menit)
  ☐ 3.7 — M10: LeaveService end_date >= start_date (2 menit)
  ☐ 3.9 — H3: N+1 overtime query (10 menit)
  ☐ 3.10 — M16: No guard employee without position (5 menit)
  ☐ 3.12 — H4: FaceRecognition dimension validation (5 menit)

HARI 6-8:
  ☐ Fase 2 — Missing Infrastructure (seeders, commands, notifications)
  ☐ 3.11 — L8: Attendance penalty count filter (depends on Migration 2)

HARI 9+:
  ☐ Fase 4 — PRD Gap Implementation (WFA flow, etc.)
```

---

## PRD vs Kode vs ERD — Cross Reference

### Attendance

| Aspek | PRD/ERD | Kode Aktual | Status |
|-------|---------|-------------|--------|
| Unique `(employee_id, date)` | ERD: unique index | **SUDAH ADA** di DB (`attendances_employee_id_date_unique`) | Fix 0.3 — service-level catch |
| `verification_method` in/out terpisah | PRD §7.3 | Di-overwrite | Fix 0.2 |
| WFA `status_wfa` column | PRD §26.9 | TIDAK ADA | Gap G1 + §2.20 |
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
| `getTERCategory()` DIVORCED/WIDOWED bug | Second instance in PayrollCalculatorService | Same bug as M4, different method | Fix 3.3 |
| DomainException 500 error | Should be 422 | `throw new DomainException(...)` returns 500 | Fix 3.6 |
| No guard for employee without position | Should throw error | Silent 0 salary | Fix 3.10 |
| Loan deduction | PRD §12.3 | Hardcoded 0 | Gap P4 |
| Meal allowance | Diagram | Tidak ada | Gap P5 |

### Overtime

| Aspek | PRD/ERD | DB Aktual | Status |
|-------|---------|-----------|--------|
| `description` NOT NULL | ERD: `text` (implisit nullable) | `text NOT NULL` | Fix 4.1d — edit migration asli |
| `attendance_id` NOT NULL | ERD: nullable, PRD §8.1: submit sebelum absen | `bigint NOT NULL` | Fix 4.1d — edit migration asli |

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

### KnowledgeBase

| Aspek | PRD/ERD | Kode Aktual | Status |
|-------|---------|-------------|--------|
| `status` column | ERD: `knowledge_base_status` | TIDAK ADA | Fix 2.1 |
| `category` column | ERD: `knowledge_base_category` | TIDAK ADA | Fix 2.1 |
| `source_document`, `page_number` | ERD | TIDAK ADA | Fix 2.1 |
| `embedding` NOT NULL | Should be nullable (created before embedding) | DB: `vector(1536) NOT NULL` | Fix 4.1i |
| `embedding` model cast | Should be `vector` | **TIDAK ADA** — serialization risk | Fix 2.12 |
| `ProcessKnowledgeBaseEmbedding` job | PRD §16 | TIDAK ADA | Gap INF-3 |
| RAG chat (Gemini) | PRD §15.6 | TIDAK ADA | Gap |

### Company & Branch

| Aspek | PRD/ERD | Kode Aktual | Status |
|-------|---------|-------------|--------|
| `address_detail` NOT NULL | Should be nullable | DB: `text NOT NULL` | Fix 4.1j |
| Missing address fillable fields | 6 kolom address tidak di fillable | Bypass mass-assignment | Fix 2.5 |

### Asset

| Aspek | PRD/ERD | Kode Aktual | Status |
|-------|---------|-------------|--------|
| Missing columns (company_id, code, category, status) | ERD | TIDAK ADA di DB | Fix 2.6 |
| AssetStatus enum unused | Model pakai boolean `is_available` | Enum orphaned | Fix 2.17 |
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

### Token/Auth/API

| Aspek | PRD/ERD | Kode Aktual | Status |
|-------|---------|-------------|--------|
| Sanctum package | API Contracts §1 | TIDAK ADA — not even installed | Fix 1.3 |
| `routes/api.php` | API Contracts | TIDAK ADA | Fix 1.3 |
| `AuthServiceProvider` | Policy registration | TIDAK ADA | Fix 1.4 |
| Permission enum + seeders | Policy authorization | TIDAK ADA — `$user->can()` always false | Fix 1.4 |
| API Controllers | API Contracts | Orphan AttendanceController, no routes | Gap |
| API Form Requests | API Contracts | 2 files, no policy-based auth | Gap |

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

*Terakhir diupdate: 12 Mei 2026 — v3.2 (30 errata, comprehensive audit)*  
*Versi: 3.0 — Final Audit Release*