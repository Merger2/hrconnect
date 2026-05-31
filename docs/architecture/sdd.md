# SDD — Software Design Description
## HRConnect — HRIS Enterprise

## 1. Pendahuluan

### 1.1 Tujuan

Dokumen Software Design Description (SDD) ini menyajikan desain arsitektur perangkat lunak HRConnect secara rinci dan komprehensif sesuai standar IEEE 1016. Dokumen ini mencakup dekomposisi modul, desain data, desain antarmuka, komponen-komponen sistem, alur proses, dan mekanisme keamanan. SDD ini digunakan sebagai acuan implementasi bagi developer, arsitek, dan pemangku kepentingan teknis dalam pengembangan sistem HRIS Enterprise untuk PT 521 Teknologi Indonesia.

### 1.2 Ruang Lingkup

Dokumen ini meliputi:
- Desain arsitektur keseluruhan sistem dengan pola Layered Architecture
- Dekomposisi modul ke dalam 4 lapisan: Presentation Layer, Application Layer, Domain Layer, dan Infrastructure Layer
- Desain database mencakup seluruh 48 tabel dengan skema kolom, tipe data, konstrain, dan indeks
- Desain komponen perangkat lunak meliputi Services, Jobs, Commands, Notifications, Enums, Traits, Concerns, dan Observers
- Desain antarmuka mencakup API endpoints (43 endpoint REST), Livewire components (69 komponen), dan integrasi pihak ketiga
- Desain keamanan mencakup CipherSweet encryption, RBAC dengan 5 roles, 2FA TOTP, password policy, dan exception handling
- Matriks ketelusuran kebutuhan yang memetakan fitur dari SRS ke komponen implementasi

### 1.3 Definisi dan Akronim

| Istilah | Definisi |
|---------|----------|
| **HRIS** | Human Resource Information System |
| **ESS** | Employee Self-Service — fitur layanan mandiri karyawan |
| **PWA** | Progressive Web Application — aplikasi web yang dapat diinstal sebagai aplikasi mobile |
| **Livewire** | Framework full-stack Laravel untuk dynamic UI tanpa JavaScript framework |
| **Flux UI** | Component library untuk Livewire berbasis Tailwind CSS |
| **Alpine.js** | JavaScript library minimal untuk interaktivitas frontend |
| **RAG** | Retrieval Augmented Generation — teknik AI yang menggabungkan pencarian vektor dengan LLM |
| **pgvector** | Ekstensi PostgreSQL untuk vector similarity search |
| **pg_trgm** | Ekstensi PostgreSQL untuk trigram fuzzy text matching |
| **pgcrypto** | Ekstensi PostgreSQL untuk fungsi kriptografi |
| **CipherSweet** | Library enkripsi data dengan blind index untuk pencarian terenkripsi |
| **RBAC** | Role-Based Access Control — kontrol akses berbasis peran |
| **2FA TOTP** | Two-Factor Authentication menggunakan Time-based One-Time Password |
| **TER** | Tarif Efektif Rata-rata — metode perhitungan PPh 21 per bulan |
| **BPJS** | Badan Penyelenggara Jaminan Sosial — program jaminan kesehatan dan ketenagakerjaan |
| **PPh 21** | Pajak Penghasilan Pasal 21 — pajak atas penghasilan karyawan |
| **WFO** | Work From Office — mode kerja dari kantor |
| **WFA** | Work From Anywhere — mode kerja dari mana saja |
| **UUID** | Universally Unique Identifier |
| **ORM** | Object-Relational Mapping — Eloquent ORM pada Laravel |
| **PDO** | PHP Data Objects — antarmuka database untuk PHP |
| **FTK** | Full-Time Karyawan — status karyawan tetap |
| **PKWT** | Perjanjian Kerja Waktu Tertentu — karyawan kontrak |
| **PHK** | Pemutusan Hubungan Kerja — terminasi karyawan |
| **Haversine** | Rumus trigonometri untuk menghitung jarak antara dua titik di permukaan bumi |
| **FaceNet** | Model deep learning untuk face recognition dan embedding |
| **Cosine Distance** | Metrik jarak untuk vector similarity pada pgvector |
| **JWT** | JSON Web Token |
| **CSRF** | Cross-Site Request Forgery |
| **XSS** | Cross-Site Scripting |
| **SQL** | Structured Query Language |

### 1.4 Referensi

Dokumen ini merujuk pada sumber-sumber berikut:

1. SRS (Software Requirements Specification) — `docs/srs.md`
2. PRD v3.1 (Product Requirements Document) — `docs/PRD.md`
3. ERD (Entity Relationship Diagram) — `docs/architecture/erd.dbml`
4. Class Diagram — `docs/architecture/class-diagram.md`
5. Sequence Diagrams — `docs/architecture/sequence-diagrams.md`
6. Activity Diagrams — `docs/architecture/activity-diagrams.md`
7. Data Flow Diagram — `docs/architecture/data-flow-diagram.md`
8. Deployment Diagram — `docs/architecture/deployment-diagram.md`
9. API Contracts — `docs/api/api-contracts.md`
10. Security Config — `docs/security/security-config.md`
11. Error Handling Strategy — `docs/security/error-handling-strategy.md`
12. Caching Strategy — `docs/security/caching-strategy.md`
13. Folder Structure — `docs/architecture/folder-structure.md`
14. Execution Plan — `docs/planning/task.md`
15. Testing Strategy — `docs/testing/testing-strategy.md`

## 2. Representasi Arsitektur

### 2.1 Arsitektur Berlapis (Layered Architecture)

HRConnect menggunakan arsitektur berlapis (Layered Architecture) dengan 4 lapisan utama yang memisahkan tanggung jawab secara jelas:

**1. Presentation Layer**
Lapisan presentasi menangani interaksi pengguna dan rendering antarmuka. Teknologi yang digunakan adalah Livewire 4 untuk komponen server-rendered dengan reaktivitas real-time, Flux UI 2 untuk komponen UI siap pakai (button, modal, tabel, form, date-picker, badge, tooltip), Tailwind CSS v4 untuk styling utility-first, dan Alpine.js untuk interaktivitas frontend ringan. Presentation layer mencakup 69 Livewire components yang terbagi dalam kategori Employee (ESS), HRD, Finance, Admin, dan Reusable Components. Setiap komponen Livewire menangani siklus hidup request-response sendiri dengan state management di server.

**2. Application Layer**
Lapisan aplikasi mengandung logika bisnis spesifik use-case dan koordinasi aliran data. Terdiri dari Controllers yang menangani HTTP request, Service Classes (PayrollCalculatorService, AttendanceService, LeaveService, ApprovalService, GeofenceService, FaceRecognitionService, EmployeeTerminationService, DeviceDetectionService) yang mengimplementasikan logika bisnis, Form Requests untuk validasi input, Jobs untuk pemrosesan async, Commands untuk operasi terjadwal, dan Notifications untuk komunikasi pengguna.

**3. Domain Layer**
Lapisan domain merepresentasikan inti bisnis sistem. Mencakup Models (29+ Eloquent models dengan relasi dan logika domain), Enums (33 PHP 8.1 backed enums untuk status dan klasifikasi), Traits/Concerns (Approvable, ManagesWorkDays, PasswordValidationRules, ProfileValidationRules), dan Observers (6 observer class untuk event siklus hidup model).

**4. Infrastructure Layer**
Lapisan infrastruktur menyediakan dukungan teknis untuk lapisan di atasnya. Mencakup Database (PostgreSQL 15+ dengan ekstensi pgvector, pg_trgm, pgcrypto), Queue (database driver dengan 2 queue: payroll_high dan default), Cache (database driver — tanpa Redis, Cache::tags() tidak didukung), File Storage (private disk untuk payslips, knowledgebase, face photos; public disk untuk avatars dan logo), serta External API Integration (Gemini API untuk embedding dan LLM, Google OAuth 2.0 untuk SSO, SMTP untuk email).

**Aliran Data:**
```
User → Browser → Livewire Component → Service → Model → Database
```
Secara rinci:
1. User berinteraksi dengan antarmuka Livewire di browser
2. Livewire mengirim request AJAX ke server (wire:submit, wire:click, wire:model)
3. Livewire component memproses input, validasi melalui Form Request
4. Component memanggil Service class untuk logika bisnis
5. Service berinteraksi dengan Model (Eloquent ORM) untuk akses data
6. Model melakukan query ke PostgreSQL melalui PDO
7. Response dikirim kembali melalui jalur sebaliknya: Database → Model → Service → Livewire Component → Browser

### 2.2 Pola Desain Utama

**Service Layer Pattern**
Business logic ditempatkan di Service classes, bukan di Controller atau Model. Ini memastikan separation of concerns dan reusability. Service utama: PayrollCalculatorService (kalkulasi gaji, PPh 21, BPJS), AttendanceService (clock-in/out workflow, validasi GPS, face recognition), LeaveService (kalkulasi hari kerja, validasi kuota, apply cuti), ApprovalService (multi-level approval workflow).

**Repository Pattern**
Diimplementasikan secara native melalui Eloquent ORM. Setiap Model memiliki kemampuan query builder, relasi, dan global scope tanpa perlu membuat kelas Repository terpisah. Query kompleks ditangani melalui local scopes, query builder, dan raw expressions untuk kebutuhan pgvector.

**Observer Pattern**
Model events (created, updated, deleted) digunakan untuk efek samping otomatis. Observer class menangani: (1) AttendanceObserver — link overtime ke attendance saat clock-out, deteksi late/early; (2) EmployeeObserver — generate employee number otomatis, set default shift, invalidate cache; (3) LeaveObserver — trigger approval workflow; (4) PayrollObserver — generate PDF payslip; (5) ApprovalObserver — trigger notification cascade; (6) UserObserver — set password_changed_at.

**Strategy Pattern**
PHP 8.5 backed enums digunakan sebagai strategy pattern untuk status dan klasifikasi. Setiap enum dapat memiliki method yang berbeda perilakunya berdasarkan value. Contoh: ApprovalStatus memiliki method color() yang mengembalikan nama warna Flux UI berbeda untuk setiap status (pending → info, approved → success, rejected → danger).

