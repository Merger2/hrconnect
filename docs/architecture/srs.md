# SRS — Software Requirements Specification
## HRConnect — HRIS Enterprise

**Versi:** 1.0
**Tanggal:** 31 Mei 2026
**Status:** Draft untuk Validasi

**PT 521 Teknologi Indonesia**
**Laravel 13 + Livewire 4 + Flux UI 2 + PostgreSQL 15+**

---

## 1. Pendahuluan

### 1.1 Tujuan

Dokumen Software Requirements Specification (SRS) ini bertujuan untuk mendefinisikan secara lengkap dan terstruktur kebutuhan sistem perangkat lunak HRConnect — sistem Human Resource Information System (HRIS) Enterprise untuk PT 521 Teknologi Indonesia. Dokumen ini mengikuti standar IEEE 830-1998 dan berfungsi sebagai acuan utama dalam pengembangan, pengujian, validasi, dan pemeliharaan sistem. Kesepakatan antara pengembang, pengguna, dan pemangku kepentingan didokumentasikan secara eksplisit dalam dokumen ini untuk memastikan keselarasan pemahaman dan ekspektasi terhadap produk akhir.

### 1.2 Ruang Lingkup

HRConnect adalah sistem HRIS Enterprise berbasis web dengan arsitektur client-server yang mencakup modul-modul berikut:

1. **Autentikasi & Keamanan** — Login email/password, Google OAuth SSO, 2FA TOTP, manajemen sesi, kebijakan password, RBAC (Spatie Permission).
2. **Master Data** — Manajemen Perusahaan, Cabang (dengan GPS geofence), Departemen, Jabatan, Shift, Hari Libur, Pengaturan Perusahaan, Roles & Permissions.
3. **Presensi (Attendance)** — Clock-in/out dengan GPS Geofencing (Haversine) + Face Recognition (face-api.js, FaceNet 128D), dukungan WFO/WFA, deteksi alpha via cron, peringatan keterlambatan kronis, manajemen perangkat.
4. **Manajemen Cuti (Leave)** — 7 tipe cuti, perhitungan otomatis hari kerja (exclude weekend/holiday), kuota pro-rated tahun pertama, carry-forward, approval multi-level.
5. **Manajemen Lembur (Overtime)** — Pengajuan sebelum lembur, kalkulasi tiered (1.5x/2x weekday, 2x/3x/4x holiday), observer link ke attendance, approval multi-level.
6. **Penggajian (Payroll)** — Perhitungan GROSS/DEDUCTIONS/NET, PPh 21 TER (kategori A/B/C), BPJS (Kesehatan, JHT, JP, JKK, JKM), pro-rated salary, denda kehadiran, payroll lock permanen, E-Payslip PDF, adjustment pasca-lock.
7. **Approval Workflow** — Multi-level polymorphic approval, matrix approval per modul, timeout reminder, withdraw, rejection permanen.
8. **KnowledgeBase AI (RAG)** — Upload PDF, chunking, embedding via Gemini text-embedding-004, vector search pgvector, Q&A via Gemini 2.5 Flash, fallback pg_trgm.
9. **Notifikasi** — In-App (database), Email (SMTP), matrix notifikasi 20+ event.
10. **Dashboard & Laporan** — Dashboard per role, rekap absensi/cuti/payroll, laporan pajak & kepatuhan (1721-A1, BPJS).

Fitur yang didefer ke V2: Loan/Kasbon, Asset Management, Performance Review, WhatsApp Notifications, drag-and-drop shift calendar, DJP API integration, ClamAV antivirus, offline PWA.

### 1.3 Definisi, Akronim, dan Singkatan

Berikut adalah istilah kunci yang digunakan dalam dokumen ini:

| Istilah | Definisi |
|---------|----------|
| **Alpha** | Karyawan yang tidak melakukan clock-in dan tidak memiliki cuti/izin pada hari kerja |
| **BPJS** | Badan Penyelenggara Jaminan Sosial — program jaminan sosial nasional Indonesia (Kesehatan, Ketenagakerjaan) |
| **CipherSweet** | Library enkripsi searchable encryption untuk field PII di database PostgreSQL |
| **ESS** | Employee Self-Service — fitur yang memungkinkan karyawan mengelola data pribadi, absensi, dan pengajuan secara mandiri |
| **FaceNet** | Model deep learning Google untuk face recognition yang menghasilkan 128-dimensional embedding vector |
| **Fortify** | Laravel Fortify — backend autentikasi headless untuk Laravel (login, register, 2FA, password reset) |
| **Geofence** | Batas geografis virtual yang mendeteksi apakah perangkat berada dalam radius tertentu dari suatu lokasi |
| **Haversine** | Rumus matematika untuk menghitung jarak antara dua titik koordinat di permukaan bola (bumi) |
| **HNSW** | Hierarchical Navigable Small World — algoritma indexing untuk approximate nearest neighbor search di pgvector |
| **HRIS** | Human Resource Information System — sistem informasi sumber daya manusia |
| **JHT** | Jaminan Hari Tua — program BPJS Ketenagakerjaan untuk tabungan pensiun (3.7% employer, 2% employee) |
| **JKK** | Jaminan Kecelakaan Kerja — program BPJS Ketenagakerjaan (0.24% employer, 0% employee) |
| **JKM** | Jaminan Kematian — program BPJS Ketenagakerjaan (0.30% employer, 0% employee) |
| **JP** | Jaminan Pensiun — program BPJS Ketenagakerjaan (2% employer, 1% employee) |
| **Kasbon** | Pinjaman karyawan yang dipotong dari gaji secara cicilan |
| **KnowledgeBase** | Basis pengetahuan AI dengan Retrieval Augmented Generation (RAG) untuk dokumen HRD |
| **L1/L2** | Level approval — Level 1 (Manager/Supervisor), Level 2 (HR Manager atau Finance) |
| **NIK** | Nomor Induk Kependudukan — nomor identitas penduduk Indonesia (disimpan terenkripsi) |
| **NPWP** | Nomor Pokok Wajib Pajak — nomor identitas perpajakan (disimpan terenkripsi) |
| **PDP** | Perlindungan Data Pribadi — Undang-Undang No. 27 Tahun 2022 tentang Perlindungan Data Pribadi |
| **Payslip** | Slip gaji elektronik dalam format PDF yang menampilkan rincian pendapatan dan potongan |
| **Pesangon** | Uang kompensasi pemutusan hubungan kerja sesuai UU Cipta Kerja |
| **pgvector** | Ekstensi PostgreSQL untuk vector similarity search (digunakan untuk face embedding 128D dan KB embedding 768D) |
| **pg_trgm** | Ekstensi PostgreSQL untuk trigram text search (fallback saat AI down) |
| **PPh 21** | Pajak Penghasilan Pasal 21 — pajak atas penghasilan karyawan |
| **PKWT** | Perjanjian Kerja Waktu Tertentu — karyawan kontrak |
| **PKWTT** | Perjanjian Kerja Waktu Tidak Tertentu — karyawan tetap |
| **PTKP** | Penghasilan Tidak Kena Pajak — batas penghasilan yang tidak dikenakan PPh 21 |
| **PWA** | Progressive Web Application — aplikasi web yang dapat diinstal di perangkat mobile |
| **RAG** | Retrieval Augmented Generation — teknik AI yang menggabungkan pencarian informasi dengan generative LLM |
| **RBAC** | Role-Based Access Control — kontrol akses berbasis peran (Spatie Permission) |
| **SoftDeletes** | Fitur Laravel untuk soft delete (record ditandai dihapus, bukan dihapus secara fisik) |
| **SPT** | Surat Pemberitahuan Tahunan — laporan pajak tahunan |
| **TER** | Tarif Efektif Rata-rata — metode perhitungan PPh 21 per bulan berdasarkan kategori A/B/C |
| **THR** | Tunjangan Hari Raya — bonus tahunan yang dibayarkan sebelum hari raya keagamaan |
| **Uang Kompensasi** | Kompensasi untuk karyawan PKWT saat kontrak berakhir (Pasal 61A UU Cipta Kerja) |
| **Uang Penghargaan Masa Kerja** | Penghargaan untuk karyawan tetap yang di-PHK dengan masa kerja tertentu |
| **WFA** | Work From Anywhere — mode kerja jarak jauh tanpa validasi GPS geofence |
| **WFO** | Work From Office — mode kerja di kantor dengan validasi GPS geofence |
| **2FA** | Two-Factor Authentication — autentikasi dua faktor menggunakan TOTP |
| **3NF** | Third Normal Form — normalisasi database tingkat 3 untuk menghilangkan redundansi data |
| **Flux UI** | Komponen UI Laravel/Livewire premium untuk Tailwind CSS |
| **Alpine.js** | JavaScript framework minimalis untuk interaktivitas frontend |
| **Gemini** | Model AI Google yang digunakan untuk embedding (text-embedding-004) dan LLM (Gemini 2.5 Flash) |
| **Spatie** | Package Laravel untuk manajemen role & permission (Spatie Permission) dan activity log |
| **Pest** | PHP testing framework yang digunakan untuk unit test, feature test, dan browser test |

### 1.4 Referensi

Dokumen-dokumen berikut digunakan sebagai referensi dan sumber informasi dalam penyusunan SRS ini:

1. **PRD v3.1** — Product Requirements Document HRConnect (docs/PRD.md)
2. **Use Case Diagram** — Diagram use case UML seluruh modul (docs/architecture/use-case-diagram.md)
3. **Activity Diagrams** — Diagram aktivitas per modul (docs/architecture/activity-diagrams.md)
4. **Data Flow Diagram** — Diagram aliran data Level 0 dan Level 1 (docs/architecture/data-flow-diagram.md)
5. **ERD** — Entity Relationship Diagram source of truth (docs/architecture/erd.dbml)
6. **Class Diagram** — Diagram class UML (docs/architecture/class-diagram.md)
7. **Sequence Diagrams** — Diagram sequence interaksi (docs/architecture/sequence-diagrams.md)
8. **Deployment Diagram** — Diagram deployment infrastruktur (docs/architecture/deployment-diagram.md)
9. **API Contracts** — 43 endpoint RESTful API (docs/api/api-contracts.md)
10. **Security Config** — Konfigurasi keamanan CipherSweet, 2FA, RBAC (docs/security/security-config.md)
11. **Error Handling Strategy** — Strategi penanganan error dan fallback (docs/security/error-handling-strategy.md)
12. **Caching Strategy** — Strategi caching, key naming, TTL (docs/security/caching-strategy.md)
13. **Testing Strategy** — Strategi pengujian unit, feature, browser (docs/testing/testing-strategy.md)
14. **Deployment Guide** — Panduan deployment VPS, SSL, CI/CD (docs/deployment/deployment-guide.md)
15. **Task Spec** — Spesifikasi eksekusi perbaikan (docs/planning/task.md)
16. **AGENTS.md** — Instruksi agent dan arsitektur proyek
17. **IEEE 830-1998** — Standar IEEE untuk Software Requirements Specification

### 1.5 Ikhtisar Dokumen

Dokumen SRS ini disusun dalam empat bagian utama. Bagian 1 (Pendahuluan) memberikan gambaran umum tentang tujuan, ruang lingkup, definisi, dan referensi. Bagian 2 (Gambaran Umum) menjelaskan perspektif produk, fungsi produk, karakteristik pengguna, batasan, asumsi, dan ketergantungan sistem. Bagian 3 (Kebutuhan Spesifik) merupakan inti dokumen yang mencakup kebutuhan antarmuka eksternal, kebutuhan fungsional per modul, kebutuhan kinerja, batasan desain, atribut sistem perangkat lunak (keamanan, keandalan, ketersediaan, pemeliharaan, portabilitas), dan kebutuhan basis data logis. Bagian 4 (Lampiran) berisi referensi ke diagram dan dokumen pendukung.

