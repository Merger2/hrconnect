# HRConnect - Wireframe Specifications

> **Dokumen ini berisi deskripsi lengkap setiap halaman/wireframe HRConnect.**
> Gunakan sebagai referensi saat membuat UI dengan Livewire + Flux UI.

---

## 1. AUTH PAGES

### 1.1 Login Page (`resources/views/pages/auth/login.blade.php`)

```
┌─────────────────────────────────────────────┐
│                  HRConnect                   │
│            Sistem Informasi HRIS             │
│                                              │
│  ┌─────────────────────────────────────┐    │
│  │  Email                              │    │
│  │  [_____________________________]    │    │
│  │                                     │    │
│  │  Password                           │    │
│  │  [_____________________________] 👁 │    │
│  │                                     │    │
│  │  ☐ Remember Me                      │    │
│  │                                     │    │
│  │  [───────── Masuk ─────────]        │    │
│  │                                     │    │
│  │  Lupa password?                     │    │
│  │  Belum punya akun? Daftar           │    │
│  │  ─────── atau ───────               │    │
│  │  [🔵 Masuk dengan Google]           │    │
│  └─────────────────────────────────────┘    │
└─────────────────────────────────────────────┘
```

**Komponen Flux UI:**
- `flux:input` untuk email & password
- `flux:checkbox` untuk remember me
- `flux:button` untuk submit (variant="primary")
- `flux:link` untuk lupa password & daftar

### 1.2 Register Page (`resources/views/pages/auth/register.blade.php`)

```
┌─────────────────────────────────────────────┐
│              Daftar Akun Baru                │
│                                              │
│  ┌─────────────────────────────────────┐    │
│  │  Nama Lengkap                       │    │
│  │  [_____________________________]    │    │
│  │                                     │    │
│  │  Email                              │    │
│  │  [_____________________________]    │    │
│  │                                     │    │
│  │  Password                           │    │
│  │  [_____________________________] 👁 │    │
│  │                                     │    │
│  │  Konfirmasi Password                │    │
│  │  [_____________________________] 👁 │    │
│  │                                     │    │
│  │  [───────── Daftar ─────────]       │    │
│  │                                     │    │
│  │  Sudah punya akun? Masuk            │    │
│  └─────────────────────────────────────┘    │
└─────────────────────────────────────────────┘
```

### 1.3 Two-Factor Challenge (`resources/views/pages/auth/two-factor-challenge.blade.php`)

```
┌─────────────────────────────────────────────┐
│          Verifikasi Two-Factor               │
│                                              │
│  ┌─────────────────────────────────────┐    │
│  │  Masukkan kode 6 digit dari         │    │
│  │  aplikasi authenticator Anda        │    │
│  │                                     │    │
│  │  ┌───┬───┬───┬───┬───┬───┐         │    │
│  │  │   │   │   │   │   │   │         │    │
│  │  └───┴───┴───┴───┴───┴───┘         │    │
│  │                                     │    │
│  │  [──── Verifikasi ────]             │    │
│  │                                     │    │
│  │  Gunakan recovery code              │    │
│  │  Kirim ulang kode                   │    │
│  └─────────────────────────────────────┘    │
└─────────────────────────────────────────────┘
```

---

## 2. EMPLOYEE SELF SERVICE (ESS) - MOBILE PWA

### 2.1 ESS Dashboard (`resources/views/employee/dashboard.blade.php`)