**DTO Pattern**
Form Request class berfungsi sebagai Data Transfer Object sekaligus validasi. Setiap request memiliki aturan validasi sendiri, pesan error kustom dalam Bahasa Indonesia, dan authorization check via policy.

**Queue/Job Pattern**
Pemrosesan berat dijalankan secara async melalui queue jobs: GenerateEmployeePayrollJob (queue: payroll_high, tries: 3, timeout: 120s) untuk kalkulasi payroll per employee, ProcessKnowledgeBaseEmbedding (queue: default, tries: 2, timeout: 300s) untuk ekstraksi dan embedding PDF.

**Polymorphic Pattern**
Digunakan untuk Approval (approvable_type + approvable_id yang bisa merujuk ke Leave, Overtime, Reimbursement) dan KnowledgeBase (knowledgeable_type + knowledgeable_id yang bisa merujuk ke Company, Branch, Department).

### 2.3 Diagram Arsitektur Sistem

Sistem HRConnect terdiri dari komponen-komponen berikut berdasarkan deployment-diagram.md:

**PWA Client (Perangkat Karyawan)**
- Browser (Chrome/Safari/Firefox) sebagai runtime
- face-api.js untuk client-side face recognition (FaceNet 128D embedding)
- Geolocation API untuk GPS coordinates
- Service Worker untuk kemampuan PWA (installable, offline readiness)
- manifest.json untuk konfigurasi PWA
- Alpine.js untuk interaktivitas frontend
- Bottom Navigation: Beranda, Absensi, Inbox, Profil

**Web Server (VPS)**
- Nginx sebagai reverse proxy dan static file server
- Laravel 13 Application dengan Livewire 4, Flux UI 2, Tailwind CSS v4
- Laravel Fortify untuk autentikasi (login, register, reset password, email verification, 2FA)
- Spatie Permission untuk RBAC
- Google OAuth untuk SSO

**PHP Runtime (VPS)**
- PayrollCalculatorService — kalkulasi gaji dan komponen payroll
- AttendanceService — logika presensi dan validasi
- LeaveService — manajemen cuti dan kuota
- ApprovalService — alur persetujuan multi-level
- GeofenceService — validasi GPS dengan Haversine formula
- FaceRecognitionService — verifikasi face embedding
- EmployeeTerminationService — proses resign dan PHK
- DeviceDetectionService — deteksi dan verifikasi perangkat

**Queue Worker**
- Database queue driver (tabel jobs + failed_jobs)
- payroll_high queue: prioritas tinggi untuk GenerateEmployeePayrollJob
- default queue: untuk ProcessKnowledgeBaseEmbedding, SendNotificationJob

**Cron Scheduler**
- attendance:detect-alpha (setiap hari 23:59) — deteksi karyawan alpha
- leave:reset-quota (1 Januari 00:00) — reset kuota cuti tahunan
- model:prune (harian) — pembersihan data kadaluwarsa

**Database (Neon PostgreSQL Cloud)**
- PostgreSQL 15+ dengan ekstensi pgvector, pg_trgm, pgcrypto
- 48 tabel termasuk master data, transaksi, konfigurasi, dan log
- CipherSweet untuk enkripsi data sensitif dengan blind index

**External APIs**
- Gemini API: text-embedding-004 (768D embedding) + Gemini 2.5 Flash (RAG LLM)
- Google OAuth 2.0: SSO dengan OAuth 2.0 authorization code flow
- SMTP: Mailtrap (development), SES/Mailgun (production) untuk email

**File Storage**
- Private disk (storage/app/private): payslips/{period}/{employee_id}.pdf, knowledgebase/{filename}.pdf, leaves/proofs/, reimbursements/, profile_photos/
- Public disk (storage/app/public): avatars/, logos/

## 3. Dekomposisi Modul

### 3.1 Presentation Layer

Presentation layer menggunakan Livewire 4 dengan server-rendered components yang memberikan pengalaman SPA-like melalui wire:navigate. Flux UI 2 menyediakan komponen UI siap pakai yang konsisten. Tailwind CSS v4 menangani styling utility-first. Alpine.js digunakan untuk interaktivitas ringan di client-side seperti toggle, animasi, dan validasi frontend.

**Kategori Livewire Components:**

**Employee Components (ESS — Employee Self-Service)**
- Attendance: ClockIn, ClockOut, History, Summary — presensi harian dan riwayat
- Leave: Create, History, Quota — pengajuan cuti, riwayat, dan sisa kuota
- Finance: LoanRequest, ReimbursementRequest, PayrollSlip — layanan keuangan mandiri
- Profile: PersonalInfo, FamilyDetails, FaceRegistration, Devices — data pribadi, keluarga, registrasi wajah, dan perangkat

**HRD Components**
- Dashboard: Overview, AttendanceToday — ringkasan dan presensi hari ini
- Employees: Index, Create, Edit, Show, BulkUpload — manajemen direktori karyawan
- Approvals: Pending, All, Escalated — persetujuan cuti/lembur dengan filter
- Leaves: Pending, Calendar, QuotaManagement — pengelolaan cuti
- Shifts: Index, Schedule — manajemen shift dan penjadwalan
- Terminations: Pending, Handover, Reassignment — proses resign dan PHK
- Reports: Attendance, Leave, Employee — laporan operasional

**Finance Components**
- Payroll: Index, Generate, Detail, Publish, BulkGenerate — siklus penggajian
- Loans: Pending, Installments, Report — manajemen pinjaman
- Reimbursements: Pending, Report — manajemen reimbursemen
- Reports: Payroll, Tax — laporan finansial

**Admin Components**
- Settings: Company, Attendance, Leave, Branding, Security, System — konfigurasi sistem
- Users: Index, Create, Edit — manajemen akun pengguna
- KnowledgeBase: Index, Create, Edit, Chat — manajemen KnowledgeBase AI
- ActivityLog: Index — audit trail

**Reusable Components**
- Notifications — daftar notifikasi in-app
- Search — pencarian global dengan pg_trgm
- ApprovalTimeline — timeline persetujuan multi-level
- DataTable — tabel interaktif dengan sorting, filtering, pagination
- FileUpload — upload file dengan validasi dan progress bar
- FaceCapture — komponen kamera untuk face recognition
- GpsLocator — komponen GPS dengan peta dan status radius

**Navigasi**

Navigasi desktop menggunakan sidebar berbasis peran dengan 5 layout berbeda: app (default), ess (Employee), hrd (HR Manager), finance (Finance), dan admin (Super Admin). Navigasi mobile PWA menggunakan bottom navigation dengan 4 tab: Beranda, Absensi, Inbox, Profil. Implementasi navigasi menggunakan @can('permission') di Blade, bukan @role('role'), untuk memastikan permission-based access control.

### 3.2 Application Layer

#### 3.2.1 Service Classes

Application layer mengandung 8 service classes yang mengimplementasikan business logic sesuai PRD:

**PayrollCalculatorService**
Service ini menangani seluruh kalkulasi penggajian:
- `calculateProratedSalary(Employee $employee, string $periodYearMonth): float` — Menghitung gaji prorata berdasarkan hari kerja aktual dibagi hari kerja efektif. Hari kerja efektif dihitung dengan countWorkingDays() yang mengecualikan weekend dan holidays. Division by zero protection mengembalikan 0.0 jika totalWorkingDays = 0.
- `calculatePTKP(Employee $employee): float` — Menghitung Penghasilan Tidak Kena Pajak berdasarkan status marital dan jumlah anak (family_details dengan relationship = CHILD). Nilai PTKP bersumber dari CompanySetting.
- `getTERCategory(Employee $employee): string` — Menentukan kategori TER (A/B/C) berdasarkan PTKP: A untuk TK/0 dan TK/1, B untuk TK/2, TK/3, K/0, K/1, C untuk K/2 dan K/3.
- `calculatePPh21(float $grossIncome, string $category): float` — Menghitung PPh 21 menggunakan metode TER per bulan. Mengambil rate dari tabel tax_configs berdasarkan kategori dan rentang penghasilan.
- `calculateBPJS(Employee $employee, float $grossIncome): array` — Menghitung iuran BPJS Kesehatan (4% employer + 1% employee) dan BPJS Ketenagakerjaan (JHT 3.7%+2%, JP 2%+1%, JKK 0.24%, JKM 0.30%). Menggunakan ceiling dari tabel bpjs_configs.
- `calculateOvertimePay(Overtime $overtime, Employee $employee): float` — Menghitung upah lembur dengan rate tiered per UU Cipta Kerja: weekday jam pertama 1.5x, jam berikutnya 2x; hari libur 8 jam pertama 2x, jam 9-10 3x, jam 11+ 4x. Upah per jam = (gaji pokok + tunjangan tetap) / 173.
- `countWorkingDays(Carbon $start, Carbon $end): int` — Menghitung hari kerja efektif (Senin-Jumat, minus holidays, minus unpaid leave approved). Mengambil holidays sebagai flat array sekali untuk optimasi.
- `calculateThrProrated(Employee $employee, float $monthlySalary, int $monthsWorked): float` — THR prorata untuk karyawan yang belum 12 bulan.
- `calculatePesanggon(Employee $employee, TerminationType $type, ?string $phkVariant): float` — Pesangon sesuai UU Cipta Kerja.
- `calculateLeaveCashOut(Employee $employee): float` — Uang pengganti cuti yang tidak diambil saat resign.
- `calculateUangKompensasi(Employee $employee): float` — Uang kompensasi PKWT.
- `calculateUangPenghargaanMasaKerja(Employee $employee): float` — Uang penghargaan masa kerja.