## 2. Gambaran Umum

### 2.1 Perspektif Produk

HRConnect adalah sistem HRIS Enterprise berbasis web dengan arsitektur client-server yang dirancang untuk melayani kebutuhan pengelolaan sumber daya manusia PT 521 Teknologi Indonesia. Sistem ini dibangun di atas platform Laravel 13 dengan Livewire 4 untuk server-side rendering, Flux UI 2 sebagai komponen UI, dan PostgreSQL 15+ sebagai database dengan ekstensi pgvector, pg_trgm, dan pgcrypto.

**Komponen Sistem:**

1. **PWA Client (Mobile-first)** — Aplikasi web progresif yang berjalan di browser smartphone (Chrome/Safari/Firefox). Komponen ini menangani:
   - Face detection dan embedding generation via face-api.js (FaceNet 128D, client-side)
   - Geolocation acquisition via browser Geolocation API
   - Anti-fake GPS detection (mocked flag, accuracy check)
   - Bottom navigation (Beranda, Absensi, Inbox, Profil)
   - Service Worker untuk cache statis dan push notification
   - Responsive design untuk mobile dan desktop

2. **Server Aplikasi (Laravel 13 + Livewire 4 + Flux UI)** — Server backend yang menangani:
   - Livewire 4 components untuk UI interaktif server-side
   - Flux UI 2 untuk komponen antarmuka (buttons, modals, tables, forms, date-pickers)
   - Tailwind CSS v4 untuk styling utility-first
   - Alpine.js untuk interaktivitas frontend ringan
   - Laravel Fortify untuk autentikasi backend
   - Spatie Permission untuk RBAC (5 roles, 40+ permissions)
   - Service layer pattern (AttendanceService, PayrollCalculatorService, dll.)
   - Queue worker untuk async processing (database driver)

3. **Database Server (PostgreSQL 15+)** — Database relasional dengan ekstensi khusus:
   - pgvector untuk vector similarity search (face embedding 128D, KB embedding 768D)
   - pg_trgm untuk trigram text search (fallback KnowledgeBase)
   - pgcrypto untuk enkripsi tingkat database
   - HNSW index untuk performa vector search

4. **Queue Worker (Database Queue)** — Queue worker untuk pemrosesan async:
   - Queue `default`: Email, notifikasi, knowledgebase embedding
   - Queue `payroll_high`: Payroll generation (prioritas tinggi)
   - Queue `notifications`: Notifikasi in-app dan email

5. **External APIs** — Layanan eksternal yang diintegrasikan:
   - Gemini API (text-embedding-004 untuk embedding, Gemini 2.5 Flash untuk LLM RAG)
   - Google OAuth 2.0 untuk SSO
   - SMTP Server (Mailtrap dev / SES production) untuk email
   - Google reCAPTCHA (opsional) untuk form protection

**Arsitektur Komunikasi:**
- Seluruh komunikasi menggunakan HTTPS dengan TLS 1.3
- HTTP/2 untuk optimasi performa
- RESTful API (43 endpoint) untuk PWA client
- Livewire wire:submit/wire:click untuk interaksi form
- Database queue untuk async job processing
- Session database driver untuk manajemen sesi

### 2.2 Fungsi Produk

Berdasarkan Use Case Diagram (docs/architecture/use-case-diagram.md), sistem HRConnect memiliki 46 use case utama yang dikelompokkan dalam 10 modul:

**Modul Master Data (UC1-UC9):**
- UC1: Mengelola Perusahaan (CRUD companies, aktivasi/deaktivasi)
- UC2: Mengelola Cabang (CRUD branches, konfigurasi GPS lat/lng/radius)
- UC3: Mengelola Departemen (CRUD departments per branch)
- UC4: Mengelola Jabatan (CRUD positions, basic_salary, grade, allowance)
- UC5: Mengelola Karyawan (CRUD employees, face enrollment, data PII terenkripsi)
- UC6: Mengelola Shift (CRUD shifts, start/end time, late tolerance)
- UC7: Mengelola Hari Libur (CRUD holidays, bulk import CSV)
- UC8: Mengelola Pengaturan Perusahaan (key-value settings, branch-level overrides)
- UC9: Mengelola Roles & Permissions (RBAC, Spatie Permission)

**Modul Presensi (UC10-UC13):**
- UC10: Clock-In/Out (WFO: GPS+Face, WFA: Face+Note, PIN fallback)
- UC11: Melihat Riwayat Absensi (filter tanggal, status, export)
- UC12: Mengelola Data Absensi (koreksi manual, approval exception)
- UC13: Deteksi Alpha via Cron Job (daily 23:59)

**Modul Manajemen Cuti (UC14-UC19):**
- UC14: Mengajukan Cuti (7 tipe, validasi kuota, upload bukti)
- UC15: Melihat Riwayat Cuti (filter, status approval)
- UC16: Menyetujui Cuti Level 1 (Manager)
- UC17: Menyetujui Cuti Level 2 (HR Manager)
- UC18: Mengelola Tipe Cuti (leave types master data)
- UC19: Melihat Saldo Cuti (quota, used, carry-forward)

**Modul Lembur (UC20-UC24):**
- UC20: Mengajukan Lembur (H-1, max 4 jam/hari)
- UC21: Melihat Riwayat Lembur
- UC22: Menyetujui Lembur Level 1 (Manager)
- UC23: Menyetujui Lembur Level 2 (HR Manager)
- UC24: Menautkan Lembur ke Attendance (Observer saat clock-out)

**Modul Penggajian (UC25-UC29):**
- UC25: Generate Payroll (async queue, perhitungan otomatis)
- UC26: Melihat Riwayat Payroll (per employee, filter period)
- UC27: Download E-Payslip (PDF 2 kolom, password confirmation)
- UC28: Mengelola Konfigurasi Pajak (tax_configs, TER A/B/C)
- UC29: Mengelola Konfigurasi BPJS (bpjs_configs, rates, ceilings)

**Modul Approval Workflow (UC30-UC33):**
- UC30: Membuat Approval Workflow (polymorphic, multi-level)
- UC31: Menyetujui Request (L1/L2 dengan notes)
- UC32: Menolak Request (dengan rejection reason)
- UC33: Memeriksa Status Approval (pending/approved/rejected)

**Modul KnowledgeBase AI (UC34-UC37):**
- UC34: Upload PDF KnowledgeBase (max 10MB, chunking)
- UC35: Chat dengan AI RAG (query + Gemini response + sources)
- UC36: Mengelola KnowledgeBase (status, kategori, edit, delete)
- UC37: Proses Embedding via Background Job

**Modul Notifikasi (UC38-UC40):**
- UC38: Melihat Notifikasi In-App (inbox, filter)
- UC39: Menerima Email Notifikasi (SMTP)
- UC40: Mengirim Alert Login Perangkat Baru (email + in-app)

**Modul Autentikasi (UC41-UC45):**
- UC41: Login Email/Password (Laravel Fortify)
- UC42: Login Google OAuth (SSO)
- UC43: Mengaktifkan/Menonaktifkan 2FA (TOTP, recovery codes)
- UC44: Memaksa Perubahan Password (force_change_password)
- UC45: Melihat Activity Logs (Spatie Activitylog)

**Modul Dashboard (UC46):**
- UC46: Melihat Dashboard (per role: employee, manager, HR, finance, admin)

### 2.3 Karakteristik Pengguna

Sistem HRConnect melayani lima peran (role) pengguna dengan hak akses dan tanggung jawab yang berbeda:

| Role | Slug | Jumlah (Estimasi) | Deskripsi | Hak Akses Utama |
|------|------|-------------------|-----------|-----------------|
| Super Admin | super-admin | 1-2 orang | Pemilik akses penuh ke sistem, master data, konfigurasi, user management | Semua fitur: manage companies, branches, departments, positions, employees, roles, settings, activity logs, payroll, approval final |
| HR Manager | hr-manager | 2-5 orang | Operasional SDM, approval final cuti/lembur, direktori karyawan, monitoring | Manage employees, view all attendances, approve L2 leaves/overtimes, manage leave types, manage knowledgebase, view activity logs |
| Finance | finance | 2-3 orang | Payroll processing, laporan keuangan | Process payroll, view payrolls, manage tax & BPJS configs, approve L2 reimbursements, view/download payslips |
| Manager | manager | 10-30 orang | Approval Level 1, monitoring tim | Approve L1 leaves/overtimes, approve WFA, view team attendance/leaves/overtimes, dashboard tim |
| Employee | employee | 50-500 orang | Employee Self-Service (ESS) | Clock-in/out (WFO/WFA), submit leaves/overtime/reimbursement, view own attendance/leave/payslip history, chat AI KnowledgeBase, manage profile |

Setiap Manager secara otomatis memiliki hak akses Employee (multi-role inheritance). Super Admin memiliki semua permission yang ada.

### 2.4 Batasan (Constraints)

**Batasan Teknologi:**
1. **Framework & Bahasa:** Laravel 13 dengan PHP 8.5, bukan framework lain (Node.js, Python, dll.)
2. **Frontend:** Livewire 4 server-side rendering (bukan SPA React/Vue), Flux UI 2 + Tailwind CSS v4 + Alpine.js
3. **Database:** PostgreSQL 15+ sebagai satu-satunya database production (bukan MySQL/SQLite)
4. **Cache & Queue:** Database driver untuk cache dan queue (CACHE_STORE=database, QUEUE_CONNECTION=database). Redis TIDAK tersedia — tidak ada Cache::tags()
5. **PWA Mobile:** Mobile-first dengan bottom navigation; offline mode didefer ke V2 (IndexedDB)
6. **Bahasa Antarmuka:** 100% Bahasa Indonesia untuk seluruh UI, error messages, dan notifikasi

**Batasan Fungsional:**
7. **Fitur Didefer V2:** Loan/Kasbon (tabel ada, service incomplete), Asset Management, Performance Review, WhatsApp Notifications, drag-and-drop shift calendar, DJP API integration, ClamAV antivirus, offline PWA, auto-fetch holiday API
8. **No Offline Support:** MVP tidak mendukung operasi offline. Face recognition membutuhkan koneksi internet
9. **No Delegation:** Approval workflow tidak mendukung delegasi ke approver lain
10. **Single Company:** Sistem dirancang untuk satu perusahaan (multi-branch, bukan multi-company)

**Batasan Deployment:**
11. **Production Environment:** VPS dengan Nginx/Apache, Neon PostgreSQL serverless, SSL/TLS 1.3
12. **DB::prohibitDestructiveCommands()** aktif di production — migrate:fresh, db:wipe diblokir
13. **PostgreSQL Extensions Required:** pgvector, pg_trgm, pgcrypto harus diinstal sebelum migrasi
14. **Flux UI License:** Diperlukan FLUX_USERNAME dan FLUX_LICENSE_KEY untuk composer install

### 2.5 Asumsi dan Ketergantungan