```
┌─────────────────────────────────┐
│ ☰  HRConnect          🔔  👤   │  ← Header
├─────────────────────────────────┤
│  Selamat Pagi, John Doe!        │
│  Staff IT Department            │
│                                 │
│  ┌───────────────────────────┐  │
│  │  JAM SEKARANG             │  │
│  │  08:45:32                 │  │
│  │                           │  │
│  │  [──── CLOCK IN ────]     │  │  ← Tombol besar
│  └───────────────────────────┘  │
│                                 │
│  ┌─────┐ ┌─────┐ ┌─────┐      │  ← Quick Stats
│  │ H-1 │ │ S-2 │ │ A-0 │      │
│  │Hadir│ │Sakit│ │Alpha│      │
│  └─────┘ └─────┘ └─────┘      │
│                                 │
│  ┌───────────────────────────┐  │
│  │  📋 Status Hari Ini       │  │
│  │  Status: ✓ Sudah Clock In │  │
│  │  Masuk: 08:30             │  │
│  │  Shift: Pagi (08:00-17:00)│  │
│  └───────────────────────────┘  │
│                                 │
│  Menu Cepat:                    │
│  ┌──────┐ ┌──────┐ ┌──────┐    │
│  │ 📅   │ │ 🏖️   │ │ 💰   │    │
│  │Absen │ │Cuti  │ │Gaji  │    │
│  └──────┘ └──────┘ └──────┘    │
│  ┌──────┐ ┌──────┐ ┌──────┐    │
│  │ 📝   │ │ 💳   │ │ 👤   │    │
│  │Lembur│ │Pinjam│ │Profil│    │
│  └──────┘ └──────┘ └──────┘    │
│                                 │
├─────────────────────────────────┤
│  🏠    📋    🔔    👤          │  ← Bottom Nav
│  Home  Menu  Notif   Profil    │
└─────────────────────────────────┘
```

### 2.2 Clock In Page (`resources/views/employee/attendance/clock-in.blade.php`)

```
┌─────────────────────────────────┐
│ ← Clock In                      │
├─────────────────────────────────┤
│                                 │
│  ┌─────────────────────────┐    │
│  │                         │    │
│  │   [  CAMERA PREVIEW  ]  │    │  ← Face capture
│  │                         │    │
│  │   ┌───────────────┐     │    │
│  │   │   Wajah Anda  │     │    │  ← Face outline
│  │   └───────────────┘     │    │
│  │                         │    │
│  └─────────────────────────┘    │
│                                 │
│  Status GPS:                    │
│  📍 -6.2088, 106.8456          │
│  ✓ Dalam radius kantor (50m)   │
│                                 │
│  📅 Senin, 8 Mei 2026           │
│  ⏰ 08:30:45 WIB                │
│                                 │
│  Shift: Pagi (08:00 - 17:00)   │
│                                 │
│  [──── CLOCK IN SEKARANG ────]  │
│                                 │
└─────────────────────────────────┘
```

**Livewire Component:** `ClockIn`
- `flux:button` untuk submit
- Alpine.js untuk face capture
- GPS locator component
- Real-time clock

### 2.3 Attendance History (`resources/views/employee/attendance/history.blade.php`)

```
┌─────────────────────────────────┐
│ ← Riwayat Absensi               │
├─────────────────────────────────┤
│  [◀ Mei 2026 ▶]                 │  ← Month navigator
│                                 │
│  ┌───────────────────────────┐  │
│  │ Senin 04 │ 08:30 │ 17:05 │  │
│  │ ✓ Tepat Waktu            │  │
│  └───────────────────────────┘  │
│  ┌───────────────────────────┐  │
│  │ Selasa 05│ 08:45 │ 17:00 │  │
│  │ ⚠ Terlambat 15 menit     │  │
│  └───────────────────────────┘  │
│  ┌───────────────────────────┐  │
│  │ Rabu 06  │ 08:25 │ 17:10 │  │
│  │ ✓ Tepat Waktu            │  │
│  └───────────────────────────┘  │
│  ┌───────────────────────────┐  │
│  │ Kamis 07 │  -    │   -   │  │
│  │ ✗ Alpha                  │  │
│  └───────────────────────────┘  │
│  ┌───────────────────────────┐  │
│  │ Jumat 08 │ 08:30 │   -   │  │
│  │ ✓ Belum Clock Out        │  │
│  └───────────────────────────┘  │
│                                 │
│  Summary: H-20 | S-2 | A-1     │
│                                 │
│  [📥 Export PDF]                │
└─────────────────────────────────┘
```

