# PRODUCT REQUIREMENTS DOCUMENT (PRD)
## HRConnect — HRIS Enterprise System
**PT 521 Teknologi Indonesia**
**Laravel 13 + Livewire 4 + Material Design 3 + PostgreSQL**

**Versi:** 5.0 — Penambahan modul Project & Task Management, HR Checklist, Announcements
**Tanggal Update:** 2026-07-02
**Status:** Frontend Development — Backend + PRD update

---

## DAFTAR ISI

1. [Overview](#1-overview)
2. [Nilai Jual Utama Skripsi (Core Features)](#2-nilai-jual-utama-skripsi-core-features)
3. [Roles & Permissions](#3-roles--permissions)
4. [Authentication & Session](#4-authentication--session)
5. [Navigation Structure](#5-navigation-structure)
6. [Module: Presensi (Attendance)](#6-module-presensi-attendance)
7. [Module: Leave Management](#7-module-leave-management)
8. [Module: Overtime](#8-module-overtime)
9. [Module: Reimbursement](#9-module-reimbursement)
10. [Module: Loan/Kasbon](#10-module-loankasbon)
11. [Module: Payroll](#11-module-payroll)
12. [Module: Approval Workflow](#12-module-approval-workflow)
13. [Module: KnowledgeBase AI (RAG)](#13-module-knowledgebase-ai-rag)
14. [Module: Master Data](#14-module-master-data)
15. [Notifications](#15-notifications)
16. [Module: Project & Task Management](#16-module-project--task-management)
17. [Module: HR Checklist](#17-module-hr-checklist)
18. [Module: Announcements](#18-module-announcements)
19. [Queue & Job Architecture](#19-queue--job-architecture)
20. [Security & Data Protection](#20-security--data-protection)
21. [Database Schema](#21-database-schema)
22. [Migration Plan](#22-migration-plan)
23. [Model Plan](#23-model-plan)
24. [Service Classes Plan](#24-service-classes-plan)
25. [Seeder Plan](#25-seeder-plan)
26. [PWA Requirements](#26-pwa-requirements)
27. [UI/UX Guidelines](#27-uiux-guidelines)
28. [12-Week Execution Plan](#28-12-week-execution-plan)
29. [Edge Cases & Real-World Scenarios](#29-edge-cases--real-world-scenarios)
30. [Features Deferred to V2](#30-features-deferred-to-v2)
31. [Glossary](#31-glossary)
32. [Validation Rules](#32-validation-rules)
33. [Locale & Format](#33-locale--format)
34. [Data Retention & PDP Compliance](#34-data-retention--pdp-compliance)

---

## 1. OVERVIEW

HRConnect adalah sistem HRIS (Human Resource Information System) berskala Enterprise untuk PT 521 Teknologi Indonesia yang mengelola:
- **Presensi berbasis GPS Geofencing + Face Recognition** (nilai jual utama skripsi)
- Pengajuan Cuti dan Lembur dengan Multi-Layer Approval
- Penggajian Otomatis (PPh 21 TER + BPJS)
- **KnowledgeBase AI (RAG)** untuk HRD (nilai jual utama skripsi)
- **Project & Task Management** dengan alokasi resource + timesheet
- **HR Checklist** untuk onboarding/offboarding terstruktur
- **Announcements** untuk pengumuman internal
- PWA Mobile-First untuk Employee Self-Service (ESS)

**Fitur yang DITUNDA ke V2:** Loan/Kasbon, Asset Management, Performance Review, WhatsApp Notifications.

**Tech Stack:**
- Backend: Laravel 13, PHP 8.5
- Frontend: Livewire 4, Tailwind CSS v4, Alpine.js, Material Symbols (Google Icons), Rubik + Inter (Google Fonts)
- Database: PostgreSQL (dengan pgvector, pg_trgm, pgcrypto)
- Queue: Database (default + payroll_high)
- Auth: Laravel Fortify + Spatie Permission + 2FA (TOTP)
- PWA: Service Worker + face-api.js (IndexedDB ditunda)

**Bahasa:** 100% Bahasa Indonesia

---

## 2. NILAI JUAL UTAMA SKRIPSI (CORE FEATURES)

Tiga fitur ini adalah **pembeda utama** skripsi ini dari HRIS biasa dan **TIDAK BOLEH di-defer**:

### 2.1 Face Recognition (face-api.js)
- Client-side face detection via **face-api.js** (FaceNet, 128D embedding)
- **Distance threshold** disimpan di `company_settings.face_distance_threshold` (default `0.15`) `[K3]`
- Konversi: `similarity_percentage = (1 - distance) × 100`. Threshold distance `0.15` ≡ similarity ≥ 85%.
- Implementasi `FaceRecognitionService` menggunakan **distance** (`<=>` cosine distance pgvector). UI menampilkan **similarity %** ke user.
- Enrollment dilakukan HRD/karyawan saat orientasi hari pertama
- Embedding 128D dikirim dari browser → disimpan di `employees.face_embedding` (vector(128))
- Saat clock-in: kamera HP mencocokkan wajah live dengan embedding dari database
- **Tech:** face-api.js (client) → PostgreSQL pgvector (server)

### 2.2 GPS Geofencing (Haversine Formula)
- Validasi GPS via **Haversine formula** (jarak titik ke titik)
- Radius tolerance: konfigurasi per branch di `branches.radius` (default 100m)
- Jika jarak > radius → **clock-in ditolak**
- WFA mode: GPS dilewati, tapi wajib isi catatan ≥20 karakter dan butuh approval setelahnya
- **Tech:** Browser Geolocation API → Haversine calculation di `AttendanceService`

### 2.3 KnowledgeBase AI (RAG - Retrieval Augmented Generation)
- HRD upload dokumen PDF (max 10MB) → chunking (~60 token, overlap 10 token)
- Embedding via Gemini `text-embedding-004` (768 dimensi)
- Vector disimpan di PostgreSQL `pgvector`
- Employee/HRD bisa tanya → AI jawab dengan referensi sumber
- LLM: **Gemini 2.5 Flash (API)** — jangan self-host (hindari OOM) `[K1]`
- **Tech:** PDF upload → chunking → embedding → pgvector → Gemini API → response + source

---

## 3. ROLES & PERMISSIONS

### 5 Role Utama

| Role | Slug | Deskripsi |
|------|------|-----------|
| Super Admin | `super-admin` | Pemilik akses penuh ke sistem, master data, konfigurasi, user management |
| HR Manager | `hr-manager` | Operasional SDM, approval final cuti/lembur, direktori karyawan, monitoring |
| Finance | `finance` | Payroll, laporan keuangan |
| Manager | `manager` | Approval Level 1, monitoring tim |
| Employee | `employee` | ESS - clock-in/out, pengajuan, slip gaji, profil |

### Permission Matrix

| Permission | Super Admin | HR Manager | Finance | Manager | Employee |
|-----------|:---:|:---:|:---:|:---:|:---:|
| `view_dashboard` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `view_companies` | ✅ | ❌ | ❌ | ❌ | ❌ |
| `manage_companies` | ✅ | ❌ | ❌ | ❌ | ❌ |
| `view_branches` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `manage_branches` | ✅ | ❌ | ❌ | ❌ | ❌ |
| `view_departments` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `manage_departments` | ✅ | ❌ | ❌ | ❌ | ❌ |
| `view_positions` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `manage_positions` | ✅ | ❌ | ❌ | ❌ | ❌ |
| `view_employees` | ✅ | ✅ | ✅ | ✅ (tim) | ❌ |
| `manage_employees` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `view_attendances` | ✅ | ✅ (semua) | ❌ | ✅ (tim) | ✅ (diri) |
| `manage_attendances` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `view_leaves` | ✅ | ✅ (semua) | ❌ | ✅ (tim) | ✅ (diri) |
| `approve_leaves_l1` | ✅ | ❌ | ❌ | ✅ | ❌ |
| `approve_leaves_l2` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `view_overtimes` | ✅ | ✅ (semua) | ❌ | ✅ (tim) | ✅ (diri) |
| `approve_overtimes_l1` | ✅ | ❌ | ❌ | ✅ | ❌ |
| `approve_overtimes_l2` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `view_reimbursements` | ✅ | ✅ (semua) | ✅ | ✅ (tim) | ✅ (diri) | `[M1]` |
| `manage_reimbursements` | ✅ | ❌ | ❌ | ❌ | ❌ |
| `approve_reimbursements_l1` | ✅ | ❌ | ❌ | ✅ | ❌ |
| `approve_reimbursements_l2` | ✅ | ❌ | ✅ | ❌ | ❌ |
| `approve_wfa` | ✅ | ❌ | ❌ | ✅ | ❌ | `[M1]` |
| `view_wfa_pending` | ✅ | ✅ | ❌ | ✅ (tim) | ❌ |
| `view_loans` | ✅ | ✅ | ✅ | ❌ | ✅ (diri) |
| `manage_loans` | ✅ | ❌ | ✅ | ❌ | ❌ |
| `view_assets` | ✅ | ✅ | ❌ | ❌ | ✅ (diri) |
| `manage_assets` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `view_payslip` | ✅ | ❌ | ✅ | ❌ | ✅ (diri) |
| `download_payslip` | ✅ | ❌ | ✅ | ❌ | ✅ (diri) |
| `process_payroll` | ✅ | ❌ | ✅ | ❌ | ❌ |
| `view_payrolls` | ✅ | ❌ | ✅ | ❌ | ✅ (diri) |
| `manage_tax_configs` | ✅ | ❌ | ✅ | ❌ | ❌ |
| `manage_bpjs_configs` | ✅ | ❌ | ✅ | ❌ | ❌ |
| `view_activity_logs` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `view_audit_logs` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `manage_settings` | ✅ | ❌ | ❌ | ❌ | ❌ |
| `manage_company_settings` | ✅ | ❌ | ❌ | ❌ | ❌ |
| `manage_roles` | ✅ | ❌ | ❌ | ❌ | ❌ |
| `manage_holidays` | ✅ | ❌ | ❌ | ❌ | ❌ |
| `manage_shifts` | ✅ | ❌ | ❌ | ❌ | ❌ |
| `manage_knowledgebase` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `view_knowledgebase` | ✅ | ✅ | ❌ | ❌ | ❌ |

**Catatan:** Manager otomatis memiliki permission Employee (multi-role).

---

## 4. AUTHENTICATION & SESSION

### Login Methods
- **Email + Password** (default)
- **2FA (TOTP)** — Opsional, bisa diaktifkan di settings

### 4.4 2FA Recovery `[S12]`
- Saat enable 2FA: generate **8 recovery codes** (8 chars alphanumeric, hashed di DB).
- User download recovery codes sebagai TXT (sekali tampil — tidak bisa lihat lagi setelah close).
- **Lost device:** input recovery code di `/two-factor-challenge` (1 kali pakai per code).
- **Habis recovery codes:** kontak HRD via email/WA → super-admin reset 2FA manual via panel.
- **Audit:** log event `2fa.recovery_used`, `2fa.reset_by_admin` (lihat §17.3).
- Implementasi: built-in Fortify 2FA + custom view di `resources/views/auth/two-factor-challenge.blade.php`.

### Password Policy
- Minimal **8 karakter**
- Wajib kombinasi **huruf besar + huruf kecil + angka**
- Simbol disarankan (Password Strength Indicator di frontend)
- **Password expiry 90 hari** (Security Config §1.5) `[CAT-005]`
- **Force Change Password** — Saat pertama login, kolom `password_changed = false` → wajib ganti password
- **`password_changed_at`** — Timestamp kolom untuk tracking kapan password terakhir diubah `[CAT-001]`

### Session
- **Timeout:** 120 menit (2 jam) idle
- **Remember Me:** ✅ Aktif
- **Login Perangkat Baru** → Notifikasi email ke karyawan + In-App ke HRD

### E-Payslip Security
- Download E-Payslip → wajib **re-enter password login** (Password Confirmation)
- PIN (6 digit) tersimpan di `employees.pin` — khusus untuk absensi (fallback saat face gagal)
- **Separation of Concerns:** password (users) → login + payslip, pin (employees) → absensi shortcut
- Download **unlimited** (tidak ada limit per bulan)
- PDF di-generate **satu kali** saat status `published` → disimpan di `storage/app/payslips/`
- Download = file streaming (bukan generate ulang), beban server = 0%

---

## 5. NAVIGATION STRUCTURE

### Desktop (Sidebar) — Super Admin
1. Dashboard
2. Perusahaan & Cabang
3. Departemen & Jabatan
4. Manajemen Pengguna (Role & Akun)
5. Konfigurasi Sistem
6. Log Aktivitas

### Desktop (Sidebar) — HR Manager
1. Dashboard
2. Direktori Karyawan
3. Rekap Absensi & Shift
4. Persetujuan (Cuti, Lembur, dll)
5. KnowledgeBase AI

### Desktop (Sidebar) — Finance
1. Dashboard
2. Generate Payroll
3. Laporan Keuangan

### Desktop (Sidebar) — Manager
1. Dashboard Tim (My Team)
2. Persetujuan Level 1 (Cuti, Lembur Bawahan)

### Mobile PWA (Bottom Navigation) — Employee
1. Beranda (Clock-In/Out utama)
2. Riwayat Absensi
3. Inbox (Pengajuan & Notifikasi)
4. Profil (Slip Gaji & Data Pribadi)

**Implementasi:** Gunakan `@can('permission')` di Blade, BUKAN `@role('role')`.

---

## 6. MODULE: PRESENSI (ATTENDANCE)

### 6.1 Clock-In/Out
- **WFO (Work From Office):**
  1. Validasi GPS via **Haversine** (jarak titik ke titik)
  2. Radius tolerance: konfigurasi per branch di `branches.radius` (default 100m)
  3. Jika jarak > radius → **tolak**
  4. Face Recognition via **face-api.js** (client-side, 128D FaceNet embedding)
  5. Distance score < threshold (`company_settings.face_distance_threshold`, default `0.15` ≡ similarity ≥ 85%) → **terima** `[K3]`
  6. Foto selfie + GPS koordinat disimpan
  7. Setelah clock-in → sistem menampilkan jarak karyawan dari kantor (meter) dan status "Dalam Radius" / "Di Luar Radius"

- **WFA (Work From Anywhere):**
  1. Toggle WFA → validasi GPS **dilewati**
  2. Tetap wajib Face ID
  3. **Wajib isi catatan pekerjaan** minimal 20 karakter
  4. `is_wfa = true` di database, GPS koordinat tetap disimpan sebagai catatan
  5. **Approval SETELAH clock-in** (bukan sebelum) — karyawan absen dulu, baru manager review
  6. Jika WFA di-reject → status absensi hari itu bisa diubah menjadi `absent`
  7. Setelah clock-in → sistem menampilkan label "Anda sedang WFA — GPS tidak divalidasi 📍"

#### 6.1.1 WFA Post-Approval Flow `[M2]`

```
1. Karyawan toggle WFA → clock-in dengan note ≥ 20 chars (config: company_settings.wfa_note_min_chars).
2. Attendance tersimpan dengan is_wfa=true, status_wfa=PENDING (WfaStatus enum).
3. Notifikasi In-App ke direct supervisor (employees.parent_id),
   atau ke HR Manager jika parent_id=NULL.
4. Approver review via halaman "WFA Approvals" → approve / reject.
5. Approve  → status_wfa=APPROVED, attendance.status tetap on_time/late sesuai shift.
6. Reject   → status_wfa=REJECTED, attendance.status di-set 'absent', notif ke karyawan.
7. Timeout  → jika tidak di-approve dalam wfa_auto_approve_days hari kerja
              (default 3, configurable di company_settings) → auto-approved oleh sistem.
```

**Permission terkait (lihat §3):** `approve_wfa` (Manager + Super Admin), `view_wfa_pending` (HR Manager monitoring).

**Notifikasi (lihat §15.2):** event `wfa.submitted`, `wfa.approved`, `wfa.rejected`, `wfa.auto_approved`.

- **Double Clock Prevention:** Max 1x Clock-In + 1x Clock-Out per hari
- **Grace Period:** `shifts.late_tolerance_minutes` (default 0)
- **Semua karyawan wajib punya shift** (ada "Flexible/Office Hour" sebagai default)

**Verifikasi Clock-In/Out:** `[ERR-004]`
- Kolom verifikasi terpisah untuk clock-in dan clock-out: `verification_method`, `clock_out_verification_method`, `face_similarity_score`, `clock_out_face_similarity_score`
- **`VerificationMethod` enum** menggantikan magic strings: `face`, `pin`, `gps`, `manual` `[CAT-015]`

### 6.2 Status Kehadiran
| Status | Kondisi |
|--------|---------|
| `on_time` | Clock-In <= (shift.start_time + tolerance) |
| `late` | Clock-In > (shift.start_time + tolerance) |
| `early` | Clock-Out < shift.end_time |
| `holiday` | Tanggal ada di tabel `holidays` |
| `permission` | Cuti/izin yang sudah di-approve |
| `absent` | Tidak ada record absensi (di-set Cron Job) |
| `missed_clock_in` | Ada clock-out tapi tidak ada clock-in `[CAT-009]` |
| `missed_clock_out` | Ada clock-in tapi tidak ada clock-out `[CAT-009]` |

### 6.3 Alpha Detection (Cron Job)
- **Command:** `attendance:detect-alpha`
- **Jadwal:** Setiap hari pukul 23:59
- **Logic:** Cari karyawan ACTIVE tanpa record attendance + tanpa cuti approved + bukan holiday → buat record `status = absent`

### 6.4 Device Management
- Device UUID di tabel `devices`
- Device baru → perlu verifikasi HRD (`is_verified = false`)

### 6.5 Chronic Late Warning System (V1)
- **Command:** `attendance:detect-chronic-late`
- **Jadwal:** Setiap Jumat pukul 18:00
- **Logic:** Cari karyawan dengan status `late` ≥ 3 kali dalam 1 bulan terakhir
- **Action:** Kirim notifikasi In-App + Email ke Manager dan HRD
- Notifikasi berisi: nama karyawan, jumlah keterlambatan, periode

---

## 7. MODULE: LEAVE MANAGEMENT

### 7.1 Leave Types (Master Data)
Default seed:
- Cuti Tahunan (12 hari, is_paid)
- Sakit (unlimited, is_paid, wajib upload bukti) — **TIDAK potong quota tahunan**
- Menstruasi (2 hari, is_paid)
- Melahirkan (90 hari, is_paid)
- Cuti Penting/Menikah (3 hari, is_paid)
- Unpaid Leave (is_paid = false, tidak potong quota)

### 7.2 Pengajuan Cuti
- Pilih `leave_type`, `start_date`, `end_date`, `day_type` (full_day/morning/afternoon)
- **Sistem otomatis hitung `total_days`**:
  - Exclude Sabtu, Minggu, dan holidays
  - morning/afternoon = 0.5 hari, full_day = 1.0 hari
- **Validasi:**
  - Sisa kuota cuti tidak boleh minus
  - Quota **VALIDATED** pada saat submit, tetapi **DEDUCTED** hanya setelah full approval (L1 + L2) `[ERR-002]`
  - Tidak boleh overlapping dengan cuti `approved` atau `pending`
  - Cuti Sakit → wajib upload bukti
  - Cuti mundur (retroaktif) → maksimal H+3

### 7.3 Leave Quota & Balance
- Tabel `leave_balances`: employee_id, leave_type_id, year, quota, used, carry_forward
- **Tahun pertama: Pro-rated**
  - Join Juli → (6/12) × 12 = 6 hari
  - Karyawan bisa langsung pakai cuti tanpa menunggu Januari
- **Reset:** Cron Job setiap 1 Januari 00:00 → full 12 hari
- **Carry-Forward:** Sisa cuti bisa dibawa **maksimal 3 hari**, hangus **31 Maret** tahun berikutnya

### 7.4 Approval Workflow
- **Level 1:** Manager (`parent_id`) → validasi operasional
- **Level 2:** HR Manager → final approval
- Jika `parent_id = NULL` → **skip Level 1**, langsung ke HR Manager (Level 2)
- Jika approved → otomatis kurangi `leave_balances.used` (`[ERR-002]` — quota dideduct hanya setelah full approval, bukan saat submit)

---

## 8. MODULE: OVERTIME

### 8.1 Pengajuan Lembur
- **WAJIB submit request SEBELUM lembur dilakukan** (bisa pagi hari atau H-1)
- Data masuk ke `overtimes` dengan `attendance_id = NULL` dan status `pending`
- Isi: `start_time`, `end_time`, `description`
- **Validasi:** Max 4 jam/hari / 18 jam/minggu (UU Cipta Kerja)

### 8.2 Observer Saat Clock-Out
- Saat karyawan Clock-Out melewati jam normal, Observer pada model `Attendance` mendeteksi:
  - "Apakah karyawan ini punya surat lembur yang di-ACC hari ini?"
- Jika ada → hitung selisih jam pulang aktual dengan jam jadwal → update `attendance_id` dan jam lembur

### 8.3 Kalkulasi Upah Lembur `[ERR-001]`
- **Upah per jam** = (Gaji Pokok + Tunjangan Tetap) / **173**
- **Rate hari kerja (weekday) — TIERED:** `[ERR-001]`
  - Jam pertama lembur: 1.5x upah/jam
  - Jam kedua dan seterusnya: 2x upah/jam
- **Rate hari libur / holiday — TIERED:** `[ERR-001]`
  - 8 jam pertama: 2x upah/jam
  - Jam ke-9 s/d ke-10: 3x upah/jam
  - Jam ke-11+: 4x upah/jam
- **Catatan:** Weekend = holiday rate (multiplier sama, detection berbeda) `[ERR-007]`

### 8.4 Kerja di Hari Libur Nasional (Tanggal Merah)
- Karyawan yang bekerja di hari libur nasional **mendapat bayaran lembur** (bukan rate weekday biasa)
- Rate: 2x upah/jam (8 jam pertama), 3x upah/jam (jam ke-9+)
- Karyawan monthly salary sudah dapat gaji di hari libur, lembur = **tambahan** dari gaji bulanan
- **Catatan:** Weekend menggunakan rate yang sama dengan holiday (multiplier identik), hanya detection berbeda `[ERR-007]`

### 8.5 Approval Workflow
- **Level 1:** Manager (`parent_id`) → validasi operasional
- **Level 2:** HR Manager → final approval
- Jika `parent_id = NULL` → skip ke HR Manager
- **Approved otomatis masuk payroll** (tidak perlu manual pilih)

---

## 9. MODULE: REIMBURSEMENT (V1)

### 9.1 Kategori Reimbursement
- Kategori: Transport, Makan, Akomodasi, Medis, Lainnya
- Dikelola oleh HRD via `reimbursement_categories`
- Upload: JPG, PNG, PDF, **max 2 MB**

### 9.2 Approval Workflow
- Approval: Manager (L1) → Finance (L2)
- Tidak ada ceiling/max amount
- Setelah approved → masuk ke payroll bulan berikutnya

### 9.3 Integrasi Payroll
- Reimbursement approved → masuk ke `gross_salary` sebagai `reimbursement_paid`
- **Reimbursement harus difilter berdasarkan `expense_date` dalam periode payroll** (bukan tanggal approval) `[CAT-002]`
- Rumus payroll: `GROSS = basic_salary + allowance_jabatan + ... + reimbursement_paid`

### 9.4 Status Transitions `[M3]`

```
PENDING ──(Manager L1 approve)──▶ APPROVED_L1 ──(Finance L2 approve)──▶ APPROVED ──(payroll publish)──▶ PAID
   │                                    │                                    │
   │                                    │                                    └─(reject in L2)──▶ REJECTED (terminal)
   │                                    └─(reject in L1)──▶ REJECTED (terminal)
   └─(reject di L1 atau L2)──▶ REJECTED (terminal — buat pengajuan baru)
```

**Catatan transisi:**
- `PAID` adalah terminal state — tidak ada perubahan setelah payroll bersangkutan publish.
- `REJECTED` permanen — karyawan harus submit pengajuan baru jika ingin koreksi.
- Withdraw oleh karyawan: hanya bisa di status `PENDING`.
- Notifikasi per transisi: lihat §15.2.

---

## 10. MODULE: LOAN/KASBON (V2)

**DITUNDA ke V2.** Tabel tetap dibuat sebagai persiapan.

### Rencana V2
- Max 1 active loan per karyawan
- Plafon: min(basic_salary × 30%, 5.000.000)
- Bunga: 0% (flat)
- Auto-generate `loan_installments` saat approved
- Auto-deduction via payroll
- Loan dihapuskan jika karyawan meninggal

---

## 11. MODULE: PAYROLL

### 11.1 Payroll Period & Cut-off
- **Frekuensi:** 1x per bulan
- **Cut-off:** `company_settings.payroll_cutoff_date` (default: 25)
- **Karyawan join setelah cut-off** → masuk payroll bulan berikutnya

### 11.2 Rumus Kalkulasi
```
GROSS = basic_salary + allowance_jabatan + (tunjangan_makan × hari_hadir) + overtime_pay + reimbursement_paid + income_thr + income_bonus
DEDUCTIONS = pph21 + bpjs_health + bpjs_employment + loan_deduction + attendance_penalty
NET = GROSS - DEDUCTIONS
```

### 11.3 Pro-Rated Salary (Join/Resign di Tengah Bulan)
- **Rumus:** (Hari Kerja Aktual / Hari Kerja Efektif Sebulan) × Gaji Pokok
- **Hari Kerja Efektif** = Senin-Jumat minus weekend DAN holidays dari tabel `holidays`
- **Karyawan join di tengah bulan:** actual_start = join_date
- **Karyawan resign di tengah bulan:** actual_end = resign_date
- **Karyawan join + resign di bulan yang sama:** actual_start = join_date, actual_end = resign_date
- **Division by zero protection:** jika totalWorkingDays = 0 → return 0.0
- **Optimasi memori:** holidays di-fetch sekali sebagai flat array (bukan Collection object)
- Implementasi: `PayrollCalculatorService::calculateProratedSalary()`

#### 11.3.1 Unpaid Leave Impact `[M8]`
- Karyawan dengan unpaid leave bulan berjalan: hari unpaid_leave **dikurangi** dari hari kerja efektif.
  - Contoh: 22 hari kerja efektif − 5 hari unpaid_leave = 17 hari → ratio 17/22.
- Status absensi `permission` (cuti **paid** approved) tetap dihitung sebagai hari kerja.
- Status absensi `permission` dengan `leave_type.is_paid = false` (unpaid leave) → **dipotong** dari hari kerja efektif.
- Implementasi: `countWorkingDays($start, $end)` mengurangi hari yang ada `Leave::approved()` dengan `leave_type.is_paid = false`.

### 11.4 PPh 21 TER
- **Dihitung PER BULAN** (bukan tahunan dibagi 12)
- **Konfigurasi:** Tabel `tax_configs`
- **PTKP:** Dari `marital_status` + jumlah anak di `family_details`
- **Kategori TER:**
  - A: TK/0, TK/1
  - B: TK/2, TK/3, K/0, K/1
  - C: K/2, K/3
- **Catatan:** Nilai PTKP yang saat ini hardcoded (Appendix D) harus dipindahkan ke `CompanySetting` agar bisa dikonfigurasi tanpa code change `[CAT-014]`

### 11.5 BPJS
| Jenis | Employer | Employee | Ceiling |
|-------|----------|----------|---------|
| Kesehatan | 4% | 1% | Configurable (default Rp 12jt) |
| JHT | 3.7% | 2% | **TIDAK ADA** |
| JP | 2% | 1% | Configurable (default Rp 10jt) |
| JKK | 0.24% | 0% | TIDAK ADA |
| JKM | 0.30% | 0% | TIDAK ADA |

- **BPJS ceiling TIDAK di-hardcode** — disimpan di `bpjs_configs` table
- **Karyawan resign/meninggal:** BPJS tetap aktif sampai bulan terakhir

### 11.6 Denda Kehadiran
- **Keterlambatan:** Flat per kejadian → `company_settings.attendance_penalty_per_day`
- **Alpha:** 1 hari = potong 1 hari gaji (gross_monthly / countWorkingDays()) `[CAT-016]` — jangan hardcode 22 hari kerja

### 11.7 Payroll Lock
- Status `published` → **LOCKED PERMANEN**
- Koreksi → buat record baru di `payroll_adjustments` (amount positif/negatif, reason, applied_to_period) untuk bulan berikutnya

### 11.8 E-Payslip PDF
- 2 kolom: Pendapatan (kiri) vs Potongan (kanan)
- Take Home Pay ukuran besar di bawah
- **Generate sekali saat publish** → simpan di `storage/app/payslips/{period}/{employee_id}.pdf`
- Download unlimited (file streaming, bukan generate ulang)
- THR/Bonus ditampilkan sebagai item terpisah di payslip

### 11.9 Async Queue
- Job: `#[Queue('payroll_high')]`, `#[Tries(3)]`, `#[Timeout(120)]`, `#[Backoff([10, 30, 60])]`
- **`retry_after` harus lebih besar dari job `timeout`** — jika tidak, job bisa di-retry sebelum selesai `[ERR-006]`
- `DB::transaction()` untuk integritas
- Memory-safe: holidays di-fetch sekali per employee, gunakan `in_array()` bukan `collect()->contains()`

### 11.10 Komponen yang TIDAK Pro-Rated
| Komponen | Pro-rated? | Catatan |
|----------|:---:|---------|
| Gaji Pokok | ✅ Ya | Sesuai hari kerja aktif |
| Tunjangan Jabatan | ✅ Ya | Sama dengan gaji pokok |
| Tunjangan Makan | ✅ Ya | Berdasarkan hari hadir aktual |
| BPJS Kesehatan | ❌ Tidak | Tetap aktif dari bulan pertama |
| BPJS JHT/JP | ❌ Tidak | Tetap aktif dari bulan pertama |
| PPh21 TER | ❌ Tidak | Dihitung dari bruto aktual |
| THR/Bonus | ❌ Tidak | Pro-rated terpisah (bulan kerja / 12) |

### 11.11 THR/Bonus Auto-Calculation (V1)
- **Service method:** `PayrollCalculatorService::calculateThrProrated(Employee, float $monthlySalary, int $monthsWorked): float`
- **Rumus THR pro-rated:** `(monthsWorked / 12) × monthlySalary`
- **Syarat dapat THR:** Minimal 1 bulan kerja
- **Bonus:** Input manual via `payroll_items` dengan type `allowance`, nama `income_bonus`
- THR/Bonus ditampilkan sebagai item terpisah di E-Payslip
- Tampil di payslip: `income_thr` dan `income_bonus` sebagai komponen pendapatan

### 11.12 Salary Type Variants `[M5]`

| Type | Perhitungan GROSS | Pro-Rating | THR | Tunjangan Tetap |
|------|-------------------|-----------|-----|-----------------|
| `monthly` | `basic_salary` flat per bulan | Saat join/resign mid-month | (months/12) × salary | Ya |
| `daily` | `daily_rate × hari_hadir_aktual` | N/A (sudah by attendance) | (months/12) × avg_monthly_total | Tidak (kecuali eksplisit) |
| `hourly` | `hourly_rate × total_jam_kerja` (sum dari `attendance.work_hours`) | N/A (sudah by hour) | (months/12) × avg_monthly_total | Tidak |

**Catatan:**
- PPh 21: semua tipe pakai TER bulanan dari akumulasi penghasilan kotor periode tsb.
- BPJS: berdasarkan upah bulanan yang dilaporkan (untuk daily/hourly = total bulan tsb).
- Lembur: standar tetap berlaku (tier 1.5x/2x weekday, 2x/3x/4x holiday) — pakai upah/jam dari rumus §8.3.
- `SalaryType` enum (`monthly`/`daily`/`hourly`) sudah ada di `app/Enums/SalaryType.php`.

### 11.13 Special Employment Types `[M6]`

| Type | Gaji | THR | BPJS Kesehatan | BPJS Ketenagakerjaan | PPh 21 | Catatan |
|------|------|-----|----------------|---------------------|--------|---------|
| `permanent` (PKWTT) | Penuh | Penuh / Pro-rated | Wajib | Wajib (5 jenis) | TER bulanan | Standar |
| `contract` (PKWT) | Penuh | Pro-rated jika ≥3 bulan | Wajib | Wajib | TER bulanan | Akhir kontrak: dapat uang kompensasi |
| `probation` | Penuh (bukan 80%) | Pro-rated | Wajib | Wajib | TER bulanan | Max 3 bulan, tidak boleh diperpanjang |
| `intern` | Uang saku | ❌ Tidak (jika <12 bln) | Opsional | Tidak wajib | PPh 21 progresif (bukan TER) | Bukan karyawan tetap |

**Aturan tambahan:**
- **probation**: setelah 3 bulan, sistem reminder ke HRD untuk auto-promote ke `permanent` atau end. Tidak boleh diperpanjang (UU Cipta Kerja).
- **intern**: PPh 21 progresif dipakai karena penghasilan biasanya di bawah PTKP, tidak butuh TER bulanan akumulatif.
- **contract**: lihat §11.13.1 di bawah untuk uang kompensasi.

### 11.14 PPh 21 Compliance & Annual Reconciliation `[S13]`

| Aktivitas | Tanggal | Penanggung Jawab |
|-----------|---------|------------------|
| Pemotongan PPh 21 bulanan (TER) | Saat payroll publish | Sistem (auto) |
| Penyetoran ke kas negara | Tanggal 10 bulan berikutnya | Finance (manual via DJP Online) |
| Pelaporan SPT Masa | Tanggal 20 bulan berikutnya | Finance |
| Rekonsiliasi tahunan (1721-A1) | Januari tahun berikutnya | Finance |
| Selisih TER vs Annual | → Koreksi via `PayrollAdjustment` di periode berikutnya | Finance |

**Form 1721-A1 Bulanan (Bukti Potong):** PDF auto-generated saat payroll publish, distribusi via download oleh karyawan (lihat §15.5).

**DITUNDA V2:** Integrasi DJP API untuk auto-submit SPT Masa.

---

## 12. MODULE: APPROVAL WORKFLOW

### 12.1 Matrix
| Pengajuan | Level 1 | Level 2 |
|-----------|---------|---------|
| Cuti | Manager (`parent_id`) | HR Manager |
| Lembur | Manager (`parent_id`) | HR Manager |
| Reimbursement | Manager (`parent_id`) | Finance `[ERR-003]` |
| WFA | Manager (`parent_id`) | (skip — single-level) `[M2]` |

### 12.2 Rules
- Semua pengajuan: 2 level (tidak ada auto-approve)
- Jika `parent_id = NULL` → skip Level 1, langsung ke Level 2 (HR Manager)
- Withdraw: Bisa jika status `pending`
- Timeout >24 jam → reminder In-App + Email
- Delegation: Tidak ada (MVP)
- Rejection: `rejected` permanen, buat pengajuan baru
- Rejection reason: gunakan kolom `notes` yang sudah ada (tidak perlu kolom terpisah)
- Polymorphic: `approvable_type` + `approvable_id`

### 12.3 Implementation di Model
```php
// Employee.php
public function getDirectApprover()
{
    if ($this->parent_id !== null) {
        return $this->parent; // Atasan langsung
    }
    return User::role('HR Manager')->first()->employee; // Fallback ke HRD
}
```

---

## 13. MODULE: KNOWLEDGEBASE AI (RAG)

### 13.1 Spesifikasi Teknis
- **Format:** HANYA PDF (MVP)
- **Max file size:** 10 MB per dokumen
- **Embedding:** Gemini `text-embedding-004` (768 dimensi) — **no OpenAI dependency**
- **LLM:** **Gemini 2.5 Flash (API)** — jangan self-host (hindari OOM di server)
- **Vector:** PostgreSQL `pgvector` (kolom `knowledge_bases.embedding` vector(768))
- **Chunking:** ±60 token, overlap 10 token
- **Response:** Teks jawaban + referensi sumber (nama dokumen + halaman)
- **CRITICAL:** `KnowledgeBase::processEmbedding()` membutuhkan kolom `status` yang harus ada sebelum embedding dijalankan — tanpa kolom ini, job crash `[ERR-008]`

### 13.2 Alur Kerja
1. HRD upload PDF → disimpan di `storage/app/knowledgebase/`
2. Background Job: Ekstrak teks → chunking → embedding → simpan ke `knowledge_bases`
3. Employee/HRD tanya → query vector similarity → ambil top-k chunks → kirim ke Gemini API
4. Gemini generate response → tampilkan ke user + referensi sumber

### 13.3 Queue & Performance
- Job: `ProcessKnowledgeBaseEmbedding`
- Queue: `default`
- Timeout: 300s (karena proses PDF bisa lama)
- Tries: 2
- **Tidak ada retry pada dokumen yang gagal parsing** → HRD harus upload ulang

### 13.4 Search Modes & Fallback `[S11]`

| Mode | Trigger | Performa | Catatan |
|------|---------|----------|---------|
| **Primary**: Vector similarity (pgvector cosine) | Default | Cepat (HNSW index) | Butuh Gemini API untuk embedding query |
| **Fallback**: `pg_trgm` full-text search | Saat Gemini API down/error | Lebih lambat, tapi tetap relevan | Index GIN di kolom `content_chunk` |

**Detection:**
```php
try {
    $embedding = $gemini->embed($query);
    return KnowledgeBase::orderByDistance('embedding', $embedding)->limit(5)->get();
} catch (GeminiException $e) {
    Log::warning('Gemini API down, fallback to pg_trgm', ['query' => $query]);
    return KnowledgeBase::whereRaw('content_chunk % ?', [$query])
        ->orderByRaw('similarity(content_chunk, ?) DESC', [$query])
        ->limit(5)
        ->get();
}
```

**UI:** banner "AI offline, menggunakan keyword search" saat fallback aktif. Service: `KnowledgeBaseService::search($query, bool $useFallback = false)`.

---

## 14. MODULE: MASTER DATA

### 14.1 Company
name, phone, email, website, npwp (encrypted), code, logo, is_active

### 14.2 Branch
company_id, name, address, latitude, longitude, radius, is_main, is_active

#### 14.2.1 Branch-Level Setting Overrides `[S14]`
Untuk fleksibilitas multi-cabang, beberapa setting bisa di-override per branch (kolom nullable — fallback ke `company_settings` jika NULL):

| Kolom Override | Tipe | Default (NULL) | Use Case |
|---------------|------|----------------|----------|
| `face_distance_threshold` | decimal(4,3) nullable | Pakai `company_settings.face_distance_threshold` | Cabang dengan kondisi pencahayaan buruk perlu threshold lebih longgar |
| `attendance_grace_minutes` | integer nullable | Pakai `shifts.late_tolerance_minutes` | Cabang dengan akses jalan macet perlu toleransi tambahan |
| `wfa_enabled` | boolean default true | Aktif | Cabang pabrik/produksi: WFA disable (harus on-site) |
| `radius` | integer | 100m | Sudah ada — geofence per branch |

**Resolve order:** branch override → company setting → hardcoded default.

### 14.3 Department
branch_id, name, code, description, is_active

### 14.4 Position
department_id, name, code, grade, basic_salary, allowance_jabatan, is_active

### 14.5 Shift
name, start_time, end_time, late_tolerance_minutes, is_active

#### 14.5.1 Shift Scheduling UX `[S16]`
- **Storage:** `shift_schedules` pivot table (employee_id, shift_id, date) — assign per (employee, date).
- **Bulk assign UI:** HRD pilih multiple employees + date range + shift → backend loop create rows. Tampilkan progress bar untuk volume besar.
- **Recurring pattern:** UI "Apply Senin-Jumat shift A untuk 4 minggu ke depan" → auto-fill rows (skip weekend, skip holidays).
- **Override:** 1 employee bisa punya shift berbeda per hari (ShiftSchedule type=`override`).
- **Default shift fallback:** jika tidak ada row di `shift_schedules` untuk tanggal tsb → fallback ke `employees.default_shift_id`.
- **Holiday handling:** type=`holiday` di shift_schedules untuk shift libur khusus (lebaran, dll).
- **DITUNDA V2:** Drag-and-drop calendar UI (mirip Google Calendar shift swap).

### 14.6 Holiday
date (unique), name, is_active

#### 14.6.1 Holiday Management `[S15]`
- **Manual entry:** HRD input tanggal di `/master/holidays` (CRUD).
- **Bulk import:** `HolidaySeeder` + UI upload CSV/XLSX (template `import-libur-2026.xlsx` disediakan).
- **Source of truth:** SKB 3 Menteri (annually update Desember tahun berjalan untuk tahun berikutnya).
- **Cache invalidation:** observer di model `Holiday` (lihat `app/Observers/HolidayObserver.php`) — invalidate `holidays:{year}` saat CRUD `[CAT-013]`.
- **DITUNDA V2:** Auto-fetch dari API kalender Indonesia (contoh: `api-harilibur.vercel.app`) saat awal tahun dengan opsi review HRD sebelum apply.

### 14.7 Company Settings (Key-Value)
| Key | Default | Deskripsi |
|-----|---------|-----------|
| `face_distance_threshold` | `0.15` | Distance threshold face recognition (≡ similarity ≥ 85%) `[K3]` |
| `overtime_tiers_weekday` | `{"1": 1.5, "2+": 2.0}` | Tiered rate lembur hari kerja (jam ke-1: 1.5x, jam ke-2+: 2x) `[ERR-001]` `[CAT-007]` |
| `overtime_tiers_holiday` | `{"1-8": 2.0, "9-10": 3.0, "11+": 4.0}` | Tiered rate lembur hari libur (jam 1-8: 2x, jam 9-10: 3x, jam 11+: 4x) `[ERR-001]` `[CAT-007]` |
| `attendance_penalty_per_day` | `50000` | Denda telat/alfa per hari |
| `payroll_cutoff_date` | `25` | Tanggal cut-off payroll |
| `wfa_note_min_chars` | `20` | Min karakter catatan WFA |
| `bpjs_kesehatan_ceiling` | `12000000` | Ceiling BPJS Kesehatan |
| `bpjs_jp_ceiling` | `10042300` | Ceiling BPJS Jaminan Pensiun |
| `leave_carry_forward_max` | `3` | Max sisa cuti dibawa |
| `leave_carry_forward_deadline` | `03-31` | Tanggal hangus carry-forward |
| `wfa_auto_approve_days` | `3` | Hari kerja sebelum WFA auto-approved `[M2]` |
| `ptkp_tk_0` | `54000000` | PTKP TK/0 (single, no dependent) `[CAT-014][N8]` |
| `ptkp_dependent` | `4500000` | Tambahan PTKP per tanggungan |
| `ptkp_max_dependents` | `3` | Max jumlah tanggungan untuk perhitungan PTKP |
| `currency_code` | `IDR` | Kode mata uang `[S17]` |
| `app_timezone` | `Asia/Jakarta` | Timezone aplikasi `[S1]` |
| `app_locale` | `id_ID` | Locale formatting (date/number) |

### 14.8 Tax Configs (PPh21 TER)
| Kolom | Deskripsi |
|-------|-----------|
| `category` | A, B, atau C |
| `min_income` | Batas bawah bruto bulanan |
| `max_income` | Batas atas bruto bulanan |
| `rate` | Persentase pajak |

### 14.9 BPJS Configs
| Kolom | Deskripsi |
|-------|-----------|
| `name` | kesehatan, jht, jp, jkk, jkm |
| `employer_rate` | Persentase employer |
| `employee_rate` | Persentase employee |
| `ceiling` | Batas atas (nullable) |

---

## 15. NOTIFICATIONS

### 15.1 Channels
| Channel | Penggunaan |
|---------|------------|
| **In-App** | Semua notifikasi, Inbox |
| **Email** | E-Payslip, Reset Password, Security Alert |
| **WhatsApp** | **DITUNDA** — Urgent & actionable |

### 15.2 Matrix
| Event | Employee | Manager | HRD | Finance |
|-------|:---:|:---:|:---:|:---:|
| Pengajuan Cuti/Lembur | ❌ | ✅ | ❌ | ❌ |
| Cuti di-ACC L1 | ✅ | ❌ | ✅ | ❌ |
| Cuti Final | ✅ | ❌ | ❌ | ❌ |
| Reimbursement Submitted `[M3]` | ❌ | ✅ | ❌ | ❌ |
| Reimbursement Approved L1 | ✅ | ❌ | ❌ | ✅ |
| Reimbursement Final Approved | ✅ | ❌ | ❌ | ❌ |
| Reimbursement Rejected | ✅ | ❌ | ❌ | ❌ |
| Reimbursement Paid | ✅ | ❌ | ❌ | ❌ |
| WFA Submitted `[M2]` | ❌ | ✅ | ✅ | ❌ |
| WFA Approved | ✅ | ❌ | ❌ | ❌ |
| WFA Rejected | ✅ | ❌ | ❌ | ❌ |
| WFA Auto-Approved (timeout) | ✅ | ✅ | ✅ | ❌ |
| Payroll Published | ✅ | ❌ | ❌ | ❌ |
| Login Baru | ✅ | ❌ | ✅ | ❌ |
| Overdue >24 jam | ❌ | ✅ | ✅ | ❌ |

### 15.3 Implementation
- Semua class implement `ShouldQueue`
- `MailMessage` untuk email HTML
- `toDatabase()` untuk in-app
- Email provider: Mailtrap (dev) → SES/Mailgun (production)

### 15.4 Export to Excel (V1)
- **Package:** `maatwebsite/laravel-excel`
- **Format:** `.xlsx` (Excel 2007+)
- **Data yang bisa di-export:**
  | Modul | Data | Oleh |
  |-------|------|------|
  | Attendance | Rekap absen per periode | HRD |
  | Leave | Riwayat cuti per karyawan | HRD |
  | Payroll | Slip gaji (bulanan) | Finance |
  | Employee | Direktori karyawan | HRD |
- Implementasi via **Export classes** (`app/Exports/`) yang extend `Maatwebsite\Excel\Concerns\FromCollection`
- Download langsung via browser — tidak disimpan di server
- **Payroll export password-protected:** password = NIK karyawan (untuk file individual) atau password admin (untuk bulk export). Implementasi via `WithProtection` `[N9]`

### 15.5 Tax & Compliance Reports `[S9]`

| Report | Format | Frequency | Generator | Catatan |
|--------|--------|-----------|-----------|---------|
| Bukti Potong PPh 21 (1721-A1 Bulanan) | PDF | Monthly per employee | `PayrollPdfService::generateBuktiPotong()` | Auto-generate saat payroll publish |
| SPT 1721-A1 Tahunan | PDF + XLSX | Annually January | `TaxAnnualReportService` | Rekap tahunan untuk DJP |
| BPJS Kesehatan Iuran | XLS (template Tenaga Kerja) | Monthly | `BpjsExportService::kesehatan()` | Format SISKA/BPJS portal |
| BPJS JHT/JP/JKK/JKM Iuran | XLS | Monthly | `BpjsExportService::ketenagakerjaan()` | Format BPJSTK |
| Daftar Karyawan Aktif | XLSX | On-demand | `EmployeeExport` | Filter status=active |
| Rekap Absen Bulanan | XLSX (per dept/branch) | On-demand | `AttendanceExport` | Filter by date range + branch |
| Rekap Cuti | XLSX | On-demand | `LeaveExport` | Filter by year + leave_type |
| Loan Outstanding | XLSX | On-demand | `LoanExport` (V2) | Status active |

**DITUNDA V2:** e-bupot via DJP API integration (auto-submit SPT Masa).

---

## 16. MODULE: PROJECT & TASK MANAGEMENT

### 16.1 Overview
Module untuk mengelola proyek, tugas (task), dan alokasi sumber daya karyawan. Mengintegrasikan timesheet untuk melacak waktu kerja per tugas/proyek. Pola arsitektur mengacu pada PasPapan Operational Workspace.

**Fitur Utama:**
- Manajemen proyek (CRUD, status lifecycle, timeline)
- Alokasi anggota tim per proyek dengan persentase alokasi
- Task board (Kanban-style: todo → in_progress → done)
- Checklist item per task
- Timesheet (log durasi per task per hari)
- Laporan utilisasi sumber daya

### 16.2 Data Model

```
Project
  ├── manager_id → Employee (project manager)
  ├── ProjectMember
  │     ├── employee_id → Employee
  │     └── allocation_pct (decimal 5,2) — % alokasi waktu
  └── ProjectTask
        ├── assigned_to → Employee
        ├── status: todo / in_progress / done
        ├── priority: low / normal / high
        ├── estimated_hours (decimal 8,2)
        └── ProjectTaskChecklistItem
              ├── title
              ├── is_done (boolean)
              └── sort_order

TimeEntry
  ├── project_id → Project
  ├── task_id → ProjectTask (nullable — untuk log ke proyek tanpa task spesifik)
  ├── employee_id → Employee
  ├── date (date)
  ├── duration_minutes (integer)
  └── description (text, nullable)
```

### 16.3 Project Lifecycle

| Status | Deskripsi |
|--------|-----------|
| `active` | Proyek berjalan |
| `on_hold` | Ditunda sementara |
| `completed` | Selesai |

Transisi: `active` ↔ `on_hold` → `completed` (tidak bisa kembali setelah completed).

### 16.4 Task Lifecycle

| Status | Deskripsi |
|--------|-----------|
| `todo` | Belum dikerjakan |
| `in_progress` | Sedang dikerjakan |
| `done` | Selesai (set `completed_at` timestamp) |

Transisi: `todo` → `in_progress` → `done`. Checklist item bisa di-toggle kapan saja.

### 16.5 Resource Allocation (ProjectMember)
- Satu karyawan bisa dialokasikan ke banyak proyek
- `allocation_pct` = persentase waktu (1-100%)
- Contoh: Employee A: Proyek X 60% + Proyek Y 40% = 100%
- Validasi: total alokasi per employee tidak wajib 100% (bisa < 100% jika ada idle/non-project time)
- HR/Manager bisa lihat laporan utilisasi: total capacity vs allocated

### 16.6 Timesheet (TimeEntry)
- Employee log waktu per task via `MyTasksComponent`
- Input: date, duration (jam:menit), description (opsional)
- Validasi: tidak boleh log di masa depan, durasi maks 16 jam/hari
- Laporan: total hours per project/employee per periode

### 16.7 UI/UX

| Halaman | Komponen | Akses |
|---------|----------|-------|
| **Project List** | `ProjectManagerComponent` — tabel + filter status, search | Admin (manage_projects) |
| **Project Detail** | Tab: Overview, Team, Tasks, Timesheet | Admin (view_projects) |
| **Task Board** | Kanban-style columns (todo/in_progress/done), drag-drop | Admin + assignee |
| **My Tasks** | `MyTasksComponent` — daftar tugas saya, filter status | Employee |
| **My Timesheet** | `MyTimesheetComponent` — log waktu per task per hari | Employee |
| **Resource Report** | Tabel utilisasi per employee per periode | Admin (view_reports) |

### 16.8 Permissions

| Permission | Role | Gate |
|-----------|------|------|
| `view_projects` | HR-Manager, Manager | ✅ |
| `manage_projects` | HR-Manager | ✅ |
| `view_all_tasks` | HR-Manager, Manager | ✅ |
| `view_reports_resource` | HR-Manager, Finance | ✅ |

### 16.9 Related Enums

| Enum | Values |
|------|--------|
| `ProjectStatus` | `active`, `on_hold`, `completed` |
| `TaskStatus` | `todo`, `in_progress`, `done` |
| `TaskPriority` | `low`, `normal`, `high` |

---

## 17. MODULE: HR CHECKLIST

### 17.1 Overview
Template-based checklist untuk onboarding (karyawan baru) dan offboarding (resign/PHK). Mengkoordinasikan tugas antar departemen (HR, IT, Admin) untuk memastikan tidak ada langkah terlewat.

### 17.2 Data Model

```
HrChecklistTemplate
  ├── type: onboarding / offboarding
  ├── name
  └── HrChecklistTemplateItem
        ├── title
        ├── category (hr / it / admin / manager)
        ├── assignee_type: hr / employee / manager
        ├── due_offset_days (integer — H+berapa dari effective_date)
        ├── is_required (boolean)
        └── sort_order

HrChecklistCase
  ├── template_id → HrChecklistTemplate
  ├── employee_id → Employee
  ├── type: onboarding / offboarding
  ├── status: active / completed / cancelled
  ├── effective_date (date — tanggal join/resign)
  └── HrChecklistTask
        ├── template_item_id → HrChecklistTemplateItem
        ├── assigned_to → Employee
        ├── due_date (date)
        ├── status: pending / done / skipped / blocked
        ├── completed_at
        └── notes
```

### 17.3 Template System

Dua template default:

**Onboarding Template:**
| Item | Kategori | Assignee | H+ |
|------|----------|----------|:--:|
| Siapkan akun email & aplikasi | IT | IT | -3 |
| Siapkan laptop/PC | IT | IT | -2 |
| Siapkan meja & akses kantor | Admin | Admin | -1 |
| Orientasi perusahaan & pengenalan tim | HR | HR | 0 |
| Input data karyawan ke sistem | HR | HR | 0 |
| Setup face enrollment | HR | HR | 1 |
| Assign project & perkenalan tugas | Manager | Manager | 1 |

**Offboarding Template:**
| Item | Kategori | Assignee | H+ |
|------|----------|----------|:--:|
| Kembalikan laptop/aset | IT | Employee | 0 |
| Nonaktifkan akun email & akses | IT | IT | 0 |
| Serah terima tugas & dokumen | Manager | Employee | -3 |
| Hitung gaji akhir & pesangon | HR | HR | 0 |
| Exit interview | HR | HR | 0 |

### 17.4 Case Lifecycle
1. HR buat `HrChecklistCase` saat employee join (onboarding) atau saat resign diajukan (offboarding)
2. System generate `HrChecklistTask` dari template items
3. Masing-masing assignee lihat tugas di `MyChecklistComponent`
4. Tugas bisa di-skip (jika tidak relevan) atau blocked (jika ada dependency)
5. Case otomatis `completed` ketika semua required task selesai

### 17.5 Permissions

| Permission | Role |
|-----------|------|
| `manage_hr_checklist` | HR-Manager |
| View/update assigned tasks | Employee (hanya tugas sendiri) |

---

## 18. MODULE: ANNOUNCEMENTS

### 18.1 Overview
Fitur pengumuman internal perusahaan. HR/Manager bisa mempublikasikan pengumuman yang muncul di dashboard & notifikasi karyawan.

### 18.2 Data Model

```
Announcement
  ├── title (string 255)
  ├── content (text)
  ├── published_at (timestamp, nullable — null = draft)
  ├── created_by → Employee
  └── timestamps
```

### 18.3 Behavior
- **Draft:** `published_at = null` — hanya visible ke HR
- **Published:** `published_at = now()` — visible ke semua employee
- **Expired:** `expired_at` (nullable) — jika diisi, pengumuman auto-hide setelah tanggal tsb
- Urutan: published_at DESC (terbaru di atas)
- Batas: 5 pengumuman terbaru di dashboard, "Lihat Semua" → full list

### 18.4 UI/UX

| Halaman | Konten |
|---------|--------|
| Dashboard | 5 pengumuman terbaru (card, judul + excerpt) |
| Announcement List | Full list dengan filter (published/draft/expired) |
| Create/Edit | Form: title, content, publish date (opsional), expire date (opsional) |

### 18.5 Permissions

| Permission | Role |
|-----------|------|
| `manage_announcements` | HR-Manager, Super-Admin |
| View | Semua employee (hanya published) |

---

## 19. QUEUE & JOB ARCHITECTURE

| Queue | Purpose |
|-------|---------|
| `default` | Email, notifications, knowledgebase embedding |
| `payroll_high` | Payroll generation (dedicated) |

### Jobs
| Job | Queue | Tries | Timeout |
|-----|-------|-------|---------|
| GenerateEmployeePayrollJob | payroll_high | 3 | 120s |
| ProcessKnowledgeBaseEmbedding | default | 2 | 300s |

### Commands
| Command | Schedule | Deskripsi |
|---------|----------|-----------|
| `attendance:detect-alpha` | dailyAt 23:59 | Deteksi karyawan alfa |
| `attendance:detect-chronic-late` | weeklyOn Friday 18:00 | Deteksi karyawan telat kronis `[CAT-010]` |
| `leave:reset-quota` | yearOn 1 Jan 00:00 | Reset quota cuti tahunan |
| `payroll:generate {period}` | Manual / via UI | Dispatch GenerateEmployeePayrollJob untuk seluruh karyawan aktif `[N6]` |
| `model:prune` | daily | Hapus activity log > 1 tahun |

---

## 20. SECURITY & DATA PROTECTION

### 20.1 CipherSweet Encryption
| Model | Field | Blind Index |
|-------|-------|-------------|
| Employee | nik, phone, npwp, bank_account_number | phone_hash, nik_hash, npwp_hash |
| Company | npwp | npwp_hash |
| FamilyDetail | nik, phone, address | nik_hash, phone_hash |

**Status enkripsi (per AGENTS.md + code):** Employee — ✅ selesai; Company.npwp — ✅ selesai; FamilyDetail — ⚠️ partial (model `#[Hidden]` sudah, blind index pending) `[CAT-003][N2]`

### 20.2 Data Masking
- Tampilkan `3271********99`, data asli tetap terenkripsi

### 20.3 Activity Logs
- Dihandle oleh **Spatie ActivityLog Package** (`php artisan activitylog:clean`)
- Command: `activitylog:clean --days=365` dijadwalkan via Scheduler harian
- Custom model `ActivityLog.php` dihapus — mencegah conflict dengan model bawaan Spatie

#### 20.3.1 Activity Log Coverage `[S6]`

**Models yang di-log via Spatie LogsActivity trait:**
Employee, User, Payroll, PayrollAdjustment, Leave, Overtime, Reimbursement, Loan, Asset, AssetHandover, CompanySetting, TaxConfig, BpjsConfig, Holiday, Approval, KnowledgeBase, Shift.

**Log level:**
- Row-level: `created`/`updated`/`deleted` events otomatis.
- Field-level: hanya untuk salary fields (`basic_salary`, `allowance_jabatan`) — log old vs new value.

**Custom event log (manual `activity()->log(...)`):**
```
payroll.published          payroll.regenerated         payroll.paid
leave.approved_l1          leave.approved_l2           leave.rejected
overtime.approved_l1       overtime.approved_l2        overtime.rejected
reimbursement.approved_l1  reimbursement.approved_l2   reimbursement.rejected
employee.terminated        employee.face_enrolled
settings.changed           settings.tax_config_updated  settings.bpjs_config_updated
password.changed           password.reset
2fa.enabled                2fa.disabled                 2fa.recovery_used
2fa.reset_by_admin         login.new_device             login.failed_attempt
attendance.exception_approved
```

**PII handling di log:** untuk field PII (`nik`, `phone`, `npwp`, `bank_account_number`) → log NAMA FIELD-nya saja, **bukan value** (privacy).

### 20.4 Soft Deletes
- employees, departments, positions, leaves, overtimes, payrolls, attendances

### 20.5 Backup
- **SERVER-LEVEL ONLY** — tidak ada tombol di UI

### 20.6 Face Embedding Security
- Embedding 128D disimpan di `employees.face_embedding` (vector(128))
- Hanya digunakan untuk similarity comparison, tidak bisa di-reverse ke foto asli

### 20.7 File Upload Specifications `[S3]`

| Type | Max Size | MIME | Storage Path | Encryption | Antivirus |
|------|----------|------|--------------|-----------|-----------|
| Face Enrollment Image | 2 MB | `image/jpeg`, `image/png` | Tidak disimpan (langsung embedding) | N/A | N/A |
| Profile Photo | 1 MB | `image/jpeg`, `image/png` | `storage/app/public/avatars` | Disk-level | DITUNDA V2 (ClamAV) |
| Company Logo | 1 MB | `image/png`, `image/svg+xml` | `storage/app/public/logos` | Disk-level | DITUNDA V2 |
| Sick Leave Proof | 5 MB | `image/jpeg`, `image/png`, `application/pdf` | `storage/app/leaves/proofs` | Disk-level | DITUNDA V2 |
| Reimbursement Receipt | 2 MB | `image/jpeg`, `image/png`, `application/pdf` | `storage/app/reimbursements` | Disk-level | DITUNDA V2 |
| KnowledgeBase PDF | 10 MB | `application/pdf` | `storage/app/knowledgebase` | Disk-level | DITUNDA V2 |
| Payslip PDF | N/A (server-generated) | `application/pdf` | `storage/app/payslips` | Disk-level + password-protected (NIK karyawan) | N/A |

**Implementasi MIME validation:** Laravel `mimes:` rule + `mimetypes:` rule (deteksi dari header file, bukan extension).

**DITUNDA V2:** Antivirus scan via ClamAV daemon (clamav-symfony), virus quarantine folder.

### 20.8 Concurrency Control `[S10]`
- **Pessimistic locking:** `Payroll::generatePayroll()` pakai `lockForUpdate()` (per task.md §0.4) untuk cegah double-generation.
- **Optimistic locking:** Employee/Payroll edit — DITUNDA V2 (toleransi last-write-wins untuk MVP).
- **Multi-device session:** User boleh login multi-device. Tidak ada force-logout otomatis.
- **Session timeout:** 120 menit idle (lihat §4).
- **"Logout from all devices":** Tombol di `/settings/security` (V1) — invalidate semua session via `DB::table('sessions')->where('user_id', $id)->delete()`.

---

## 21. DATABASE SCHEMA

### Existing Tables — Source of Truth: `docs/architecture/erd.dbml` (48 tabel) `[K4][N3]`

Database lengkap berisi 48 tabel mencakup: users, companies, branches, departments, positions, employees, attendances, shifts, holidays, leave_types, leaves, overtimes, reimbursements, loans, loan_installments, approvals, payrolls, payroll_items, payroll_adjustments, family_details, devices, assets, asset_handovers, performance_reviews, knowledge_bases, company_settings, reimbursement_categories, shift_schedules, leave_balances, tax_configs, bpjs_configs, activity_log, sessions, cache, jobs, job_batches, password_reset_tokens, indonesia_provinces, indonesia_cities, indonesia_districts, indonesia_villages, permission_tables (Spatie), blind_indexes (CipherSweet), serta tabel pendukung lainnya.

**Detail kolom, tipe, dan relasi ada di `erd.dbml` — file itu source of truth, bukan PRD.**

**Catatan — Tabel `assets`:** Membutuhkan `SoftDeletes` trait dan `AssetStatus` enum (`available`, `assigned`, `maintenance`, `disposed`) `[CAT-011]`

### Kolom Tambahan untuk `employees` (Migration Alter)
| Kolom | Tipe | Nullable | Default |
|-------|------|:---:|:---:|
| `contract_start_date` | date | ✅ | null |
| `contract_end_date` | date | ✅ | null |
| `deceased_date` | date | ✅ | null |
| `termination_reason` | text | ✅ | null |
| `employment_type` | string(20) | ❌ | 'permanent' |
| `pin` | string(60) | ✅ | null |

**employment_type values:** `permanent`, `contract`, `probation`, `intern` `[CAT-006]`

### New Tables (7)
1. `company_settings` — key-value config
2. `reimbursement_categories` — master kategori `[N1]` (promoted V1)
3. `shift_schedules` — pivot jadwal shift
4. `leave_balances` — saldo cuti per tahun
5. `tax_configs` — PPh21 TER rates
6. `bpjs_configs` — BPJS rates + ceilings
7. `payroll_adjustments` — koreksi payroll pasca-lock

**Catatan:** Semua tabel menggunakan `$table->id()` (Auto-Increment BIGINT UNSIGNED), BUKAN UUID. Foreign keys menggunakan `$table->foreignId()` untuk type consistency.

**Catatan ERD:** ERD mengandung kolom yang tidak dijelaskan secara eksplisit di teks PRD tetapi ada di migrations — referensi utama tetap file migration `[CAT-004]`

### Strategi ENUM: "Dumb Database, Smart Application"

**Prinsip:** Tidak ada `$table->enum()` atau `CHECK` constraint di database. Kolom "enum-like" tetap `$table->string()` atau `$table->char()`. Validasi dilakukan di **PHP level** menggunakan **PHP Backed Enums + Eloquent Model Casts**.

**Alasan:**
1. **Aturan pemerintah bisa berubah** — BPJS bisa ditambah kategori baru tanpa migration baru
2. **Zero downtime** — tidak perlu `ALTER TABLE` di production
3. **Clean Code** — validasi di Model via `casts()`, error terjadi sebelum query SQL

**Implementasi:**
```php
// Migration: tetap string
$table->char('ter_category', 1);

// Model: casting ke PHP Enum
protected function casts(): array {
    return [
        'ter_category' => TerCategory::class,
    ];
}

// PHP Enum: validasi otomatis
enum TerCategory: string {
    case A = 'A';
    case B = 'B';
    case C = 'C';
}
```

**Daftar PHP Enums (33 total — 16 Status dengan `color()` + 17 Classification tanpa `color()`) `[K5]`:**

> **Aturan warna:** Status enum return MD3 semantic token (`success`/`warning`/`danger`/`info`). Classification enum **TIDAK** punya `color()` — menambahkannya = visual noise.

#### 16 Enum STATUS (dengan `color()`)

| Enum File | Model | Kolom | Values |
|-----------|-------|-------|--------|
| `ApprovalStatus` | Approval | `status` | `pending`, `approved`, `rejected` |
| `AssetStatus` | Asset | `status` | `available`, `assigned`, `maintenance`, `disposed` `[CAT-011]` |
| `AttendanceStatus` | Attendance | `status` | `on_time`, `late`, `early`, `holiday`, `permission`, `absent`, `missed_clock_in`, `missed_clock_out` `[CAT-009]` |
| `EmployeeStatus` | Employee | `status` | `active`, `inactive`, `resigned`, `deceased`, `terminated` |
| `EmploymentType` | Employee | `employment_type` | `permanent`, `contract`, `probation`, `intern` `[CAT-006]` |
| `KnowledgeBaseStatus` | KnowledgeBase | `status` | `pending`, `processing`, `completed`, `failed` |
| `LoanInstallmentStatus` | LoanInstallment | `status` | `pending`, `paid`, `overdue` |
| `LoanStatus` | Loan | `status` | `pending`, `approved`, `rejected`, `active`, `paid_off`, `cancelled` |
| `MaritalStatus` | Employee | `marital_status` | `single`, `married`, `divorced`, `widowed` |
| `PayrollItemType` | PayrollItem | `type` | `allowance`, `deduction` |
| `PayrollStatus` | Payroll | `status` | `draft`, `published`, `paid` |
| `ReimbursementStatus` | Reimbursement | `status` | `pending`, `approved`, `rejected`, `paid` |
| `RequestStatus` | Leave, Overtime | `status` | `pending`, `approved_l1`, `approved`, `rejected`, `cancelled` |
| `TerCategory` | TaxConfig | `ter_category` | `A`, `B`, `C` |
| `TerminationType` | Employee | `termination_type` | `resign`, `dismissed`, `deceased`, `contract_end` |
| `WfaStatus` | Attendance | `status_wfa` | `pending`, `approved`, `rejected` |

#### 17 Enum CLASSIFICATION (TANPA `color()` — data only)

| Enum File | Model | Kolom | Values |
|-----------|-------|-------|--------|
| `ApprovalLevel` | Approval | `level` | `L1_SUPERVISOR=1`, `L2_MANAGER=2`, `L3_HRD=3`, `L4_DIRECTOR=4` (MVP pakai L1 & L2; L3/L4 disiapkan untuk V2 — lihat ERR-003) |
| `BloodType` | Employee | `blood_type` | `A+`, `A-`, `B+`, `B-`, `O+`, `O-`, `AB+`, `AB-` |
| `BpjsType` | BpjsConfig | `name` | `kesehatan`, `jht`, `jp`, `jkk`, `jkm` |
| `CompanySettingType` | CompanySetting | `type` | `string`, `integer`, `decimal`, `boolean`, `json` |
| `DayType` | Leave | `day_type` | `full_day`, `morning`, `afternoon` (gunakan `weight()` bukan `color()`) |
| `DeviceType` | Device | `device_type` | `mobile`, `tablet`, `desktop`, `unknown` |
| `EducationLevel` | Employee | `education_level` | `sd`, `smp`, `sma`, `d1`, `d3`, `s1`, `s2`, `s3` |
| `FamilyRelationship` | FamilyDetail | `relationship` | `spouse`, `child`, `parent`, `sibling`, `other` |
| `Gender` | Employee | `gender` | `male`, `female` |
| `HandoverCategory` | AssetHandover | `category` | `assignment`, `return`, `maintenance` |
| `KnowledgeBaseCategory` | KnowledgeBase | `category` | `policy`, `sop`, `regulation`, `faq`, `other` |
| `LeaveQuotaReset` | LeaveType | `quota_reset` | `yearly`, `monthly`, `none` |
| `NotificationType` | Notification | `type` | `info`, `success`, `warning`, `danger` |
| `ResignationReason` | Employee | `resignation_reason` | `personal`, `career`, `salary`, `relocation`, `other` |
| `SalaryType` | Employee | `salary_type` | `monthly`, `daily`, `hourly` |
| `ShiftScheduleType` | ShiftSchedule | `type` | `regular`, `override`, `holiday` |
| `VerificationMethod` | Attendance | `verification_method`, `clock_out_verification_method` | `face`, `pin`, `gps`, `manual` `[CAT-015]` |

#### Schema: `company_settings`
| Kolom | Tipe | Nullable | Default |
|-------|------|:---:|:---:|
| `id` | bigint (PK) | ❌ | auto |
| `company_id` | bigint (FK→companies) | ✅ | null |
| `key` | string (unique) | ❌ | — |
| `value` | json | ✅ | null |
| `description` | text | ✅ | null |

#### Schema: `reimbursement_categories`
| Kolom | Tipe | Nullable | Default |
|-------|------|:---:|:---:|
| `id` | bigint (PK) | ❌ | auto |
| `company_id` | bigint (FK→companies) | ✅ | null |
| `name` | string | ❌ | — |
| `code` | string (unique) | ❌ | — |
| `is_active` | boolean | ❌ | true |

#### Schema: `shift_schedules`
| Kolom | Tipe | Nullable | Default |
|-------|------|:---:|:---:|
| `id` | bigint (PK) | ❌ | auto |
| `employee_id` | bigint (FK→employees) | ❌ | — |
| `shift_id` | bigint (FK→shifts) | ❌ | — |
| `date` | date | ❌ | — |
| **Unique:** `(employee_id, date)` |

#### Schema: `leave_balances`
| Kolom | Tipe | Nullable | Default |
|-------|------|:---:|:---:|
| `id` | bigint (PK) | ❌ | auto |
| `employee_id` | bigint (FK→employees) | ❌ | — |
| `leave_type_id` | bigint (FK→leave_types) | ❌ | — |
| `year` | integer | ❌ | — |
| `quota` | integer | ❌ | 0 |
| `used` | integer | ❌ | 0 |
| `carry_forward` | integer | ❌ | 0 |
| `carry_forward_deadline` | date | ✅ | null |
| **Unique:** `(employee_id, leave_type_id, year)` |

#### Schema: `tax_configs`
| Kolom | Tipe | Nullable | Default |
|-------|------|:---:|:---:|
| `id` | bigint (PK) | ❌ | auto |
| `ter_category` | char(1) | ❌ | — |
| `min_income` | decimal(15,2) | ❌ | — |
| `max_income` | decimal(15,2) | ❌ | — |
| `rate` | decimal(5,4) | ❌ | — |
| `effective_rate` | decimal(5,4) | ✅ | null |

#### Schema: `bpjs_configs`
| Kolom | Tipe | Nullable | Default |
|-------|------|:---:|:---:|
| `id` | bigint (PK) | ❌ | auto |
| `name` | string | ❌ | — |
| `employer_rate` | decimal(5,4) | ❌ | — |
| `employee_rate` | decimal(5,4) | ❌ | — |
| `ceiling` | decimal(15,2) | ✅ | null |

#### Schema: `payroll_adjustments`
| Kolom | Tipe | Nullable | Default |
|-------|------|:---:|:---:|
| `id` | bigint (PK) | ❌ | auto |
| `payroll_id` | bigint (FK→payrolls) | ❌ | — |
| `amount` | integer | ❌ | — |
| `reason` | text | ❌ | — |
| `created_by` | bigint (FK→users) | ❌ | — |
| `applied_to_period` | date | ❌ | — |

### Alter Tables (7)
1. `loan_installments` — add: status, due_date
2. `leaves` — add: rejection_reason
3. `overtimes` — add: start_time, end_time, description, rejection_reason
4. `payrolls` — add: gross_salary, overtime_pay, pph21, bpjs_health, bpjs_employment, loan_deduction, attendance_penalty
5. `shifts` — add: late_tolerance_minutes
6. `attendances` — add: late_minutes, verification_method, clock_out_verification_method, face_similarity_score, clock_out_face_similarity_score `[ERR-004]`
7. `attendances` — **`shift_id` harus nullable** (karyawan tanpa shift assignment) `[ERR-005]`
8. `employees` — add: pin

---

## 22. MIGRATION PLAN

### Defensive Migration Pattern `[CAT-018]`
- **pgvector guard:** Gunakan `DB::statement()` untuk ekstensi pgvector, dengan `Schema::hasColumn()` check sebelum alter kolom — pastikan kompatibel dengan SQLite untuk testing lokal
- **Contoh:** `if (DB::getDriverName() === 'pgsql') { DB::statement('...') }` — jangan jalankan pgvector-specific SQL di SQLite

### Urutan Eksekusi
1. `add_columns_to_employees_table` — contract_start_date, contract_end_date, deceased_date, termination_reason, employment_type
2. `create_company_settings_table`
3. `create_reimbursement_categories_table` (V1 — promoted ke V1, lihat §9) `[N1]`
4. `create_shift_schedules_table`
5. `create_leave_balances_table`
6. `create_tax_configs_table`
7. `create_bpjs_configs_table`
8. `create_payroll_adjustments_table`
9. `add_status_and_due_date_to_loan_installments` (V2 prep — Loan UI ditunda V2)
10. `add_rejection_reason_to_leaves`
11. `add_details_to_overtimes`
12. `add_breakdown_to_payrolls`
13. `add_late_tolerance_to_shifts`
14. `add_late_minutes_to_attendances`
15. `add_verification_method_to_attendances`
16. `add_pin_to_employees`
17. `add_exception_fields_to_attendances` — exception_type, exception_notes, approved_late_by `[N10]`
18. `add_wfa_status_to_attendances` — status_wfa enum (per §6.1.1)
19. `add_device_detection_to_devices` — device_type, device_name, browser, os
20. `add_password_changed_at_to_users` — timestamp untuk password expiry tracking
21. `add_foreign_key_indexes` — index pada FK kolom untuk performance PostgreSQL
22. `add_phk_variant_to_employees` — `phk_variant` enum nullable (per §26.3 `[M7]`)

> **Catatan:** Migration list ini high-level. Detail per-file edit (termasuk edit-original-migration untuk fix nullable kolom) ada di `docs/planning/task.md` §4.1.

---

## 23. MODEL PLAN

### New Models (7)
1. CompanySetting — key-value helper (get/set static methods)
2. `reimbursement_categories` (V1 — promoted ke V1, lihat §9) `[N1]`
3. ShiftSchedule
4. LeaveBalance
5. TaxConfig — cast `ter_category` ke TerCategory enum
6. BpjsConfig — cast `name` ke BpjsType enum
7. PayrollAdjustment — relasi `payroll`, `createdBy`

### Modified Models (14)
| Model | Perubahan |
|-------|-----------|
| User | cast `password_changed`, relasi `employee` |
| Employee | 5 kolom baru, relasi `parent`, `children`, `approvals`, `leaveBalances`, `leaveBalances.currentYear()` |
| Leave | relasi `approvals()`, method `calculateTotalDays()`, `validateQuota()` |
| Overtime | kolom baru (start_time, end_time, description), relasi `attendance`, `approvals` |
| Payroll | kolom breakdown, relasi `items`, `adjustments`, method `isLocked()`, `generatePdf()` |
| Shift | kolom `late_tolerance_minutes`, cast `time` |
| Holiday | method `isHoliday(date)`, **cache invalidation di model observer** — hapus cache saat holiday CRUD `[CAT-013]` |
| ActivityLog | Dihandle oleh **Spatie ActivityLog Package** (model custom dihapus) |
| Branch | relasi `company`, `departments`, method `validateRadius(lat, lng, radius)` |
| PayrollItem | relasi `payroll` |
| LeaveType | relasi `leaveBalances`, method `isPaid()`, `deductsFromQuota()` |
| Position | relasi `department`, `employees` |
| Approval | relasi polymorphic `approvable`, relasi `approver` |
| KnowledgeBase | relasi polymorphic `knowledgeable`, method `processEmbedding()` |

### New Service Classes (5 inti — total 24 terdaftar, lihat `app/Services/`)
1. **PayrollCalculator** — calculateProratedSalary(), calculatePTKP(), getTERCategory(), calculatePPh21(), calculateBPJS(), calculateOvertimePay(), countWorkingDays(), calculateThrProrated()
2. **AttendanceService** — clockIn(), clockOut(), validateGPS(), validateFace(), handleWFA()
3. **LeaveService** — calculateWorkDays(), validateLeaveQuota(), applyLeave(), initializeBalance()
4. **ApprovalService** — createApprovalWorkflow(), approve(), reject(), checkAllApproved(), getDirectApprover()
5. **ReimbursementService** — createReimbursement(), validateReceipt(), approve(), reject(), linkToPayroll()

> **Catatan aktual:** Saat ini `app/Services/` berisi 24 service (20 top-level + 4 di `app/Services/Payroll/`: BpjsService, LemburService, PotonganService, Pph21Service). Daftar 5 di atas adalah yang asli direncanakan; sisanya ditambahkan saat development berjalan.

### New Export Classes (1)
1. **Exports/** — AttendanceExport, LeaveExport, PayrollExport, EmployeeExport — extend `Maatwebsite\Excel\Concerns\FromCollection`

### New Jobs (2)
1. GenerateEmployeePayrollJob — queue: payroll_high, tries: 3, timeout: 120s
2. ProcessKnowledgeBaseEmbedding — queue: default, tries: 2, timeout: 300s

### New Commands (9)
1. `attendance:detect-alpha` — dailyAt 23:59
2. `attendance:detect-chronic-late` — weeklyOn Friday 18:00
3. `attendance:detect-missed-clock` — dailyAt 00:01 (deteksi hari sebelumnya)
4. `attendance:send-reminders` — weekdays dailyAt 09:00
5. `attendance:auto-approve-wfa` — dailyAt 02:00 (WFA pending > 3 hari kerja)
6. `leave:reset-quota` — yearlyOn 1 Jan 00:00
7. `payroll:generate` — manual trigger via Finance UI / artisan (tidak di-schedule)
8. `knowledgebase:index` — manual trigger untuk reindex knowledge base
9. `cache:warm` — dailyAt 05:00 (sebelum jam kerja)

> **Catatan:** Jadwal lengkap ada di `routes/console.php`. Verifikasi via `php artisan schedule:list`.

### New Notifications (7)
1. LeaveRequestSubmitted
2. LeaveApproved
3. LeaveRejected
4. PayrollPublished
5. ApprovalOverdue
6. NewDeviceLogin
7. ChronicLateWarning

---

## 24. SERVICE CLASSES PLAN

**Catatan:** Observers sudah dibuat (8 total) — EmployeeObserver, AttendanceObserver, LeaveObserver, PayrollObserver, TaxConfigObserver, BpjsConfigObserver, HolidayObserver, CompanySettingObserver — semua terdaftar di `AppServiceProvider::registerObservers()` (dipanggil dari `boot()`).

### 24.1 PayrollCalculatorService

```php
// Method utama
calculateProratedSalary(Employee $employee, string $periodYearMonth): float
getTERCategory(Employee $employee): TerCategory (A|B|C)
calculatePPh21(Employee $employee, float $grossIncome, TerCategory $category): float
calculateBPJS(Employee $employee, float $grossIncome): array
calculateOvertimePay(Overtime $overtime): float
countWorkingDays(Carbon $start, Carbon $end): int
calculateThrProrated(Employee $employee, float $monthlySalary, int $monthsWorked): float

// Termination-related methods `[M4]` ✅ Implemented 2026-06-06
calculatePesangon(Employee $employee): float
// Per Appendix C tabel pesangon (UU Cipta Kerja).
// phk_variant multiplier: dismissed=1.0, dismissed_severe=2.0, mutual=0.5.

calculateLeaveCashOut(Employee $employee): float
// Sisa kuota cuti tahunan × (gross_monthly / countWorkingDays(month)).
// Dipanggil saat resign/PHK/contract_end/deceased.

calculateUangKompensasi(Employee $employee): float
// Hanya untuk PKWT (employment_type=contract) yang kontrak habis.
// Rumus: (masa_kerja_bulan / 12) × monthly_salary.

calculateUangPenghargaanMasaKerja(Employee $employee): float
// Tambahan untuk PHK (UU Cipta Kerja, Pasal 156 UU 6/2023).
// Tabel masa kerja → multiplier:
//   3-6 thn = 2 bulan, 6-9 thn = 3, 9-12 thn = 4, 12-15 thn = 5,
//   15-18 thn = 6, 18-21 thn = 7, 21-24 thn = 8, ≥24 thn = 10.
```

**Catatan penting:**
- `countWorkingDays()` fetch holidays SEKALI sebagai flat array, gunakan `in_array()` bukan `collect()`
- Division by zero protection: `if ($totalWorkingDays === 0) return 0.0`
- `Carbon::copy()` untuk mencegah pass-by-reference bug
- **Hardcoded SQL error code 23505 DILARANG** — gunakan `UniqueConstraintViolationException` untuk menangani constraint violation secara database-agnostic `[CAT-019]`
- `calculatePTKP()` dihapus dari implementasi — PTKP hanya digunakan untuk menentukan kategori TER via `TerCategory::resolveFromStatus()`. Nilai nominal PTKP tidak dipakai di metode TER (Tarif Efektif Rata-rata).

### 24.2 AttendanceService

```php
// Method utama
clockIn(Employee $employee, array $data): Attendance
clockOut(Employee $employee, array $data): Attendance
```

**Catatan:** Fungsi yang dulu ada di AttendanceService kini terdistribusi:
- `validateGPS()` → `GeofenceService::validateLocation()` (Haversine)
- `validateFace()` → `FaceRecognitionService::verifyFace()` (cosine distance via pgvector)
- `handleWFA()` → inline di `AttendanceService::clockIn()` — notifikasi + auto-approve cron
- `linkOvertimeToAttendance()` → `AttendanceObserver::saved()` (observer pattern)
- `FaceNotRecognizedException` tidak boleh di-swap (swallowed) — tiered fallback: face → PIN → manual approval `[CAT-017]`

### 24.3 LeaveService

```php
// Method utama
applyLeave(Employee $employee, array $data): Leave
calculateWorkDays(Carbon $start, Carbon $end, DayType $dayType): float
initializeBalance(Employee $employee, int $year): void
carryForward(Employee $employee, int $fromYear, int $toYear): void
```

**Catatan:** `validateLeaveQuota()` tidak sebagai method terpisah — validasi kuota dilakukan inline di `applyLeave()`. Quota hanya divalidasi saat submit, baru di-deduct setelah full L2 approval (via `ApprovalService::approve()` callback ke `LeaveService::applyLeave()`).

### 24.4 ApprovalService

```php
// Method utama
createApprovalWorkflow(Model $approvable): void
approve(Approval $approval, string $notes = ''): void
reject(Approval $approval, string $reason): void
```

**Catatan:** `checkAllApproved()` tidak sebagai method terpisah — logika inline di `approve()`. `getDirectApprover()` ada di model `Employee` (method `Employee::getDirectApprover()`).

---

## 25. SEEDER PLAN

### Urutan Seeder
1. RolesAndPermissionsSeeder — 5 roles + 50+ permissions
2. CompanyAndDepartmentSeeder — 1 company, 1 branch, 1 department
3. SuperAdminSeeder — admin@521.com, password dari env var `${SUPER_ADMIN_PASSWORD}` (jangan hardcode plaintext) `[N4]`
4. CompanySettingsSeeder — semua key-value defaults
5. PayrollConfigSeeder — tax_configs (A/B/C rates), bpjs_configs
6. LeaveTypeSeeder — Cuti Tahunan, Sakit, Menstruasi, Melahirkan, Penting, Unpaid
7. HolidaySeeder — hari libur nasional 2026
8. ShiftSeeder — "Office Hour" (08:00-17:00), "Morning" (06:00-14:00), "Night" (14:00-22:00), "Flexible"

---

## 26. PWA REQUIREMENTS

### 26.1 manifest.json `[S7]`
```json
{
  "name": "HRConnect",
  "short_name": "HRConnect",
  "start_url": "/",
  "display": "standalone",
  "theme_color": "#EA580C",
  "background_color": "#FDFBF7",
  "lang": "id-ID",
  "dir": "ltr",
  "scope": "/",
  "icons": [
    { "src": "/icons/icon-72.png",  "sizes": "72x72",  "type": "image/png" },
    { "src": "/icons/icon-96.png",  "sizes": "96x96",  "type": "image/png" },
    { "src": "/icons/icon-128.png", "sizes": "128x128","type": "image/png" },
    { "src": "/icons/icon-144.png", "sizes": "144x144","type": "image/png" },
    { "src": "/icons/icon-152.png", "sizes": "152x152","type": "image/png" },
    { "src": "/icons/icon-192.png", "sizes": "192x192","type": "image/png", "purpose": "maskable" },
    { "src": "/icons/icon-384.png", "sizes": "384x384","type": "image/png" },
    { "src": "/icons/icon-512.png", "sizes": "512x512","type": "image/png", "purpose": "maskable" }
  ]
}
```

### 26.2 Service Worker
- **Strategy:** `NetworkFirst` untuk API calls, `CacheFirst` untuk static assets (JS/CSS/images), `StaleWhileRevalidate` untuk halaman dashboard.
- **Offline page:** `/offline.html` — tampilkan saat tidak ada koneksi + ada attempt clock-in.
- **DITUNDA V2:** IndexedDB sync queue untuk clock-in offline (PWA Offline mode).

### 26.3 Camera Permission Flow
```
1. User tap "Clock In"
2. Browser prompt permission akses kamera
3. Granted → face-api.js initialize → preview kamera → capture → embedding → kirim ke server
4. Denied → fallback ke PIN verification (per error-handling-strategy.md §1 Skenario 1)
5. Camera tidak tersedia (HP tidak punya kamera depan) → langsung fallback ke PIN
```

### 26.4 Splash Screens
- Generate via `realfavicongenerator.net` atau `pwa-asset-generator`
- **iOS splash:** 8 sizes (1125x2436, 1242x2688, 828x1792, 1242x2208, 750x1334, 640x1136, 1668x2224, 2048x2732) — link via `<link rel="apple-touch-startup-image">`
- **Android:** handled otomatis oleh manifest (`background_color` + `icon-512`)

### 26.5 Install Prompt UX
- **Trigger:** setelah 2x login berturut-turut, ATAU setelah first successful clock-in.
- **Custom UI:** Custom modal/bottom sheet (Alpine + Tailwind) dengan tombol "Install Aplikasi" + "Nanti Saja".
- **Suppress:** simpan flag di localStorage; tidak muncul lagi 14 hari jika user dismiss.
- **Bottom Navigation:** Beranda, Absensi, Inbox, Profil
- **Mobile-first responsive design**
- **Push Notification:** DITUNDA V2
- **Face Recognition:** face-api.js, 128D, client-side

---

## 27. UI/UX GUIDELINES

### 27.1 Design System — Material Design 3

HRConnect menggunakan **Material Design 3** sebagai design language dengan palette kustom cream-warm. Tidak ada Flux UI.

#### Color Palette

| Token | Value | Penggunaan |
|-------|-------|------------|
| `canvas` | `#fffaf0` | Background utama (cream warm) |
| `ink` | `#0a0a0a` | Headline, primary text |
| `on-background` | `#1c1b1b` | Body text default |
| `on-surface-variant` | `#444748` | Label, caption |
| `surface-container-low` | `#f7f3f2` | Card background |
| `surface-container` | `#f1edec` | Elevated card, panel |
| `surface-container-high` | `#ebe7e6` | Hover state |
| `surface-container-highest` | `#e5e2e1` | Active state |
| `outline` | `#747878` | Border default |
| `outline-variant` | `#c4c7c7` | Border soft |
| `error` | `#ba1a1a` | Error text, icon |
| `error-container` | `#ffdad6` | Error background |
| `on-error` | `#ffffff` | Text on error |

#### Brand Colors

| Token | Value | Penggunaan |
|-------|-------|------------|
| `brand-pink` | `#ff4d8b` | Hero, illustration |
| `brand-teal` | `#1a3a3a` | Feature cards, geofence badge |
| `brand-lavender` | `#b8a4ed` | Camera card background |
| `brand-peach` | `#ffb084` | Illustration |
| `brand-ochre` | `#e8b94a` | WFA toggle active |
| `brand-mint` | `#a4d4c5` | Geofence badge, success indicator |
| `brand-coral` | `#ff6b5a` | Accent |
| `primary` | `#000000` | Destructive CTA / branded text |
| `primary-container` | `#1c1b1b` | Bottom nav active tab |

#### Typography

| Token | Font | Size | Weight | Penggunaan |
|-------|------|:----:|:------:|------------|
| `display-xl` | Rubik | 72px | 500 | Homepage h1 |
| `display-lg` | Rubik | 56px | 500 | Section heads |
| `display-md` | Rubik | 40px | 500 | Sub-section heads |
| `display-sm` | Rubik | 32px | 500 | CTA heads |
| `headline-lg-mobile` | Rubik | 36px | 500 | Mobile h1 |
| `title-lg` | Inter | 24px | 600 | Page titles |
| `title-md` | Inter | 18px | 600 | Card titles |
| `body-md` | Inter | 16px | 400 | Default body text |
| `body-sm` | Inter | 14px | 400 | Caption, metadata |
| `button` | Inter | 14px | 600 | Button labels |
| `cap-upper` | Inter | 12px | 600 | Section label, badge |

#### Icons

**Material Symbols** (Google Fonts) — semua ikon aplikasi. Variable font-weight (`wght` 100-700) dan fill (`FILL` 0/1). Import via CSS:

```css
@import 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap';
```

#### Spacing

| Token | Value |
|-------|-------|
| `xxs` | 4px |
| `xs` | 8px |
| `sm` | 12px |
| `md` | 16px |
| `lg` | 24px |
| `xl` | 32px |
| `xxl` | 48px |
| `section` | 64px |

#### Border Radius

| Token | Value | Penggunaan |
|-------|-------|------------|
| DEFAULT | 4px | Small badge |
| `rounded-lg` | 8px | Input, small button |
| `rounded-xl` | 12px | Button, card standard |
| `rounded-2xl` | 16px | Content card |
| `rounded-3xl` | 24px | Feature card |
| `rounded-[2rem]` | 32px | Camera card |
| `rounded-full` | 9999px | Avatar, pill |

### 27.2 Layout Strategy

| Platform | Navigation | Komponen |
|----------|-----------|----------|
| **Mobile** (< 768px) | Bottom Nav (4 tab) + TopAppBar | PWA-first, touch target ≥ 44px |
| **Desktop** (≥ 768px) | Sidebar + Top Header | Sidebar collapsible via Alpine |

**Bottom Navigation (Mobile):**
- 4 tab: Home, Absen, Inbox, Profile
- Active tab: `bg-primary-container` + filled icon
- Inactive tab: `text-on-surface-variant` + outlined icon
- Height: `h-20` (80px), `rounded-t-xl`

**TopAppBar:**
- Avatar claymation (kiri) + Page title (tengah) + Notifications icon (kanan)
- `sticky top-0 z-50 bg-canvas`
- Hidden on desktop (`md:hidden`)

### 27.3 Accessibility Commitment `[S8]`
- **Target:** WCAG 2.1 Level AA (subset).
- **Color contrast:** minimum 4.5:1 untuk text normal, 3:1 untuk large text/UI components.
- **Keyboard navigation:** semua aksi (form submit, modal close, dropdown, dll) harus bisa dijangkau via Tab/Shift+Tab/Enter/Esc.
- **Focus indicator:** visible (custom focus ring via Tailwind `focus:ring-2` — tidak boleh di-disable).
- **ARIA labels:** wajib untuk icon-only button, form input, status indicator.
- **Screen reader:** tested di NVDA (Windows) + VoiceOver (iOS) untuk halaman utama (Login, Clock-In, Inbox, Payslip).
- **Catatan:** full WCAG validation memerlukan manual testing dengan assistive technologies + expert review accessibility — tidak fully automated.

### 27.4 Component Standards `[N5]`
- **Loading state:** Skeleton card (Tailwind `animate-pulse bg-surface-container-high rounded-2xl`) untuk list/table; spinner untuk button submit.
- **Empty state:** ilustrasi claymation + pesan kontekstual + CTA primary (contoh: "Belum ada cuti — Ajukan Cuti").
- **Error toast:** Custom Alpine toast (success/warning/danger/info) — durasi 5 detik, dismissible.
- **Breadcrumb:** position di top header (di bawah app bar), separator `/`, max 4 level deep.
- **Form validation:** real-time via Livewire `wire:model.blur` + error message di bawah input (`text-body-sm text-error`).
- **Confirmation dialog:** Alpine `x-show` modal + backdrop untuk aksi destructive (delete, regenerate payroll, terminate employee).
- **Color contrast spec:** seluruh kombinasi text/background harus dicek dengan tool seperti Stark/axe DevTools sebelum commit.
- **Camera card (Absensi):** rounded-[2rem], bg-brand-lavender, reticle oval dashed, status overlay "Wajah Terdeteksi".
- **Geofence status:** Badge "Dalam Radius" — bg-brand-mint/20 text-brand-teal, pulsing dot.

---

## 28. EXECUTION PLAN

> **Update 2026-06-23:** Backend 100% ✅ selesai (193 app/ PHP files, 51 API endpoints, 1,121 tests). Sekarang fokus Frontend ESS.

### BULAN 1: Foundation + Face + Geofencing ✅ BACKEND DONE

| Minggu | Task | Deliverables | Status |
|--------|------|-------------|:---:|
| **1** | Migrations (13 files), Seeders (8 files), Spatie Permissions | Database ready, login works | ✅ |
| **2** | Employee CRUD, Master Data CRUD, Company Settings | HRD bisa manage data | ✅ |
| **3** | **Face Recognition**: face-api.js, enrollment, embedding storage | HRD enroll → server simpan 128D | ✅ |
| **4** | **GPS Geofencing**: Haversine, radius check, WFA mode | Clock-In validasi GPS + Face | ✅ |

**Checkpoint Bulan 1:** Employee bisa login → absen dengan Face + GPS → HRD bisa lihat attendance. ✅

### BULAN 2: RAG + Leave + Payroll ✅ BACKEND DONE

| Minggu | Task | Deliverables | Status |
|--------|------|-------------|:---:|
| **5** | **RAG - PDF Upload**: chunking, embedding, pgvector storage | HRD upload PDF → tersimpan | ✅ |
| **6** | **RAG - Chat Interface**: Gemini API, query vector, response + source | Employee tanya → AI jawab | ✅ |
| **7** | Leave Management: pengajuan, quota, approval | Employee bisa ajukan cuti | ✅ |
| **8** | Payroll Engine: Calculator, PPh21, BPJS, E-Payslip | Finance bisa generate gaji | ✅ |

**Checkpoint Bulan 2:** Full cycle: Absen (Face+GPS) → Cuti → Payroll + AI Chat bisa jawab. ✅

### BULAN 3: Overtime + Polish + Testing ✅ BACKEND DONE

| Minggu | Task | Deliverables | Status |
|--------|------|-------------|:---:|
| **9** | Overtime: pengajuan, observer, calculation | Employee bisa ajukan lembur | ✅ |
| **10** | Notifications, Dashboard, Activity Log | Sistem lengkap | ✅ |
| **11** | Flux → MD3 Migration + Mobile ESS Frontend | Hapus Flux, implement MD3 design system | 🚧 |
| **12** | Frontend completion, Testing, UAT | **READY FOR SIDANG** | ⏳ |

### FRONTEND SCOPE (Saat Ini)

| No | Task | Detail | Status |
|:--:|------|--------|:-----:|
| 1 | Hapus Flux UI | `composer remove`, CSS cleanup, 35 Blade files migrasi | ⏳ |
| 2 | MD3 Design System | CSS variables, Rubik + Inter, Material Symbols, spacing/radius | ⏳ |
| 3 | Mobile Layout | Bottom Nav 4 tab (Home/Absen/Inbox/Profile) + TopAppBar | ⏳ |
| 4 | Clock In Livewire | face-api.js, GPS, WFA toggle, full desain HTML user | 🚧 |
| 5 | Attendance History | Table + summary cards + filters | ⏳ |
| 6 | Leave (Apply + History) | Livewire form + quota + table | ⏳ |
| 7 | Overtime (Apply + History) | Livewire form + table | ⏳ |
| 8 | Reimbursement (Request + History) | Livewire form + upload + table | ⏳ |
| 9 | Payroll Slip | Period list + PIN prompt + PDF download | ⏳ |
| 10 | Profile & Devices | Personal info + device management | ⏳ |
| 11 | Inbox + RAG | Notifications + approval + RAG chat | ⏳ |
| 12 | Missing Route Views | Payroll, Approvals, KB, Assets, Loans landing | ⏳ |

### STRICT RULES `[N11]`
- **Tidak boleh nambah fitur baru DI LUAR PRD ini setelah Week 4** — item dalam PRD tetap dikerjakan sesuai jadwal Week 5-12.
- **Tidak boleh refactor arsitektur setelah Week 6**
- **Semua fitur V2 hanya dicatat di PRD, tidak di-code**
- **Test setiap minggu: `php artisan test --compact`**

---

## 29. EDGE CASES & REAL-WORLD SCENARIOS

### 29.1 Karyawan Meninggal
| Komponen | Treatment |
|----------|-----------|
| Gaji | Pro-rated s/d `deceased_date` |
| Cuti belum dipakai | Dibayar (uang pengganti) |
| Loan/Kasbon | **DIHAPUSKAN** (tidak tagih keluarga) |
| BPJS JHT/JP | Ditandai `claimable` untuk ahli waris |
| Status | `termination_type = deceased` |
| Payslip | Tetap di-generate untuk ahli waris |

### 29.2 Karyawan Resign di Tengah Bulan
| Komponen | Treatment |
|----------|-----------|
| Gaji | Pro-rated s/d `resign_date` |
| Cuti belum dipakai | Dibayar (wajib oleh hukum) |
| THR | Pro-rated: (bulan kerja / 12) × 1 bulan gaji |
| Pesangon | Sesuai masa kerja (1-6 bulan gaji) |
| Loan/Kasbon | Potong sekaligus dari gaji terakhir |
| Status | `termination_type = resign` |

### 29.3 Karyawan PHK — 3 Variant `[M7]`

| Variant | `phk_variant` | Pesangon | Penghargaan Masa Kerja | Catatan |
|---------|---------------|----------|------------------------|---------|
| PHK Biasa (efisiensi/kondisi perusahaan) | `dismissed` | 1× tabel (Appendix C) | 1× tabel (§21.1) | UU Cipta Kerja Pasal 156 |
| PHK Sepihak / Pelanggaran berat | `dismissed_severe` | 2× tabel | 2× tabel | Wajib SP1/2/3 sebelumnya, ada surat peringatan |
| PHK Mutually Agreed (kesepakatan) | `mutual` | 0.5× tabel | 0× | Sukarela, ada surat kesepakatan bersama |

**Field tambahan di `employees`:** `phk_variant` enum nullable (`dismissed`, `dismissed_severe`, `mutual`).

| Komponen | Treatment |
|----------|-----------|
| Gaji | Pro-rated s/d tanggal PHK |
| Pesangon | Lihat tabel di atas (`calculatePesangon()` §21.1) |
| Uang penghargaan masa kerja | Lihat `calculateUangPenghargaanMasaKerja()` §21.1 |
| Cuti belum dipakai | Dibayar (`calculateLeaveCashOut()` §21.1) |
| THR | Pro-rated jika sudah kerja ≥1 bulan |
| Status | `termination_type = dismissed`, `phk_variant` = sesuai variant |

### 29.4 Karyawan Kontrak (PKWT) Habis
| Komponen | Treatment |
|----------|-----------|
| Gaji | Pro-rated s/d `contract_end_date` |
| Uang kompensasi | (masa kerja / 12) × 1 bulan gaji |
| Cuti belum dipakai | Dibayar |
| THR | Pro-rated jika sudah kerja >3 bulan |
| Status | `termination_type = contract_end` |

### 29.5 Karyawan Cuti Besar (Unpaid Leave)
| Komponen | Treatment |
|----------|-----------|
| Gaji | TIDAK dibayar selama cuti |
| BPJS | Tetap aktif (karyawan bayar sendiri atau perusahaan cover) |
| Quota cuti tahunan | TIDAK berkurang |
| Masa kerja | Tetap dihitung |

### 29.6 Karyawan Join di Tengah Bulan
- Gaji pro-rated dari `join_date`
- Jika join setelah cut-off → masuk payroll bulan berikutnya
- Cuti tahunan pro-rated: (sisa bulan / 12) × 12 hari

### 29.7 Karyawan Kerja di Hari Libur Nasional
- Dihitung sebagai **lembur holiday**, bukan weekday biasa
- Rate: tiered — 2x (jam 1-8), 3x (jam 9-10), 4x (jam 11+) `[ERR-001]`
- Karyawan monthly salary sudah dapat gaji di hari libur, lembur = tambahan

### 29.8 Karyawan Tanpa Atasan Langsung (`parent_id = NULL`)
- Approval Level 1 otomatis **skip** ke HR Manager (Level 2)
- Ini terjadi untuk CEO, direktur, atau posisi baru yang belum punya atasan

### 29.9 WFA Approval Setelah Clock-In
- Karyawan absen dulu → status `is_wfa = true`, `status_wfa = pending`
- Manager review setelahnya
- Jika reject → status absensi bisa diubah menjadi `absent`

### 29.10 Payroll Lock Permanen
- Status `published` → **LOCKED PERMANEN**
- Koreksi hanya bisa via adjustment di periode berikutnya
- Tidak ada "unpublish" atau "edit" setelah publish

---

## 30. FEATURES DEFERRED TO V2

### 30.1 Promoted to V1 (sudah masuk MVP) `[N12]`

| Fitur | Lokasi PRD | Catatan |
|-------|-----------|---------|
| Reimbursement | §9 (V1) | Approval Manager → Finance |
| Export to Excel | §15.4 (V1) | 4 modul: Attendance, Leave, Payroll, Employee |
| Chronic Late Warning System | §6.5 (V1) | Cron weekly Friday 18:00 |
| THR/Bonus Auto-Calculation | §11.11 (V1) | Pro-rated formula |

### 30.2 Tetap V2 (TIDAK di-code di MVP)

| Fitur | Alasan | Estimasi |
|-------|--------|----------|
| WhatsApp Notifications (Twilio) | Biaya + setup API | 1 minggu |
| Multi-KPI Performance Review | MVP: single score cukup | 1-2 minggu |
| Employee Mutation Tracking | HRD update manual dulu | 1-2 minggu |
| Asset Management UI | Bisa Excel dulu | 1-2 minggu |
| Drag-and-Drop Shift Scheduler | HRD input manual dulu | 1 minggu |
| PWA Offline (IndexedDB sync) | Kompleks, sync logic | 2 minggu |
| PWA Push Notifications | DITUNDA | 1 minggu |
| Dark Mode | Bisa CSS nanti | 3-4 hari |
| KnowledgeBase Multi-Format (Word, Excel) | PDF only untuk MVP | 1-2 minggu |
| Delegation Approval | Manager cuti = HRD handle | 1 minggu |
| Custom Approval Workflow Builder | Fixed 2-level sudah cukup | 2 minggu |
| Advanced Analytics Dashboard | Basic dashboard cukup | 1-2 minggu |
| Multi-Language Support | 100% Bahasa Indonesia | 2 minggu |
| API untuk Mobile App Native | PWA sudah cukup | 2 minggu |
| Loan/Kasbon UI + service | Bisa manual dulu | 1 minggu |
| Antivirus scan untuk file upload | ClamAV setup | 3-4 hari |
| DJP API integration (PPh 21 e-bupot) | Compliance V2 | 2 minggu |
| Auto-fetch holidays dari API | Manual entry sudah cukup | 3 hari |
| Optimistic locking (Employee/Payroll edit) | Last-write-wins toleransi MVP | 1 minggu |
| Tax/BPJS configurable rates UI | Hardcoded di seeder MVP | 1 minggu |

### 30.3 CATATAN V2
- Fitur V2 sudah tercatat di PRD ini untuk referensi.
- Tabel-tabel persiapan (`reimbursement_categories`, `loan_installments`) tetap dibuat di Phase 1.
- Saat V2 dimulai, tinggal implementasi UI dan logic, tidak perlu migration baru.

---

## 31. GLOSSARY `[F]`

| Istilah | Definisi |
|---------|----------|
| Alpha | Status absen tanpa keterangan (auto-detect cron) |
| BPJS | Badan Penyelenggara Jaminan Sosial (Kesehatan + Ketenagakerjaan) |
| Bukti Potong | Surat keterangan PPh 21 yang dipotong perusahaan |
| Carry-forward | Sisa cuti tahunan yang dibawa ke tahun berikutnya (max 3 hari) |
| Ceiling | Batas atas upah untuk perhitungan iuran BPJS |
| CipherSweet | Library enkripsi searchable encryption (untuk PII) |
| Cut-off | Tanggal akhir periode payroll untuk hitung gaji bulan tsb |
| ESS | Employee Self-Service |
| Fortify | Laravel Fortify — backend authentication scaffolding |
| Geofence | Area virtual berbentuk lingkaran (lat,lng,radius) untuk validasi WFO |
| Haversine | Formula hitung jarak 2 titik di permukaan bumi |
| HNSW | Hierarchical Navigable Small World — indeks pgvector |
| HRIS | Human Resource Information System |
| JHT | Jaminan Hari Tua (BPJS Ketenagakerjaan) |
| JKK | Jaminan Kecelakaan Kerja (BPJS Ketenagakerjaan) |
| JKM | Jaminan Kematian (BPJS Ketenagakerjaan) |
| JP | Jaminan Pensiun (BPJS Ketenagakerjaan) |
| Kasbon | Pinjaman karyawan (V2) |
| KnowledgeBase | Sistem RAG untuk Q&A internal HRD |
| L1/L2 | Approval Level 1 (Manager) / Level 2 (HR Manager / Finance) |
| NIK | Nomor Induk Kependudukan (16 digit KTP) |
| NPWP | Nomor Pokok Wajib Pajak (15 digit) |
| PDP | Perlindungan Data Pribadi (UU 27/2022) |
| Payslip | Slip gaji elektronik (PDF) |
| Pesangon | Severance pay sesuai UU Cipta Kerja |
| pgvector | Ekstensi PostgreSQL untuk vector embedding |
| pg_trgm | Ekstensi PostgreSQL untuk trigram fuzzy search |
| PPh 21 | Pajak Penghasilan Pasal 21 (gaji karyawan) |
| PKWT | Perjanjian Kerja Waktu Tertentu (kontrak) |
| PKWTT | Perjanjian Kerja Waktu Tidak Tertentu (permanent) |
| PTKP | Penghasilan Tidak Kena Pajak |
| PWA | Progressive Web App |
| RAG | Retrieval Augmented Generation |
| RBAC | Role-Based Access Control |
| RPO/RTO | Recovery Point Objective / Recovery Time Objective |
| SKB 3 Menteri | Surat Keputusan Bersama 3 Menteri tentang Hari Libur Nasional |
| SoftDeletes | Laravel pattern: kolom `deleted_at` untuk delete logical |
| SPT | Surat Pemberitahuan (Pajak) — bulanan/tahunan |
| TER | Tarif Efektif Rata-rata (PPh 21 bulanan) |
| THR | Tunjangan Hari Raya |
| Uang Kompensasi | Uang akhir kontrak PKWT (UU Cipta Kerja) |
| Uang Penghargaan Masa Kerja | Tambahan PHK selain pesangon |
| WFA | Work From Anywhere |
| WFO | Work From Office |

---

## 32. VALIDATION RULES `[S2]`

Aturan validasi standar untuk semua FormRequest. Implementasikan via Laravel validation rules:

| Field | Rule | Format / Contoh |
|-------|------|-----------------|
| NIK | `required, digits:16, unique:employees,nik_hash` | 16 digit numeric (e.g. `3271012345678901`) — query via `nik_hash` |
| NPWP | `nullable, regex:/^\d{2}\.\d{3}\.\d{3}\.\d{1}-\d{3}\.\d{3}$/` | 15 digit XX.XXX.XXX.X-XXX.XXX |
| Phone | `required, regex:/^(\+62\|62\|0)8\d{8,11}$/` | `+62812345678` atau `0812345678` |
| Bank Account | `nullable, digits_between:10,16` | numeric only |
| Bank Name | `nullable, string, max:100` | "BCA", "Mandiri", "BNI" |
| Email | `required, email:rfc,dns, unique:users` | RFC 5322 + DNS check |
| Password | `min:8, regex:/[A-Z]/, regex:/[a-z]/, regex:/[0-9]/` | Min 8 chars, mixed case + numeric (per security-config.md §1.5) |
| PIN (employee) | `required, digits:6, unique with employee_id` | 6 digit numeric, hashed |
| Postal Code | `nullable, digits:5` | 5 digit numeric |
| Birth Date | `required, date, before:today, after:1900-01-01` | ISO date |
| Salary | `required, numeric, min:0, max:999999999` | integer rupiah |
| Latitude | `required, numeric, between:-90,90` | decimal(10,7) |
| Longitude | `required, numeric, between:-180,180` | decimal(10,7) |
| Radius (geofence) | `required, integer, between:10,5000` | meters |
| Leave Days | `required, numeric, min:0.5, max:90` | bisa 0.5 (half day) |
| Overtime Hours | `required, numeric, min:0.5, max:18` | max 18 jam/minggu (UU Cipta Kerja) |
| File Image | `image, mimes:jpeg,png, max:1024` | KB size |
| File PDF | `mimes:pdf, max:10240` | 10 MB |
| Date Range | `start_date <= end_date` | custom rule via Closure |

**Catatan:** Untuk field encrypted (`nik`, `phone`, `npwp`, `bank_account_number`), validasi `unique` harus dilakukan via `*_hash` (blind index), bukan kolom asli.

---

## 33. LOCALE & FORMAT `[S1][S17]`

### 33.1 Application Defaults
| Setting | Value | Lokasi Config |
|---------|-------|---------------|
| Timezone | `Asia/Jakarta` (WIB, UTC+7) | `config/app.php` `'timezone'` |
| Locale | `id` | `config/app.php` `'locale'` |
| Fallback Locale | `en` | `config/app.php` `'fallback_locale'` |
| Currency | IDR | `company_settings.currency_code` |
| Date format display | `DD-MM-YYYY` (`d-m-Y`) | Helper / Carbon macro |
| Date format storage | ISO 8601 (`Y-m-d`) | Database default |
| Time format display | `HH:mm` (24h) | Helper |
| Datetime display | `21 Mei 2026, 14:30 WIB` | Carbon `translatedFormat('d F Y, H:i')` + WIB |
| Number thousands | `.` (titik) | `number_format($n, 0, ',', '.')` |
| Number decimal | `,` (koma) | id_ID locale |
| Currency display | `Rp 1.234.567,-` (no decimal untuk IDR) | Helper `formatRupiah($amount)` |

### 33.2 Carbon Configuration
- Application uses `CarbonImmutable` — set di `AppServiceProvider::boot()` via `Date::use(CarbonImmutable::class)` (sudah ada — lihat AGENTS.md).
- All datetime stored di UTC, displayed di WIB via `->setTimezone('Asia/Jakarta')`.

### 33.3 Number/Currency Helpers
```php
// app/Helpers/Format.php
function formatRupiah(int|float $amount, bool $withSymbol = true): string {
    $formatted = number_format($amount, 0, ',', '.');
    return $withSymbol ? "Rp {$formatted},-" : $formatted;
}

function formatDateID(Carbon $date): string {
    return $date->translatedFormat('d F Y'); // "21 Mei 2026"
}
```

---

## 34. DATA RETENTION & PDP COMPLIANCE `[S4][S5][S18]`

### 34.1 Backup Schedule (Server-Level Only — sesuai §17.5)
| Aspek | Spesifikasi |
|-------|-------------|
| Frequency | Daily 01:00 WIB (PostgreSQL `pg_dump` full) + weekly tarball `/storage` |
| Retention | 30 daily, 12 weekly, 12 monthly |
| Encryption | GPG symmetric (key di vault terpisah, BUKAN di server yang sama) |
| Storage | Off-site S3-compatible (Wasabi / Cloudflare R2 / DigitalOcean Spaces) |
| Restore drill | Quarterly — verify backup integrity + restore time |
| RPO | 24 jam (max data loss tolerance) |
| RTO | 4 jam (max downtime tolerance) |
| Akses backup | Hanya super-admin via SSH ke backup server, bukan via UI |

### 34.2 Data Retention by Entity

| Entity | Active Period | Post-Resign Action | Anonymization |
|--------|---------------|--------------------|---------------|
| Attendance selfie photo | 1 tahun | Hapus 30 hari setelah resign | N/A |
| `face_embedding` (vector 128D) | Aktif selama employee status=active | **Set NULL immediately** saat resign | N/A |
| Payslip PDF | 7 tahun (UU Pajak Penghasilan) | Tetap simpan 7 tahun | NIK di-mask saat re-render: `3271***********99` |
| `activity_log` | 365 hari (Spatie default) | — | `model:prune` daily |
| Knowledge embeddings | Selama dokumen aktif | — | — |
| User account | Soft-delete saat resign | Hapus permanen 5 tahun setelah resign | Email di-randomize: `deleted_<id>@deleted.local` |
| Reimbursement receipt | 7 tahun (audit pajak) | Tetap | — |
| Leave proof (sakit) | 2 tahun | Tetap | — |

**Implementasi:** Job `PruneStaleDataJob` (scheduled monthly) yang mengeksekusi retention policy per entity.

### 34.3 PDP Compliance (UU 27/2022)

**Hak Subjek Data:**
| Hak | Implementasi |
|-----|--------------|
| Hak akses (right to access) | Endpoint `/profile/export-my-data` — JSON dengan seluruh data pribadi karyawan |
| Hak rektifikasi | Form di `/profile/edit` (sudah ada via Fortify update profile) |
| Hak hapus (right to be forgotten) | Form request manual pasca-resign — di-handle HRD via super-admin |
| Hak pembatasan pengolahan | Toggle di profile: opt-out face_embedding (fallback ke PIN) |
| Hak portabilitas | Export JSON via `/profile/export-my-data` |

**DPO (Data Protection Officer):**
- Email: `dpo@521.com`
- Ditampilkan di footer aplikasi + halaman Privacy Policy.

**Consent Management:**
- Saat first-login, tampilkan consent dialog untuk:
  - Penyimpanan `face_embedding` (untuk absensi)
  - GPS tracking (untuk validasi WFO)
- Tanpa consent → fallback ke PIN-only (face dan GPS disabled).
- Consent disimpan di `users.consent_face_embedding`, `users.consent_gps_tracking` (boolean).

### 34.4 Data Breach Response
- Jika terjadi breach (akses tidak sah, data leak):
  1. Segera hentikan akses (revoke token, force logout).
  2. Investigasi via `activity_log` + server access log.
  3. Notifikasi ke Kominfo dalam 3x24 jam (UU PDP Pasal 46).
  4. Notifikasi ke subjek data terdampak via email.
- DITUNDA V2: automated breach detection via anomaly monitoring.

---

## APPENDIX A: Environment Variables

```
DB_CONNECTION=pgsql
MAIL_MAILER=smtp
CACHE_STORE=database
QUEUE_CONNECTION=database
GEMINI_API_KEY=
GEMINI_EMBEDDING_MODEL=text-embedding-004
GEMINI_MODEL=gemini-2.5-flash
CIPHERSWEET_SECRET_KEY=
APP_NAME=HRConnect
```

## APPENDIX B: Cron Jobs

```
attendance:detect-alpha          → dailyAt 23:59
attendance:detect-chronic-late   → weeklyOn Friday 18:00
leave:reset-quota                → yearOn 1 Jan 00:00
payroll:generate {period}        → Manual / via UI (Finance trigger)
activitylog:clean --days=365     → daily
```

## APPENDIX C: Pesangon Table (UU Cipta Kerja)

| Masa Kerja | Pesangon |
|------------|----------|
| < 1 tahun | 0 bulan gaji |
| 1 tahun | 1 bulan gaji |
| 2 tahun | 2 bulan gaji |
| 3 tahun | 3 bulan gaji |
| 4 tahun | 4 bulan gaji |
| 5 tahun | 5 bulan gaji |
| ≥ 6 tahun | 6 bulan gaji |

## APPENDIX D: PTKP Values (2025)

| Status | PTKP |
|--------|------|
| TK/0 | 54.000.000 |
| K/0 | 58.500.000 |
| +Dependent | +4.500.000/orang (max 3) |

## APPENDIX E: TER Category Mapping

| PTKP | TER Category |
|------|-------------|
| TK/0, TK/1 | A (Terendah) |
| TK/2, TK/3, K/0, K/1 | B (Menengah) |
| K/2, K/3 | C (Tertinggi) |

---

**PRD VERSI 5.0 — Frontend Development Phase. Backend 100% ✅.**

---

## CHANGELOG v3.1 (2026-05-21)

35 koreksi konsolidasi: 5 kontradiksi kritis (K1-K5) + 8 gap modul (M1-M8) + 18 spec area baru (S1-S18) + 12 cleanup minor (N1-N12) + Glossary (F).

### Kontradiksi Kritis (K1-K5)

| Kode | Bagian | Perubahan |
|------|--------|-----------|
| K1 | §2.3 | LLM Gemini 2.5 Pro → 2.5 Flash (sinkron dengan §13.1 + AGENTS.md) |
| K2 | task.md §4.1i | Patch external — embedding dim 1536 → 768 (PRD §13.1 sudah benar) |
| K3 | §2.1, §6.1, §14.7 | "similarity_threshold 0.85" → "face_distance_threshold 0.15" + formula konversi eksplisit |
| K4 | §18 | "34 migrations + truncated list" → "48 tabel, source of truth = erd.dbml" |
| K5 | §18 | Enum list 16 → 33 (16 Status dengan color() + 17 Classification tanpa color()) |

### Gap Modul (M1-M8)

| Kode | Bagian | Perubahan |
|------|--------|-----------|
| M1 | §3 | Permission Matrix: tambah 17 baris (Reimbursement, WFA, Loan, Asset, Payslip, Tax/BPJS configs, audit logs) |
| M2 | §6.1.1, §12.1, §15.2 | WFA Post-Approval Flow lengkap + matrix update |
| M3 | §9.4, §15.2 | Reimbursement state machine (PENDING→APPROVED_L1→APPROVED→PAID) + 5 notifikasi |
| M4 | §21.1 | 4 method baru: calculatePesangon, calculateLeaveCashOut, calculateUangKompensasi, calculateUangPenghargaanMasaKerja |
| M5 | §11.12 | Salary Type Variants (monthly/daily/hourly) — perhitungan terpisah |
| M6 | §11.13 | Special Employment Types (probation/intern/contract/permanent) — gaji, THR, BPJS, PPh21 |
| M7 | §26.3 | PHK 3 Variant (dismissed / dismissed_severe / mutual) + field `phk_variant` |
| M8 | §11.3.1 | Unpaid Leave Impact pada pro-rated salary |

### Spec Area Baru (S1-S18)

| Kode | Bagian | Perubahan |
|------|--------|-----------|
| S1 + S17 | §30 (BARU) | Locale & Format: timezone, currency, date/number format, helpers |
| S2 | §29 (BARU) | Validation Rules: NIK, NPWP, phone, password, file size, dll. |
| S3 | §17.7 | File Upload Specifications (7 type) + MIME + storage path |
| S4 + S5 + S18 | §31 (BARU) | Data Retention & PDP Compliance (UU 27/2022): backup, retention, hak subjek |
| S6 | §17.3.1 | Activity Log Coverage: 17 model + 30+ custom event + PII handling |
| S7 | §23 | PWA expand: manifest.json, service worker, camera flow, splash, install UX |
| S8 | §24.2 | Accessibility: WCAG 2.1 AA, contrast, keyboard, ARIA |
| S9 | §15.5 | Tax & Compliance Reports (PPh, BPJS, daftar karyawan) |
| S10 | §17.8 | Concurrency Control (pessimistic/optimistic lock, multi-device) |
| S11 | §13.4 | KnowledgeBase Search Modes (vector primary + pg_trgm fallback) |
| S12 | §4.4 | 2FA Recovery flow (8 codes + admin reset) |
| S13 | §11.14 | PPh 21 Compliance & Annual Reconciliation (1721-A1) |
| S14 | §14.2.1 | Branch-Level Setting Overrides (face threshold, grace, WFA) |
| S15 | §14.6.1 | Holiday Management (manual + bulk import + V2 auto-fetch) |
| S16 | §14.5.1 | Shift Scheduling UX (bulk + recurring + override) |

### Cleanup Minor (N1-N12)

| Kode | Bagian | Perubahan |
|------|--------|-----------|
| N1 | §19, §20, §22 | "(V2 prep)" reimbursement → V1 (promoted) |
| N2 | §17.1 | CipherSweet status update sesuai code aktual |
| N3 | §18 | Truncated table list dihapus, ganti referensi erd.dbml |
| N4 | §22 | Password seeder dari env var, bukan hardcoded plaintext |
| N5 | §24.3 | Component Standards (loading, empty, toast, breadcrumb, dll.) |
| N6 | §16, Appendix B | Tambah `payroll:generate {period}` command |
| N7 | §4 | OAuth-only user re-auth via Google `prompt=reauth` untuk download payslip |
| N8 | §14.7 | PTKP keys ke company_settings (ptkp_tk_0, ptkp_dependent, ptkp_max_dependents) |
| N9 | §15.4 | Payroll Excel export password-protected (NIK / admin password) |
| N10 | §19 | Migration list expand + reference ke task.md §4.1 |
| N11 | §25 | STRICT RULES klarifikasi: "fitur baru DI LUAR PRD ini" |
| N12 | §27 | V2 list refactor: hapus strikethrough, pisah Promoted to V1 + Tetap V2 |

### Section Baru

| Kode | Bagian | Konten |
|------|--------|--------|
| F | §28 | Glossary (44 istilah) |

---

## CHANGELOG v3.0 (2026-05-20)

| Kode | Bagian | Perubahan |
|------|--------|-----------|
| ERR-001 | §8.3, §14.7, §26.7 | Overtime multiplier flat → tiered (weekday: 1.5x/2x, holiday: 2x/3x/4x) |
| ERR-002 | §7.2, §7.4 | Quota validated on submit, deducted only after full approval |
| ERR-003 | §12.1 | Tambah Reimbursement di approval matrix (L1: Manager, L2: Finance) |
| ERR-004 | §6.1, §18 | 4 kolom verifikasi terpisah untuk clock-in/out (verification_method, clock_out_verification_method, face_similarity_score, clock_out_face_similarity_score) |
| ERR-005 | §18 | shift_id di tabel attendances harus nullable |
| ERR-006 | §11.9 | retry_after harus lebih besar dari job timeout |
| ERR-007 | §8.3, §8.4 | Clarifikasi: weekend = holiday rate (multiplier sama, detection berbeda) |
| ERR-008 | §13.1 | KnowledgeBase::processEmbedding() butuh kolom status sebelum embedding |
| ERR-009 | §21 | EmployeeObserver & AttendanceObserver directory kosong/hilang |
| CAT-001 | §4 | Tambah kolom password_changed_at |
| CAT-002 | §9.3 | Reimbursement difilter berdasarkan expense_date dalam periode payroll |
| CAT-003 | §17.1 | CipherSweet: Employee selesai, FamilyDetail & Company deferred |
| CAT-004 | §18 | ERD mengandung kolom yang ada di migration tapi tidak di teks PRD |
| CAT-005 | §4 | "Tidak ada password expiry" → "Password expiry 90 hari" |
| CAT-006 | §18 | EmploymentType: 3 values → 4 values (tambah intern) |
| CAT-007 | §14.7 | Sudah tercakup oleh ERR-001 |
| CAT-008 | §27 | Hapus duplikat "Export to Excel", simpan versi V1 |
| CAT-009 | §6.2 | Tambah missed_clock_in & missed_clock_out ke AttendanceStatus (6→8) |
| CAT-010 | §16 | Tambah command attendance:detect-chronic-late |
| CAT-011 | §18 | Tabel assets butuh SoftDeletes + AssetStatus enum |
| CAT-012 | §21.2 | AttendanceService::invalidateCache() dead code (dihapus) |
| CAT-013 | §21 | Holiday model harus invalidate cache saat CRUD |
| CAT-014 | §11.2 | PTKP hardcoded values harus pindah ke CompanySetting |
| CAT-015 | §6.1 | VerificationMethod enum menggantikan magic strings |
| CAT-016 | §11.2 | Hardcoded 22 hari kerja → countWorkingDays() |
| CAT-017 | §21 | FaceNotRecognizedException tidak boleh di-swallow, tiered fallback wajib |
| CAT-018 | §19 | Defensive Migration pattern: pgvector guard untuk SQLite compatibility |
| CAT-019 | §21.1 | Hardcoded SQL error code 23505 dilarang, pakai UniqueConstraintViolationException |

---

## CHANGELOG v4.0 (2026-06-23)

| Kode | Bagian | Perubahan |
|------|--------|-----------|
| MIG-1 | Header, §1 | Hapus "Flux UI" dari tech stack, ganti "Material Design 3" |
| MIG-2 | §1 (Tech Stack) | Tambah Material Symbols + Rubik + Inter |
| MIG-3 | §18 (Enum color) | Hapus referensi Flux UI semantic names → MD3 tokens |
| MIG-4 | §23.5 (Install UX) | Flux UI modal → custom bottom sheet (Alpine + Tailwind) |
| MIG-5 | §24 (UI/UX) | **Rewrite total**: Flux tokens → MD3 palette (canvas #fffaf0, surface-container-*, outline-variant, brand-*), Rubik + Inter, Material Symbols, spacing/radius MD3, layout strategy (Bottom Nav mobile) |
| MIG-6 | §24.3 (Component Standards) | Flux skeleton/toast/modal → custom Tailwind + Alpine |
| MIG-7 | §25 (Execution Plan) | Tandai backend items ✅, frontend scope baru (Flux removal, MD3, ESS Livewire) |
| MIG-8 | Versi | 3.1 → 4.0 |
