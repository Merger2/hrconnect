# PRODUCT REQUIREMENTS DOCUMENT (PRD)
## HRConnect — HRIS Enterprise System
**PT 521 Teknologi Indonesia**
**Laravel 13 + Livewire 4 + Flux UI + PostgreSQL**

**Versi:** 2.0 — Final (Revisi 12-Week Plan)
**Tanggal Update:** 2026-05-08
**Status:** LOCKED — Tidak ada perubahan scope setelah Week 4

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
16. [Queue & Job Architecture](#16-queue--job-architecture)
17. [Security & Data Protection](#17-security--data-protection)
18. [Database Schema](#18-database-schema)
19. [Migration Plan](#19-migration-plan)
20. [Model Plan](#20-model-plan)
21. [Service Classes Plan](#21-service-classes-plan)
22. [Seeder Plan](#22-seeder-plan)
23. [PWA Requirements](#23-pwa-requirements)
24. [UI/UX Guidelines](#24-uiux-guidelines)
25. [12-Week Execution Plan](#25-12-week-execution-plan)
26. [Edge Cases & Real-World Scenarios](#26-edge-cases--real-world-scenarios)
27. [Features Deferred to V2](#27-features-deferred-to-v2)

---

## 1. OVERVIEW

HRConnect adalah sistem HRIS (Human Resource Information System) berskala Enterprise untuk PT 521 Teknologi Indonesia yang mengelola:
- **Presensi berbasis GPS Geofencing + Face Recognition** (nilai jual utama skripsi)
- Pengajuan Cuti dan Lembur dengan Multi-Layer Approval
- Penggajian Otomatis (PPh 21 TER + BPJS)
- **KnowledgeBase AI (RAG)** untuk HRD (nilai jual utama skripsi)
- PWA Mobile-First untuk Employee Self-Service (ESS)

**Fitur yang DITUNDA ke V2:** Reimbursement, Loan/Kasbon, Asset Management, Performance Review, WhatsApp Notifications.

**Tech Stack:**
- Backend: Laravel 13, PHP 8.5
- Frontend: Livewire 4, Flux UI, Tailwind CSS v4, Alpine.js
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
- Similarity threshold: `company_settings.face_similarity_threshold` (default 0.85)
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
- Embedding via OpenAI `text-embedding-3-small` (1536 dimensi)
- Vector disimpan di PostgreSQL `pgvector`
- Employee/HRD bisa tanya → AI jawab dengan referensi sumber
- LLM: **Gemini 2.5 Pro (API)** — jangan self-host (hindari OOM)
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
| `process_payroll` | ✅ | ❌ | ✅ | ❌ | ❌ |
| `view_payrolls` | ✅ | ❌ | ✅ | ❌ | ✅ (diri) |
| `view_activity_logs` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `manage_settings` | ✅ | ❌ | ❌ | ❌ | ❌ |
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
- **Google OAuth (SSO)** — Aktif dan utama, via Google Workspace
- **2FA (TOTP)** — Opsional, bisa diaktifkan di settings

### Password Policy
- Minimal **8 karakter**
- Wajib kombinasi **huruf besar + huruf kecil + angka**
- Simbol disarankan (Password Strength Indicator di frontend)
- **Tidak ada password expiry**
- **Force Change Password** — Saat pertama login, kolom `password_changed = false` → wajib ganti password

### Session
- **Timeout:** 120 menit (2 jam) idle
- **Remember Me:** ✅ Aktif
- **Login Perangkat Baru** → Notifikasi email ke karyawan + In-App ke HRD

### E-Payslip Security
- Download E-Payslip → wajib **re-enter password login** (Password Confirmation)
- Tidak ada PIN terpisah
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
  5. Similarity score > threshold (`company_settings.face_similarity_threshold`, default 0.85) → **terima**
  6. Foto selfie + GPS koordinat disimpan

- **WFA (Work From Anywhere):**
  1. Toggle WFA → validasi GPS **dilewati**
  2. Tetap wajib Face ID
  3. **Wajib isi catatan pekerjaan** minimal 20 karakter
  4. `is_wfa = true` di database
  5. **Approval SETELAH clock-in** (bukan sebelum) — karyawan absen dulu, baru manager review
  6. Jika WFA di-reject → status absensi hari itu bisa diubah menjadi `absent`

- **Double Clock Prevention:** Max 1x Clock-In + 1x Clock-Out per hari
- **Grace Period:** `shifts.late_tolerance_minutes` (default 0)
- **Semua karyawan wajib punya shift** (ada "Flexible/Office Hour" sebagai default)

### 6.2 Status Kehadiran
| Status | Kondisi |
|--------|---------|
| `on_time` | Clock-In <= (shift.start_time + tolerance) |
| `late` | Clock-In > (shift.start_time + tolerance) |
| `early` | Clock-Out < shift.end_time |
| `holiday` | Tanggal ada di tabel `holidays` |
| `permission` | Cuti/izin yang sudah di-approve |
| `absent` | Tidak ada record absensi (di-set Cron Job) |

### 6.3 Alpha Detection (Cron Job)
- **Command:** `attendance:detect-alpha`
- **Jadwal:** Setiap hari pukul 23:59
- **Logic:** Cari karyawan ACTIVE tanpa record attendance + tanpa cuti approved + bukan holiday → buat record `status = absent`

### 6.4 Device Management
- Device UUID di tabel `devices`
- Device baru → perlu verifikasi HRD (`is_verified = false`)

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
- Jika approved → otomatis kurangi `leave_balances.used`

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

### 8.3 Kalkulasi Upah Lembur
- **Upah per jam** = (Gaji Pokok + Tunjangan Tetap) / **173**
- **Rate hari kerja (weekday):** 1.5x upah/jam
- **Rate hari libur / weekend:** 2.0x upah/jam (8 jam pertama), 3.0x (jam ke-9+)

### 8.4 Kerja di Hari Libur Nasional (Tanggal Merah)
- Karyawan yang bekerja di hari libur nasional **mendapat bayaran lembur** (bukan rate weekday biasa)
- Rate: 2x upah/jam (8 jam pertama), 3x upah/jam (jam ke-9+)
- Karyawan monthly salary sudah dapat gaji di hari libur, lembur = **tambahan** dari gaji bulanan

### 8.5 Approval Workflow
- **Level 1:** Manager (`parent_id`) → validasi operasional
- **Level 2:** HR Manager → final approval
- Jika `parent_id = NULL` → skip ke HR Manager
- **Approved otomatis masuk payroll** (tidak perlu manual pilih)

---

## 9. MODULE: REIMBURSEMENT (V2)

**DITUNDA ke V2.** Tabel `reimbursement_categories` tetap dibuat sebagai persiapan.

### Rencana V2
- Kategori: Transport, Makan, Akomodasi, Medis, Lainnya
- Upload: JPG, PNG, PDF, **max 2 MB**
- Approval: Manager (L1) → Finance (L2)
- Tidak ada ceiling/max amount

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
- **Hari Kerja Efektif** = Senin-J минус weekend DAN holidays dari tabel `holidays`
- **Karyawan join di tengah bulan:** actual_start = join_date
- **Karyawan resign di tengah bulan:** actual_end = resign_date
- **Karyawan join + resign di bulan yang sama:** actual_start = join_date, actual_end = resign_date
- **Division by zero protection:** jika totalWorkingDays = 0 → return 0.0
- **Optimasi memori:** holidays di-fetch sekali sebagai flat array (bukan Collection object)
- Implementasi: `PayrollCalculatorService::calculateProratedSalary()`

### 11.4 PPh 21 TER
- **Dihitung PER BULAN** (bukan tahunan dibagi 12)
- **Konfigurasi:** Tabel `tax_configs`
- **PTKP:** Dari `marital_status` + jumlah anak di `family_details`
- **Kategori TER:**
  - A: TK/0, TK/1
  - B: TK/2, TK/3, K/0, K/1
  - C: K/2, K/3

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
- **Alpha:** 1 hari = potong 1 hari gaji (gross_monthly / 22)

### 11.7 Payroll Lock
- Status `published` → **LOCKED PERMANEN**
- Koreksi → buat `Adjustment` (payroll_items dengan type `adjustment`) di periode berikutnya

### 11.8 E-Payslip PDF
- 2 kolom: Pendapatan (kiri) vs Potongan (kanan)
- Take Home Pay ukuran besar di bawah
- **Generate sekali saat publish** → simpan di `storage/app/payslips/{period}/{employee_id}.pdf`
- Download unlimited (file streaming, bukan generate ulang)
- THR/Bonus ditampilkan sebagai item terpisah di payslip

### 11.9 Async Queue
- Job: `#[Queue('payroll_high')]`, `#[Tries(3)]`, `#[Timeout(120)]`, `#[Backoff([10, 30, 60])]`
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

---

## 12. MODULE: APPROVAL WORKFLOW

### 12.1 Matrix
| Pengajuan | Level 1 | Level 2 |
|-----------|---------|---------|
| Cuti | Manager (`parent_id`) | HR Manager |
| Lembur | Manager (`parent_id`) | HR Manager |

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
- **Embedding:** OpenAI `text-embedding-3-small` (1536 dimensi)
- **LLM:** **Gemini 2.5 Pro (API)** — jangan self-host (hindari OOM di server)
- **Vector:** PostgreSQL `pgvector` (kolom `knowledge_bases.embedding` vector(1536))
- **Chunking:** ±60 token, overlap 10 token
- **Response:** Teks jawaban + referensi sumber (nama dokumen + halaman)

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

---

## 14. MODULE: MASTER DATA

### 14.1 Company
name, phone, email, website, npwp (encrypted), code, logo, is_active

### 14.2 Branch
company_id, name, address, latitude, longitude, radius, is_main, is_active

### 14.3 Department
branch_id, name, code, description, is_active

### 14.4 Position
department_id, name, code, grade, basic_salary, allowance_jabatan, is_active

### 14.5 Shift
name, start_time, end_time, late_tolerance_minutes, is_active

### 14.6 Holiday
date (unique), name, is_active

### 14.7 Company Settings (Key-Value)
| Key | Default | Deskripsi |
|-----|---------|-----------|
| `face_similarity_threshold` | `0.85` | Threshold face recognition |
| `overtime_multiplier` | `1.5` | Rate lembur hari kerja |
| `overtime_weekend_multiplier` | `2.0` | Rate lembur hari libur |
| `attendance_penalty_per_day` | `50000` | Denda telat/alfa per hari |
| `payroll_cutoff_date` | `25` | Tanggal cut-off payroll |
| `wfa_note_min_chars` | `20` | Min karakter catatan WFA |
| `bpjs_kesehatan_ceiling` | `12000000` | Ceiling BPJS Kesehatan |
| `bpjs_jp_ceiling` | `10042300` | Ceiling BPJS Jaminan Pensiun |
| `leave_carry_forward_max` | `3` | Max sisa cuti dibawa |
| `leave_carry_forward_deadline` | `03-31` | Tanggal hangus carry-forward |

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
| Payroll Published | ✅ | ❌ | ❌ | ❌ |
| Login Baru | ✅ | ❌ | ✅ | ❌ |
| Overdue >24 jam | ❌ | ✅ | ✅ | ❌ |

### 15.3 Implementation
- Semua class implement `ShouldQueue`
- `MailMessage` untuk email HTML
- `toDatabase()` untuk in-app
- Email provider: Mailtrap (dev) → SES/Mailgun (production)

---

## 16. QUEUE & JOB ARCHITECTURE

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
| `leave:reset-quota` | yearOn 1 Jan 00:00 | Reset quota cuti tahunan |
| `model:prune` | daily | Hapus activity log > 1 tahun |

---

## 17. SECURITY & DATA PROTECTION

### 17.1 CipherSweet Encryption
| Model | Field | Blind Index |
|-------|-------|-------------|
| Employee | nik, phone, npwp, bank_account_number | phone_hash, nik_hash, npwp_hash |
| Company | npwp | npwp_hash |
| FamilyDetail | nik, phone, address | nik_hash, phone_hash |

### 17.2 Data Masking
- Tampilkan `3271********99`, data asli tetap terenkripsi

### 17.3 Activity Logs
- Trait `Prunable` → hapus log > 1 tahun

### 17.4 Soft Deletes
- employees, departments, positions, leaves, overtimes, payrolls, attendances

### 17.5 Backup
- **SERVER-LEVEL ONLY** — tidak ada tombol di UI

### 17.6 Face Embedding Security
- Embedding 128D disimpan di `employees.face_embedding` (vector(128))
- Hanya digunakan untuk similarity comparison, tidak bisa di-reverse ke foto asli

---

## 18. DATABASE SCHEMA

### Existing Tables (34 migrations)
users, companies, branches, departments, positions, employees, attendances, shifts, holidays, leave_types, leaves, overtimes, reimbursements, loans, loan_installments, approvals, payrolls, payroll_items, family_details, devices, assets, asset_handovers, performance_reviews, knowledge_bases, activity_log, sessions, cache, jobs, job_batches, password_reset_tokens, indonesia_provinces, indonesia_cities, indonesia_districts, indonesia_villages, permission_tables, blind_indexes

### Kolom Tambahan untuk `employees` (Migration Alter)
| Kolom | Tipe | Nullable | Default |
|-------|------|:---:|:---:|
| `contract_start_date` | date | ✅ | null |
| `contract_end_date` | date | ✅ | null |
| `deceased_date` | date | ✅ | null |
| `termination_reason` | text | ✅ | null |
| `employment_type` | string(20) | ❌ | 'permanent' |

**employment_type values:** `permanent`, `contract`, `probation`

### New Tables (6)
1. `company_settings` — key-value config
2. `reimbursement_categories` — master kategori (V2)
3. `shift_schedules` — pivot jadwal shift
4. `leave_balances` — saldo cuti per tahun
5. `tax_configs` — PPh21 TER rates
6. `bpjs_configs` — BPJS rates + ceilings

### Alter Tables (6)
1. `loan_installments` — add: status, due_date
2. `leaves` — add: rejection_reason
3. `overtimes` — add: start_time, end_time, description, rejection_reason
4. `payrolls` — add: gross_salary, overtime_pay, pph21, bpjs_health, bpjs_employment, loan_deduction, attendance_penalty
5. `shifts` — add: late_tolerance_minutes
6. `attendances` — add: late_minutes

---

## 19. MIGRATION PLAN

### Urutan Eksekusi
1. `add_columns_to_employees_table` — contract_start_date, contract_end_date, deceased_date, termination_reason, employment_type
2. `create_company_settings_table`
3. `create_reimbursement_categories_table` (V2 prep)
4. `create_shift_schedules_table`
5. `create_leave_balances_table`
6. `create_tax_configs_table`
7. `create_bpjs_configs_table`
8. `add_status_and_due_date_to_loan_installments` (V2 prep)
9. `add_rejection_reason_to_leaves`
10. `add_details_to_overtimes`
11. `add_breakdown_to_payrolls`
12. `add_late_tolerance_to_shifts`
13. `add_late_minutes_to_attendances`

---

## 20. MODEL PLAN

### New Models (4)
1. CompanySetting — key-value helper (get/set static methods)
2. ReimbursementCategory (V2)
3. ShiftSchedule
4. LeaveBalance

### Modified Models (14)
| Model | Perubahan |
|-------|-----------|
| User | cast `password_changed`, relasi `employee` |
| Employee | 5 kolom baru, relasi `parent`, `children`, `approvals`, `leaveBalances`, `leaveBalances.currentYear()` |
| Leave | relasi `approvals()`, method `calculateTotalDays()`, `validateQuota()` |
| Overtime | kolom baru (start_time, end_time, description), relasi `attendance`, `approvals` |
| Payroll | kolom breakdown, relasi `items`, method `isLocked()`, `generatePdf()` |
| Shift | kolom `late_tolerance_minutes`, cast `time` |
| Holiday | method `isHoliday(date)` |
| ActivityLog | trait `Prunable` |
| Branch | relasi `company`, `departments`, method `validateRadius(lat, lng, radius)` |
| PayrollItem | relasi `payroll` |
| LeaveType | relasi `leaveBalances`, method `isPaid()`, `deductsFromQuota()` |
| Position | relasi `department`, `employees` |
| Approval | relasi polymorphic `approvable`, relasi `approver` |
| KnowledgeBase | relasi polymorphic `knowledgeable`, method `processEmbedding()` |

### New Service Classes (4)
1. **PayrollCalculator** — calculateProratedSalary(), calculatePTKP(), getTERCategory(), calculatePPh21(), calculateBPJS(), calculateOvertimePay(), countWorkingDays()
2. **AttendanceService** — clockIn(), clockOut(), validateGPS(), validateFace(), handleWFA()
3. **LeaveService** — calculateWorkDays(), validateLeaveQuota(), applyLeave(), initializeBalance()
4. **ApprovalService** — createApprovalWorkflow(), approve(), reject(), checkAllApproved(), getDirectApprover()

### New Jobs (2)
1. GenerateEmployeePayrollJob — queue: payroll_high, tries: 3, timeout: 120s
2. ProcessKnowledgeBaseEmbedding — queue: default, tries: 2, timeout: 300s

### New Commands (2)
1. `attendance:detect-alpha` — dailyAt 23:59
2. `leave:reset-quota` — yearOn 1 Jan 00:00

### New Notifications (6)
1. LeaveRequestSubmitted
2. LeaveApproved
3. LeaveRejected
4. PayrollPublished
5. ApprovalOverdue
6. NewDeviceLogin

---

## 21. SERVICE CLASSES PLAN

### 21.1 PayrollCalculatorService

```php
// Method utama
calculateProratedSalary(Employee $employee, string $periodYearMonth): float
calculatePTKP(Employee $employee): float
getTERCategory(Employee $employee): string (A|B|C)
calculatePPh21(float $grossIncome, string $category): float
calculateBPJS(Employee $employee, float $grossIncome): array
calculateOvertimePay(Overtime $overtime, Employee $employee): float
countWorkingDays(Carbon $start, Carbon $end): int
calculateThrProrated(Employee $employee, float $monthlySalary, int $monthsWorked): float
```

**Catatan penting:**
- `countWorkingDays()` fetch holidays SEKALI sebagai flat array, gunakan `in_array()` bukan `collect()`
- Division by zero protection: `if ($totalWorkingDays === 0) return 0.0`
- `Carbon::copy()` untuk mencegah pass-by-reference bug

### 21.2 AttendanceService

```php
// Method utama
clockIn(Employee $employee, float $lat, float $lng, string $faceEmbedding, bool $isWfa = false, ?string $wfaNote = null): Attendance
clockOut(Employee $employee, float $lat, float $lng, string $faceEmbedding): Attendance
validateGPS(float $lat, float $lng, Branch $branch): bool (Haversine)
validateFace(string $liveEmbedding, string $storedEmbedding): float (similarity score)
handleWFA(Attendance $attendance): void
linkOvertimeToAttendance(Attendance $attendance): void (Observer pattern)
```

### 21.3 LeaveService

```php
// Method utama
calculateWorkDays(Carbon $start, Carbon $end, string $dayType): float
validateLeaveQuota(Employee $employee, LeaveType $type, float $days): bool
applyLeave(Leave $leave): Leave
initializeBalance(Employee $employee, int $year): void
carryForward(Employee $employee, int $fromYear, int $toYear): void
```

### 21.4 ApprovalService

```php
// Method utama
createApprovalWorkflow(Model $approvable): void
approve(Approval $approval, string $notes = ''): void
reject(Approval $approval, string $reason): void
checkAllApproved(Model $approvable): bool
getDirectApprover(Employee $employee): Employee
```

---

## 22. SEEDER PLAN

### Urutan Seeder
1. RolesAndPermissionsSeeder — 5 roles + 50+ permissions
2. CompanyAndDepartmentSeeder — 1 company, 1 branch, 1 department
3. SuperAdminSeeder — admin@521.com, password: password123!
4. CompanySettingsSeeder — semua key-value defaults
5. PayrollConfigSeeder — tax_configs (A/B/C rates), bpjs_configs
6. LeaveTypeSeeder — Cuti Tahunan, Sakit, Menstruasi, Melahirkan, Penting, Unpaid
7. HolidaySeeder — hari libur nasional 2026
8. ShiftSeeder — "Office Hour" (08:00-17:00), "Morning" (06:00-14:00), "Night" (14:00-22:00), "Flexible"

---

## 23. PWA REQUIREMENTS

- manifest.json + service-worker.js
- **Offline: DITUNDA** (IndexedDB untuk clock-in/out) — require online only untuk MVP
- Face Recognition: face-api.js, 128D, client-side
- Push Notification: **DITUNDA**
- Bottom Navigation: Beranda, Absensi, Inbox, Profil
- Mobile-first responsive design

---

## 24. UI/UX GUIDELINES

| Element | Light | Dark |
|---------|-------|------|
| Background | #FDFBF7 | #0F172A |
| Text | #334155 | #F8FAFC |
| Primary | #EA580C | #06B6D4 |
| Success | #16A34A | #22C55E |
| Font | Inter / Public Sans | |
| Desktop | Sidebar | |
| Mobile | Bottom Nav | |
| Dark Mode | **DITUNDA** | |

---

## 25. 12-WEEK EXECUTION PLAN

### BULAN 1: Foundation + Face + Geofencing

| Minggu | Task | Deliverables | Status |
|--------|------|-------------|:---:|
| **1** | Migrations (13 files), Seeders (8 files), Spatie Permissions | Database ready, login works | ⬜ |
| **2** | Employee CRUD, Master Data CRUD, Company Settings | HRD bisa manage data | ⬜ |
| **3** | **Face Recognition**: face-api.js, enrollment, embedding storage | HRD enroll → server simpan 128D | ⬜ |
| **4** | **GPS Geofencing**: Haversine, radius check, WFA mode | Clock-In validasi GPS + Face | ⬜ |

**Checkpoint Bulan 1:** Employee bisa login → absen dengan Face + GPS → HRD bisa lihat attendance. **Demo pertama.**

### BULAN 2: RAG + Leave + Payroll

| Minggu | Task | Deliverables | Status |
|--------|------|-------------|:---:|
| **5** | **RAG - PDF Upload**: chunking, embedding, pgvector storage | HRD upload PDF → tersimpan | ⬜ |
| **6** | **RAG - Chat Interface**: Gemini API, query vector, response + source | Employee tanya → AI jawab | ⬜ |
| **7** | Leave Management: pengajuan, quota, approval | Employee bisa ajukan cuti | ⬜ |
| **8** | Payroll Engine: Calculator, PPh21, BPJS, E-Payslip | Finance bisa generate gaji | ⬜ |

**Checkpoint Bulan 2:** Full cycle: Absen (Face+GPS) → Cuti → Payroll + AI Chat bisa jawab. **Demo kedua.**

### BULAN 3: Overtime + Polish + Testing

| Minggu | Task | Deliverables | Status |
|--------|------|-------------|:---:|
| **9** | Overtime: pengajuan, observer, calculation | Employee bisa ajukan lembur | ⬜ |
| **10** | Notifications, Dashboard, Activity Log | Sistem lengkap | ⬜ |
| **11** | PWA basics, Mobile responsive, Bug fixes | Siap demo | ⬜ |
| **12** | Testing, Documentation, UAT | **READY FOR SIDANG** | ⬜ |

**Checkpoint Bulan 3:** Semua core features berfungsi, siap sidang.

### STRICT RULES
- **Tidak boleh nambah fitur baru setelah Week 4**
- **Tidak boleh refactor arsitektur setelah Week 6**
- **Semua fitur V2 hanya dicatat di PRD, tidak di-code**
- **Test setiap minggu: php artisan test --compact**

---

## 26. EDGE CASES & REAL-WORLD SCENARIOS

### 26.1 Karyawan Meninggal
| Komponen | Treatment |
|----------|-----------|
| Gaji | Pro-rated s/d `deceased_date` |
| Cuti belum dipakai | Dibayar (uang pengganti) |
| Loan/Kasbon | **DIHAPUSKAN** (tidak tagih keluarga) |
| BPJS JHT/JP | Ditandai `claimable` untuk ahli waris |
| Status | `termination_type = deceased` |
| Payslip | Tetap di-generate untuk ahli waris |

### 26.2 Karyawan Resign di Tengah Bulan
| Komponen | Treatment |
|----------|-----------|
| Gaji | Pro-rated s/d `resign_date` |
| Cuti belum dipakai | Dibayar (wajib oleh hukum) |
| THR | Pro-rated: (bulan kerja / 12) × 1 bulan gaji |
| Pesangon | Sesuai masa kerja (1-6 bulan gaji) |
| Loan/Kasbon | Potong sekaligus dari gaji terakhir |
| Status | `termination_type = resign` |

### 26.3 Karyawan PHK
| Komponen | Treatment |
|----------|-----------|
| Gaji | Pro-rated s/d tanggal PHK |
| Pesangon | Sesuai tabel (bisa 2x lipat jika PHK sepihak) |
| Uang penghargaan masa kerja | Tambahan 1-2 bulan gaji |
| Cuti belum dipakai | Dibayar |
| THR | Pro-rated |
| Status | `termination_type = dismissed` |

### 26.4 Karyawan Kontrak (PKWT) Habis
| Komponen | Treatment |
|----------|-----------|
| Gaji | Pro-rated s/d `contract_end_date` |
| Uang kompensasi | (masa kerja / 12) × 1 bulan gaji |
| Cuti belum dipakai | Dibayar |
| THR | Pro-rated jika sudah kerja >3 bulan |
| Status | `termination_type = contract_end` |

### 26.5 Karyawan Cuti Besar (Unpaid Leave)
| Komponen | Treatment |
|----------|-----------|
| Gaji | TIDAK dibayar selama cuti |
| BPJS | Tetap aktif (karyawan bayar sendiri atau perusahaan cover) |
| Quota cuti tahunan | TIDAK berkurang |
| Masa kerja | Tetap dihitung |

### 26.6 Karyawan Join di Tengah Bulan
- Gaji pro-rated dari `join_date`
- Jika join setelah cut-off → masuk payroll bulan berikutnya
- Cuti tahunan pro-rated: (sisa bulan / 12) × 12 hari

### 26.7 Karyawan Kerja di Hari Libur Nasional
- Dihitung sebagai **lembur holiday**, bukan weekday biasa
- Rate: 2x upah/jam (8 jam pertama), 3x (jam ke-9+)
- Karyawan monthly salary sudah dapat gaji di hari libur, lembur = tambahan

### 26.8 Karyawan Tanpa Atasan Langsung (`parent_id = NULL`)
- Approval Level 1 otomatis **skip** ke HR Manager (Level 2)
- Ini terjadi untuk CEO, direktur, atau posisi baru yang belum punya atasan

### 26.9 WFA Approval Setelah Clock-In
- Karyawan absen dulu → status `is_wfa = true`, `status_wfa = pending`
- Manager review setelahnya
- Jika reject → status absensi bisa diubah menjadi `absent`

### 26.10 Payroll Lock Permanen
- Status `published` → **LOCKED PERMANEN**
- Koreksi hanya bisa via adjustment di periode berikutnya
- Tidak ada "unpublish" atau "edit" setelah publish

---

## 27. FEATURES DEFERRED TO V2

### DITUNDA (TIDAK DI-CODE DI MVP)

| Fitur | Alasan | Estimasi |
|-------|--------|----------|
| WhatsApp Notifications (Twilio) | Biaya + setup API | 1 minggu |
| Multi-KPI Performance Review | MVP: single score cukup | 1-2 minggu |
| Employee Mutation Tracking | HRD update manual dulu | 1-2 minggu |
| Asset Management | Bisa Excel dulu | 1-2 minggu |
| Chronic Late Warning System | Nice to have | 3-4 hari |
| Drag-and-Drop Shift Scheduler | HRD input manual dulu | 1 minggu |
| PWA Offline (IndexedDB sync) | Kompleks, sync logic | 2 minggu |
| PWA Push Notifications | DITUNDA | 1 minggu |
| Dark Mode | Bisa CSS nanti | 3-4 hari |
| KnowledgeBase Multi-Format (Word, Excel) | PDF only untuk MVP | 1-2 minggu |
| Delegation Approval | Manager cuti = HRD handle | 1 minggu |
| Custom Approval Workflow Builder | Fixed 2-level sudah cukup | 2 minggu |
| Advanced Analytics Dashboard | Basic dashboard cukup | 1-2 minggu |
| Export to Excel | PDF sudah cukup | 3-4 hari |
| Multi-Language Support | 100% Bahasa Indonesia | 2 minggu |
| API untuk Mobile App Native | PWA sudah cukup | 2 minggu |
| Reimbursement | Bisa manual dulu | 1 minggu |
| Loan/Kasbon | Bisa manual dulu | 1 minggu |
| THR/Bonus Auto-Calculation | Input manual via payroll_items | 3-4 hari |

### CATATAN V2
- Fitur V2 sudah tercatat di PRD ini untuk referensi
- Tabel-tabel persiapan (reimbursement_categories, loan_installments) tetap dibuat di Phase 1
- Saat V2 dimulai, tinggal implementasi UI dan logic, tidak perlu migration baru

---

## APPENDIX A: Environment Variables

```
DB_CONNECTION=pgsql
MAIL_MAILER=smtp
QUEUE_CONNECTION=database
REDIS_HOST=127.0.0.1
OPENAI_API_KEY=
GEMINI_API_KEY=
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
CIPHERSWEET_SECRET_KEY=
APP_NAME=HRConnect
```

## APPENDIX B: Cron Jobs

```
attendance:detect-alpha          → dailyAt 23:59
leave:reset-quota                → yearOn 1 Jan 00:00
model:prune                      → daily
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

**PRD VERSI 2.0 — FINAL LOCKED. Tidak ada perubahan scope setelah Week 4.**