### 2.4 Leave Request (`resources/views/employee/leave/create.blade.php`)

```
┌─────────────────────────────────┐
│ ← Pengajuan Cuti                │
├─────────────────────────────────┤
│                                 │
│  Tipe Cuti:                     │
│  ┌─────────────────────────┐    │
│  │ Cuti Tahunan ▼          │    │  ← flux:select
│  └─────────────────────────┘    │
│                                 │
│  Sisa Cuti Tahunan: 10 hari     │
│                                 │
│  Tanggal Mulai:                 │
│  ┌─────────────────────────┐    │
│  │ 📅 12 Mei 2026          │    │  ← flux:input date
│  └─────────────────────────┘    │
│                                 │
│  Tanggal Selesai:               │
│  ┌─────────────────────────┐    │
│  │ 📅 14 Mei 2026          │    │
│  └─────────────────────────┘    │
│                                 │
│  Total: 3 hari kerja            │
│                                 │
│  Alasan:                        │
│  ┌─────────────────────────┐    │
│  │ Keperluan keluarga      │    │  ← flux:textarea
│  │                         │    │
│  └─────────────────────────┘    │
│                                 │
│  Upload Bukti (opsional):       │
│  ┌─────────────────────────┐    │
│  │ 📎 Pilih File           │    │  ← flux:file
│  └─────────────────────────┘    │
│                                 │
│  [──── AJUKAN CUTI ────]        │
│                                 │
└─────────────────────────────────┘
```

### 2.5 Leave History (`resources/views/employee/leave/history.blade.php`)

```
┌─────────────────────────────────┐
│ ← Riwayat Cuti                  │
├─────────────────────────────────┤
│  ┌───────────────────────────┐  │
│  │ 🏖️ Cuti Tahunan          │  │
│  │ 12-14 Mei 2026 (3 hari)   │  │
│  │ Keperluan keluarga        │  │
│  │         🟡 Menunggu       │  │  ← Status badge
│  └───────────────────────────┘  │
│  ┌───────────────────────────┐  │
│  │ 🤒 Cuti Sakit            │  │
│  │ 5-6 Apr 2026 (2 hari)     │  │
│  │ Demam tinggi              │  │
│  │      ✓ Disetujui HRD      │  │
│  └───────────────────────────┘  │
│  ┌───────────────────────────┐  │
│  │ 📋 Cuti Penting          │  │
│  │ 20 Mar 2026 (1 hari)      │  │
│  │ Urusan KTP                │  │
│  │      ✗ Ditolak            │  │
│  │  Alasan: Quota habis      │  │
│  └───────────────────────────┘  │
└─────────────────────────────────┘
```

### 2.6 Payroll Slip (`resources/views/employee/finance/payroll-slip.blade.php`)

```
┌─────────────────────────────────┐
│ ← Slip Gaji                     │
├─────────────────────────────────┤
│  Periode: [Mei 2026 ▼]          │
│                                 │
│  ┌───────────────────────────┐  │
│  │  SLIP GAJI                │  │
│  │  PT. HRConnect Indonesia  │  │
│  │  John Doe - Staff IT      │  │
│  │  Periode: Mei 2026        │  │
│  └───────────────────────────┘  │
│                                 │
│  PENGHASILAN:                   │
│  Gaji Pokok          Rp 8.000K  │
│  Tunjangan Jabatan   Rp 1.500K  │
│  Tunjangan Makan     Rp   600K  │
│  Lembur              Rp   400K  │
│  ─────────────────────────────  │
│  Total Penghasilan   Rp10.500K  │
│                                 │
│  POTONGAN:                      │
│  BPJS Kesehatan      Rp   160K  │
│  BPJS Ketenagakerjaan Rp  120K  │
│  PPh 21              Rp   250K  │
│  Cicilan Pinjaman    Rp   500K  │
│  ─────────────────────────────  │
│  Total Potongan      Rp 1.030K  │
│                                 │
│  ═════════════════════════════  │
│  GAJI BERSIH         Rp 9.470K  │
│  ═════════════════════════════  │
│                                 │
│  [📥 Download PDF]              │
└─────────────────────────────────┘
```

