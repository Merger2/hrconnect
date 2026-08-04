# PRD — HRConnect Release 1

**Versi:** Draft v0.1  
**Tanggal:** 2026-08-03  
**Owner:** [Diisi owner produk]  
**Reviewer:** [Diisi reviewer, misal: tech lead / HR lead / finance lead]  
**Status:** Draft — perlu finalisasi keputusan pengguna di bagian **Risiko & Open Decisions**

---

## 1. Status Dokumen & Keputusan yang Sudah Disepakati

Dokumen ini mencatat kebutuhan dan gate *Release 1* HRConnect. Keputusan berikut sudah disepakati dan menjadi landasan PRD:

- **Target release pertama:** deployment internal untuk **satu perusahaan** dengan **maksimal 50 user awal**.
- **Tujuh modul wajib masuk Release 1** dan harus mencapai **production penuh**:
  1. Master data karyawan
  2. Absensi & jadwal
  3. Cuti & approval
  4. Payroll & payslip
  5. Dokumen & HR checklist
  6. Reports & import/export
  7. AI Knowledge Base
- **Quality gate adalah constraint absolut.** Target waktu kurang dari satu bulan adalah target optimistis; timeline harus mundur jika gate belum lulus.
- **Tidak ada fitur baru di luar scope** sampai Release 1 lolos gate.
- **Non-goal:** SaaS multi-tenant komersial, layer edition/license/commercial, feature-lock baru, dan ekspansi fitur di luar tujuh modul di atas.
- **Model payroll Release 1:** gross salary; rincian komponen dan formula PPh21 tetap harus difinalkan Finance/HR.
- **Kebijakan absensi Release 1:** face-only; PIN fallback tidak diizinkan.
- **Approval default Release 1:** alur Manager → HR.
- **Kebijakan geofence Release 1:** radius 50 meter dengan toleransi 15 menit.
- **Face failure Release 1:** clock-in/out ditolak; karyawan menggunakan alur koreksi HR.
- **PPh21 Release 1:** payroll bulanan menggunakan TER.
- **Kalender kerja Release 1:** 5 hari kerja sebagai baseline.
- **AI provider Release 1:** Gemini menggunakan konfigurasi provider yang sudah ada; corpus dibatasi pada dokumen HR yang disetujui.
- **Data migration Release 1:** full history, termasuk histori absensi, cuti, payroll, dan dokumen yang disepakati.
- **Target recovery minimum:** RPO 24 jam dan RTO 4 jam.
- **Target kualitas AI awal:** minimal 90% jawaban relevan dan setiap jawaban menyertakan citation.

---

## 2. Problem Statement & Tujuan Produk

### Problem Statement
HRConnect dibuat untuk mengatasi kondisi berikut pada operasi HR perusahaan target:

- Data karyawan tersebar di berbagai spreadsheet/system dan tidak konsisten.
- Absensi, jadwal, cuti, dan lembur diproses manual sehingga rawan error dan sulit diaudit.
- Payroll dan payslip membutuhkan waktu lama serta rentan terhadap perbedaan perhitungan.
- Dokumen HR dan checklist onboarding/offboarding tidak terpusat.
- Laporan HR dan data ekspor/impor sering tidak sinkron dengan data operasional.
- Karyawan dan HR tidak punya akses cepat ke basis pengetahuan perusahaan.

### Tujuan Produk

Membangun **HRIS internal terintegrasi** untuk satu perusahaan yang:

- Menyatukan master data karyawan, kehadiran, cuti, payroll, dokumen, dan knowledge base.
- Menyediakan approval workflow yang jelas dan jejak audit.
- Mengotomatisasi perhitungan payroll dan payslip sesuai kebijakan perusahaan.
- Memberikan laporan serta kemampuan impor/ekspor yang andal.
- Menyediakan AI Knowledge Base yang menjawab dari sumber resmi dan mengutip referensinya.

---

## 3. Product Boundary, Target User, Asumsi, & Non-Goals

### Product Boundary

- **Deployment:** Single-tenant internal untuk satu perusahaan (bukan SaaS multi-tenant).
- **Akses:** Web-first (dengan PWA/Capacitor sebagai akses pendukung), mengakses data perusahaan yang sama.
- **Lingkungan:** Production berjalan di PostgreSQL dengan fitur `pgvector`, `pg_trgm`, dan `pgcrypto`.