**Asumsi:**
1. PostgreSQL dengan ekstensi pgvector, pg_trgm, dan pgcrypto sudah tersedia dan terkonfigurasi
2. Gemini API key sudah tersedia untuk layanan embedding (text-embedding-004) dan LLM (Gemini 2.5 Flash)
3. Google OAuth credentials (Client ID, Client Secret) sudah terdaftar dan dikonfigurasi
4. CipherSweet key sudah di-generate (`php artisan ciphersweet:generate-key`) untuk enkripsi data PII
5. Semua karyawan memiliki smartphone dengan kamera depan (minimal 2MP) dan GPS receiver
6. Karyawan memiliki koneksi internet minimal 3G untuk operasi real-time (clock-in, chat AI)
7. Browser yang digunakan mendukung face-api.js (WebGL), Geolocation API, dan Service Worker
8. Karyawan memiliki alamat email yang valid untuk notifikasi dan reset password
9. Karyawan sudah memiliki NIK, NPWP, dan nomor rekening bank (kecuali probation/intern)
10. Data master (departemen, jabatan, shift) sudah didefinisikan sebelum karyawan mulai menggunakan sistem

**Ketergantungan:**
1. Laravel 13 — dependensi framework inti (composer)
2. Livewire 4 — dependensi komponen interaktif (composer)
3. Flux UI 2 — dependensi komponen antarmuka (composer, memerlukan license key)
4. Spatie Permission — dependensi RBAC (composer)
5. Spatie Activitylog — dependensi audit trail (composer)
6. ParagonIE CipherSweet — dependensi enkripsi searchable (composer)
7. Gemini API — dependensi eksternal untuk embedding dan RAG (HTTP API)
8. Google OAuth 2.0 — dependensi eksternal untuk SSO (HTTP API)
9. SMTP Server — dependensi eksternal untuk email (Mailtrap/SES)
10. face-api.js — dependensi client-side face recognition (npm)
11. Alpine.js — dependensi interaktivitas frontend (npm)
12. pgvector — dependensi PostgreSQL extension
13. pg_trgm — dependensi PostgreSQL extension
14. pgcrypto — dependensi PostgreSQL extension

## 3. Kebutuhan Spesifik

### 3.1 Kebutuhan Antarmuka Eksternal

#### 3.1.1 Antarmuka Pengguna

Sistem HRConnect menyediakan antarmuka pengguna berbasis web yang responsif untuk desktop dan mobile:

**Desktop (≥1024px):**
- Navigasi sidebar vertikal dengan ikon dan teks
- Multi-role dashboard dengan widget informasi
- Tabel data dengan sorting, filtering, pagination
- Form input dengan validasi real-time
- Modal dan drawer untuk operasi CRUD
- Komponen Flux UI (buttons, cards, badges, tables, date-pickers, tooltips)

**Mobile PWA (<1024px):**
- Bottom navigation dengan 4 tab: Beranda, Absensi, Inbox, Profil
- Full-screen kamera preview untuk face recognition
- GPS indicator dengan status "Dalam Radius" / "Di Luar Radius"
- Skeleton loader saat loading data
- Pull-to-refresh untuk data terbaru
- Service Worker untuk notifikasi push
- Manifest JSON untuk instalasi PWA

**Halaman Kunci:**
1. **Halaman Login** — Form email/password + tombol Google OAuth + opsi 2FA challenge
2. **Halaman Clock-In** — Kamera preview (face-api.js), GPS status, WFO/WFA toggle, catatan WFA
3. **Dashboard** — Peran-spesifik: employee (ringkasan hari ini), manager (tim), HR (rekap), finance (payroll)
4. **Riwayat Absensi** — Kalender bulanan dengan warna status, filter, export
5. **Form Pengajuan** — Cuti (leave type, tanggal, alasan, upload bukti), Lembur (waktu, deskripsi)
6. **Daftar Persetujuan** — Pending approvals untuk manager/HR, tombol approve/reject
7. **Slip Gaji (E-Payslip)** — PDF 2 kolom: pendapatan vs potongan, download button
8. **Chat KnowledgeBase AI** — Input pertanyaan, chat bubble, source references
9. **Inbox Notifikasi** — Daftar notifikasi in-app, filter by type, read/unread status
10. **Pengaturan Profil** — Data pribadi, password change, 2FA setup, device management

**Teknologi Antarmuka:**
- Livewire 4 untuk komponen interaktif server-side
- Flux UI 2 untuk komponen UI (dengan Tailwind CSS v4)
- Alpine.js untuk interaktivitas frontend ringan (toggle, dropdown, modal)
- Tailwind CSS v4 untuk styling utility-first
- Heroicons/Lucide icons untuk ikon
- face-api.js (TensorFlow.js-based) untuk face detection

#### 3.1.2 Antarmuka Perangkat Keras

Sistem HRConnect membutuhkan perangkat keras berikut untuk beroperasi penuh:

**Perangkat Karyawan (Client):**
- Smartphone dengan sistem operasi Android 10+ atau iOS 14+
- Kamera depan dengan resolusi minimal 5MP (untuk akurasi face recognition)
- GPS receiver dengan akurasi minimal 10 meter
- Koneksi internet minimal 3G (direkomendasikan 4G/LTE)
- RAM minimal 3GB (face-api.js + WebGL/WebGPU model loading membutuhkan ~150-200MB heap; rekomendasi 4GB)
- Browser dengan dukungan WebGL atau WebGPU (Chrome 90+, Safari 14+, Firefox 88+)
- Layar sentuh dengan resolusi minimal 720p

**Server:**
- VPS dengan CPU minimal 2 core (direkomendasikan 4 core)
- RAM minimal 4GB (direkomendasikan 8GB)
- Storage SSD minimal 50GB
- Bandwidth minimal 1 TB/bulan

#### 3.1.3 Antarmuka Perangkat Lunak

Sistem HRConnect berinteraksi dengan perangkat lunak eksternal berikut:

| Perangkat Lunak | Tujuan | Metode Koneksi | Data yang Dipertukarkan |
|-----------------|--------|----------------|-------------------------|
| Google OAuth 2.0 | Autentikasi SSO | OAuth 2.0 Authorization Code Flow | Email, profile (scope: openid, email, profile) |
| Gemini text-embedding-004 | Pembuatan embedding vektor | REST API (HTTP POST) | Teks chunk → vector 768D |
| Gemini 2.5 Flash | LLM untuk RAG | REST API (HTTP POST) | Query + context chunks → response teks + sources |
| SMTP Server (Mailtrap/SES) | Pengiriman email | SMTP Protocol | Email notifikasi, payslip, password reset |
| Browser Geolocation API | Posisi GPS pengguna | JavaScript Geolocation API | Latitude, longitude, accuracy, mocked flag |
| Browser Webcam API | Akses kamera untuk face recognition | JavaScript MediaDevices API | Video stream → canvas capture → face embedding |
| face-api.js (CDN) | Face detection dan embedding | JavaScript (TensorFlow.js) | Canvas image → 128D FaceNet embedding |

#### 3.1.4 Antarmuka Komunikasi

**Protokol Komunikasi:**
- HTTPS (TLS 1.3) untuk semua komunikasi antara client dan server
- HTTP/2 untuk optimasi performa multiplexing
- RESTful API (JSON) untuk komunikasi PWA dengan server
- Livewire wire:submit/wire:click untuk interaksi form server-side
- Database polling untuk queue processing (bukan WebSocket/SSE)

**Format Data:**
- Request/Response API dalam format JSON
- Form data untuk file upload (multipart/form-data)
- PDF untuk E-Payslip dan laporan
- XLSX untuk export Excel

**API Endpoint:**
- 43 endpoint RESTful yang terdokumentasi di docs/api/api-contracts.md
- Versioning via URL prefix: /api/v1/
- Autentikasi: Bearer Token (Sanctum) + Rate Limiting
- Error response format: `{ "message": "...", "errors": {...} }`

### 3.2 Kebutuhan Fungsional

> **Konvensi Penomoran:** Sub-bab 3.2.x dirujuk dengan **UC-XX** (mengikuti use-case-diagram.md, untuk traceability dengan diagram UML). Setiap kebutuhan fungsional di dalam tabel menggunakan **REQ-ID** (`{MODULE}-{NN}`) sebagai identifier kanonik untuk traceability ke Pest test case dan implementasi (controller/service/livewire). Mapping UC↔REQ-ID dibahas pada §4 Traceability Matrix.

#### 3.2.1 Modul Autentikasi & Keamanan (UC41-UC45)

Modul autentikasi dan keamanan menangani login, registrasi, verifikasi email, reset password, 2FA, Google OAuth, manajemen sesi, dan kebijakan password.

| ID | Nama | Deskripsi | Aktor | Prioritas |
|----|------|-----------|-------|-----------|
| AUTH-01 | Login Email/Password | Authentikasi menggunakan email dan password dengan rate limiting 5 percobaan/menit | Semua | Tinggi |
| AUTH-02 | Login Google OAuth | Authentikasi SSO menggunakan Google Workspace dengan auto-verifikasi email | Semua | Tinggi |
| AUTH-03 | Registrasi Akun | Registrasi pengguna baru (hanya via admin, bukan self-registration) | Super Admin | Tinggi |
| AUTH-04 | Verifikasi Email | Verifikasi alamat email setelah registrasi (otomatis jika via Google OAuth) | Semua | Sedang |
| AUTH-05 | Reset Password | Lupa password via email link (Fortify built-in) | Semua | Tinggi |
| AUTH-06 | Aktifkan 2FA TOTP | Mengaktifkan two-factor authentication dengan QR code (Google Authenticator/Authy) | Semua | Tinggi |
| AUTH-07 | Nonaktifkan 2FA TOTP | Menonaktifkan 2FA dengan konfirmasi password | Semua | Sedang |
| AUTH-08 | Recovery 2FA dengan Kode | Login menggunakan 8 recovery codes saat perangkat 2FA hilang | Semua | Tinggi |
| AUTH-09 | Enforce 2FA | 2FA wajib untuk role HR Manager, Finance, Super Admin | Sistem | Tinggi |
| AUTH-10 | Ubah Password | Mengganti password dengan validasi aturan kekuatan password | Semua | Tinggi |
| AUTH-11 | Force Change Password | Memaksa pengguna mengganti password saat login pertama (password_changed=false) | Semua | Tinggi |
| AUTH-12 | Session Timeout | Logout otomatis setelah 120 menit idle | Sistem | Sedang |
| AUTH-13 | Logout dari Semua Device | Invalidasi semua session user dari pengaturan keamanan | Semua | Sedang |
| AUTH-14 | Re-enter Password | Konfirmasi password ulang untuk aksi sensitif (download payslip) | Semua | Tinggi |
| AUTH-15 | PIN 6 Digit Absensi | PIN pendek untuk absensi fallback saat face recognition gagal | Employee | Tinggi |
| AUTH-16 | Login Perangkat Baru | Notifikasi email ke user + in-app ke HRD saat login dari device tidak dikenal | Sistem | Sedang |
| AUTH-17 | Lihat Activity Logs | Melihat riwayat aktivitas akun (login, password change, 2FA, dll.) | Super Admin, HR Manager | Sedang |
| AUTH-18 | Atur Password Policy | Konfigurasi aturan password (min length, expiry 90 hari, history 3 password) | Super Admin | Rendah |

**Kebijakan Password:**
- Minimal 8 karakter
- Wajib kombinasi: huruf besar + huruf kecil + angka (simbol disarankan)
- Password expiry 90 hari (dihitung dari password_changed_at)
- Password history: tidak boleh menggunakan 3 password terakhir
- Force change password saat pertama login
- Password strength indicator di frontend