**AttendanceService**
Service ini menangani seluruh alur presensi:
- `clockIn(Employee $employee, float $lat, float $lng, ?string $faceEmbedding, bool $isWfa, ?string $wfaNote): Attendance` — Proses clock-in dengan 3 tier verifikasi: Face Recognition (Tier 1) → GPS + PIN (Tier 2) → Manual Request (Tier 3). Mode WFO mewajibkan GPS + Face. Mode WFA melewati GPS tapi mewajibkan catatan minimal 20 karakter dan face recognition.
- `clockOut(Employee $employee, float $lat, float $lng, ?string $faceEmbedding): Attendance` — Proses clock-out dengan verifikasi face recognition. Melempar NotClockedInException jika belum clock-in. Melempar AlreadyClockedOutException jika sudah clock-out.
- `validateGPS(float $lat, float $lng, Branch $branch): bool` — Validasi GPS menggunakan Haversine formula. Menghitung jarak antara koordinat karyawan dan koordinat kantor. Melempar GeofenceViolationException jika jarak > radius branch. Melempar AntiFakeGPSException jika is_mocked = true atau accuracy > 100m.
- `validateFace(string $liveEmbedding, string $storedEmbedding): float` — Verifikasi face embedding menggunakan cosine distance (pgvector <=> operator). Threshold maksimum 0.15 (≈ similarity 85%). Melempar FaceNotRegisteredException jika employee belum memiliki face_embedding.
- `handleWFA(Attendance $attendance): void` — Mengirim notifikasi ke supervisor untuk approval WFA. Auto-approve setelah wfa_auto_approve_days hari kerja (default 3, configurable).
- `linkOvertimeToAttendance(Attendance $attendance): void` — Observer method yang mencari approved overtime untuk employee di tanggal yang sama dan menghubungkannya ke attendance.

**LeaveService**
Service ini menangani manajemen cuti:
- `calculateWorkDays(Carbon $start, Carbon $end, DayType $dayType): float` — Menghitung hari kerja antara dua tanggal, mengecualikan Sabtu, Minggu, dan holidays. morning/afternoon = 0.5 hari, full_day = 1.0 hari.
- `validateLeaveQuota(Employee $employee, LeaveType $type, float $days): bool` — Memvalidasi bahwa sisa kuota cuti mencukupi. Kuota hanya divalidasi saat submit, bukan d deduct. Melempar BusinessRuleException jika tidak mencukupi.
- `applyLeave(Leave $leave): Leave` — Mend deduct kuota cuti (leave_balances.used += total_days). Hanya dipanggil setelah full approval (L1 + L2). Melempar BusinessRuleException jika available() < $days.
- `initializeBalance(Employee $employee, int $year): void` — Membuat leave_balance untuk tahun pertama dengan quota prorata berdasarkan join_date.
- `carryForward(Employee $employee, int $fromYear, int $toYear): void` — Memindahkan sisa cuti maksimal 3 hari ke tahun berikutnya. Sisa yang tidak dipindahkan hangus 31 Maret.

**ApprovalService**
Service ini menangani alur persetujuan multi-level:
- `createApprovalWorkflow(Model $approvable): void` — Membuat rantai approval berdasarkan hierarki organisasi. Level 1: direct supervisor (parent_id). Level 2: HR Manager. Jika parent_id = NULL, skip Level 1 langsung ke Level 2.
- `approve(Approval $approval, string $notes): void` — Menyetujui approval pada level tertentu. Setelah approve, memeriksa checkAllApproved(). Jika semua level sudah approve, status entitas diubah menjadi approved dan efek samping dijalankan (deduct quota untuk cuti, hitung overtime).
- `reject(Approval $approval, string $reason): void` — Menolak approval. Status entitas langsung menjadi rejected, regardless of level. Notifikasi rejection dikirim ke pemohon.
- `checkAllApproved(Model $approvable): bool` — Memeriksa apakah semua level approval sudah approved. Iterasi seluruh approval records terkait.
- `getDirectApprover(Employee $employee): Employee` — Mendapatkan atasan langsung dari employee (parent_id). Jika parent_id NULL, mengembalikan HR Manager default.

#### 3.2.2 Jobs

**GenerateEmployeePayrollJob**
- Queue: payroll_high (prioritas tinggi)
- Tries: 3 kali percobaan
- Timeout: 120 detik
- Backoff: [10, 30, 60] detik — interval eksponensial
- handle(): void — Memanggil PayrollCalculatorService untuk menghitung seluruh komponen gaji (basic salary, allowance, overtime, PPh 21, BPJS, loan deduction, attendance penalty). Membuat atau memperbarui record Payroll. Generate PDF payslip. Update status.
- failed(): void — Mencatat error ke log dan mengirim notifikasi ke Finance admin melalui SendNotificationJob.

**ProcessKnowledgeBaseEmbedding**
- Queue: default
- Tries: 2 kali percobaan
- Timeout: 300 detik (5 menit) untuk PDF besar
- handle(): void — Mengekstrak teks dari PDF, melakukan chunking (~60 token dengan overlap 10 token), generate embedding via Gemini text-embedding-004, menyimpan chunk + embedding ke tabel knowledge_bases.
- extractText(): string — Mengekstrak teks dari file PDF menggunakan library parser.
- chunking(string $fullText): array — Memotong teks menjadi chunk-chunk kecil dengan overlap untuk mempertahankan konteks.
- generateEmbedding(string $chunk): vector — Mengirim chunk ke Gemini API dan mendapatkan vector embedding 768 dimensi.

#### 3.2.3 Commands

**AttendanceDetectAlphaCommand**
- Signature: `attendance:detect-alpha`
- Deskripsi: Mendeteksi karyawan yang alpha (tidak hadir tanpa keterangan)
- Schedule: setiap hari pukul 23:59 (dailyAt)
- handle(): void — Mencari employee dengan status ACTIVE yang tidak memiliki record attendance hari ini, tidak memiliki leave approved, dan tanggal tersebut bukan holiday. Membuat record attendance dengan status `absent`. Mencatat log untuk setiap deteksi.

**LeaveResetQuotaCommand**
- Signature: `leave:reset-quota`
- Deskripsi: Mereset kuota cuti tahunan
- Schedule: setiap 1 Januari 00:00 (yearOn)
- handle(): void — Mereset leave_balances.used = 0 untuk semua employee. Menerapkan carry_forward maksimal 3 hari dari tahun sebelumnya. Sisa cuti yang tidak dibawa hangus (carry_forward_deadline = 31 Maret).

#### 3.2.4 Notifications

Seluruh notification class mengimplementasikan ShouldQueue untuk pengiriman async melalui antrian. Setiap notification mengirim melalui 2 channel: mail (SMTP) dan database (tabel notifications).

- **LeaveRequestSubmitted** — Dikirim ke approver L1 (Manager) saat karyawan mengajukan cuti. Berisi detail cuti (jenis, tanggal, total hari, alasan).
- **LeaveApproved** — Dikirim ke karyawan saat cuti disetujui penuh (L1 + L2). Berisi informasi bahwa cuti telah approved.
- **LeaveRejected** — Dikirim ke karyawan saat cuti ditolak. Berisi alasan penolakan.
- **PayrollPublished** — Dikirim ke seluruh karyawan saat payroll dipublikasikan. Berisi notifikasi bahwa slip gaji tersedia.
- **ApprovalOverdue** — Dikirim ke approver jika approval belum ditindaklanjuti dalam waktu tertentu. Escalation ke atasan approver jika perlu.
- **NewDeviceLogin** — Dikirim ke karyawan dan HRD saat login dari perangkat baru yang belum terverifikasi.

### 3.3 Domain Layer

#### 3.3.1 Models

Domain layer terdiri dari 29+ Eloquent models yang merepresentasikan entitas bisnis:

**Master Data Models**
- **User** — Akun pengguna dengan autentikasi Laravel Fortify. Traits: SoftDeletes, HasRoles (Spatie), TwoFactorAuthenticatable, HasApiTokens (Sanctum). Relasi: belongsTo Company, hasOne Employee.
- **Company** — Identitas legal perusahaan. Fields: name, phone, email, website, npwp (encrypted), code, logo, is_active. Relasi: hasMany Branch, hasMany User.
- **Branch** — Cabang perusahaan dengan data geofence. Fields: name, address, latitude, longitude, radius (default 100m), is_main, is_active. Relasi: belongsTo Company, hasMany Department.
- **Department** — Unit organisasi. Fields: name, code (unique), description, is_active. SoftDeletes. Relasi: belongsTo Branch, hasMany Position.
- **Position** — Jabatan dengan informasi kompensasi. Fields: name, code (unique), grade, basic_salary, allowance_jabatan, is_active. SoftDeletes. Relasi: belongsTo Department, hasMany Employee.
- **Shift** — Jadwal kerja. Fields: name, start_time, end_time, late_tolerance_minutes, is_active. Relasi: hasMany Employee, hasMany Attendance.
- **ShiftSchedule** — Penugasan shift ke employee per tanggal. Unique: (employee_id, date). Relasi: belongsTo Employee, belongsTo Shift.
- **Holiday** — Hari libur nasional. Unique: date. Fields: date, name, is_active.
- **LeaveType** — Jenis cuti. Fields: name, code (unique), quota (default 12), is_paid, deducts_from_quota, is_active.

**Employee Data Models**
- **Employee** — Data inti karyawan dengan perlindungan PII. Fields: user_id (FK unique), parent_id (FK self — manager), company_id, branch_id, department_id, position_id, shift_id, employee_number (unique), full_name, nik (encrypted + blind index), phone (encrypted + blind index), npwp (encrypted + blind index), bank_account_number (encrypted + blind index), marital_status, gender, blood_type, status (EmployeeStatus), employment_type (EmploymentType), birth_date, join_date, resign_date, face_embedding (vector(128)), pin (hashed), photo, education_level, institution_name, major, graduation_year, salary_type. Hidden: ['face_embedding', 'pin', 'nik', 'phone', 'npwp', 'bank_account_number']. SoftDeletes.
- **FamilyDetail** — Data keluarga dengan enkripsi. Fields: nik (encrypted), name, relationship (FamilyRelationship), gender, birth_date, phone (encrypted), address (encrypted), job, is_emergency.
- **Device** — Perangkat karyawan. Fields: device_uuid (unique), device_type, device_name, browser, os, is_verified, verified_at, last_used_at.