---

## 3. HRD ADMIN PAGES

### 3.1 HRD Dashboard (`resources/views/hrd/dashboard.blade.php`)

```
┌──────────────────────────────────────────────────┐
│ ☰  HRConnect        HRD Dashboard     🔔  👤   │
├──────────────────────────────────────────────────┤
│  Selamat Pagi, Admin HRD!                        │
│                                                   │
│  ┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐   │
│  │  👥 150│ │  ✅120│ │  ⏰ 15│ │  📋 8  │   │
│  │ Karyawan│ │Hadir  │ │Terlmbt│ │Pending │   │
│  └────────┘ └────────┘ └────────┘ └────────┘   │
│                                                   │
│  ┌─────────────────────┐ ┌────────────────────┐ │
│  │ Attendance Hari Ini │ │ Pending Approvals  │ │
│  │                     │ │                    │ │
│  │ John    08:30  ✓   │ │ 🏖️ Cuti - Jane    │ │
│  │ Jane    08:45  ⚠   │ │ 💰 Loan - Bob     │ │
│  │ Bob     -      ✗   │ │ 📝 OT - Alice     │ │
│  │ Alice   07:50  ✓   │ │                    │ │
│  │ ...     ...    ... │ │ [Lihat Semua →]    │ │
│  │                     │ │                    │ │
│  │ [Lihat Semua →]    │ │                    │ │
│  └─────────────────────┘ └────────────────────┘ │
│                                                   │
│  ┌─────────────────────┐ ┌────────────────────┐ │
│  │ Leave Calendar      │ │ Quick Actions      │ │
│  │                     │ │                    │ │
│  │  M  T  W  T  F     │ │ [+] Tambah Karyawan│ │
│  │        J  B  K     │ │ [📅 Atur Shift]    │ │
│  │        (Cuti)      │ │ [💰 Generate Gaji] │ │
│  │                     │ │ [📊 Export Report] │ │
│  └─────────────────────┘ └────────────────────┘ │
│                                                   │
├──────────────────────────────────────────────────┤
│  Dashboard │ Karyawan │ Absensi │ Cuti │ Payroll │
└──────────────────────────────────────────────────┘
```

### 3.2 Employee List (`resources/views/hrd/employees/index.blade.php`)

```
┌──────────────────────────────────────────────────┐
│ ← Manajemen Karyawan              [+ Tambah]    │
├──────────────────────────────────────────────────┤
│                                                   │
│  [🔍 Cari karyawan...]     [Filter ▼] [Export]  │
│                                                   │
│  ┌────────────────────────────────────────────┐  │
│  │ NIK │ Nama │ Dept │ Posisi │ Status │ ⋮ │  │
│  ├────────────────────────────────────────────┤  │
│  │EMP-001│John│IT│Staff│● Aktif│ ⋮│  │
│  │EMP-002│Jane│HR│Spv│● Aktif│ ⋮│  │
│  │EMP-003│Bob│FN│Mgr│● Aktif│ ⋮│  │
│  │EMP-004│Ali│IT│Spv│🟡 Prob│ ⋮│  │
│  │EMP-005│Siti│FN│Staff│⏸ Resign│ ⋮│ │
│  │...│...│...│...│...│ ⋮│  │
│  └────────────────────────────────────────────┘  │
│                                                   │
│  Showing 1-10 of 150    [◀ 1 2 3 ... 15 ▶]      │
│                                                   │
├──────────────────────────────────────────────────┤
│  Dashboard │ 👥Karyawan │ Absensi │ Cuti │ ...  │
└──────────────────────────────────────────────────┘
```

### 3.3 Employee Detail (`resources/views/hrd/employees/show.blade.php`)