**Aturan 2FA:**
- Metode: TOTP (Time-based One-Time Password) dengan window 30 detik
- Recovery codes: 8 kode alfanumerik 8 karakter (di-hash di database)
- Wajib untuk role: HR Manager, Finance, Super Admin
- Opsional untuk role: Manager, Employee
- Download recovery codes sebagai file TXT (sekali tampil setelah enable)

**Session Management:**
- Driver: database (tabel sessions)
- Lifetime: 120 menit idle
- Expire on close: true
- Max 5 sesi simultan per user
- HTTPS only (secure=true)
- HttpOnly cookies
- SameSite=Lax

#### 3.2.2 Modul Master Data (UC1-UC9)

Modul master data menyediakan CRUD untuk data referensi utama sistem: perusahaan, cabang, departemen, jabatan, karyawan, shift, hari libur, pengaturan perusahaan, dan manajemen role/permission.

| ID | Nama | Deskripsi | Aktor | Prioritas |
|----|------|-----------|-------|-----------|
| MSTR-01 | Kelola Perusahaan | CRUD company: nama, phone, email, website, NPWP (encrypted), code, logo, is_active | Super Admin | Tinggi |
| MSTR-02 | Kelola Cabang | CRUD branch: company_id, nama, address, latitude, longitude, radius, is_main, is_active | Super Admin | Tinggi |
| MSTR-03 | Kelola Departemen | CRUD department: branch_id, nama, code (unique), description, is_active | Super Admin | Tinggi |
| MSTR-04 | Kelola Jabatan | CRUD position: department_id, nama, code (unique), grade, basic_salary, allowance_jabatan, is_active | Super Admin | Tinggi |
| MSTR-05 | Kelola Karyawan | CRUD employee: data pribadi, employment, face enrollment, dokumen | Super Admin, HR Manager | Tinggi |
| MSTR-06 | Kelola Shift | CRUD shift: nama, start_time, end_time, late_tolerance_minutes, is_active | Super Admin | Tinggi |
| MSTR-07 | Jadwalkan Shift | Assign shift ke karyawan per tanggal via shift_schedules pivot, bulk assign, recurring pattern | HR Manager | Tinggi |
| MSTR-08 | Kelola Hari Libur | CRUD holiday: date (unique), nama, is_active, bulk import CSV | Super Admin | Tinggi |
| MSTR-09 | Kelola Pengaturan Perusahaan | CRUD key-value company_settings: face_distance_threshold, overtime tiers, penalty, cutoff, dll. | Super Admin | Tinggi |
| MSTR-10 | Kelola Tipe Cuti | CRUD leave_type: nama, code, quota, is_paid, is_active | HR Manager | Sedang |
| MSTR-11 | Kelola Kategori Reimbursement | CRUD reimbursement_category: company_id, nama, code, is_active | HR Manager | Sedang |
| MSTR-12 | Manajemen Roles & Permissions | Kelola role Spatie: assign/unassign permission, buat role baru | Super Admin | Tinggi |

**Permission Matrix — 44 Permissions, 5 Roles:**

| Permission | Super Admin | HR Manager | Finance | Manager | Employee |
|------------|:-----------:|:----------:|:-------:|:-------:|:--------:|
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
| `view_reimbursements` | ✅ | ✅ (semua) | ✅ | ✅ (tim) | ✅ (diri) |
| `manage_reimbursements` | ✅ | ❌ | ❌ | ❌ | ❌ |
| `approve_reimbursements_l1` | ✅ | ❌ | ❌ | ✅ | ❌ |
| `approve_reimbursements_l2` | ✅ | ❌ | ✅ | ❌ | ❌ |
| `approve_wfa` | ✅ | ❌ | ❌ | ✅ | ❌ |
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

Catatan: Manager secara otomatis memiliki semua permission Employee (multi-role inheritance).

**Branch-Level Setting Overrides:**
Beberapa pengaturan dapat di-override per cabang (nullable — fallback ke company_settings jika NULL):
- `face_distance_threshold` (decimal 4,3) — threshold face recognition spesifik cabang
- `attendance_grace_minutes` (integer) — toleransi keterlambatan tambahan per cabang
- `wfa_enabled` (boolean) — enable/disable WFA per cabang (default true)
- `radius` (integer) — radius geofence per cabang (meter)

**Aturan Master Data:**
1. Setiap cabang memiliki latitude, longitude, dan radius untuk geofence
2. Setiap karyawan wajib memiliki minimal satu shift (default "Flexible/Office Hour")
3. Data PII (NIK, phone, NPWP, bank_account_number) disimpan terenkripsi via CipherSweet
4. Query data terenkripsi hanya melalui blind index (nik_hash, phone_hash, npwp_hash)
5. Face embedding 128D tidak bisa di-reverse ke foto asli
6. Hanya Super Admin yang bisa menghapus data master (soft delete)

#### 3.2.3 Modul Presensi / Attendance (UC10-UC13)

Modul presensi menangani clock-in/out dengan validasi GPS geofencing dan face recognition, mode WFA, status kehadiran, deteksi alpha, peringatan keterlambatan kronis, dan manajemen perangkat.

| ID | Nama | Deskripsi | Aktor | Prioritas |
|----|------|-----------|-------|-----------|
| ATT-01 | Clock-In WFO | Absen WFO: validasi GPS Haversine + face recognition + foto selfie + status on_time/late | Employee | Tinggi |
| ATT-02 | Clock-In WFA | Absen WFA: face recognition + note ≥20 karakter + status_wfa=pending | Employee | Tinggi |
| ATT-03 | Clock-Out | Clock-out: face recognition + link overtime jika ada + status early/normal | Employee | Tinggi |
| ATT-04 | GPS Validasi | Validasi jarak Haversine: distance <= radius? block jika > radius | Sistem | Tinggi |
| ATT-05 | Face Recognition | Validasi face: <=> cosine distance, threshold 0.15 (≈85% similarity) | Sistem | Tinggi |
| ATT-06 | Anti-Fake GPS | Deteksi GPS palsu: block jika is_mocked=true atau accuracy > 100m | Sistem | Tinggi |
| ATT-07 | PIN Fallback | Absensi via PIN 6 digit saat face recognition gagal (3 attempts) | Employee | Tinggi |
| ATT-08 | WFA Post-Approval | Manager review WFA attendance: approve (status normal) / reject (status absent) | Manager | Tinggi |
| ATT-09 | WFA Auto-Approve | Auto-approve WFA setelah wfa_auto_approve_days (default 3) tanpa review | Sistem | Sedang |
| ATT-10 | Double Clock Prevention | Cegah clock-in/out ganda: max 1x clock-in + 1x clock-out per hari per karyawan | Sistem | Tinggi |
| ATT-11 | Grace Period | Clock-in dalam late_tolerance_minutes dari shift.start_time dianggap on_time | Sistem | Sedang |
| ATT-12 | Lihat Riwayat Absensi | Melihat riwayat absensi: filter bulan/tahun, status, kalender | Employee, Manager | Sedang |
| ATT-13 | Kelola Data Absensi | Koreksi manual attendance (exception_type, notes, approved_by) | HR Manager | Sedang |
| ATT-14 | Deteksi Alpha (Cron) | Daily 23:59: deteksi karyawan aktif tanpa attendance + tanpa cuti + bukan holiday → status absent | Sistem | Tinggi |
| ATT-15 | Chronic Late Warning | Weekly Friday 18:00: notifikasi ke Manager + HR untuk karyawan late ≥3x/bulan | Sistem | Rendah |
| ATT-16 | Verifikasi Perangkat | Device UUID verification: device baru perlu verifikasi HRD (is_verified=false) | HR Manager | Sedang |
| ATT-17 | Link Overtime ke Attendance | Observer saat clock-out: deteksi overtime approved, link + hitung jam lembur | Sistem | Sedang |

**Status Kehadiran (AttendanceStatus Enum):**
| Status | Kondisi |
|--------|---------|
| `on_time` | Clock-In <= (shift.start_time + late_tolerance_minutes) |
| `late` | Clock-In > (shift.start_time + late_tolerance_minutes) |
| `early` | Clock-Out < shift.end_time |
| `holiday` | Tanggal ada di tabel holidays |
| `permission` | Cuti/izin approved untuk tanggal tersebut |
| `absent` | Tidak ada record absensi (di-set oleh cron job) |
| `missed_clock_in` | Ada clock-out tapi tidak ada clock-in |
| `missed_clock_out` | Ada clock-in tapi tidak ada clock-out |

**VerificationMethod Enum:**
Metode verifikasi terpisah untuk clock-in dan clock-out:
- `clock_in_verification_method`, `clock_in_face_similarity_score`
- `clock_out_verification_method`, `clock_out_face_similarity_score`
- Nilai: `face`, `pin`, `gps`, `manual`

**Alur WFO Clock-In:**
1. Pilih mode WFO
2. Sistem ambil koordinat GPS (latitude, longitude, accuracy, is_mocked)
3. Validasi anti-fake GPS: block jika is_mocked=true atau accuracy > 100m
4. Validasi Haversine: hitung jarak dari branch.latitude/longitude, bandingkan dengan branch.radius
5. Jika jarak > radius → tolak dengan pesan "Lokasi anda di luar radius kantor"
6. Face recognition: ambil 128D embedding via face-api.js, bandingkan dengan employees.face_embedding
7. Jika cosine distance > 0.15 (similarity < 85%) → retry 2x, lalu fallback PIN
8. Jika valid → simpan attendance dengan status on_time/late
9. Cek overtime approved untuk tanggal ini → link jika ada
10. Tampilkan hasil: jarak dari kantor (meter), status kehadiran

**Alur WFA Clock-In:**
1. Pilih mode WFA
2. Wajib isi catatan pekerjaan minimal 20 karakter
3. Face recognition (sama seperti WFO)
4. GPS dilewati (latitude/longitude tetap disimpan sebagai catatan)
5. Simpan attendance dengan is_wfa=true, status_wfa=pending
6. Notifikasi in-app ke direct supervisor
7. Supervisor review: approve (status normal) atau reject (status absent)
8. Auto-approve setelah 3 hari kerja (configurable)

**Verification Fallback Strategy (Face → PIN):**
1. Tier 1: Face Recognition (128D FaceNet, threshold 0.15)
2. Jika face gagal setelah 3 attempts → Tier 2: GPS + PIN 6 digit
3. Jika PIN juga gagal → manual request ke supervisor
4. Log semua attempt: face_attempts, similarity_scores, bypass_reason

#### 3.2.4 Modul Manajemen Cuti / Leave (UC14-UC19)

Modul cuti menangani pengajuan cuti, validasi kuota, perhitungan hari kerja, upload bukti, approval multi-level, dan manajemen saldo cuti.