### Target User

| Role | Kegunaan utama Release 1 |
|------|--------------------------|
| **Employee** | Lihat profil, absen, ajukan cuti/izin, lihat payslip, tanya AI KB. |
| **Manager** | Setujui cuti/izin, lihat laporan tim, absen tim. |
| **HR** | Kelola master data, jadwal, dokumen, laporan, dan checklist. |
| **Finance** | Verifikasi payroll, akses laporan payroll, payslip. |
| **Admin/IT** | Kelola user, role, RBAC, monitoring, backup. |

### Asumsi

- Server production tersedia dan stabil.
- Data awal karyawan akan dimigrasi atau di-input sebelum go-live.
- Provider AI (embedding + LLM) tersedia dan memiliki quota yang cukup untuk pilot.
- Kebijakan internal (kebijakan cuti, payroll, approval, dll.) akan ditetapkan sebelum go-live.

### Non-Goals

- **SaaS multi-tenant komersial** untuk banyak perusahaan.
- **Edition/license/commercial layer** (enterprise/community edition, license key, tiering harga).
- **Feature-lock baru** di luar yang sudah ada saat ini.
- **Ekspansi fitur** di luar tujuh modul Release 1 (misal: recruitment, training, performance review, employee engagement, marketplace, integrasi pihak ketiga baru).

---

## 4. Scope Release 1

### Outcome Umum Release 1

- Semua tujuh modul berfungsi production-ready untuk satu perusahaan.
- Semua user dapat login dengan role dan permission yang sesuai.
- Data tersimpan aman, teraudit, dan dapat di-backup/restore.
- Tidak ada *silent degraded behavior* atau *placeholder* yang menyembunyikan kegagalan sistem.

### Acceptance Criteria per Modul

#### 1. Master Data Karyawan

- [ ] CRUD data karyawan, jabatan, departemen, status kontrak, dan data pendukung.
- [ ] Validasi data wajib (misal: NIK, email, status aktif) dan unique constraint.
- [ ] Riwayat perubahan data dapat dilihat (audit trail).
- [ ] Impor data karyawan dari format standar (CSV/Excel) dengan preview error sebelum commit.

#### 2. Absensi & Jadwal

- [ ] Clock-in/clock-out dengan geofence dan face recognition sesuai kebijakan perusahaan.
- [ ] Mode production menggunakan face recognition tanpa PIN fallback.
- [ ] Geofence menggunakan radius 50 meter dengan toleransi waktu 15 menit.
- [ ] Jika face recognition gagal, clock-in/out ditolak dan tersedia alur koreksi HR dengan audit trail.
- [ ] Pengaturan shift/jadwal individu dan grup.
- [ ] Kalender kerja baseline 5 hari diterapkan konsisten pada jadwal, absensi, dan payroll.
- [ ] Koreksi absensi dengan approval workflow dan audit trail.
- [ ] Dashboard ringkasan kehadiran untuk HR dan manager.

#### 3. Cuti & Approval

- [ ] Pengajuan cuti/izin dengan saldo cuti, jenis cuti, dan lampiran.
- [ ] Approval workflow default mengikuti alur Manager → HR dan dapat dikonfigurasi untuk pengecualian.
- [ ] Notifikasi ke pengaju dan approval queue.
- [ ] Audit status dari draft → pending → approved/rejected.

#### 4. Payroll & Payslip

- [ ] Perhitungan payroll gross salary bulanan dengan PPh21 berbasis TER, komponen pendapatan, dan potongan sesuai kebijakan.
- [ ] Generate payslip per periode untuk karyawan yang berhak.
- [ ] Payroll dapat di-lock dan di-review sebelum publish.
- [ ] Karyawan dapat melihat payslip historisnya sendiri.

#### 5. Dokumen & HR Checklist

- [ ] Repository dokumen karyawan dengan kategori dan permission.
- [ ] HR checklist untuk onboarding, offboarding, dan milestone lain.
- [ ] Notifikasi checklist yang belum selesai.
- [ ] Export daftar dokumen dan checklist.

#### 6. Reports & Import/Export

