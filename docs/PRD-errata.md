# PRD ERRATA — Amandemen Kitab Suci

**Versi:** 1.0  
**Tanggal:** 12 Mei 2026  
**PRD Referensi:** PRD v2.0 (2026-05-08)  
**Status:** LOCKED — Dokumen ini mengesampingkan PRD.md jika ada konflik

---

## Cara Pakai

Jika ada konflik antara `PRD.md` dan dokumen ini, **dokumen ini yang benar**. DeepSeek dan semua AI agent harus membaca kedua dokumen sebelum menulis kode.

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

---

*Terakhir diupdate: 12 Mei 2026 — v1.0*