| ID | Nama | Deskripsi | Aktor | Prioritas |
|----|------|-----------|-------|-----------|
| LV-01 | Ajukan Cuti | Input leave_type, start_date, end_date, day_type, reason, proof_file | Employee | Tinggi |
| LV-02 | Hitung Total Hari | Otomatis hitung total_days: exclude Sabtu/Minggu/holiday, morning/afternoon=0.5 | Sistem | Tinggi |
| LV-03 | Validasi Kuota | Cek sisa kuota cuti (leave_balances) cukup untuk pengajuan | Sistem | Tinggi |
| LV-04 | Validasi Overlap | Cek tidak ada cuti approved/pending yang overlap tanggalnya | Sistem | Tinggi |
| LV-05 | Validasi Retroaktif | Cuti mundur (retroaktif) maksimal H+3 dari tanggal awal cuti | Sistem | Sedang |
| LV-06 | Upload Bukti Sakit | Wajib upload bukti (JPG/PNG/PDF, max 5MB) untuk tipe cuti Sakit | Employee | Tinggi |
| LV-07 | Setujui Cuti L1 | Persetujuan Level 1 oleh Manager langsung (parent_id) | Manager | Tinggi |
| LV-08 | Setujui Cuti L2 | Persetujuan Level 2 oleh HR Manager | HR Manager | Tinggi |
| LV-09 | Tolak Cuti | Tolak pengajuan dengan rejection_reason (permanen — buat baru) | Manager, HR Manager | Tinggi |
| LV-10 | Tarik Pengajuan | Withdraw pengajuan hanya jika status masih pending | Employee | Sedang |
| LV-11 | Deduksi Kuota | Kurangi leave_balances.used SETELAH full L2 approval (bukan saat submit) | Sistem | Tinggi |
| LV-12 | Refund Kuota | Kembalikan kuota jika cuti ditolak atau dibatalkan setelah deduksi | Sistem | Tinggi |
| LV-13 | Inisialisasi Saldo | Buat leave_balances pro-rated untuk karyawan baru: (bulan_kerja/12) × quota | Sistem | Tinggi |
| LV-14 | Reset Kuota Tahunan | Cron 1 Januari 00:00: reset used=0, apply carry-forward max 3 hari | Sistem | Tinggi |
| LV-15 | Carry Forward | Sisa cuti dibawa max 3 hari, hangus 31 Maret tahun berikutnya | Sistem | Sedang |
| LV-16 | Lihat Riwayat Cuti | Melihat riwayat cuti: status, tanggal, total_days | Employee, Manager, HR | Sedang |
| LV-17 | Lihat Saldo Cuti | Melihat sisa kuota cuti per leave_type (quota, used, available) | Employee, HR | Sedang |

**Tipe Cuti (Default Seed):**
| Tipe | Kuota | Dibayar | Potong Kuota | Upload Bukti |
|------|-------|:-------:|:------------:|:------------:|
| Cuti Tahunan | 12 hari | ✅ | ✅ | ❌ |
| Sakit | Unlimited | ✅ | ❌ | ✅ (wajib) |
| Menstruasi | 2 hari | ✅ | ❌ | ❌ |
| Melahirkan | 90 hari | ✅ | ❌ | ❌ |
| Cuti Penting/Nikah | 3 hari | ✅ | ❌ | ❌ |
| Unpaid Leave | Unlimited | ❌ | ❌ | ❌ |

**Aturan Perhitungan Hari:**
- Exclude Sabtu, Minggu, dan hari libur nasional (holidays)
- full_day = 1.0 hari, morning/afternoon = 0.5 hari
- Retroaktif maksimal H+3 dari start_date (tidak boleh mengajukan cuti lebih dari 3 hari setelah tanggal mulai)
- Sakit: wajib upload bukti, tidak potong kuota tahunan

**Aturan Kuota:**
- Tahun pertama: pro-rated berdasarkan bulan kerja (join / 12 × quota)
- Reset: cron 1 Januari 00:00
- Carry-forward: max 3 hari ke tahun berikutnya, hangus 31 Maret
- Kuota divalidasi saat submit (cek cukup), DIDEDUKSI hanya setelah full L2 approval
- Refund jika cuti ditolak/dibatalkan setelah deduksi

#### 3.2.5 Modul Lembur / Overtime (UC20-UC24)

Modul lembur menangani pengajuan lembur sebelum pelaksanaan, validasi batas jam, kalkulasi upah tiered, observer link ke attendance, dan approval multi-level.

| ID | Nama | Deskripsi | Aktor | Prioritas |
|----|------|-----------|-------|-----------|
| OT-01 | Ajukan Lembur | Input date, start_time, end_time, description (SEBELUM jam lembur, H-1) | Employee | Tinggi |
| OT-02 | Validasi Maks Jam | Validasi max 4 jam/hari, max 18 jam/minggu (UU Cipta Kerja) | Sistem | Tinggi |
| OT-03 | Setujui Lembur L1 | Persetujuan Level 1 oleh Manager langsung (parent_id) | Manager | Tinggi |
| OT-04 | Setujui Lembur L2 | Persetujuan Level 2 oleh HR Manager | HR Manager | Tinggi |
| OT-05 | Tolak Lembur | Tolak pengajuan dengan rejection_reason | Manager, HR Manager | Tinggi |
| OT-06 | Link ke Attendance | Observer saat clock-out: link overtime ke attendance, hitung jam lembur aktual | Sistem | Sedang |
| OT-07 | Hitung Upah Lembur | Kalkulasi tiered rate berdasarkan UU Cipta Kerja | Sistem | Tinggi |
| OT-08 | Lihat Riwayat Lembur | Melihat riwayat lembur: status, jam, amount | Employee, Manager, HR | Sedang |

**Rumus Upah Lembur:**
- Upah per jam = (Gaji Pokok + Tunjangan Tetap) / 173
- **Weekday (tiered):** Jam pertama = 1.5x, Jam kedua dan seterusnya = 2x
- **Hari Libur/Weekend (tiered):** 8 jam pertama = 2x, Jam 9-10 = 3x, Jam 11+ = 4x
- Weekend menggunakan rate yang sama dengan holiday (multiplier identik, detection berbeda)

**Aturan Pengajuan:**
- WAJIB submit request SEBELUM lembur dilakukan (bisa pagi hari atau H-1)
- Max 4 jam/hari, max 18 jam/minggu
- Attendance_id = NULL saat submit, diisi oleh Observer saat clock-out
- Approved otomatis masuk ke payroll (tidak perlu manual pilih)

#### 3.2.6 Modul Penggajian / Payroll (UC25-UC29)

Modul penggajian menangani perhitungan gaji bulanan, PPh 21 TER, BPJS, pro-rated salary, denda kehadiran, payroll lock, adjustment, dan E-Payslip PDF.

| ID | Nama | Deskripsi | Aktor | Prioritas |
|----|------|-----------|-------|-----------|
| PAY-01 | Generate Payroll | Dispatch async job GenerateEmployeePayrollJob untuk semua karyawan aktif per periode | Finance | Tinggi |
| PAY-02 | Hitung Pro-Rated Salary | (Hari Kerja Aktual / Hari Kerja Efektif) × Gaji Pokok, proteksi division by zero | Sistem | Tinggi |
| PAY-03 | Hitung PPh 21 TER | PPh 21 per bulan: kategori A/B/C dari PTKP (marital_status + jumlah anak) | Sistem | Tinggi |
| PAY-04 | Hitung BPJS | BPJS Kesehatan 4%/1%, JHT 3.7%/2%, JP 2%/1%, JKK 0.24%/0%, JKM 0.30%/0% | Sistem | Tinggi |
| PAY-05 | Hitung Upah Lembur | Lembur approved → tiered rate ke payroll | Sistem | Tinggi |
| PAY-06 | Hitung Denda Kehadiran | Denda keterlambatan/alpha: penalty_per_day (configurable) | Sistem | Tinggi |
| PAY-07 | Hitung THR Pro-Rated | (monthsWorked / 12) × monthlySalary, minimal 1 bulan kerja | Sistem | Sedang |
| PAY-08 | Generate E-Payslip PDF | PDF 2 kolom (Pendapatan/Potongan), generate sekali saat publish | Sistem | Tinggi |
| PAY-09 | Publish Payroll | Ubah status draft → published (LOCKED PERMANEN) | Finance | Tinggi |
| PAY-10 | Lock Payroll | Payroll published = locked. Tidak bisa di-edit, di-unpublish, atau dihapus | Finance | Tinggi |
| PAY-11 | Adjustment Pasca-Lock | Buat PayrollAdjustment untuk koreksi bulan berikutnya (positif/negatif) | Finance | Tinggi |
| PAY-12 | Download E-Payslip | Download PDF payslip dengan re-enter password confirmation | Employee, Finance | Tinggi |
| PAY-13 | View Payroll History | Lihat riwayat payroll per employee filter periode | Employee (diri), Finance (semua) | Sedang |
| PAY-14 | Kelola Tax Configs | CRUD tax_configs: kategori A/B/C, min_income, max_income, rate | Finance | Sedang |
| PAY-15 | Kelola BPJS Configs | CRUD bpjs_configs: name, employer_rate, employee_rate, ceiling | Finance | Sedang |

**Rumus Payroll:**
```
GROSS = basic_salary + allowance_jabatan + (tunjangan_makan × hari_hadir) + overtime_pay + reimbursement_paid + income_thr + income_bonus
DEDUCTIONS = pph21 + bpjs_health + bpjs_employment + loan_deduction + attendance_penalty
NET = GROSS - DEDUCTIONS
```

**Detail Perhitungan:**
| Komponen | Keterangan | Pro-rated? |
|----------|-----------|:----------:|
| Gaji Pokok | Dari positions.basic_salary | ✅ |
| Tunjangan Jabatan | Dari positions.allowance_jabatan | ✅ |
| Tunjangan Makan | Configurable, × hari_hadir_aktual | ✅ |
| Upah Lembur | Tiered rate × jam lembur | ❌ (dihitung terpisah) |
| Reimbursement | Approved reimbursement dalam periode | ❌ (ditambahkan utuh) |
| THR | (monthsWorked/12) × monthlySalary | ❌ (pro-rated terpisah) |
| Bonus | Input manual via payroll_items (allowance) | ❌ |
| PPh 21 | TER kategori A/B/C dari bruto | ❌ (dari bruto aktual) |
| BPJS Kesehatan | 4% employer / 1% employee × ceiling | ❌ (tetap dari bulan 1) |
| BPJS JHT | 3.7% employer / 2% employee | ❌ (tetap) |
| BPJS JP | 2% employer / 1% employee × ceiling | ❌ (tetap) |
| BPJS JKK | 0.24% employer / 0% employee | ❌ (tetap) |
| BPJS JKM | 0.30% employer / 0% employee | ❌ (tetap) |
| Denda Alpha | Gross_monthly / countWorkingDays() per hari alpha | ❌ |
| Denda Telat | Flat per kejadian (configurable) | ❌ |
| Pinjaman | Installment dari loan aktif | ❌ |

**Pro-Rated Salary:**
- Rumus: (Hari Kerja Aktual / Hari Kerja Efektif) × Gaji Pokok
- Hari Kerja Efektif = Senin-Jumat minus weekend dan holidays
- Join mid-month: actual_start = join_date
- Resign mid-month: actual_end = resign_date
- Unpaid leave: dikurangi dari hari kerja efektif
- Division by zero protection: jika totalWorkingDays = 0 → return 0.0

**Payroll Lock Permanen:**
- Status DRAFT → dapat diedit dan di-regenerasi
- Status PUBLISHED → LOCKED PERMANEN (tidak bisa diunpublish atau diedit)
- Status PAID → LOCKED (sama seperti published)
- Koreksi hanya via PayrollAdjustment untuk bulan berikutnya
- Saat regenerasi: gunakan forceDelete() (bukan delete()) untuk menghindari unique constraint violation

**Async Queue Config:**
| Parameter | Value |
|-----------|-------|
| Queue | `payroll_high` |
| Tries | 3 |
| Timeout | 120 detik |
| Backoff | [10, 30, 60] detik |
| Lock | `lockForUpdate()` untuk cegah double generation |