```
┌──────────────────────────────────────────────────┐
│ ← Detail Karyawan               [✏️ Edit]       │
├──────────────────────────────────────────────────┤
│                                                   │
│  ┌────────────────────────────────────────────┐  │
│  │  [Foto]  John Doe                          │  │
│  │          EMP-202401-001                    │  │
│  │          Staff IT Department               │  │
│  │          ● Aktif - Full Time               │  │
│  └────────────────────────────────────────────┘  │
│                                                   │
│  [Data Pribadi] [Absensi] [Cuti] [Gaji] [Dokumen]│
│  ──────────────────────────────────────────────  │
│                                                   │
│  DATA PRIBADI:                                   │
│  NIK:                  3201234567890001          │
│  Tempat/Tgl Lahir:     Jakarta, 15 Jan 1990     │
│  Jenis Kelamin:        Laki-laki                │
│  Status Pernikahan:    Menikah                  │
│  Alamat:               Jl. Contoh No. 123       │
│  No. Telepon:          081234567890             │
│  Email:                john@company.com          │
│                                                   │
│  DATA PEKERJAAN:                                 │
│  Department:           IT                        │
│  Posisi:               Staff                     │
│  Manager:              Alice (IT Manager)        │
│  Tanggal Masuk:        15 Jan 2024              │
│  Tipe:                 Full Time                │
│  Status:               Karyawan Tetap           │
│  Gaji Pokok:           Rp 8.000.000             │
│                                                   │
│  RIWAYAT ABSENSI (Bulan Ini):                    │
│  Hadir: 20  Sakit: 1  Alpha: 0  Terlambat: 2    │
│                                                   │
└──────────────────────────────────────────────────┘
```

### 3.4 Leave Calendar (`resources/views/hrd/leaves/calendar.blade.php`)

```
┌──────────────────────────────────────────────────┐
│ ← Kalender Cuti                    [Mei 2026 ▼] │
├──────────────────────────────────────────────────┤
│                                                   │
│       Mei 2026                                   │
│  Min  Sen  Sel  Rab  Kam  Jum  Sab               │
│                    1    2    3    4               │
│   5    6    7    8    9   10   11               │
│  12   13   14   15   16   17   18               │
│  19   20   21   22   23   24   25               │
│  26   27   28   29   30   31                    │
│                                                   │
│  Legend: 🟦 Cuti Tahunan  🟩 Cuti Sakit  🟨 Lainnya│
│                                                   │
│  Cuti pada tanggal 12-14 Mei:                     │
│  ┌────────────────────────────────────────────┐  │
│  │ John Doe │ Cuti Tahunan │ 12-14 Mei │ 🟡  │  │
│  │ Jane Smith│ Cuti Sakit │ 12 Mei │ ✓    │  │
│  └────────────────────────────────────────────┘  │
│                                                   │
├──────────────────────────────────────────────────┤
│  Dashboard │ Karyawan │ Absensi │ 👥Cuti │ ...  │
└──────────────────────────────────────────────────┘
```

### 3.5 Approval Pending (`resources/views/hrd/approvals/pending.blade.php`)

```
┌──────────────────────────────────────────────────┐
│ ← Persetujuan Menunggu                            │
├──────────────────────────────────────────────────┤
│                                                   │
│  [Semua] [Cuti] [Pinjaman] [Lembur] [Reimburse]  │
│  ──────────────────────────────────────────────  │
│                                                   │
│  ┌────────────────────────────────────────────┐  │
│  │ 🏖️ PENGAJUAN CUTI                         │  │
│  │ John Doe - Staff IT                        │  │
│  │ 12-14 Mei 2026 (3 hari)                   │  │
│  │ Alasan: Keperluan keluarga                 │  │
│  │ Level: L2 Manager                          │  │
│  │                                            │  │
│  │ [✓ Setujui]  [✗ Tolak]  [👁️ Detail]      │  │
│  └────────────────────────────────────────────┘  │
│                                                   │
│  ┌────────────────────────────────────────────┐  │
│  │ 💰 PENGAJUAN PINJAMAN                      │  │
│  │ Bob Finance - Finance Manager              │  │
│  │ Rp 10.000.000 / 12 bulan                   │  │
│  │ Cicilan: Rp 833.333/bulan                  │  │
│  │ Level: L3 HRD                              │  │
│  │                                            │  │
│  │ [✓ Setujui]  [✗ Tolak]  [👁️ Detail]      │  │
│  └────────────────────────────────────────────┘  │
│                                                   │
│  ┌────────────────────────────────────────────┐  │
│  │ 📝 PERMINTAAN LEMBUR                       │  │
│  │ Alice IT - IT Manager                      │  │
│  │ 10 Mei 2026 (4 jam)                       │  │
│  │ Alasan: Deploy sistem baru                 │  │
│  │                                            │  │
│  │ [✓ Setujui]  [✗ Tolak]  [👁️ Detail]      │  │
│  └────────────────────────────────────────────┘  │
│                                                   │
└──────────────────────────────────────────────────┘
```