**Transaction Models**
- **Attendance** — Record presensi harian. Fields: employee_id, shift_id (nullable), date (unique per employee), clock_in, clock_out, lat_in, long_in, clock_in_is_mocked, clock_in_accuracy, lat_out, long_out, clock_out_is_mocked, clock_out_accuracy, verification_method, face_similarity_score, clock_out_verification_method, clock_out_face_similarity_score, photo_selfie_in, photo_selfie_out, status (AttendanceStatus), exception_type, is_wfa, status_wfa (WfaStatus), wfa_note, late_minutes. SoftDeletes.
- **Leave** — Pengajuan cuti. Fields: employee_id, leave_type_id, start_date, end_date, day_type (DayType), total_days, reason, proof_file, status (RequestStatus), rejection_reason. SoftDeletes.
- **LeaveBalance** — Saldo kuota cuti. Unique: (employee_id, leave_type_id, year). Fields: quota (decimal 4.1), used (decimal 4.1), carry_forward (max 3), carry_forward_deadline.
- **Overtime** — Pengajuan lembur. Fields: employee_id, attendance_id (nullable), date, start_time, end_time, description, total_hours, amount, status (RequestStatus), rejection_reason. SoftDeletes.
- **Approval** — Approval multi-level polymorphic. Fields: approvable_type, approvable_id, approver_id (employee), level (ApprovalLevel), status (ApprovalStatus), notes, approved_at.
- **Payroll** — Slip gaji. Unique: (employee_id, period). Fields: basic_salary, total_allowance, gross_salary, overtime_pay, pph21, bpjs_health, bpjs_employment, loan_deduction, attendance_penalty, total_deduction, net_salary, status (PayrollStatus). SoftDeletes.
- **PayrollItem** — Item detail payroll (allowance/deduction). Fields: name, amount, type.
- **PayrollAdjustment** — Koreksi payroll untuk bulan berikutnya. Fields: amount (positive/negative), reason, applied_to_period.
- **Reimbursement** — Klaim reimbursemen. Fields: category_id, title, expense_date, amount, description, receipt_file, status. SoftDeletes.
- **Loan** — Pinjaman/kasbon (V2). Fields: amount, tenor_months, monthly_installment, status. SoftDeletes.
- **LoanInstallment** — Cicilan pinjaman. Fields: amount_paid, installment_number, due_date, paid_at, status.

**KnowledgeBase Models**
- **KnowledgeBase** — Dokumen dan chunk embedding untuk RAG. Polymorphic morph knowledgeable. Fields: title, content (chunked text), embedding (vector(768)), metadata (json), category (KnowledgeBaseCategory), status (KnowledgeBaseStatus), source_document, page_number.

**Configuration & Log Models**
- **CompanySetting** — Key-value configuration. Unique: key. Fields: key, value (json), description.
- **TaxConfig** — Konfigurasi tarif PPh 21 TER. Fields: ter_category, min_income, max_income, rate.
- **BpjsConfig** — Konfigurasi tarif BPJS. Fields: name (bpjs_type), employer_rate, employee_rate, ceiling.
- **ActivityLog** — Audit trail (Spatie Activity Log). Polymorphic subject + causer.
- **Asset** — Manajemen aset (V2). Fields: name, serial_number, category, status (AssetStatus). SoftDeletes.
- **AssetHandover** — Serah terima aset.
- **PerformanceReview** — Review kinerja (V2).

#### 3.3.2 Enums

HRConnect memiliki 33 PHP 8.1 backed enums yang terbagi dalam 2 kategori:

**16 Status Enums (dengan method color())**
Status enums memiliki method color() yang mengembalikan nama warna Flux UI (hanya 5 semantic names: success, warning, danger, info, zinc):

- **ApprovalStatus**: pending(→info), approved(→success), rejected(→danger)
- **AssetStatus**: available(→success), assigned(→info), maintenance(→warning), disposed(→zinc)
- **AttendanceStatus**: on_time(→success), late(→warning), early(→info), holiday(→info), permission(→info), absent(→danger), missed_clock_in(→danger), missed_clock_out(→danger)
- **EmployeeStatus**: active(→success), inactive(→zinc), resigned(→warning), terminated(→danger), deceased(→zinc)
- **EmploymentType**: permanent(→success), contract(→info), probation(→warning), intern(→info)
- **KnowledgeBaseStatus**: processing(→info), ready(→success), error(→danger)
- **LoanInstallmentStatus**: pending(→info), paid(→success), overdue(→danger)
- **LoanStatus**: pending(→info), approved(→success), rejected(→danger), active(→info), paid_off(→success), cancelled(→zinc)
- **MaritalStatus**: single(→info), married(→success), divorced(→zinc), widowed(→zinc)
- **PayrollItemType**: allowance(→success), deduction(→danger)
- **PayrollStatus**: draft(→info), published(→success), paid(→success)
- **ReimbursementStatus**: pending(→info), approved(→success), rejected(→danger), paid(→success)
- **RequestStatus**: pending(→info), approved_l1(→warning), approved(→success), rejected(→danger), cancelled(→zinc)
- **TerCategory**: A(→info), B(→warning), C(→danger)
- **TerminationType**: resign(→info), dismissed(→danger), deceased(→zinc), contract_end(→warning)
- **WfaStatus**: pending(→info), approved(→success), rejected(→danger)

**17 Classification Enums (tanpa color())**
Classification enums TIDAK memiliki method color() untuk menghindari visual noise ("pasar malam" effect). Sebagai gantinya, jika perlu display logika, menggunakan method seperti weight() atau label():

- **ApprovalLevel**: values 1 (L1_SUPERVISOR), 2 (L2_HR_MANAGER), 3, 4
- **BloodType**: A+, A-, B+, B-, O+, O-, AB+, AB-
- **BpjsType**: kesehatan, jht, jp, jkk, jkm
- **CompanySettingType**: geodata, branding, attendance, leave, payroll, system
- **DayType**: full_day, morning, afternoon
- **DeviceType**: desktop, mobile, tablet
- **EducationLevel**: sd, smp, sma, smk, diploma, bachelor, master, doctorate, other
- **FamilyRelationship**: spouse, parent, child, sibling, friend, other
- **Gender**: L, P
- **HandoverCategory**: document, asset, data, access, responsibility
- **KnowledgeBaseCategory**: hr_policy, it_guide, general, finance, other
- **LeaveQuotaReset**: yearly, monthly, one_time
- **NotificationType**: attendance, leave, payroll, system, approval, reminder
- **ResignationReason**: personal, better_offer, relocation, health, other
- **SalaryType**: monthly, hourly, daily
- **ShiftScheduleType**: regular, rotating, custom
- **VerificationMethod**: face, pin, gps, manual

#### 3.3.3 Traits & Concerns

- **App\Traits\Approvable** — Trait untuk model yang membutuhkan multi-level approval (Leave, Overtime, Reimbursement). Menyediakan method: approvals() morphMany, isFullyApproved(): bool, getPendingApprovals(): Collection.
- **App\Traits\ManagesWorkDays** — Trait untuk kalkulasi hari kerja. Menyediakan method: calculateWorkDays($start, $end, $dayType): float, isWeekend($date): bool, isHoliday($date): bool.
- **App\Concerns\PasswordValidationRules** — Concern untuk aturan password. Menyediakan static method: rules(): array yang mengembalikan aturan: min:8, regex untuk uppercase+lowercase+number+symbol, confirmed.
- **App\Concerns\ProfileValidationRules** — Concern untuk validasi profil. Menyediakan static method: rules(): array untuk update profil (name, email, phone, address).

### 3.4 Infrastructure Layer

#### 3.4.1 Database Infrastructure

HRConnect menggunakan PostgreSQL 15+ sebagai database utama dengan 3 ekstensi kunci:

- **pgvector**: Digunakan untuk vector similarity search pada face recognition (embedding 128 dimensi pada employees.face_embedding) dan KnowledgeBase RAG (embedding 768 dimensi pada knowledge_bases.embedding). Query cosine distance menggunakan operator <=>. Indeks HNSW untuk performa pencarian vektor yang cepat.
- **pg_trgm**: Digunakan untuk fuzzy text search pada KnowledgeBase sebagai fallback saat Gemini API tidak tersedia. Juga digunakan untuk pencarian global (karyawan, dokumen, kebijakan) dengan similarity threshold. Indeks GIN pada kolom content.
- **pgcrypto**: Digunakan untuk fungsi kriptografi tingkat database sebagai lapisan keamanan tambahan.

Koneksi database menggunakan PDO via Laravel Eloquent ORM. Konfigurasi koneksi: host (Neon cloud), port 5432, SSL/TLS required. Connection pooling dikelola oleh Neon untuk koneksi serverless yang efisien.

#### 3.4.2 Queue Infrastructure

Queue driver: database (tabel jobs + failed_jobs dari Laravel). Tidak ada Redis dependency untuk menjaga kesederhanaan infrastruktur.

Dua queue dengan prioritas berbeda:
- **payroll_high**: Untuk GenerateEmployeePayrollJob dan ProcessLoanInstallments. Prioritas lebih tinggi karena payroll terkait batas waktu penggajian.
- **default**: Untuk ProcessKnowledgeBaseEmbedding, SendNotificationJob, dan job lainnya.

Worker dijalankan dengan perintah: `php artisan queue:work --queue=default,payroll_high,notifications`

#### 3.4.3 Cache Infrastructure

Cache driver: database (CACHE_STORE=database). Tidak mendukung Cache::tags() karena menggunakan database driver. Semua operasi cache menggunakan Cache::forget('key') per-key.

Cache key format mengikuti konvensi colon separator: `module:identifier:key` (contoh: `holidays:2026`, `tax_configs`, `settings:attendance_penalty_per_day`).

Kebijakan cache:
- Array primitif (bukan Eloquent Object) untuk menghindari memory leak
- TTL configurable per key (1 menit sampai 1 bulan)
- Invalidsi manual via observer saat data berubah

#### 3.4.4 File Storage

Dua disk storage:

**Private disk** (storage/app/private):
- payslips/{period}/{employee_id}.pdf — Slip gaji yang di-generate sekali saat publish
- knowledgebase/{filename}.pdf — Dokumen PDF yang diupload HRD
- leaves/proofs/ — Bukti cuti (foto surat dokter, dll)
- reimbursements/ — Bukti reimbursemen
- profile_photos/ — Foto profil karyawan

**Public disk** (storage/app/public):
- avatars/ — Foto profil yang bisa diakses publik
- logos/ — Logo perusahaan