**Salary Types:**
| Type | Perhitungan GROSS | Pro-Rating | THR |
|------|-------------------|-----------|-----|
| `monthly` | basic_salary flat per bulan | Saat join/resign mid-month | (months/12) × salary |
| `daily` | daily_rate × hari_hadir_aktual | N/A | (months/12) × avg_monthly_total |
| `hourly` | hourly_rate × total_jam_kerja | N/A | (months/12) × avg_monthly_total |

**Employment Types:**
| Type | Gaji | BPJS | PPh 21 | Catatan |
|------|------|------|--------|---------|
| `permanent` (PKWTT) | Penuh | Wajib (5 jenis) | TER bulanan | Standar, ada pesangon |
| `contract` (PKWT) | Penuh | Wajib | TER bulanan | Dapat uang kompensasi saat kontrak berakhir |
| `probation` | Penuh | Wajib | TER bulanan | Max 3 bulan, auto-promote ke permanent |
| `intern` | Uang saku | Opsional (tidak wajib) | PPh 21 progresif | Bukan karyawan tetap, tanpa THR jika <12 bulan |

#### 3.2.7 Modul Approval Workflow (UC30-UC33)

Modul approval workflow menangani proses persetujuan multi-level polymorphic untuk cuti, lembur, reimbursemen, dan WFA.

| ID | Nama | Deskripsi | Aktor | Prioritas |
|----|------|-----------|-------|-----------|
| APPR-01 | Buat Approval Workflow | Buat approval records (polymorphic: approvable_type + approvable_id) berdasarkan matrix | Sistem | Tinggi |
| APPR-02 | Setujui Level 1 | Approve L1 oleh Manager langsung (parent_id) dengan notes opsional | Manager | Tinggi |
| APPR-03 | Setujui Level 2 | Approve L2 oleh HR Manager (cuti/lembur) atau Finance (reimbursement) | HR Manager, Finance | Tinggi |
| APPR-04 | Tolak Request | Reject permanen (terminal state) dengan rejection_reason | Manager, HR Manager, Finance | Tinggi |
| APPR-05 | Executive Approve | Super Admin dapat approve di level mana pun (override) | Super Admin | Tinggi |
| APPR-06 | Tarik Pengajuan | Withdraw hanya jika status masih pending (belum di-approve siapa pun) | Employee | Sedang |
| APPR-07 | Cek Overdue | Jika pending > 24 jam → kirim reminder in-app + email | Sistem | Sedang |
| APPR-08 | Skip L1 | Jika parent_id = NULL → skip Level 1, langsung ke Level 2 | Sistem | Tinggi |
| APPR-09 | Cek Semua Approved | `isAllApproved()`: semua level harus approved sebelum execute callback | Sistem | Tinggi |
| APPR-10 | Execute Callback | Eksekusi aksi setelah full approval: deduksi kuota cuti, update status, dll. | Sistem | Tinggi |

**Matrix Approval:**

| Modul | Level 1 | Level 2 | Catatan |
|-------|---------|---------|---------|
| Cuti (Leave) | Manager (parent_id) | HR Manager | Skip L1 jika parent_id=NULL |
| Lembur (Overtime) | Manager (parent_id) | HR Manager | Skip L1 jika parent_id=NULL |
| Reimbursement | Manager (parent_id) | Finance | Skip L1 jika parent_id=NULL |
| WFA | Manager (parent_id) | (single level) | Tidak ada L2 |

**Aturan:**
1. Semua pengajuan menggunakan polymorphic approvals (approvable_type + approvable_id)
2. ApprovalLevel enum: L1_SUPERVISOR=1, L2_HR=2
3. Perbandingan level: gunakan `=== ApprovalLevel::L1_SUPERVISOR`, bukan `=== 1`
4. Rejection permanen: tidak bisa diajukan ulang, harus buat pengajuan baru
5. Withdraw: hanya di status pending
6. Delegasi: tidak didukung di MVP
7. Timeout: reminder setiap 24 jam (in-app + email)
8. Auto-approve: tidak ada (kecuali WFA setelah timeout 3 hari)

#### 3.2.8 Modul KnowledgeBase AI / RAG (UC34-UC37)

Modul KnowledgeBase AI menyediakan sistem Retrieval Augmented Generation (RAG) untuk dokumen HRD. HRD dapat mengupload PDF, sistem melakukan chunking dan embedding, dan karyawan dapat bertanya via chat AI.

| ID | Nama | Deskripsi | Aktor | Prioritas |
|----|------|-----------|-------|-----------|
| KB-01 | Upload PDF | Upload dokumen PDF max 10MB, validasi MIME dan size | HR Manager | Tinggi |
| KB-02 | Ekstrak Teks | Ekstrak teks dari PDF, simpan per chunk (~60 token, overlap 10) | Sistem | Tinggi |
| KB-03 | Generate Embedding | Embedding via Gemini text-embedding-004 (768 dimensi) → pgvector | Sistem | Tinggi |
| KB-04 | Simpan ke pgvector | Simpan embedding vector(768) ke knowledge_bases.embedding | Sistem | Tinggi |
| KB-05 | Chat AI RAG | Query: vector similarity (cosine) → top-5 chunks → Gemini 2.5 Flash → response | Employee, HR Manager | Tinggi |
| KB-06 | Tampilkan Sumber | Tampilkan source references: judul dokumen, halaman, konten relevan | Sistem | Sedang |
| KB-07 | Fallback pg_trgm | Jika Gemini down, gunakan pg_trgm full-text search sebagai fallback | Sistem | Tinggi |
| KB-08 | Status Dokumen | Status: processing → ready/error (dengan pesan error) | Sistem | Sedang |
| KB-09 | Kelola KnowledgeBase | Edit metadata, delete, re-index, filter kategori, search manual | HR Manager | Sedang |
| KB-10 | Mock Mode | Mode demo: bypass Gemini API, return pre-canned responses (RAG_MOCK_MODE=true) | Sistem | Rendah |

**Spesifikasi Teknis:**
| Parameter | Value |
|-----------|-------|
| Format file | PDF (MVP, hanya PDF) |
| Max file size | 10 MB |
| Embedding model | Gemini text-embedding-004 |
| Dimensi vector | 768 (bukan 1536) |
| LLM | Gemini 2.5 Flash (API) |
| Chunk size | ~60 token |
| Chunk overlap | 10 token |
| Top-K retrieval | 5 chunks |
| Search type | Cosine similarity (pgvector) |
| Fallback | pg_trgm GIN index |
| Queue | default (tries=2, timeout=300s) |

**Alur KnowledgeBase:**
1. HRD upload PDF → validasi format dan size
2. Simpan file ke storage/app/knowledgebase/
3. Dispatch Job: ProcessKnowledgeBaseEmbedding (queue: default)
4. Job: ekstrak teks → chunking → embedding via Gemini API → simpan ke knowledge_bases
5. Status: processing selama job berjalan, ready setelah selesai, error jika gagal
6. User input query → vector similarity search → ambil top-5 chunks → kirim ke Gemini → tampilkan response + source references
7. Jika Gemini API down → fallback ke pg_trgm text search + banner "AI offline, menggunakan keyword search"

#### 3.2.9 Modul Notifikasi (UC38-UC40)

Modul notifikasi menangani pengiriman notifikasi in-app (database) dan email untuk 20+ event sistem.

| ID | Nama | Deskripsi | Aktor | Prioritas |
|----|------|-----------|-------|-----------|
| NOTIF-01 | Notifikasi In-App | Notifikasi di inbox aplikasi (database notifications table) | Semua | Tinggi |
| NOTIF-02 | Notifikasi Email | Notifikasi via SMTP (Mailtrap/SES) | Semua | Sedang |
| NOTIF-03 | Notifikasi WhatsApp | DITUNDA V2 | — | Rendah |
| NOTIF-04 | Mark as Read | Tandai notifikasi sebagai sudah dibaca | Semua | Sedang |
| NOTIF-05 | Filter Notifikasi | Filter by type, read/unread, date | Semua | Rendah |

**Notification Matrix (20+ events):**

| Event | Employee | Manager | HR Manager | Finance |
|-------|:--------:|:-------:|:----------:|:-------:|
| Cuti/Lembur diajukan | ❌ | ✅ (L1) | ❌ | ❌ |
| Cuti di-ACC L1 | ✅ | ❌ | ✅ (L2) | ❌ |
| Cuti Final Approved | ✅ | ❌ | ❌ | ❌ |
| Cuti/Lembur ditolak | ✅ | ❌ | ❌ | ❌ |
| Reimbursement diajukan | ❌ | ✅ (L1) | ❌ | ❌ |
| Reimbursement di-ACC L1 | ✅ | ❌ | ❌ | ✅ (L2) |
| Reimbursement Final | ✅ | ❌ | ❌ | ❌ |
| Reimbursement ditolak | ✅ | ❌ | ❌ | ❌ |
| Reimbursement dibayar | ✅ | ❌ | ❌ | ❌ |
| WFA diajukan | ❌ | ✅ | ✅ | ❌ |
| WFA di-ACC | ✅ | ❌ | ❌ | ❌ |
| WFA ditolak | ✅ | ❌ | ❌ | ❌ |
| WFA auto-approved | ✅ | ✅ | ✅ | ❌ |
| Payroll dipublish | ✅ | ❌ | ❌ | ❌ |
| Login perangkat baru | ✅ | ❌ | ✅ | ❌ |
| Overdue >24 jam | ❌ | ✅ | ✅ | ❌ |
| Alpha terdeteksi | ❌ | ✅ (tim) | ✅ | ❌ |
| Chronic late warning | ❌ | ✅ (tim) | ✅ | ❌ |

#### 3.2.10 Modul Dashboard & Laporan (UC46)

| ID | Nama | Deskripsi | Aktor | Prioritas |
|----|------|-----------|-------|-----------|
| DASH-01 | Dashboard Employee | Ringkasan hari ini: status clock-in, jadwal shift, sisa cuti, pengajuan pending | Employee | Tinggi |
| DASH-02 | Dashboard Manager | Rekap tim: siapa sudah clock-in, pending approvals, daftar cuti tim hari ini | Manager | Tinggi |
| DASH-03 | Dashboard HR | Rekap kehadiran seluruh perusahaan, pending approvals L2, grafik cuti/lembur | HR Manager | Tinggi |
| DASH-04 | Dashboard Finance | Ringkasan payroll bulan berjalan, status publish, outstanding adjustments | Finance | Sedang |
| DASH-05 | Dashboard Super Admin | System-wide overview, active users, recent activity logs, storage usage | Super Admin | Sedang |
| DASH-06 | Export Rekap Absensi | Export XLSX rekap absensi per periode per departemen/branch | HR Manager | Sedang |
| DASH-07 | Export Riwayat Cuti | Export XLSX riwayat cuti per karyawan per tahun | HR Manager | Sedang |
| DASH-08 | Export Payroll | Export XLSX payroll bulanan (password-protected) | Finance | Sedang |
| DASH-09 | Export Direktori Karyawan | Export XLSX daftar karyawan aktif | HR Manager | Sedang |
| DASH-10 | Laporan 1721-A1 | Generate bukti potong PPh 21 bulanan PDF | Sistem (auto) | Sedang |
| DASH-11 | Laporan BPJS | Export XLSX BPJS Kesehatan dan Ketenagakerjaan | Finance | Sedang |

### 3.3 Kebutuhan Kinerja (Performance)