---

## 4. FINANCE ADMIN PAGES

### 4.1 Finance Dashboard (`resources/views/finance/dashboard.blade.php`)

```
┌──────────────────────────────────────────────────┐
│ ☰  HRConnect        Finance Dashboard  🔔  👤  │
├──────────────────────────────────────────────────┤
│  Selamat Pagi, Finance Team!                     │
│                                                   │
│  ┌────────────┐ ┌────────────┐ ┌────────────┐  │
│  │ 💰 Rp 1.2M │ │ ⏳ 8 Loan  │ │ 📋 5 Reimb │  │
│  │ Payroll Mei│ │ Pending    │ │ Pending    │  │
│  └────────────┘ └────────────┘ └────────────┘  │
│                                                   │
│  ┌─────────────────────┐ ┌────────────────────┐ │
│  │ Payroll Status      │ │ Pending Requests   │ │
│  │                     │ │                    │ │
│  │ April 2026: ✓ Done  │ │ 💰 Loan - Bob     │ │
│  │ Mei 2026: 🟡 Proses │ │ 💳 Reimb - Jane   │ │
│  │                     │ │ 💳 Reimb - Ali    │ │
│  │ [Generate Mei →]    │ │                    │ │
│  │                     │ │ [Lihat Semua →]    │ │
│  └─────────────────────┘ └────────────────────┘ │
│                                                   │
│  ┌─────────────────────┐ ┌────────────────────┐ │
│  │ Loan Summary        │ │ Tax Summary        │ │
│  │                     │ │                    │ │
│  │ Total Pinjaman Aktif│ │ PPh21 Mei: Rp 45M │ │
│  │ 25 karyawan        │ │ BPJS: Rp 18M       │ │
│  │ Total: Rp 250M     │ │                    │ │
│  │                       │ │                    │ │
│  └─────────────────────┘ └────────────────────┘ │
│                                                   │
├──────────────────────────────────────────────────┤
│  Dashboard │ 💰Payroll │ Pinjaman │ Reimburse │  │
└──────────────────────────────────────────────────┘
```

### 4.2 Payroll Generation (`resources/views/finance/payroll/generate.blade.php`)

```
┌──────────────────────────────────────────────────┐
│ ← Generate Payroll                                │
├──────────────────────────────────────────────────┤
│                                                   │
│  Periode: [Mei 2026 ▼]                           │
│  Department: [Semua Department ▼]                │
│                                                   │
│  ┌────────────────────────────────────────────┐  │
│  │  PREVIEW PAYROLL                           │  │
│  │                                            │  │
│  │  Total Karyawan: 150                       │  │
│  │  Total Gaji Pokok: Rp 1.200.000.000       │  │
│  │  Total Tunjangan: Rp   350.000.000        │  │
│  │  Total Potongan:  Rp   180.000.000        │  │
│  │  ──────────────────────────────────        │  │
│  │  TOTAL GAJI BERSIH: Rp 1.370.000.000      │  │
│  └────────────────────────────────────────────┘  │
│                                                   │
│  ⚠️ Pastikan semua data absensi, lembur, dan     │
│  reimburse sudah final sebelum generate.         │
│                                                   │
│  [Preview Detail]  [Generate Sekarang]           │
│                                                   │
│  Progress:                                       │
│  ████████████░░░░░░░░ 60% (90/150)              │
│  Memproses...                                    │
│                                                   │
└──────────────────────────────────────────────────┘
```