Akses file private hanya melalui authenticated routes dengan middleware otorisasi. Sensitive files menggunakan private disk dengan visibility private.

#### 3.4.5 External API Integration

**Gemini API** — Dua layanan berbeda:
- text-embedding-004: Model embedding 768 dimensi untuk KnowledgeBase RAG. Digunakan di ProcessKnowledgeBaseEmbedding job.
- Gemini 2.5 Flash: LLM untuk RAG chat. Menerima context chunks dari pgvector + query user, mengembalikan jawaban dengan referensi sumber.

**Google OAuth 2.0**:
- Provider: Google Identity Services
- Flow: OAuth 2.0 authorization code
- Scopes: email, profile
- Fitur: Login + auto-verifikasi email, link ke akun existing via email match

**SMTP**:
- Development: Mailtrap (email tidak benar-benar terkirim)
- Production: SES (AWS) atau Mailgun
- Port: 587 dengan TLS encryption

## 4. Desain Detail

### 4.1 Desain Database (Data Design)

#### 4.1.1 Entity Relationship Diagram

ERD referensi tersedia di `docs/architecture/erd.dbml` yang merupakan source of truth untuk schema database. Database terdiri dari 48 tabel yang mencakup master data (9 tabel), transaksi (12 tabel), konfigurasi (4 tabel), log dan keamanan (3 tabel), Laravel default (5 tabel), wilayah Indonesia (4 tabel), dan modul V2 (5 tabel).

Hubungan antar entitas utama:
- Company 1:N Branch
- Branch 1:N Department
- Department 1:N Position
- Position 1:N Employee
- Employee 1:1 User
- Employee 1:N Attendance, Leave, LeaveBalance, Overtime, Payroll, FamilyDetail, Device, Approval
- Employee N:1 Employee (self-referencing parent_id untuk manager)
- Shift 1:N Employee, Attendance, ShiftSchedule
- LeaveType 1:N Leave, LeaveBalance

#### 4.1.2 Key Tables Schema

**employees**
| Kolom | Tipe | Nullable | Konstrain | Keterangan |
|-------|------|----------|-----------|------------|
| id | bigint | NO | PK, auto-increment | |
| user_id | bigint | NO | FK → users.id, UNIQUE | |
| parent_id | bigint | YES | FK → employees.id | Manager/approver |
| company_id | bigint | NO | FK → companies.id | |
| branch_id | bigint | NO | FK → branches.id | |
| department_id | bigint | NO | FK → departments.id | |
| position_id | bigint | NO | FK → positions.id | |
| shift_id | bigint | YES | FK → shifts.id | Default shift |
| province_id | char(2) | YES | FK → indonesia_provinces.id | |
| city_id | char(4) | YES | FK → indonesia_cities.id | |
| district_id | char(6) | YES | FK → indonesia_districts.id | |
| village_id | char(10) | YES | FK → indonesia_villages.id | |
| employee_number | varchar(50) | NO | UNIQUE | Auto-generated |
| full_name | varchar(255) | NO | | |
| nik | text | NO | Encrypted + blind index | |
| phone | text | NO | Encrypted + blind index | |
| npwp | text | YES | Encrypted + blind index | |
| bank_account_number | text | YES | Encrypted + blind index | |
| bank_name | varchar(100) | YES | | |
| marital_status | marital_status | NO | DEFAULT 'single' | |
| gender | gender | NO | | |
| blood_type | blood_type | YES | | |
| status | employee_status | NO | DEFAULT 'active' | |
| employment_type | employment_type | NO | DEFAULT 'permanent' | |
| birth_date | date | NO | | |
| join_date | date | NO | | |
| resign_date | date | YES | | |
| face_embedding | vector(128) | YES | | pgvector |
| pin | varchar(60) | YES | | bcrypt hash |
| photo | varchar(255) | YES | | File path |
| created_at | timestamp | YES | | |
| updated_at | timestamp | YES | | |
| deleted_at | timestamp | YES | | SoftDeletes |

**attendances**
| Kolom | Tipe | Nullable | Konstrain | Keterangan |
|-------|------|----------|-----------|------------|
| id | bigint | NO | PK, auto-increment | |
| employee_id | bigint | NO | FK → employees.id | |
| shift_id | bigint | YES | FK → shifts.id | |
| date | date | NO | UNIQUE (employee_id, date) | |
| clock_in | timestamp | NO | | |
| clock_out | timestamp | YES | | |
| lat_in | decimal(10,8) | YES | | |
| long_in | decimal(11,8) | YES | | |
| clock_in_is_mocked | boolean | NO | DEFAULT false | |
| lat_out | decimal(10,8) | YES | | |
| long_out | decimal(11,8) | YES | | |
| verification_method | varchar(50) | YES | | face/pin/manual |
| face_similarity_score | decimal(5,2) | YES | | Clock-in score |
| clock_out_verification_method | varchar(50) | YES | | Terpisah |
| clock_out_face_similarity_score | decimal(5,2) | YES | | Clock-out score |
| photo_selfie_in | varchar(255) | YES | | |
| photo_selfie_out | varchar(255) | YES | | |
| status | attendance_status | NO | DEFAULT 'on_time' | |
| is_wfa | boolean | NO | DEFAULT false | |
| status_wfa | wfa_status | YES | | |
| wfa_note | text | YES | | Min 20 chars |
| late_minutes | int | NO | DEFAULT 0 | |
| deleted_at | timestamp | YES | | SoftDeletes |

**payrolls**
| Kolom | Tipe | Nullable | Konstrain | Keterangan |
|-------|------|----------|-----------|------------|
| id | bigint | NO | PK, auto-increment | |
| employee_id | bigint | NO | FK → employees.id | |
| period | varchar(7) | NO | UNIQUE (employee_id, period) | YYYY-MM |
| basic_salary | decimal(15,2) | NO | | |
| total_allowance | decimal(15,2) | NO | | |
| gross_salary | decimal(15,2) | NO | | |
| overtime_pay | decimal(15,2) | NO | DEFAULT 0 | |
| pph21 | decimal(15,2) | NO | DEFAULT 0 | |
| bpjs_health | decimal(15,2) | NO | DEFAULT 0 | |
| bpjs_employment | decimal(15,2) | NO | DEFAULT 0 | |
| loan_deduction | decimal(15,2) | NO | DEFAULT 0 | |
| attendance_penalty | decimal(15,2) | NO | DEFAULT 0 | |
| total_deduction | decimal(15,2) | NO | | |
| net_salary | decimal(15,2) | NO | | |
| status | payroll_status | NO | DEFAULT 'draft' | |
| deleted_at | timestamp | YES | | SoftDeletes |

**knowledge_bases**
| Kolom | Tipe | Nullable | Konstrain | Keterangan |
|-------|------|----------|-----------|------------|
| id | bigint | NO | PK, auto-increment | |
| knowledgeable_type | varchar(255) | YES | | Polymorphic |
| knowledgeable_id | bigint | YES | | Polymorphic |
| title | varchar(255) | NO | | |
| content | text | YES | | Chunked text |
| embedding | vector(768) | YES | | pgvector, HNSW index |
| metadata | json | YES | | |
| category | knowledge_base_category | NO | DEFAULT 'general' | |
| status | knowledge_base_status | NO | DEFAULT 'processing' | |
| source_document | varchar(255) | YES | | |
| page_number | int | YES | | |

#### 4.1.3 Indexes & Constraints

**pgvector Indexes:**
- `knowledge_bases_embedding_idx` — HNSW index pada knowledge_bases.embedding (vector(768)) untuk cosine similarity search pada RAG query
- `employees_face_embedding_idx` — HNSW index pada employees.face_embedding (vector(128)) untuk face recognition

**pg_trgm Indexes:**
- `knowledge_bases_content_gin_idx` — GIN index pada knowledge_bases.content untuk trigram search fallback
- `employees_full_name_gin_idx` — GIN index pada employees.full_name untuk pencarian nama fuzzy

**Unique Constraints:**
- `payrolls_employee_id_period_unique` — (employee_id, period) — satu payroll per employee per bulan
- `shift_schedules_employee_id_date_unique` — (employee_id, date) — satu jadwal shift per hari
- `leave_balances_employee_id_leave_type_id_year_unique` — (employee_id, leave_type_id, year) — satu balance per jenis cuti per tahun
- `holidays_date_unique` — satu hari libur per tanggal
- `leave_types_code_unique` — kode unik jenis cuti
- `employees_employee_number_unique` — nomor induk karyawan unik

**CipherSweet Blind Indexes:**
- `nik_hash` — blind index untuk pencarian NIK terenkripsi
- `phone_hash` — blind index untuk pencarian nomor telepon terenkripsi
- `npwp_hash` — blind index untuk pencarian NPWP terenkripsi
- Berlaku untuk tabel: employees (nik, phone, npwp), family_details (nik, phone), companies (npwp)

### 4.2 Desain Antarmuka

#### 4.2.1 API Contracts

HRConnect menyediakan 43 endpoint REST API dengan prefix `/api/v1/`. Autentikasi menggunakan Bearer token via Laravel Sanctum. Semua endpoint kecuali login dan forgot-password memerlukan token.

**Kategori Endpoint:**

**Auth (4 endpoints):**
- `POST /api/v1/auth/login` — Login dengan email + password
- `POST /api/v1/auth/forgot-password` — Lupa password
- `POST /api/v1/auth/refresh` — Refresh token (24 jam expiry)
- `POST /api/v1/auth/logout` — Logout + revoke token

**Employees (4 endpoints):**
- `GET /api/v1/employees` — Daftar karyawan (paginated, filterable)
- `POST /api/v1/employees` — Create karyawan baru
- `PUT /api/v1/employees/{id}` — Update data karyawan
- `GET /api/v1/employees/{id}` — Detail karyawan

**Attendance (3 endpoints):**
- `POST /api/v1/attendance/clock-in` — Clock-in dengan GPS + Face/PIN
- `POST /api/v1/attendance/clock-out` — Clock-out dengan Face/PIN
- `GET /api/v1/attendance/history` — Riwayat presensi (paginated)