**Waktu Respons (REQ-PERF-01 s/d 08):**
| ID | Operasi | Target | Catatan |
|----|---------|--------|---------|
| PERF-01 | Halaman dashboard | < 2 detik | Dengan cache warm |
| PERF-02 | Clock-in WFO | < 5 detik | Termasuk GPS + face recognition + validasi |
| PERF-03 | Chat AI RAG | < 8 detik | Termasuk vector search + Gemini API call |
| PERF-04 | Generate payroll (per employee) | < 10 detik | Async queue, timeout 120s |
| PERF-05 | Download E-Payslip PDF | < 1 detik | File streaming (generate sekali saat publish) |
| PERF-06 | CRUD master data | < 1.5 detik | Dengan eager loading |
| PERF-07 | Export Excel | < 5 detik | Untuk data < 500 records |
| PERF-08 | API response | < 500ms | Untuk 90% request |

**Face Recognition (REQ-PERF-09):**
- Threshold distance: 0.15 (≈ similarity ≥ 85%)
- Embedding 128D, client-side via face-api.js (TensorFlow.js)
- Perbandingan di server menggunakan pgvector `<=>` cosine distance
- HNSW index untuk akselerasi search
- 3 attempt max sebelum fallback ke PIN

**GPS Geofencing (REQ-PERF-10):**
- Haversine formula untuk perhitungan jarak titik ke titik
- Radius configurable per branch: 10-5000 meter
- Anti-fake GPS: block jika is_mocked=true atau accuracy > 100m
- WFA: GPS dilewati, wajib note ≥20 karakter

**Cache Performance (REQ-PERF-11):**
- Driver: database (CACHE_STORE=database)
- Cache key convention: `module:identifier:key`
- TTL: 1 jam - 1 bulan tergantung data
- Tidak ada Cache::tags() support — gunakan Cache::forget() per-key
- Cache plain array primitif, bukan Eloquent object

**Rate Limiting (REQ-PERF-12 s/d 16):**
| ID | Endpoint | Limit | Window |
|----|----------|-------|--------|
| PERF-12 | Auth (login) | 5 attempt | 1 menit |
| PERF-13 | Face recognition | 10 attempt | 1 menit |
| PERF-14 | Clock-in | 5 attempt | 5 menit |
| PERF-15 | KnowledgeBase chat | 20 request | 1 menit |
| PERF-16 | General API | 60 request | 1 menit |

**Queue & Job (REQ-PERF-17 s/d 19):**
| ID | Job | Queue | Tries | Timeout | Backoff |
|----|-----|-------|-------|---------|---------|
| PERF-17 | GenerateEmployeePayrollJob | payroll_high | 3 | 120s | [10, 30, 60] |
| PERF-18 | ProcessKnowledgeBaseEmbedding | default | 2 | 300s | [30, 60] |
| PERF-19 | SendNotificationJob | default | 3 | 60s | [10, 30, 60] |

### 3.4 Batasan Desain

**Arsitektur:**
- Laravel 13 + PHP 8.5 (server-side rendering via Livewire 4)
- Bukan SPA (React/Vue) — Livewire 4 mengirim HTML, bukan JSON
- Flux UI 2 sebagai component library + Tailwind CSS v4
- Alpine.js untuk interaktivitas frontend ringan
- Service layer pattern: semua business logic di Service classes, bukan Controller

**Database:**
- PostgreSQL 15+ (bukan MySQL/SQLite) untuk production
- 48 tabel database dengan SoftDeletes pada model utama
- 33 PHP Backed Enums (16 Status dengan color() + 17 Classification tanpa color())
- Strategi "Dumb Database, Smart Application": string column + cast ke PHP Enum
- Defensive Migration pattern: fitur PostgreSQL-specific guarded dengan `if (DB::getDriverName() === 'pgsql')`
- SQLite in-memory untuk testing (dengan defensive guard)

**Caching:**
- CACHE_STORE=database (bukan Redis/File/Memcached)
- Tidak ada Cache::tags() — menggunakan Cache::forget() per-key
- Cache plain array, bukan Eloquent Collection (cegah incomplete object error)

**Queue:**
- QUEUE_CONNECTION=database (bukan Redis)
- Dua queue: default + payroll_high

**Frontend:**
- 100% Bahasa Indonesia
- Mobile-first PWA dengan bottom navigation
- Desktop: sidebar navigation
- PWA offline: didefer ke V2

**Auth:**
- Laravel Fortify (bukan Breeze/Jetstream/Sanctum SPA)
- Google OAuth + Email/Password
- 2FA TOTP untuk role sensitif

### 3.5 Atribut Sistem Perangkat Lunak

#### 3.5.1 Keamanan (Security)

**Enkripsi Data CipherSweet (REQ-SEC-01):**
| Model | Field Encrypted | Blind Index |
|-------|----------------|-------------|
| Employee | nik, phone, npwp, bank_account_number | nik_hash, phone_hash, npwp_hash |
| Company | npwp | npwp_hash |
| FamilyDetail | nik, phone, address | nik_hash, phone_hash |

- Searchable encryption: query via blind index, bukan langsung ke encrypted field
- Backend: libsodium (sodium) via PHP 8.5
- Key: env `CIPHERSWEET_KEY`

**RBAC — Role-Based Access Control (REQ-SEC-02):**
- Package: Spatie Permission (laravel-permission)
- 5 roles: super-admin, hr-manager, finance, manager, employee
- 44 permissions dengan matrix spesifik per role
- Policy guards di setiap model (view, create, update, delete, approve, lock, publish)
- Middleware: `role:`, `permission:` di route definitions

**Keamanan Aplikasi (REQ-SEC-03):**
- CSRF protection via Laravel VerifyCsrfToken (semua form)
- XSS prevention via Blade auto-escaping `{{ }}`
- SQL injection prevention via Eloquent ORM (parameterized queries)
- File upload validation: MIME type (header) + size limit
- HTTP Security Headers: HSTS, CSP, X-Frame-Options (V2)

**Keamanan Face Recognition (REQ-SEC-04):**
- Embedding 128D tidak bisa di-reverse ke foto asli
- Threshold similarity ≥85%
- Liveness detection via blink/turn head (opsional, V2)
- Replay attack prevention via random challenge (opsional, V2)
- Hanya HRD dan karyawan sendiri yang bisa lihat face photo

**Anti-Fake GPS (REQ-SEC-05):**
- Block jika `is_mocked = true` dari browser
- Block jika `accuracy > 100m` (terlalu tidak akurat)
- Log security event untuk setiap percobaan
- Jika 3x terdeteksi dalam seminggu → auto-flag akun untuk review HRD

**Session Security (REQ-SEC-06):**
- Driver: database
- Lifetime: 120 menit idle
- HttpOnly + Secure + SameSite=Lax cookies
- Max 5 sesi simultan per user
- Logout dari semua device via invalidasi session

#### 3.5.2 Keandalan (Reliability)

**Queue Retry (REQ-REL-01):**
- Exponential backoff untuk semua job: [10, 30, 60] detik untuk payroll, [30, 60] untuk KB
- Max 3 tries (payroll) / 2 tries (KB) sebelum masuk failed_jobs
- Admin notification saat job gagal setelah semua retry

**Database Transaction (REQ-REL-02):**
- Payroll generation dalam DB::transaction()
- Pessimistic locking via `lockForUpdate()` untuk cegah double generation
- Leave quota deduksi dalam transaction setelah full approval

**Data Integrity (REQ-REL-03):**
- Validasi kuota cuti saat submit, deduksi hanya setelah full approval
- Refund kuota jika cuti ditolak/dibatalkan
- Payroll LOCKED permanen saat published — tidak ada rollback
- Unique constraint: (employee_id, period) untuk payroll, (employee_id, date) untuk attendance

**Error Prevention (REQ-REL-04):**
- Division by zero protection di semua formula (pro-rated salary, dll.)
- Null safety: nullable columns diperiksa sebelum digunakan
- Jika totalWorkingDays = 0 → return 0.0
- `isLocked()` block PAYROLL_STATUS PUBLISHED + PAID

#### 3.5.3 Ketersediaan (Availability)

**Cold Start Prevention (REQ-AVL-01):**
- Neon PostgreSQL serverless dapat "sleep" saat idle
- Warm-up script: ping health endpoint setiap 5 menit via cron
- DB_CONNECT_TIMEOUT=10 untuk koneksi lambat
- Pre-connect saat login → user tidak notice delay saat navigasi

**Fallback Mechanisms (REQ-AVL-02):**
- Face recognition gagal → fallback PIN 6 digit
- GPS accuracy buruk → accept + flag untuk review supervisor
- Gemini API down → fallback pg_trgm text search
- Email server down → notifikasi tetap tersimpan di database (in-app)
- Database down → maintenance page

**UI Resilience (REQ-AVL-03):**
- Skeleton loader saat loading data
- Loading state di setiap tombol submit (wire:loading)
- Offline detection (navigator.onLine) dengan pesan informatif
- Error boundaries di Livewire component

#### 3.5.4 Pemeliharaan (Maintainability)

**Code Organization (REQ-MNT-01):**
- Service layer pattern: App\Services\* untuk business logic
- Repositories: tidak digunakan (langsung Eloquent di Service)
- 33 PHP enums dengan pemisahan jelas: Status (color()) vs Classification (tanpa color())
- Laravel 13 attribute syntax: `#[Fillable([...])]` + `#[Hidden([...])]` bukan property
- Traits: `Approvable`, `ManagesWorkDays`
- Concerns: `PasswordValidationRules`, `ProfileValidationRules`

**Naming Conventions (REQ-MNT-02):**
- Cache key: `module:identifier:key` colon separator
- Permission: `{action}_{module}` snake_case
- Roles: kebab-case (super-admin, hr-manager)
- Enum cases: UPPER_SNAKE_CASE
- Database columns: snake_case

**Date Handling (REQ-MNT-03):**
- CarbonImmutable: semua operasi tanggal menggunakan immutable instance
- Mutasi Carbon ->addDay() mengembalikan instance baru (assign atau chain)
- Timezone: Asia/Jakarta

**Code Quality (REQ-MNT-04):**
- Lint via Laravel Pint: `vendor/bin/pint --dirty --format agent`
- Static analysis via Larastan (V2)
- Activity log untuk semua perubahan data sensitif

#### 3.5.5 Portabilitas (Portability)

**PostgreSQL-Specific Features (REQ-PRT-01):**
- pgvector index → guarded dengan `if (DB::getDriverName() === 'pgsql')`
- pg_trgm index → guarded
- pgcrypto → guarded
- HNSW index → guarded

**Testing Compatibility (REQ-PRT-02):**
- SQLite in-memory untuk PHPUnit/Pest testing
- Defensive migration pattern: 3 migration sudah guarded (users, employees, knowledge_bases)
- Fitur yang tidak kompatibel SQLite: pgvector, pg_trgm, full-text search → mock atau skip di test

**Environment Portability (REQ-PRT-03):**
- .env.example sebagai template konfigurasi
- Environment detection: APP_ENV (local/testing/production)
- Database config default: pgsql (bukan sqlite/mysql)

### 3.6 Kebutuhan Basis Data Logis

Sistem HRConnect menggunakan 48 tabel database dalam satu skema PostgreSQL. Berikut adalah ringkasan kelompok tabel beserta tujuan masing-masing:

**A. Master Data (9 tabel):**
| Tabel | Primary Key | Kolom Kunci | Unik |
|-------|-------------|-------------|------|
| companies | id (bigint) | name, phone, email, website, npwp (encrypted), code, logo, is_active | code |
| branches | id (bigint) | company_id, name, address, latitude, longitude, radius, is_main, is_active | — |
| departments | id (bigint) | branch_id, name, code, description, is_active, deleted_at | code |
| positions | id (bigint) | department_id, name, code, grade, basic_salary, allowance_jabatan, is_active, deleted_at | code |
| shifts | id (bigint) | name, start_time, end_time, late_tolerance_minutes, is_active | name |
| shift_schedules | id (bigint) | employee_id, shift_id, date | (employee_id, date) |
| holidays | id (bigint) | date, name, is_active | date |
| leave_types | id (bigint) | name, code, quota, is_paid, deducts_from_quota, is_active | code |
| reimbursement_categories | id (bigint) | company_id, name, code, is_active | code |

**B. Users & Employees (4 tabel):**
| Tabel | Primary Key | Kolom Kunci | Unik |
|-------|-------------|-------------|------|
| users | id (bigint) | company_id, name, email, password, google_id, password_changed_at, two_factor_secret, two_factor_recovery_codes, two_factor_confirmed_at, deleted_at | email |
| employees | id (bigint) | user_id, parent_id, company_id, branch_id, department_id, position_id, shift_id, employee_number, full_name, phone (encrypted), nik (encrypted), npwp (encrypted), bank_account_number (encrypted), face_embedding vector(128), pin, status, employment_type, join_date, resign_date, salary_type, deleted_at | employee_number, user_id |
| family_details | id (bigint) | employee_id, nik (encrypted), name, relationship, gender, birth_date, phone (encrypted), address (encrypted), is_emergency | — |
| devices | id (bigint) | employee_id, device_uuid, device_type, device_name, browser, os, is_verified, verified_at, last_used_at | device_uuid |

**C. Attendance (1 tabel):**
| Tabel | Primary Key | Kolom Kunci | Unik |
|-------|-------------|-------------|------|
| attendances | id (bigint) | employee_id, shift_id, date, clock_in, clock_out, lat_in, long_in, clock_in_is_mocked, clock_in_accuracy, lat_out, long_out, verification_method, face_similarity_score, clock_out_verification_method, clock_out_face_similarity_score, photo_selfie_in, status, is_wfa, status_wfa, wfa_note, late_minutes, deleted_at | (employee_id, date) |

**D. Leave Management (2 tabel):**
| Tabel | Primary Key | Kolom Kunci | Unik |
|-------|-------------|-------------|------|
| leaves | id (bigint) | employee_id, leave_type_id, start_date, end_date, day_type, total_days, reason, proof_file, status, rejection_reason, deleted_at | — |
| leave_balances | id (bigint) | employee_id, leave_type_id, year, quota, used, carry_forward, carry_forward_deadline | (employee_id, leave_type_id, year) |

**E. Overtime (1 tabel):**
| Tabel | Primary Key | Kolom Kunci | Unik |
|-------|-------------|-------------|------|
| overtimes | id (bigint) | employee_id, attendance_id, date, start_time, end_time, description, total_hours, amount, status, rejection_reason, deleted_at | — |

**F. Payroll (3 tabel):**
| Tabel | Primary Key | Kolom Kunci | Unik |
|-------|-------------|-------------|------|
| payrolls | id (bigint) | employee_id, period, basic_salary, total_allowance, gross_salary, overtime_pay, pph21, bpjs_health, bpjs_employment, loan_deduction, attendance_penalty, total_deduction, net_salary, status, deleted_at | (employee_id, period) |
| payroll_items | id (bigint) | payroll_id, name, amount, type | — |
| payroll_adjustments | id (bigint) | payroll_id, amount, reason, created_by (nullable), applied_to_period | — |

**G. Approval Workflow (1 tabel):**
| Tabel | Primary Key | Kolom Kunci | Unik |
|-------|-------------|-------------|------|
| approvals | id (bigint) | approvable_type (polymorphic), approvable_id, approver_id, level (ApprovalLevel), status, notes, approved_at | — |

**H. Reimbursement & Loan (4 tabel, Loan V2):**
| Tabel | Primary Key | Kolom Kunci | Status |
|-------|-------------|-------------|--------|
| reimbursements | id (bigint) | employee_id, payroll_id, category_id, title, expense_date, amount, description, receipt_file, rejection_reason, status, deleted_at | V1 |
| loans | id (bigint) | employee_id, created_by, amount, interest_rate, tenor_months, monthly_installment, status, deleted_at | V2 (deferred) |
| loan_installments | id (bigint) | loan_id, payroll_id, amount_paid, installment_number, status, due_date, paid_at | V2 (deferred) |

**I. Asset & Performance (3 tabel, V2):**
| Tabel | Primary Key | Kolom Kunci | Status |
|-------|-------------|-------------|--------|
| assets | id (bigint) | company_id, name, serial_number, code, category, status, is_available, deleted_at | V2 |
| asset_handovers | id (bigint) | asset_id, employee_id, handover_date, return_date, condition, category | V2 |
| performance_reviews | id (bigint) | employee_id, reviewer_id, status, review_date, period, final_score, notes, deleted_at | V2 |

**J. KnowledgeBase (1 tabel):**
| Tabel | Primary Key | Kolom Kunci | Unik |
|-------|-------------|-------------|------|
| knowledge_bases | id (bigint) | knowledgeable_type (polymorphic), knowledgeable_id, title, content, embedding vector(768), metadata (json), category, status, source_document, page_number | — |

**K. Configuration (3 tabel):**
| Tabel | Primary Key | Kolom Kunci | Unik |
|-------|-------------|-------------|------|
| company_settings | id (bigint) | company_id, key, value (json), description | key |
| tax_configs | id (bigint) | ter_category (A/B/C), min_income, max_income, rate, effective_rate | — |
| bpjs_configs | id (bigint) | name (kesehatan/jht/jp/jkk/jkm), employer_rate, employee_rate, ceiling | name |

**L. Security & Logs (4 tabel):**
| Tabel | Primary Key | Kolom Kunci | Catatan |
|-------|-------------|-------------|---------|
| activity_logs | id (bigint) | log_name, description, subject_type, subject_id, causer_type, causer_id, properties (json) | Spatie Activitylog |
| sessions | id (varchar) | user_id, ip_address, user_agent, payload, last_activity | Laravel session |
| password_reset_tokens | email (varchar) | email, token, created_at | Laravel auth |
| blind_indexes | id (bigint) | table_name, column_name, blind_index, row_id | CipherSweet |

**M. Queue & Cache (3 tabel):**
| Tabel | Primary Key | Kolom Kunci | Catatan |
|-------|-------------|-------------|---------|
| jobs | id (bigint) | queue, payload, attempts, reserved_at, available_at, created_at | Laravel queue |
| job_batches | id (varchar) | name, total_jobs, pending_jobs, failed_jobs, failed_job_ids, options | Laravel queue |
| cache | key (varchar) | key, value, expiration | Laravel cache |

**N. Indonesia Region (4 tabel):**
| Tabel | Primary Key | Kolom Kunci |
|-------|-------------|-------------|
| indonesia_provinces | id (char 2) | name |
| indonesia_cities | id (char 4) | province_id, name |
| indonesia_districts | id (char 6) | city_id, name |
| indonesia_villages | id (char 10) | district_id, name |

**O. Spatie Permission (5 tabel):**
- permissions, roles, model_has_permissions, model_has_roles, role_has_permissions

**Key Indexes:**
| Index | Tabel | Kolom | Tipe |
|-------|-------|-------|------|
| HNSW | knowledge_bases | embedding (768D) | pgvector cosine |
| HNSW | employees | face_embedding (128D) | pgvector cosine |
| GIN | knowledge_bases | content_chunk | pg_trgm |
| Unique | payrolls | (employee_id, period) | B-tree |
| Unique | attendances | (employee_id, date) | B-tree |
| Unique | shift_schedules | (employee_id, date) | B-tree |
| Unique | leave_balances | (employee_id, leave_type_id, year) | B-tree |
| Unique | holidays | date | B-tree |
| Blind Index | employees | nik_hash, phone_hash, npwp_hash | B-tree |
| Blind Index | family_details | nik_hash, phone_hash | B-tree |

## 4. Lampiran

### A. Use Case Diagram

Diagram use case lengkap untuk seluruh modul HRConnect tersedia di:
- `docs/architecture/use-case-diagram.md` — 46 use case utama dengan relasi include/extend
- Use Case Diagram Presensi Module (6 use case detail untuk clock-in WFO/WFA)
- Use Case Diagram Leave Management Module (14 use case detail)
- Use Case Diagram Payroll Module (13 use case detail)
- Use Case Diagram KnowledgeBase AI Module (11 use case detail)
- Use Case Diagram Approval Workflow Module (9 use case detail)

### B. Activity Diagrams

Diagram aktivitas untuk alur proses bisnis utama tersedia di:
- `docs/architecture/activity-diagrams.md` — 6 diagram aktivitas:
  1. Clock-In/Out (WFO + WFA) — flow validasi GPS Haversine, face recognition, status kehadiran
  2. Leave Request — flow pengajuan, validasi kuota, overlap, retroaktif, approval L1→L2
  3. Approval Workflow — flow multi-level approval dengan timeout reminder
  4. Payroll Generation — flow parallel job, kalkulasi GROSS/DEDUCTIONS/NET, lock
  5. KnowledgeBase AI RAG — flow upload PDF, chunking, embedding, query AI
  6. WFA Approval After Clock-In — flow post-approval WFA

### C. Data Flow Diagram

Diagram aliran data Level 0 dan Level 1 tersedia di:
- `docs/architecture/data-flow-diagram.md`:
  - DFD Level 0 (Context Diagram) — 24 aliran data antara 9 entitas eksternal dan sistem
  - DFD Level 1 Attendance Process — 6 proses inti presensi
  - DFD Level 1 Leave Management Process — 10 proses inti cuti
  - DFD Level 1 Payroll Process — 9 proses inti penggajian + RAG

### D. Entity Relationship Diagram

ERD source of truth dalam format DBML tersedia di:
- `docs/architecture/erd.dbml` — 48 tabel dengan relasi lengkap, index, dan constraint
- Dapat divisualisasikan via dbdiagram.io atau tools DBML lainnya

### E. API Contracts

Dokumentasi 43 endpoint RESTful API tersedia di:
- `docs/api/api-contracts.md` — request/response JSON untuk setiap endpoint
- Base URL: `/api/v1/`
- Autentikasi: Bearer Token (Sanctum) — belum terinstall (task.md §1.3)

### F. Security Configuration

Konfigurasi keamanan lengkap tersedia di:
- `docs/security/security-config.md` — CipherSweet, 2FA, RBAC, OAuth, password policy
- `docs/security/error-handling-strategy.md` — Fallback logic, custom exception HTTP codes
- `docs/security/caching-strategy.md` — Cache driver, key naming, TTL strategy

### G. Deployment Architecture

Diagram deployment dan infrastruktur tersedia di:
- `docs/architecture/deployment-diagram.md` — VPS, Neon PostgreSQL, External APIs
- `docs/deployment/deployment-guide.md` — Panduan setup VPS, SSL, CI/CD

### H. Class Diagram

Diagram class UML untuk seluruh model (25 existing + 4 new), service classes, jobs, commands, notifications, dan enums tersedia di:
- `docs/architecture/class-diagram.md`