---

## 5. ADMIN (SUPER ADMIN) PAGES

### 5.1 Admin Settings - Company (`resources/views/admin/settings/company.blade.php`)

```
┌──────────────────────────────────────────────────┐
│ ← Pengaturan Perusahaan                           │
├──────────────────────────────────────────────────┤
│                                                   │
│  Logo Perusahaan:                                │
│  ┌────────┐                                      │
│  │ [Logo] │ [Upload Logo]                        │
│  └────────┘                                      │
│                                                   │
│  Nama Perusahaan:                                │
│  [PT. HRConnect Indonesia               ]        │
│                                                   │
│  Kode Perusahaan:                                │
│  [HRCONNECT                             ]        │
│                                                   │
│  NPWP:                                           │
│  [12.345.678.9-012.345                  ]        │
│                                                   │
│  Alamat:                                         │
│  ┌──────────────────────────────────────────┐   │
│  │ Jl. Sudirman No. 123, Jakarta Pusat     │   │
│  │ 10220                                    │   │
│  └──────────────────────────────────────────┘   │
│                                                   │
│  Telepon:          [021-12345678        ]        │
│  Email:            [hr@hrconnect.com    ]        │
│                                                   │
│  Provinsi:         [DKI Jakarta ▼       ]        │
│  Kota:             [Jakarta Pusat ▼     ]        │
│  Kecamatan:        [Tanah Abang ▼      ]        │
│  Kelurahan:        [Bendungan Hilir ▼  ]        │
│                                                   │
│  [Simpan Perubahan]                              │
│                                                   │
├──────────────────────────────────────────────────┤
│  Company │ Attendance │ Leave │ Branding │ ...  │
└──────────────────────────────────────────────────┘
```

### 5.2 Admin Settings - Attendance (`resources/views/admin/settings/attendance.blade.php`)

```
┌──────────────────────────────────────────────────┐
│ ← Pengaturan Absensi                              │
├──────────────────────────────────────────────────┤
│                                                   │
│  Radius Geofence Default:                        │
│  [100] meter                                     │
│                                                   │
│  Threshold Face Recognition:                     │
│  [0.85] (85%)                                    │
│                                                   │
│  Toleransi Keterlambatan:                        │
│  [15] menit                                      │
│                                                   │
│  Anti Fake GPS:                                  │
│  ☑️ Aktifkan validasi anti fake GPS              │
│  ☑️ Blokir jika GPS mocked terdeteksi            │
│                                                   │
│  Face Recognition:                               │
│  ☑️ Wajibkan face recognition saat clock in      │
│  ☑️ Wajibkan face recognition saat clock out     │
│                                                   │
│  WFA (Work From Anywhere):                       │
│  ☑️ Izinkan karyawan request WFA                 │
│  Perlu persetujuan:  ☑️ Ya  ☐ Tidak             │
│                                                   │
│  [Simpan Perubahan]                              │
│                                                   │
├──────────────────────────────────────────────────┤
│  Company │ 👥Attendance │ Leave │ Branding │... │
└──────────────────────────────────────────────────┘
```

### 5.3 Knowledge Base Chat (`resources/views/admin/knowledge-base/chat.blade.php`)

