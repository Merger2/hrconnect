# SOP HRConnect — Sistem HRIS PT Daya Cipta Mandiri Solusi

**Versi:** 1.0  
**Tanggal:** 28 Agustus 2026  
**Status:** Aktif

---

## Daftar Isi

1. [Pendahuluan](#1-pendahuluan)
2. [Akun & Login](#2-akun--login)
3. [Modul Absensi](#3-modul-absensi)
4. [Modul Cuti](#4-modul-cuti)
5. [Modul Lembur](#5-modul-lembur)
6. [Modul Reimbursement](#6-modul-reimbursement)
7. [Modul Payroll & Payslip](#7-modul-payroll--payslip)
8. [Modul Knowledge Base](#8-modul-knowledge-base)
9. [Modul Manajemen Karyawan (Admin)](#9-modul-manajemen-karyawan-admin)
10. [Modul Approval](#10-modul-approval)
11. [Modul Pengaturan Sistem (Super Admin)](#11-modul-pengaturan-sistem-super-admin)
12. [Troubleshooting](#12-troubleshooting)

---

## 1. Pendahuluan

### 1.1 Tentang HRConnect

HRConnect adalah Sistem Human Resource Information System (HRIS) yang dikembangkan untuk PT Daya Cipta Mandiri Solusi. Sistem ini mengelola seluruh proses HR dari master data karyawan, absensi, cuti, lembur, reimbursement, hingga payroll.

### 1.2 Fitur Utama

| Modul | Fitur |
|-------|-------|
| **Absensi** | Clock-in/out, face recognition, GPS geofencing, riwayat |
| **Cuti** | Pengajuan, approval, kuota cuti, kalender |
| **Lembur** | Pengajuan, approval, kalkulasi upah |
| **Reimbursement** | Pengajuan, approval, kategori expense |
| **Payroll** | Kalkulasi gaji, PPh21, BPJS, payslip PDF |
| **Knowledge Base** | AI chatbot, artikel, pencarian |
| **Manajemen Karyawan** | CRUD karyawan, master data, import/export |
| **Approval** | Multi-level approval, workflow |

### 1.3 Role & Permission

| Role | Akses |
|------|-------|
| **Employee** | Absensi, cuti, lembur, reimbursement, payslip, KB |
| **Manager** | Semua akses employee + approval subordinate |
| **Finance** | Semua akses admin + payroll, reimbursement |
| **Admin/HR** | Semua modul kecuali pengaturan sistem |
| **Super Admin** | Akses penuh termasuk pengaturan sistem |

---

## 2. Akun & Login

### 2.1 Login

1. Buka browser, akses `http://localhost:8000` (development) atau URL production
2. Masukkan **email** dan **password**
3. Klik **Masuk** atau tekan **Enter**

### 2.2 Akun Demo

| Role | Email | Password |
|------|-------|----------|
| Employee | `employee@hrconnect.test` | `password` |
| Manager | `manager@hrconnect.test` | `Manager1234!!` |
| Finance | `finance@hrconnect.test` | `Finance1234!!` |
| Admin/HR | `hr@hrconnect.test` | `password` |
| Super Admin | `admin@hrconnect.local` | `ChangeMe!2026` |

### 2.3 Ganti Password

1. Klik icon profil di pojok kanan atas
2. Pilih **Profil Saya**
3. Scroll ke bagian **Ubah Password**
4. Masukkan password lama dan password baru
5. Klik **Simpan**

---

## 3. Modul Absensi

### 3.1 Clock-In (Presensi Masuk)

1. Buka menu **Absen** atau akses `/scan`
2. Pastikan **GPS aktif** dan berada dalam radius 50m dari kantor
3. Arahkan wajah ke kamera untuk **face recognition**
4. Tunggu hingga presensi berhasil
5. Status akan menunjukkan: **Hadir**, **Terlambat**, atau **Alpa**

### 3.2 Clock-Out (Presensi Keluar)

1. Buka menu **Absen** atau akses `/scan`
2. Pilih **Clock Out**
3. Lakukan face recognition
4. Presensi keluar tercatat

### 3.3 Riwayat Absensi

1. Buka menu **Riwayat Absensi** atau akses `/attendance-history`
2. Filter berdasarkan **bulan** atau **tanggal**
3. Lihat detail: jam masuk, jam keluar, status, keterlambatan

### 3.4 Koreksi Presensi

1. Buka menu **Koreksi Presensi** atau akses `/attendance-corrections`
2. Klik **Ajukan Koreksi**
3. Pilih tanggal yang ingin dikoreksi
4. Masukkan jam masuk/keluar yang benar
5. Sertakan **alasan** koreksi
6. Klik **Kirim** — menunggu approval HR

### 3.5 GPS Geofencing

- **Radius:** 50 meter dari kantor pusat
- **Lokasi:** Jl. Pegambiran No.292 B, Rawamangun, Jakarta Timur
- **Toleransi:** 15 menit dari jam masuk resmi

---

## 4. Modul Cuti

### 4.1 Pengajuan Cuti

1. Buka menu **Ajukan Cuti** atau akses `/apply-leave`
2. Pilih **Jenis Cuti** (Tahunan, Sakit, Melahirkan, dll)
3. Pilih **Tanggal Mulai** dan **Tanggal Selesai**
4. Masukkan **Alasan** cuti
5. Upload **Bukti** (jika cuti sakit)
6. Klik **Ajukan**

### 4.2 Jenis Cuti

| Jenis | Kuota/Tahun | Keterangan |
|-------|-------------|------------|
| Cuti Tahunan | 12 hari | Diberikan setiap tahun |
| Cuti Sakit | 12 hari | Perlu bukti surat dokter |
| Cuti Melahirkan | 90 hari | Khusus karyawan wanita |
| Cuti Besar | 30 hari | Setelah 6 tahun kerja |
| Cuti Bersama | Sesuai kebijakan | Hari libur bersama |

### 4.3 Status Pengajuan

| Status | Keterangan |
|--------|------------|
| **Menunggu** | Menunggu approval manager |
| **Disetujui Atasan** | Disetujui manager, menunggu HR |
| **Disetujui Final** | Sudah disetujui HR |
| **Ditolak** | Ditolak oleh manager/HR |

### 4.4 Membatalkan Cuti

1. Buka **Riwayat Cuti**
2. Pilih pengajuan yang ingin dibatalkan
3. Klik **Batalkan**
4. Konfirmasi pembatalan

---

## 5. Modul Lembur

### 5.1 Pengajuan Lembur

1. Buka menu **Lembur** atau akses `/overtime`
2. Klik **Ajukan Lembur**
3. Pilih **Tanggal** lembur
4. Masukkan **Jam Mulai** dan **Jam Selesai**
5. Masukkan **Deskripsi** pekerjaan
6. Klik **Kirim**

### 5.2 Kalkulasi Upah Lembur

- **Hari Kerja:** 1.5x upah per jam
- **Hari Libur:** 2x upah per jam
- **Malam (18:00-24:00):** +0.5x upah per jam

### 5.3 Status Lembur

| Status | Keterangan |
|--------|------------|
| **Menunggu** | Menunggu approval |
| **Disetujui** | Sudah disetujui, masuk payroll |
| **Ditolak** | Ditolak oleh atasan |

---

## 6. Modul Reimbursement

### 6.1 Pengajuan Reimbursement

1. Buka menu **Reimbursement** atau akses `/reimbursement`
2. Klik **Ajukan Reimbursement**
3. Pilih **Kategori** (Transport, Makan, ATK, dll)
4. Masukkan **Judul** dan **Tanggal Expense**
5. Masukkan **Jumlah** (Rp)
6. Upload **Bukti** (struk/foto)
7. Klik **Kirim**

### 6.2 Kategori Reimbursement

| Kategori | Maksimum |
|----------|----------|
| Transport | Rp 500.000/bulan |
| Makan | Rp 300.000/bulan |
| ATK | Rp 200.000/bulan |
| Internet | Rp 150.000/bulan |

### 6.3 Status Reimbursement

| Status | Keterangan |
|--------|------------|
| **Sedang Diproses** | Menunggu approval |
| **Disetujui Atasan** | Disetujui manager |
| **Menunggu Finance** | Menunggu approval finance |
| **Disetujui Final** | Sudah disetujui, masuk payroll |
| **Dibayarkan** | Sudah dicairkan |

---

## 7. Modul Payroll & Payslip

### 7.1 Melihat Payslip

1. Buka menu **Gaji** atau akses `/payroll`
2. Pilih **Periode** gaji yang ingin dilihat
3. Klik **Download Payslip** untuk PDF

### 7.2 Komponen Gaji

| Komponen | Keterangan |
|----------|------------|
| **Gaji Pokok** | Gaji bulanan sesuai jabatan |
| **Tunjangan** | Transport, makan, komunikasi |
| **Lembur** | Upah lembur yang disetujui |
| **Potongan** | PPh21, BPJS, kasbon, dll |
| **Bonus** | Insentif, THR (jika ada) |

### 7.3 PPh21 (Pajak Penghasilan)

- Dihitung otomatis berdasarkan **TER (Tarif Efektif Rata-rata)**
- Golongan disesuaikan dengan **PTKP (Penghasilan Tidak Kena Pajak)**
- Laporan pajak terintegrasi dengan DJP

### 7.4 BPJS

| Komponen | Porsi Perusahaan | Porsi Karyawan |
|----------|------------------|----------------|
| Kesehatan | 4% | 1% |
| Ketenagakerjaan | 0.88% | 0.3% |

---

## 8. Modul Knowledge Base

### 8.1 Mencari Artikel

1. Buka menu **Knowledge Base** atau akses `/knowledge-base`
2. Ketik **kata kunci** di kolom pencarian
3. Klik artikel yang relevan

### 8.2 Chat AI (RAG)

1. Buka menu **KB Chat** atau akses `/knowledge-base/chat`
2. Ketik **pertanyaan** di kolom chat
3. Tekan **Enter** atau klik **Kirim**
4. AI akan menjawab berdasarkan knowledge base perusahaan

### 8.3 Contoh Pertanyaan

- "Apa saja jenis cuti yang tersedia?"
- "Bagaimana cara pengajuan reimbursement?"
- "Berapa radius geofencing absensi?"
- "Apa kebijakan work from home?"

---

## 9. Modul Manajemen Karyawan (Admin)

### 9.1 Data Karyawan

1. Buka menu **Data Karyawan** atau akses `/admin/employees`
2. Gunakan **filter** dan **pencarian** untuk menemukan karyawan
3. Klik **Tambah Karyawan** untuk menambah baru

### 9.2 Form Tambah Karyawan

1. **Data Diri:** Nama, NIK, email, telepon, tanggal lahir
2. **Data Pekerjaan:** Jabatan, divisi, status karyawan
3. **Data Gaji:** Gaji pokok, tunjangan, BPJS
4. **Data Lain:** Alamat, pendidikan, keluarga

### 9.3 Import/Export

1. Buka menu **Import/Export** atau akses `/admin/import-export/users`
2. **Import:** Upload file Excel sesuai template
3. **Export:** Klik **Download** untuk export data

### 9.4 Jadwal Kerja

1. Buka menu **Jadwal** atau akses `/admin/schedules`
2. Buat **jadwal baru** atau **edit** jadwal yang ada
3. Assign **shift** ke karyawan

### 9.5 Libur

1. Buka menu **Libur** atau akses `/admin/holidays`
2. Tambahkan **hari libur nasional** dan **cuti bersama**

---

## 10. Modul Approval

### 10.1 Approval Cuti

1. Buka menu **Approval** atau akses `/approvals`
2. Lihat daftar pengajuan yang menunggu approval
3. Klik **Setuju** atau **Tolak**
4. Masukkan **catatan** (jika menolak)

### 10.2 Approval Lembur

1. Buka menu **Approval** → **Lembur**
2. Review jam lembur dan deskripsi
3. **Setuju** atau **Tolak**

### 10.3 Approval Reimbursement

1. Buka menu **Approval** → **Reimbursement**
2. Review bukti expense
3. **Setuju** atau **Tolak**

### 10.4 History Approval

1. Buka menu **Approval** → **Riwayat**
2. Lihat semua approval yang sudah diproses
3. Filter berdasarkan **tanggal** atau **status**

---

## 11. Modul Pengaturan Sistem (Super Admin)

### 11.1 Pengaturan Umum

1. Buka menu **Pengaturan** atau akses `/admin/settings`
2. Edit **Nama Perusahaan**, **Logo**, **Alamat**
3. Simpan perubahan

### 11.2 Role & Permission

1. Buka menu **Role & Permission** atau akses `/admin/roles-permissions`
2. Klik **role** untuk edit permission
3. Centang/centang permission yang diinginkan
4. Simpan

### 11.3 Master Data

1. Buka menu **Master Data** di sidebar
2. Kelola: **Divisi**, **Jabatan**, **Shift**, **Jenis Cuti**
3. Tambah/edit/hapus sesuai kebutuhan

### 11.4 Activity Logs

1. Buka menu **Activity Logs** atau akses `/admin/activity-logs`
2. Lihat seluruh aktivitas pengguna
3. Filter berdasarkan **user**, **tanggal**, atau **aksi**

---

## 12. Troubleshooting

### 12.1 Login Gagal

| Masalah | Solusi |
|---------|--------|
| Password salah | Gunakan **Lupa Password** atau hubungi admin |
| Email tidak terdaftar | Hubungi admin untuk daftarkan akun |
| Akun terkunci | Tunggu 15 menit atau hubungi admin |

### 12.2 Absensi Gagal

| Masalah | Solusi |
|---------|--------|
| GPS tidak terdeteksi | Aktifkan **lokasi** di HP/laptop |
| Face recognition gagal | Pastikan wajah **terlihat jelas** dan **cahaya cukup** |
| Di luar geofence | Pastikan berada dalam **radius 50m** dari kantor |

### 12.3 Error 500

| Masalah | Solusi |
|---------|--------|
| Server error | Refresh halaman atau hubungi IT |
| Session expired | Login ulang |

### 12.4 Kontak IT Support

- **Email:** it@hrconnect.test
- **Telepon:** (021) 1234-5678
- **WhatsApp:** 0812-3456-7890

---

## Lampiran

### A. Cuti Tahunan (Template Surat)

```
PT DAYA CIPTA MANDIRI SOLUSI
Jl. Pegambiran No.292 B, RT.15/RW.8
Rawamangun, Kec. Pulo Gadung
Kota Jakarta Timur, DKI Jakarta 13220

SURAT KETERANGAN CUTI

Yang bertanda tangan di bawah ini:
Nama: [Nama Karyawan]
Jabatan: [Jabatan]
Divisi: [Divisi]

Dengan ini menyatakan bahwa yang bersangkutan akan mengambil cuti:
Jenis Cuti: [Jenis Cuti]
Tanggal Mulai: [Tanggal Mulai]
Tanggal Selesai: [Tanggal Selesai]
Alasan: [Alasan]

Demikian surat ini dibuat dengan sebenar-benarnya.

Jakarta, [Tanggal]
HR Manager

[Nama HR Manager]
```

---

**Dokumen ini merupakan bagian dari Sistem HRIS HRConnect PT Daya Cipta Mandiri Solusi.**
**Untuk pertanyaan, hubungi tim IT Development.**