- [ ] Laporan kehadiran, cuti, payroll, dan karyawan dalam format tabel/printable.
- [ ] Export data ke Excel/CSV/PDF untuk laporan standar.
- [ ] Import data masal dengan validasi error dan rollback.
- [ ] Report permissions mengikuti RBAC.

#### 7. AI Knowledge Base

- [ ] Admin dapat mengelola knowledge base entries (dokumen, FAQ, policy).
- [ ] Embedding 768 dimensi dihasilkan secara otomatis dan tersimpan di `pgvector`.
- [ ] Chat KB menjawab dari corpus internal, mencantumkan sumber/citation.
- [ ] Jika AI gagal atau corpus tidak relevan, sistem menampilkan error/kegagalan secara jelas, bukan jawaban palsu.

---

## 5. Production Gate Lintas Sistem

Gate ini adalah **prasyarat absolut** untuk go-live. Release 1 tidak boleh di-deploy production penuh sampai semua gate lulus.

### P0/P1 Defect Gate

| Kategori | Definisi | Gate |
|----------|----------|------|
| **P0** | Kerusakan data, kebocoran data, gagal login, payroll salah, security breach, sistem tidak bisa diakses. | **0 open** sebelum go-live. |
| **P1** | Fitur core tidak berfungsi, queue/mail mati, backup gagal, AI silent failure, degradasi tanpa notifikasi. | **0 open** sebelum go-live. |

### Auth, RBAC, & Company Scope

- [ ] Login dan autentikasi berfungsi untuk semua role target.
- [ ] RBAC sesuai `permission_keys` JSON pada role; tidak ada bypass permission.
- [ ] Semua data berada dalam scope satu perusahaan; tidak ada bocoran antar-user di luar permission.

### Test Core

- [ ] Unit/feature test untuk setiap modul Release 1 lulus.
- [ ] Linting PHP (`pint`) dan static analysis (`phpstan`) lulus.
- [ ] Minimal 1 end-to-end flow per modul utama (happy path + 1 negative path).

### Backup, Restore, & Disaster Recovery

- [ ] Backup otomatis database dan file storage tersedia.
- [ ] Drill restore berhasil memulihkan data ke titik konsisten.
- [ ] RPO/RTO ditetapkan sebelum go-live (lihat **Open Decisions**).

### Queue, Mail, & Monitoring

- [ ] Queue worker berjalan untuk payroll, email, embedding, dan notifikasi.
- [ ] Email notifikasi (cuti, approval, payroll) dapat dikirim dan terlacak.
- [ ] Monitoring dasar tersedia (log aplikasi, error tracker, queue status).

### Security & No Placeholder/Silent Degradation

- [ ] Tidak ada password/default credential di production.
- [ ] Tidak ada endpoint yang mengekspos data tanpa autentikasi/otorisasi.
- [ ] Tidak ada fitur yang *silent fail* — setiap kegagalan harus muncul di log/UI.
- [ ] Tidak ada *placeholder copy*, tombol palsu, atau data dummy yang tidak ditandai sebagai dummy.

---

## 6. Acceptance Khusus AI Knowledge Base

AI KB memiliki gate tambahan yang wajib lulus.

- [ ] **Embedding nyata.** Setiap knowledge entry harus memiliki embedding 768 dimensi yang benar-benar dihasilkan oleh model embedding. Tidak boleh ada fake/random embedding diam-diam.
- [ ] **Failure terlihat.** Jika provider AI gagal, queue gagal, atau embedding tidak terbuat, sistem harus menampilkan error/peringatan di UI dan log; tidak boleh menjawab dengan respons palsu.
- [ ] **Dataset evaluasi.** Tersedia dataset evaluasi dengan minimal 20 pertanyaan-answer yang telah diverifikasi oleh HR.
- [ ] **Citation/source.** Setiap jawaban AI harus mencantumkan sumber knowledge entry yang digunakan.
- [ ] **Permission & isolation.** AI hanya menjawab dari corpus yang boleh diakses oleh user yang bertanya; tenant/company scope harus dijaga.
- [ ] **Timeout, retry, dan cost limit.** Request AI memiliki timeout, retry policy, dan batasan cost/usage; jika melebihi batas, sistem gagal secara terlihat.
- [ ] **Quality threshold.** Minimal 90% jawaban evaluasi relevan dan setiap jawaban menyertakan citation sebelum go-live.