```
┌──────────────────────────────────────────────────┐
│ ← AI Knowledge Base Chat                          │
├──────────────────────────────────────────────────┤
│                                                   │
│  ┌────────────────────────────────────────────┐  │
│  │  🤖 AI: Halo! Saya siap membantu Anda     │  │
│  │  menjawab pertanyaan seputar kebijakan     │  │
│  │  perusahaan. Apa yang bisa saya bantu?     │  │
│  │                                            │  │
│  │  👤 Anda: Berapa hari cuti tahunan untuk   │  │
│  │  karyawan baru?                            │  │
│  │                                            │  │
│  │  🤖 AI: Berdasarkan kebijakan perusahaan,  │  │
│  │  karyawan baru mendapatkan 12 hari cuti    │  │
│  │  tahunan per tahun. Cuti ini dapat digunakan│ │
│  │  setelah masa probation (3 bulan) selesai. │  │
│  │  Sumber: HR Policy v2.1, Section 4.2      │  │
│  │                                            │  │
│  └────────────────────────────────────────────┘  │
│                                                   │
│  ┌──────────────────────────────────────────┐   │
│  │ Ketik pertanyaan Anda...            [📤] │   │
│  └──────────────────────────────────────────┘   │
│                                                   │
│  💡 Tips: Tanyakan tentang kebijakan HR,         │
│  prosedur IT, atau panduan karyawan.             │
│                                                   │
├──────────────────────────────────────────────────┤
│  Articles │ ➕Upload │ 💬Chat │ Settings        │
└──────────────────────────────────────────────────┘
```

---

## 6. SHARED COMPONENTS

### 6.1 Approval Timeline Component (`resources/views/components/approval-timeline.blade.php`)

```
┌─────────────────────────────────┐
│  Alur Persetujuan               │
│                                 │
│  ● L1 - Supervisor              │
│    ✓ Disetujui oleh John        │
│    10 Mei 2026, 09:30          │
│    │                            │
│  ● L2 - Manager                 │
│    ✓ Disetujui oleh Jane        │
│    10 Mei 2026, 14:15          │
│    │                            │
│  ● L3 - HRD                     │
│    🟡 Menunggu                  │
│    │                            │
│  ○ L4 - Director                │
│    ⏳ Belum sampai              │
│                                 │
└─────────────────────────────────┘
```

### 6.2 Status Badge (`resources/views/components/status-badge.blade.php`)

```
┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│ ✓ Disetujui  │  │ 🟡 Menunggu  │  │ ✗ Ditolak    │
│ (green)      │  │ (yellow)     │  │ (red)        │
└──────────────┘  └──────────────┘  └──────────────┘

┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│ ● Aktif      │  │ 🟡 Probation │  │ ⏸ Resign     │
│ (green)      │  │ (yellow)     │  │ (gray)       │
└──────────────┘  └──────────────┘  └──────────────┘
```

---

## 7. RESPONSIVE BREAKPOINTS

| Breakpoint | Device | Layout |
|------------|--------|--------|
| `< 640px` | Mobile PWA | Single column, bottom nav, full-width cards |
| `640px - 1024px` | Tablet | 2-column grid, sidebar nav |
| `> 1024px` | Desktop | 3-column grid, full sidebar + header |

---

## 8. FLUX UI COMPONENTS YANG SERING DIPAKAI

| Component | Usage | Example |
|-----------|-------|---------|
| `flux:button` | Tombol aksi | `<flux:button variant="primary">Simpan</flux:button>` |
| `flux:input` | Input text | `<flux:input label="Nama" wire:model="name" />` |
| `flux:select` | Dropdown | `<flux:select label="Dept" wire:model="dept">` |
| `flux:checkbox` | Checkbox | `<flux:checkbox label="Aktif" wire:model="active" />` |
| `flux:textarea` | Text area | `<flux:textarea label="Alamat" wire:model="address" />` |
| `flux:modal` | Modal dialog | `<flux:modal wire:model="showModal">` |
| `flux:badge` | Badge status | `<flux:badge variant="success">Aktif</flux:badge>` |
| `flux:card` | Card container | `<flux:card>Content</flux:card>` |
| `flux:table` | Data table | `<flux:table>` |
| `flux:navlist` | Navigation | `<flux:navlist>` |

---

*Dokumen ini merepresentasikan semua halaman yang akan dibuat.*
*Terakhir diupdate: 2026-05-31*