**Leave (3 endpoints):**
- `POST /api/v1/leaves` — Ajukan cuti
- `GET /api/v1/leaves` — Daftar cuti (filter by status, date range)
- `PUT /api/v1/leaves/{id}/cancel` — Batalkan pengajuan (hanya status pending)

**Overtime (3 endpoints):**
- `POST /api/v1/overtimes` — Ajukan lembur
- `GET /api/v1/overtimes` — Daftar lembur
- `PUT /api/v1/overtimes/{id}/cancel` — Batalkan pengajuan

**Payroll (3 endpoints):**
- `POST /api/v1/payrolls/generate` — Generate payroll untuk periode tertentu
- `GET /api/v1/payrolls` — Daftar payroll (filter by period, employee)
- `GET /api/v1/payrolls/{id}/download` — Download slip gaji PDF (wajib re-enter password)

**Approval (2 endpoints):**
- `POST /api/v1/approvals/{id}/approve` — Setujui approval
- `POST /api/v1/approvals/{id}/reject` — Tolak approval

**KnowledgeBase (2 endpoints):**
- `POST /api/v1/knowledgebase/upload` — Upload PDF untuk RAG
- `POST /api/v1/knowledgebase/chat` — Chat dengan KnowledgeBase AI

**Notifications (2 endpoints):**
- `GET /api/v1/notifications` — Daftar notifikasi (read/unread)
- `PUT /api/v1/notifications/{id}/read` — Tandai notifikasi telah dibaca

**Profile (2 endpoints):**
- `GET /api/v1/profile` — Data profil employee
- `PUT /api/v1/profile` — Update profil

**Response Format:**
```json
{
  "status": "success",
  "message": "Clock in berhasil",
  "data": {},
  "errors": {},
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 95
  }
}
```

**Rate Limiting:**
| Kategori | Limit | Window |
|----------|-------|--------|
| Auth (login, register) | 5 request | 1 menit |
| Face validation | 10 request | 1 menit |
| Clock-In/Out | 5 request | 5 menit |
| KnowledgeBase Chat | 20 request | 1 menit |
| General API | 60 request | 1 menit |

#### 4.2.2 Livewire Component Interaction

Interaksi pengguna dengan Livewire components mengikuti pola berikut:

**Form Submission:**
- Input binding: `wire:model.blur` untuk validasi real-time saat leave focus
- Submit: `wire:submit.prevent` untuk mencegah reload dan menangani form via AJAX
- Loading states: `wire:loading` untuk indikator loading saat request diproses
- Validasi error: ditampilkan per-field menggunakan `@error('field')` dengan Flux UI styling

**Navigation:**
- SPA-like navigation: `wire:navigate` untuk transisi halaman tanpa reload penuh
- Lazy loading: `wire:init` untuk load data saat komponen pertama kali di-render
- Polling: `wire:poll.10s` untuk update real-time pada dashboard dan notifikasi

**Modal & Dialog:**
- Konfirmasi: Flux UI modal component `flux:modal` untuk konfirmasi sebelum approve/reject
- Detail: Slide-over panel untuk menampilkan detail record tanpa navigasi
- Form: Modal form untuk create/edit yang tidak memerlukan halaman terpisah

**Event Handling:**
- Component-to-component: `$dispatch` untuk komunikasi antar komponen (contoh: setelah approve, dispatch event untuk refresh list)
- Browser events: `wire:mouseenter`, `wire:click.outside` untuk interaksi frontend
- Alpine.js integration: `x-on:wire:message` untuk menangani event Livewire dari Alpine.js

#### 4.2.3 Integration Interfaces

**Google OAuth Integration:**
- Protocol: OAuth 2.0 Authorization Code Flow
- Scopes: `openid`, `email`, `profile`
- Callback URL: `{APP_URL}/auth/google/callback`
- Flow: User klik "Login dengan Google" → redirect ke consent screen Google → callback dengan authorization code → tukar dengan access token → cari/create user berdasarkan email → login
- Link akun: Jika email sudah terdaftar dengan password, user akan di-link (tidak dibuat duplikat)

**Gemini API Integration:**
- REST API via HTTPS dengan API key
- text-embedding-004: `POST https://generativelanguage.googleapis.com/v1beta/models/text-embedding-004:embedContent` dengan input text → output vector 768 dimensi
- Gemini 2.5 Flash: `POST https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent` dengan system prompt + context chunks + user query → output jawaban + citations
- Timeout: 30 detik untuk embedding, 60 detik untuk generate content
- Fallback: pg_trgm fuzzy search jika Gemini API tidak tersedia (timeout atau error)

**SMTP Email Integration:**
- Mail facade Laravel dengan Mailtrap driver (development) atau SES/Mailgun driver (production)
- Queue: Semua email dikirim via queue (ShouldQueue) untuk menghindari blocking
- Template: Email menggunakan Blade templates dengan layout responsive
- Attachment: E-payslip dikirim sebagai attachment (PDF) atau link download

### 4.3 Desain Keamanan

#### 4.3.1 CipherSweet Encryption

HRConnect menggunakan CipherSweet untuk enkripsi data sensitif dengan backend Sodium (PHP 8.5 built-in). Setiap field terenkripsi memiliki blind index untuk pencarian tanpa mendekripsi.

**Data Terenkripsi:**
| Model | Field | Blind Index | Tujuan |
|-------|-------|-------------|--------|
| Employee | nik | nik_hash | Pencarian NIK tanpa dekripsi |
| Employee | phone | phone_hash | Pencarian nomor telepon |
| Employee | npwp | npwp_hash | Pencarian NPWP |
| Employee | bank_account_number | - | Enkripsi saja (jarang dicari) |
| Company | npwp | npwp_hash | Pencarian NPWP perusahaan |
| FamilyDetail | nik | nik_hash | Perlindungan data keluarga |
| FamilyDetail | phone | phone_hash | |
| FamilyDetail | address | - | Enkripsi alamat |

**Konfigurasi:**
- Backend: Sodium (libsodium, default PHP 8.5)
- Key: disimpan di env `CIPHERSWEET_KEY` (base64 encoded, 32 bytes)
- Generate key: `php artisan ciphersweet:generate-key`
- Pencarian: `Employee::where('nik_hash', $nikHash)->first()` — tidak pernah query kolom terenkripsi langsung

#### 4.3.2 RBAC (Spatie Permission)

HRConnect menggunakan Spatie Permission untuk Role-Based Access Control dengan 5 roles dan 40+ permissions.

**5 Roles:**
| Role | Slug | Deskripsi |
|------|------|-----------|
| Super Admin | super-admin | Akses penuh ke seluruh sistem |
| HR Manager | hr-manager | Operasional SDM, approval final |
| Finance | finance | Payroll dan laporan keuangan |
| Manager | manager | Approval level 1, monitoring tim |
| Employee | employee | ESS: clock-in/out, pengajuan, profil |

**Permission Matrix:**

| Permission | Super Admin | HR Manager | Finance | Manager | Employee |
|------------|:-----------:|:----------:|:-------:|:-------:|:--------:|
| view_dashboard | ✅ | ✅ | ✅ | ✅ | ✅ |
| view_companies | ✅ | ❌ | ❌ | ❌ | ❌ |
| manage_companies | ✅ | ❌ | ❌ | ❌ | ❌ |
| view_branches | ✅ | ✅ | ❌ | ❌ | ❌ |
| manage_branches | ✅ | ❌ | ❌ | ❌ | ❌ |
| view_departments | ✅ | ✅ | ❌ | ❌ | ❌ |
| manage_departments | ✅ | ❌ | ❌ | ❌ | ❌ |
| view_positions | ✅ | ✅ | ❌ | ❌ | ❌ |
| manage_positions | ✅ | ❌ | ❌ | ❌ | ❌ |
| view_employees | ✅ | ✅ | ✅ | ✅ (tim) | ❌ |
| manage_employees | ✅ | ✅ | ❌ | ❌ | ❌ |
| view_attendances | ✅ | ✅ (semua) | ❌ | ✅ (tim) | ✅ (diri) |
| manage_attendances | ✅ | ✅ | ❌ | ❌ | ❌ |
| view_leaves | ✅ | ✅ (semua) | ❌ | ✅ (tim) | ✅ (diri) |
| approve_leaves_l1 | ✅ | ❌ | ❌ | ✅ | ❌ |
| approve_leaves_l2 | ✅ | ✅ | ❌ | ❌ | ❌ |
| view_overtimes | ✅ | ✅ (semua) | ❌ | ✅ (tim) | ✅ (diri) |
| approve_overtimes_l1 | ✅ | ❌ | ❌ | ✅ | ❌ |
| approve_overtimes_l2 | ✅ | ✅ | ❌ | ❌ | ❌ |
| view_reimbursements | ✅ | ✅ (semua) | ✅ | ✅ (tim) | ✅ (diri) |
| manage_reimbursements | ✅ | ❌ | ❌ | ❌ | ❌ |
| approve_reimbursements_l1 | ✅ | ❌ | ❌ | ✅ | ❌ |
| approve_reimbursements_l2 | ✅ | ❌ | ✅ | ❌ | ❌ |
| approve_wfa | ✅ | ❌ | ❌ | ✅ | ❌ |
| view_wfa_pending | ✅ | ✅ | ❌ | ✅ (tim) | ❌ |
| view_loans | ✅ | ✅ | ✅ | ❌ | ✅ (diri) |
| manage_loans | ✅ | ❌ | ✅ | ❌ | ❌ |
| view_assets | ✅ | ✅ | ❌ | ❌ | ✅ (diri) |
| manage_assets | ✅ | ✅ | ❌ | ❌ | ❌ |
| view_payslip | ✅ | ❌ | ✅ | ❌ | ✅ (diri) |
| download_payslip | ✅ | ❌ | ✅ | ❌ | ✅ (diri) |
| process_payroll | ✅ | ❌ | ✅ | ❌ | ❌ |
| view_payrolls | ✅ | ❌ | ✅ | ❌ | ✅ (diri) |
| manage_tax_configs | ✅ | ❌ | ✅ | ❌ | ❌ |
| manage_bpjs_configs | ✅ | ❌ | ✅ | ❌ | ❌ |
| view_activity_logs | ✅ | ✅ | ❌ | ❌ | ❌ |
| manage_settings | ✅ | ❌ | ❌ | ❌ | ❌ |
| manage_company_settings | ✅ | ❌ | ❌ | ❌ | ❌ |
| manage_roles | ✅ | ❌ | ❌ | ❌ | ❌ |
| manage_holidays | ✅ | ❌ | ❌ | ❌ | ❌ |
| manage_shifts | ✅ | ❌ | ❌ | ❌ | ❌ |
| manage_knowledgebase | ✅ | ✅ | ❌ | ❌ | ❌ |
| view_knowledgebase | ✅ | ✅ | ❌ | ❌ | ❌ |