---

## 7. Non-Functional Requirements

### Security

- Autentikasi wajib untuk setiap endpoint data.
- Otorisasi per role/permission untuk setiap fitur.
- Password tidak boleh disimpan plain-text; enkripsi data sensitif di database.
- Audit log untuk perubahan data penting (payroll, master data, approval, dokumen).

### Reliability

- Uptime target internal: **99.5%** selama jam kerja production.
- Semua proses background (payroll, email, embedding, notifikasi) menggunakan queue.
- Degradasi atau kegagalan sistem harus terlihat jelas, tidak di-silence.

### Auditability

- Setiap perubahan status (cuti, absensi, payroll, approval) memiliki jejak timestamp dan actor.
- Laporan audit dapat diekspor.
- Data yang dihapus menggunakan soft-delete atau arsip, kecuali kebijakan lain ditetapkan.

### Performance

- Halaman utama tampil dalam **< 2 detik** untuk 95% request pada 50 user aktif.
- Laporan dan export untuk 50 user tidak boleh mengganggu operasi core.
- Query embedding dan AI chat memiliki timeout yang jelas.

### Operability

- Dokumen deployment dan runbook dasar tersedia.
- Admin dapat melihat status queue, error log, dan health check.
- Backup dan restore dapat dijalankan tanpa downtime panjang.

### Privacy

- Data karyawan (KTP, NPWP, gaji, dokumen pribadi) hanya diakses sesuai permission.
- Penggunaan data untuk AI embedding harus sesuai kebijakan internal perusahaan.

---

## 8. Out of Scope & Change-Control Rule

### Out of Scope Release 1

- SaaS multi-tenant untuk banyak perusahaan.
- Layer edition, license, atau commercial/tiering.
- Feature-lock baru di luar yang sudah ada.
- Modul baru: recruitment, training, performance review, employee engagement, benefit marketplace, integrasi ERP/akuntansi pihak ketiga.
- Mobile native app (Capacitor/PWA boleh jika sudah ada, tetapi bukan scope utama).

### Change-Control Rule

- Selama Release 1 belum lulus production gate, **setiap permintaan fitur di luar tujuh modul wajib ditolak**.
- Jika ada kebutuhan kritis yang muncul, harus melalui review owner dan reviewer; solusi sementara harus tidak memperluas scope Release 1.
- Bug/security pada modul Release 1 boleh diperbaiki; penambahan fitur baru tidak boleh.

---

## 9. Roadmap (Tanpa Tanggal Pasti)

Urutan fase tidak boleh di-skip; tanggal ditentukan setelah gate tiap fase lulus.

1. **Baseline & Hardening**  
   - Stabilkan infrastruktur, auth, RBAC, dan database.  
   - Perbaiki bug P0/P1 yang ada.  
   - Siapkan CI/CD, monitoring, dan backup.

2. **Module Validation**  
   - Implementasi/verifikasi ketujuh modul.  
   - Acceptance test per modul.  
   - Perbaiki defect sampai gate P0/P1 clear.

3. **Integrated Pilot**  
   - Jalankan pilot dengan user terbatas (maksimal 50 user).  
   - Uji end-to-end real: absensi, cuti, payroll satu siklus.  
   - Uji AI KB dengan dataset evaluasi.

4. **Production Gate**  
   - Lulus lintas sistem gate (auth, test, backup, security, AI).  
   - Sign-off oleh owner, reviewer, dan stakeholder kunci.

5. **Go-Live**  
   - Deploy production penuh.  
   - Monitoring intensif 2 minggu pertama.  
   - Setelah stabil, baru diizinkan untuk merencanakan Release 2.

---

## 10. Risiko & Open Decisions (Harus Diisi Pengguna)

Keputusan berikut harus ditetapkan sebelum finalisasi PRD dan go-live:

| # | Open Decision | Dampak jika tidak diisi | Owner yang harus mengisi |
|---|---------------|------------------------|--------------------------|
| 1 | **Kebijakan payroll & PPh21:** model gross salary, periode bulanan, TER, dan kalender 5 hari sudah dipilih; komponen, formula detail, dan pengecualian pajak masih harus difinalkan. | Payroll tidak bisa dihitung akurat. | Finance + HR |
| 2 | **Aturan absensi & geofence:** face-only, radius 50 meter, toleransi 15 menit, penolakan saat face gagal dengan koreksi HR, dan kalender 5 hari sudah dipilih; aturan shift/pengecualian masih harus difinalkan. | Absensi tidak konsisten atau dianggap tidak adil. | HR + Operations |
| 3 | **Hirarki approval:** alur default Manager → HR sudah dipilih; pengecualian per jenis request dan jalur payroll masih harus difinalkan. | Workflow macet atau tidak valid. | HR + Manager |
| 4 | **Target AI quality threshold:** target awal minimal 90% jawaban relevan dan setiap jawaban memiliki citation; dataset/provider detail masih harus ditetapkan. | AI KB tidak punya gate yang jelas. | HR + Tech Lead |
| 5 | **Backup RPO/RTO:** target sudah dipilih RPO 24 jam dan RTO 4 jam; mekanisme backup, lokasi penyimpanan, dan drill masih harus ditetapkan. | Drill restore tidak punya target. | IT/Admin |
| 6 | **Data migration:** full history sudah dipilih; sumber data, mapping, cleansing, validasi, dan cutover masih harus ditetapkan. | Go-live terhambat karena data tidak lengkap atau tidak konsisten. | HR + IT |
| 7 | **AI provider & quota:** Gemini dengan konfigurasi saat ini sudah dipilih; quota, budget, model final, dan rate limit masih harus ditetapkan. | AI KB gagal atau over-budget. | Tech Lead + Finance |
| 8 | **Privacy policy internal:** data mana yang boleh masuk AI embedding, retensi data. | Risiko pelanggaran privasi. | HR + Legal |

### Risiko Utama

- **Scope creep:** penambahan fitur kecil di tengah jalan bisa menunda gate.  
  *Mitigasi:* change-control rule ketat dan review mingguan.
- **Kegagalan AI provider:** embedding atau LLM tidak tersedia.  
  *Mitigasi:* fallback error terlihat + evaluasi offline + cost limit.
- **Payroll error:** formula atau kebijakan belum final.  
  *Mitigasi:* lock kebijakan sebelum payroll pertama dan review manual.
- **Data migration:** data lama tidak bersih.  
  *Mitigasi:* migration plan + data cleansing + uji impor.
- **Downtime production:** backup/restore belum teruji.  
  *Mitigasi:* drill restore dan monitoring sebelum go-live.

---

## 11. Definition of Done & Sign-Off Checklist

### Definition of Done (per fitur Release 1)

- [ ] Kode sesuai convention dan lulus `pint` + `phpstan`.
- [ ] Test (unit/feature) untuk happy path dan negative path tersedia dan lulus.
- [ ] UI/UX sesuai design system yang berlaku (jika ada).
- [ ] Audit trail dan permission check tersedia untuk data sensitif.
- [ ] Tidak ada silent failure; error handling dan log tersedia.
- [ ] Dokumentasi runbook/endpoint diperbarui jika diperlukan.

### Pre-Go-Live Sign-Off Checklist

- [ ] Owner PRD telah menyetujui scope dan keputusan.
- [ ] Reviewer telah mereview acceptance criteria dan open decisions.
- [ ] Tujuh modul telah lulus acceptance test per modul.
- [ ] Production gate lintas sistem telah lulus (P0/P1 clear, auth, test, backup, queue, security).
- [ ] AI Knowledge Base telah lulus gate khusus (embedding, failure, eval, citation, permission, timeout, quality threshold).
- [ ] Kebijakan payroll, absensi, approval, dan AI telah ditetapkan.
- [ ] Backup/restore drill telah berhasil.
- [ ] Data migration plan telah disetujui.
- [ ] Go-live date ditetapkan hanya setelah semua gate di atas lulus.

---

**Catatan:** PRD ini adalah *Draft v0.1*. Status implementasi dan hasil test akan dicatat terpisah; dokumen ini tidak mengklaim fitur atau test saat ini sudah lulus sampai sign-off final selesai.