**Implementasi:**
- Policy classes untuk setiap model: EmployeePolicy, AttendancePolicy, LeavePolicy, PayrollPolicy, ApprovalPolicy, LoanPolicy, ReimbursementPolicy, KnowledgeBasePolicy
- Middleware: `CheckRoleMiddleware` dan `CheckPermissionMiddleware` untuk route protection
- Blade directives: `@can('permission')` untuk conditional rendering
- Livewire: `$this->authorize('action', $model)` di setiap method

#### 4.3.3 2FA TOTP

**Metode:** Time-based One-Time Password (TOTP) via Laravel Fortify
**Window:** 30 detik per kode
**Recovery Codes:** 8 kode alfanumerik 8 karakter, di-hash di database
**Enforcement:** Wajib untuk role HR Manager, Finance, dan Super Admin. Opsional untuk Employee dan Manager.
**Setup Flow:**
1. User mengaktifkan 2FA di halaman Settings → Security
2. QR code ditampilkan untuk di-scan dengan Google Authenticator / Authy
3. User mengkonfirmasi dengan memasukkan kode TOTP
4. 8 recovery codes di-generate dan ditampilkan (sekali lihat, download sebagai TXT)
5. Saat login: setelah password valid, redirect ke halaman two-factor-challenge
6. Input kode TOTP atau recovery code → berhasil login
7. Habis recovery codes: hubungi Super Admin untuk reset 2FA manual
8. Audit trail: event `2fa.enabled`, `2fa.recovery_used`, `2fa.reset_by_admin`

#### 4.3.4 Password Policy

**Kompleksitas:**
- Minimum 8 karakter
- Wajib mengandung huruf besar (A-Z)
- Wajib mengandung huruf kecil (a-z)
- Wajib mengandung angka (0-9)
- Simbol/karakter spesial disarankan (tidak wajib tapi ada di strength indicator)

**Siklus Hidup:**
- Expiry: 90 hari (kolom password_changed_at digunakan untuk tracking)
- Reminder: 7 hari sebelum expiry, notifikasi in-app + email
- History: Tidak boleh menggunakan 3 password terakhir
- Force Change: Saat pertama login (password_changed = true) — redirect ke halaman ganti password
- Reset: Via Fortify reset password flow (email verified) atau admin reset

#### 4.3.5 Exception Handling

HRConnect menggunakan custom exceptions dengan HTTP codes spesifik sesuai error-handling-strategy.md:

| Exception Class | HTTP Code | Skenario |
|-----------------|-----------|----------|
| BusinessRuleException | 422 | Pelanggaran aturan bisnis (kuota cuti habis, payroll locked, dll) |
| FaceNotRegisteredException | 422 | Karyawan belum registrasi wajah |
| ValidationException | 422 | Validasi form request gagal |
| NotClockedInException | 409 | Clock-out tanpa clock-in (state conflict) |
| AlreadyClockedInException | 409 | Double clock-in |
| AlreadyClockedOutException | 409 | Double clock-out |
| AntiFakeGPSException | 422 | GPS terdeteksi palsu (is_mocked) |
| GeofenceViolationException | 422 | Di luar radius geofence |
| InvalidPinException | 422 | PIN absensi salah |
| AuthenticationException | 401 | Tidak terautentikasi / token invalid |
| AuthorizationException | 403 | Izin tidak mencukupi |
| ModelNotFoundException | 404 | Resource tidak ditemukan |

**Error Response Format:**
```json
{
  "status": "error",
  "message": "Sisa kuota cuti tidak mencukupi",
  "errors": {
    "leave_type": ["Kuota cuti tahunan hanya tersisa 3 hari"]
  }
}
```

**Fallback Tiers (Attendance):**
Tier 1: Face Recognition (face-api.js + pgvector)
Tier 2: GPS + PIN (6 digit)
Tier 3: Manual Request (ke supervisor)

### 4.4 Desain Sequence (Alur Utama)

#### 4.4.1 Clock-In Flow

**Mode WFO (Work From Office):**
1. Employee mengakses halaman clock-in via PWA
2. Browser meminta akses GPS → mendapatkan koordinat latitude/longitude
3. Browser mengaktifkan kamera → face-api.js mendeteksi wajah dan menghasilkan 128D embedding
4. Frontend mengirim POST request ke AttendanceController dengan data: mode=wfo, lat, lng, face_embedding
5. AttendanceController memanggil AttendanceService.clockIn()
6. AttendanceService.validateGPS(lat, lng, branch) — Haversine formula membandingkan jarak dengan radius branch
7. Jika GPS valid → FaceRecognitionService.validateFace(live, stored) — cosine distance via pgvector <=>
8. Jika face match (similarity >= 85%) → AttendanceService.createAttendance() → simpan record
9. AttendanceObserver.linkedOvertimeToAttendance() — cari overtime approved hari ini
10. Response: success dengan data attendance dan jarak dari kantor

**Mode WFA (Work From Anywhere):**
1. Employee toggle WFA, mengisi catatan minimal 20 karakter
2. GPS dilewati (tidak divalidasi), koordinat tetap disimpan sebagai catatan
3. Face recognition tetap wajib
4. Attendance tersimpan dengan is_wfa=true, status_wfa=pending
5. Notifikasi dikirim ke supervisor untuk approval
6. Jika auto-approve timeout (default 3 hari kerja): status_wfa=auto_approved

#### 4.4.2 Leave Approval Flow

1. Employee submit form cuti (leave_type, start_date, end_date, day_type, reason)
2. LeaveService.calculateTotalDays() — hitung hari kerja exlcude weekend + holiday
3. LeaveService.validateLeaveQuota() — validasi kuota tersedia (hanya cek, belum deduct)
4. Jika valid → create Leave dengan status pending
5. ApprovalService.createApprovalWorkflow(leave):
   a. Cari approver L1 (parent_id) → jika ada, buat Approval level 1
   b. Cari approver L2 (HR Manager) → buat Approval level 2
6. Notifikasi ke approver L1: "Ada pengajuan cuti baru dari [employee]"
7. Approver L1 approve/reject via halaman approval
8. Jika approve → notifikasi ke approver L2
9. Approver L2 approve/reject
10. Jika semua approve → LeaveService.applyLeave() → deduct quota (leave_balances.used += total_days)
11. Notifikasi ke employee: "Cuti Anda telah disetujui"

#### 4.4.3 Payroll Generation Flow

1. Finance membuka halaman Generate Payroll → pilih periode (YYYY-MM)
2. System membaca company_settings.payroll_cutoff_date (default 25)
3. System mengambil daftar employee aktif dengan join_date <= cutoff
4. Untuk setiap employee, dispatch GenerateEmployeePayrollJob ke queue payroll_high
5. Job memproses:
   a. PayrollCalculatorService.calculateProratedSalary() — gaji prorata
   b. PayrollCalculatorService.getTERCategory() — kategori A/B/C
   c. PayrollCalculatorService.calculatePPh21() — pajak
   d. PayrollCalculatorService.calculateBPJS() — iuran BPJS
   e. Hitung overtime_pay, loan_deduction, attendance_penalty
   f. Hitung GROSS - DEDUCTIONS = NET
   g. Simpan Payroll dengan status draft
   h. Generate PDF payslip
6. Semua job selesai → Payroll status = draft
7. Finance review → klik Publish
8. Payroll status = published (LOCKED PERMANENT — tidak bisa diubah)
9. Notifikasi ke semua employee: "Slip gaji periode [period] telah tersedia"
10. Employee download payslip via streaming (re-enter password wajib)

#### 4.4.4 RAG Chat Flow

1. User mengetik pertanyaan di halaman KnowledgeBase Chat
2. KnowledgeBaseController menerima POST request
3. System mengirim query ke Gemini text-embedding-004 → mendapatkan vector 768D
4. Vector similarity search di pgvector:
   ```sql
   SELECT content, source_document, page_number
   FROM knowledge_bases
   ORDER BY embedding <=> :queryEmbedding
   LIMIT 5
   ```
5. Ambil top-5 chunks sebagai context
6. Kirim context + user query ke Gemini 2.5 Flash:
   ```
   System: "Anda adalah asisten HR untuk PT 521 Teknologi Indonesia. Jawab pertanyaan berdasarkan konteks berikut. Jika tidak ada jawaban di konteks, katakan bahwa Anda tidak tahu."
   Context: [chunks]
   Question: [user query]
   ```
7. Gemini mengembalikan jawaban dengan referensi sumber
8. Response: { answer: "...", sources: [ {document: "...", page: N} ] }

**Fallback Flow (Gemini down):**
1. Jika Gemini API timeout atau error
2. Gunakan pg_trgm search: `WHERE content % 'query'`
3. Kembalikan hasil pencarian tanpa AI summarization
4. Log: "AI unavailable — fallback to pg_trgm"

### 4.5 Desain State Machine

#### Payroll States

```
                  ┌─────────┐
                  │  DRAFT  │
                  └────┬────┘
                       │ Generate + Review
                       ▼
                  ┌───────────┐
                  │ PUBLISHED │ ← LOCKED PERMANENT
                  └─────┬─────┘
                        │ Mark as Paid
                        ▼
                    ┌──────┐
                    │ PAID │ (terminal)
                    └──────┘
```

- **DRAFT**: Payroll awal setelah generate. Finance bisa review, koreksi melalui PayrollAdjustment (tidak bisa edit langsung).
- **PUBLISHED**: Payroll di-publish ke employee. **LOCKED** — tidak bisa diubah. Status ini memicu isLocked() = true. PDF payslip di-generate sekali.
- **PAID**: Payroll telah dibayarkan. Terminal state. Koreksi dilakukan via PayrollAdjustment untuk bulan berikutnya.

Transisi: DRAFT → PUBLISHED (via publish action oleh Finance). PUBLISHED → PAID (via mark paid action). Tidak ada reverse transition dari PUBLISHED ke DRAFT.

#### Reimbursement States

```
PENDING ──(L1 approve)──▶ APPROVED_L1 ──(L2 approve)──▶ APPROVED ──(payroll)──▶ PAID
   │                          │                            │
   │                          │                            └─(reject L2)──▶ REJECTED
   │                          └─(reject L1)──▶ REJECTED
   │
   └─(withdraw)──▶ CANCELLED
```

- **PENDING**: Pengajuan baru, menunggu approval L1 (Manager). Bisa di-withdraw oleh employee.
- **APPROVED_L1**: Disetujui oleh Manager, menunggu approval L2 (Finance).
- **APPROVED**: Disetujui penuh, menunggu masuk payroll.
- **PAID**: Masuk ke payroll dan dibayarkan. Terminal state.
- **REJECTED**: Ditolak (dari level mana pun). Terminal state — employee harus submit baru.
- **CANCELLED**: Dibatalkan oleh employee saat masih PENDING.

#### WFA States

```
                  ┌─────────────┐
                  │   PENDING   │ ← Saat clock-in WFA
                  └──────┬──────┘
                         │
              ┌──────────┼──────────┐
              │          │          │
              ▼          ▼          ▼
         ┌────────┐ ┌────────┐ ┌──────────────┐
         │APPROVED│ │REJECTED│ │AUTO_APPROVED │
         └────────┘ └────────┘ └──────────────┘
         (on_time)  (absent)   (after timeout)
```

- **PENDING**: WFA clock-in terjadi, menunggu review supervisor. Status attendance tetap on_time/late sesuai shift.
- **APPROVED**: Supervisor menyetujui WFA. Attendance normal.
- **REJECTED**: Supervisor menolak WFA. Attendance diubah menjadi absent. Notifikasi dikirim ke employee.
- **AUTO_APPROVED**: Jika tidak di-review dalam wfa_auto_approve_days (default 3 hari kerja). Attendance tetap normal.

## 5. Matriks Ketelusuran Kebutuhan (Requirements Traceability)

| Modul | SRS Ref | Service | Model(s) | Controller/Livewire | Jobs/Commands |
|-------|---------|---------|----------|---------------------|---------------|
| Presensi | §3.2.3 | AttendanceService, GeofenceService, FaceRecognitionService | Attendance, Employee, Shift, Branch | ClockIn, ClockOut, History, Summary (Livewire) | attendance:detect-alpha (Command) |
| Cuti | §3.2.4 | LeaveService, ApprovalService | Leave, LeaveType, LeaveBalance, Approval | Create, History, Quota (Livewire), Pending (HRD) | leave:reset-quota (Command) |
| Lembur | §3.2.5 | AttendanceService (linkOvertime), ApprovalService | Overtime, Attendance | Create (Livewire), Pending (HRD) | - |
| Payroll | §3.2.6 | PayrollCalculatorService | Payroll, PayrollItem, PayrollAdjustment, TaxConfig, BpjsConfig | Generate, Detail, Publish, BulkGenerate (Livewire) | GenerateEmployeePayrollJob |
| Approval Workflow | §3.2.7 | ApprovalService | Approval | ApprovalTimeline, Pending, All, Escalated (Livewire) | - |
| KnowledgeBase AI | §3.2.8 | FaceRecognitionService (embedding via Service) | KnowledgeBase | Chat, Create, Edit, Index (Livewire) | ProcessKnowledgeBaseEmbedding |
| Notifikasi | §3.2.9 | - | Notification | Notifications (Livewire) | SendNotificationJob |
| Autentikasi | §3.2.1 | Fortify Service Provider | User | Login, Register, ForgotPassword, ResetPassword, TwoFactorChallenge (Fortify) | - |
| Manajemen Karyawan | §3.2.2 | EmployeeTerminationService | Employee, FamilyDetail, Device | Index, Create, Edit, Show, BulkUpload (Livewire) | - |
| Manajemen Shift | §3.2.3 (turun) | - | Shift, ShiftSchedule | Index, Schedule (Livewire) | - |
| Reimbursemen | §3.2.10 | ApprovalService | Reimbursement, ReimbursementCategory | ReimbursementRequest, Pending (Livewire) | - |
| Pinjaman (V2) | §3.2.11 | - | Loan, LoanInstallment | LoanRequest, Pending (Livewire) | ProcessLoanInstallments |
| Aset (V2) | §3.2.12 | - | Asset, AssetHandover | - | - |
| Performance Review (V2) | §3.2.13 | - | PerformanceReview | - | - |
| Settings | §3.2.14 | - | CompanySetting | Company, Attendance, Leave, Branding, Security, System (Livewire) | - |
| RBAC | §3.2.15 | - | Role, Permission | Users (Livewire) | RoleAndPermissionSeeder |
| Activity Log | §3.2.16 | - | ActivityLog | ActivityLog/Index (Livewire) | - |
| Device Management | §3.2.17 | DeviceDetectionService | Device | Devices (Livewire) | - |

## 6. Lampiran

### A. Deployment Diagram

Diagram deployment tersedia di `docs/architecture/deployment-diagram.md` yang menunjukkan arsitektur fisik sistem meliputi:
- PWA Client (Employee Device): Browser dengan face-api.js, Geolocation API, Service Worker
- VPS Server: Nginx web server, PHP 8.5 Runtime, Queue Worker, Cron Scheduler, File Storage
- Neon PostgreSQL Cloud: Database dengan pgvector, pg_trgm, pgcrypto
- External APIs: Gemini API, Google OAuth, SMTP Email Service

### B. Class Diagram

Diagram class tersedia di `docs/architecture/class-diagram.md` yang mencakup:
- 29 Models dengan relasi, property, method, dan annotations
- 4 Service Classes dengan method signatures
- 2 Jobs dengan konfigurasi queue, timeout, dan backoff
- 2 Commands dengan schedule dan logic
- 6 Notifications dengan channel mail + database
- 17 Enums dengan values
- Errata notes untuk implementasi

### C. Sequence Diagrams

Diagram sequence tersedia di `docs/architecture/sequence-diagrams.md` yang mencakup 5 skenario utama:
1. Clock-In Flow (WFO + WFA) — interaksi antara Employee, AttendanceController, AttendanceService, Geolocation API, face-api.js, PostgreSQL
2. Leave Approval Flow — alur pengajuan hingga approval L1 dan L2
3. Payroll Generate Flow — dispatch job, kalkulasi, PDF generation
4. RAG Chat Flow — embedding, vector search, LLM response
5. KnowledgeBase PDF Upload — upload, extract, chunking, embedding

### D. Activity Diagrams

Diagram aktivitas tersedia di `docs/architecture/activity-diagrams.md` yang mencakup alur kerja:
1. Clock-In/Out WFO + WFA dengan decision points untuk validasi
2. Leave Request & Approval dengan swimlane Employee, System, Manager, HR Manager

### E. Data Flow Diagram

Diagram aliran data tersedia di `docs/architecture/data-flow-diagram.md`:
- Level 0: Context Diagram dengan entitas eksternal dan aliran data utama
- Level 1: Decomposition untuk proses Attendance, Leave Management, dan Payroll

### F. Folder Structure

Struktur folder lengkap tersedia di `docs/architecture/folder-structure.md` dengan total 690+ file dan direktori yang terorganisir dalam:
- 33 Enums, 31 Models, 8 Services, 6 Observers, 9 Jobs, 12 Notifications
- 69 Livewire Components, 15 Form Requests, 5 Middleware, 8 Policies
- 48 Migration files, 12 Seeders, 13 Language files (Bahasa Indonesia)
- 110+ Blade templates, 8 JavaScript files
- 49 Feature Tests, 14 Unit Tests

### G. Referensi Errata & Known Bugs

Berdasarkan AGENTS.md, berikut bug kritis yang harus diperhatikan saat implementasi:

- **B1**: PayrollCalculatorService menggunakan delete() bukan forceDelete() saat regenerasi → unik constraint violation
- **B2**: Payroll race condition — perlu lockForUpdate() saat cek existing payroll
- **B3**: Leave quota di-deduct saat submit, bukan saat approval → pindahkan ke ApprovalService
- **B4**: LeaveBalance->deduct() tidak cek negative → tambah validasi available() < $days
- **B5**: family_details_count menghitung semua relasi, bukan hanya CHILD → filter relationship
- **B6**: Hardcoded /22 working days → gunakan countWorkingDays()
- **B7**: Overtime weekday flat 1.5x → tiered 1.5x/2x per UU Cipta Kerja
- **B12**: FaceNotRegisteredException tidak di-catch → fallback ke PIN
- **C1**: ApprovalLevel comparison menggunakan integer bukan enum
- **C2**: Payroll softDelete menyebabkan unique constraint violation
- **C3**: Sanctum belum terinstall — HasApiTokens tidak ada di User
- **C4**: Permission enum + RoleAndPermissionSeeder belum dibuat
- **SEC-5**: BusinessRuleException harus return 422, bukan 500
- **ERR-001**: Overtime tiered rates (bukan flat rate)
- **ERR-002**: Leave quota deducted after full L2 approval
- **ERR-004**: Attendance verification 4 kolom terpisah untuk clock-in dan clock-out
- **CAT-001**: ApprovalLevel enum vs integer perbandingan
- **CAT-005**: Password expiry 90 hari (Security Config override PRD)
- **CAT-014**: PTKP values harus di CompanySetting, bukan hardcoded
- **CAT-018**: Defensive migration untuk pgvector/pg_trgm/pgcrypto dengan SQLite guard
- **CAT-019**: Jangan hardcode SQL state 23505, gunakan UniqueConstraintViolationException

---

*Dokumen ini disusun berdasarkan standar IEEE 1016 — Software Design Description.*
*Versi: 1.0 | Tanggal: 2026-05-31*
*Project: HRConnect — HRIS Enterprise untuk PT 521 Teknologi Indonesia*
