# User Manual HRConnect — Admin/HR

**PT Daya Cipta Mandiri Solusi**  
**Versi:** 1.0 | **Tanggal:** 28 Agustus 2026

---

## Daftar Isi

1. [Login](#1-login)
2. [Dashboard Admin](#2-dashboard-admin)
3. [Manajemen Karyawan](#3-manajemen-karyawan)
4. [Absensi Karyawan](#4-absensi-karyawan)
5. [Cuti Karyawan](#5-cuti-karyawan)
6. [Lembur Karyawan](#6-lembur-karyawan)
7. [Reimbursement](#7-reimbursement)
8. [Jadwal Kerja](#8-jadwal-kerja)
9. [Libur](#9-libur)
10. [Dokumen & Template](#10-dokumen--template)
11. [Import/Export](#11-importexport)
12. [Notifikasi](#12-notifikasi)
13. [Kolaborasi](#13-kolaborasi)
14. [Analytics](#14-analytics)

---

## 1. Login

1. Buka browser → akses `http://localhost:8000`
2. Masukkan **email** dan **password**
3. Klik **Masuk**

> **Akun Demo:** `hr@hrconnect.test` / `password`

---

## 2. Dashboard Admin

Dashboard menampilkan:

| Widget | Keterangan |
|--------|------------|
| **Total Karyawan** | Jumlah karyawan aktif |
| **Kehadiran Hari Ini** | Persentase hadir |
| **Pengajuan Pending** | Cuti, lembur, reimbursement |
| **Gaji Bulan Ini** | Status payroll |
| **Notifikasi** | Pesan terbaru |

---

## 3. Manajemen Karyawan

### 3.1 Data Karyawan

1. Klik menu **Data Karyawan** atau akses `/admin/employees`
2. Gunakan **filter** dan **pencarian**
3. Klik nama untuk detail

### 3.2 Tambah Karyawan

1. Klik **Tambah Karyawan**
2. Isi form:
   - **Data Diri:** Nama, NIK, email, telepon, tanggal lahir
   - **Data Pekerjaan:** Jabatan, divisi, status
   - **Data Gaji:** Gaji pokok, tunjangan
   - **Data Lain:** Alamat, pendidikan, keluarga
3. Klik **Simpan**

### 3.3 Edit Karyawan

1. Klik nama karyawan
2. Klik **Edit**
3. Update data yang diperlukan
4. Klik **Simpan**

### 3.4 Hapus Karyawan

1. Klik nama karyawan
2. Klik **Hapus**
3. Konfirmasi penghapusan

---

## 4. Absensi Karyawan

### 4.1 Daftar Absensi

1. Klik menu **Absensi** atau akses `/admin/attendances`
2. Filter berdasarkan **tanggal**, **karyawan**, **status**
3. Klik untuk detail

### 4.2 Laporan Absensi

1. Klik menu **Laporan Absensi** atau akses `/admin/attendances/report`
2. Pilih **periode**
3. Klik **Download** (PDF/Excel)

### 4.3 Import Absensi

1. Buka menu **Import/Export** → **Absensi**
2. Upload file Excel sesuai template
3. Klik **Import**

### 4.4 Koreksi Presensi

1. Klik menu **Koreksi Presensi** atau akses `/admin/attendance-corrections`
2. Lihat pengajuan koreksi
3. **Setuju** atau **Tolak**

---

## 5. Cuti Karyawan

### 5.1 Daftar Pengajuan Cuti

1. Klik menu **Cuti** atau akses `/admin/leaves`
2. Lihat daftar pengajuan
3. Filter berdasarkan **status**, **karyawan**

### 5.2 Approval Cuti

1. Klik pengajuan cuti
2. Review kuota dan alasan
3. Klik **Approve** atau **Reject**
4. Masukkan **catatan** (jika menolak)

### 5.3 Kuota Cuti

1. Buka **Master Data** → **Hak Cuti**
2. Lihat kuota per karyawan
3. Edit kuota jika diperlukan

---

## 6. Lembur Karyawan

### 6.1 Daftar Pengajuan Lembur

1. Klik menu **Lembur** atau akses `/admin/overtime`
2. Lihat daftar pengajuan
3. Filter berdasarkan **tanggal**, **karyawan**

### 6.2 Approval Lembur

1. Klik pengajuan lembur
2. Review jam dan deskripsi
3. Klik **Approve** atau **Reject**

---

## 7. Reimbursement

### 7.1 Daftar Pengajuan

1. Klik menu **Reimbursement** atau akses `/admin/reimbursements`
2. Lihat daftar pengajuan
3. Filter berdasarkan **status**, **kategori**

### 7.2 Approval Reimbursement

1. Klik pengajuan
2. Review bukti expense
3. Klik **Approve** atau **Reject**

---

## 8. Jadwal Kerja

### 8.1 Daftar Jadwal

1. Klik menu **Jadwal** atau akses `/admin/schedules`
2. Lihat jadwal yang ada
3. Klik **Tambah Jadwal** untuk baru

### 8.2 Assign Shift

1. Klik jadwal
2. Pilih **karyawan**
3. Pilih **shift**
4. Pilih **tanggal**
5. Klik **Simpan**

### 8.3 Shift Swap

1. Klik menu **Tukar Shift** atau akses `/admin/shift-swaps`
2. Lihat pengajuan tukar shift
3. **Approve** atau **Reject**

---

## 9. Libur

### 9.1 Daftar Libur

1. Klik menu **Libur** atau akses `/admin/holidays`
2. Lihat hari libur yang sudah ada

### 9.2 Tambah Libur

1. Klik **Tambah Libur**
2. Masukkan **nama**, **tanggal**, **jenis**
3. Klik **Simpan**

---

## 10. Dokumen & Template

### 10.1 Template Dokumen

1. Klik menu **Template Dokumen** atau akses `/admin/document-templates`
2. Lihat template yang ada
3. Klik **Tambah Template** untuk baru

### 10.2 Pengajuan Dokumen

1. Klik menu **Dokumen** atau akses `/admin/document-requests`
2. Lihat pengajuan dari karyawan
3. **Approve** atau **Reject**
4. Upload dokumen yang sudah jadi

---

## 11. Import/Export

### 11.1 Import Karyawan

1. Klik menu **Import/Export** → **Karyawan**
2. Download **template** Excel
3. Isi data sesuai template
4. Upload file
5. Klik **Import**

### 11.2 Export Data

1. Buka menu **Import/Export**
2. Pilih **jenis data** (Karyawan, Absensi, dll)
3. Klik **Download**

---

## 12. Notifikasi

1. Klik menu **Notifikasi** atau akses `/admin/notifications`
2. Lihat notifikasi terbaru
3. Klik untuk detail

---

## 13. Kolaborasi

1. Klik menu **Kolaborasi** atau akses `/admin/collaboration`
2. Kirim pesan ke tim
3. Share file dokumen

---

## 14. Analytics

1. Klik menu **Analytics** atau akses `/admin/analytics`
2. Lihat grafik:
   - Kehadiran karyawan
   - Tren cuti
   - Statistik lembur
   - Payroll summary

---

## Kontak IT Support

- **Email:** it@hrconnect.test
- **Telepon:** (021) 1234-5678
- **WhatsApp:** 0812-3456-7890

---

**HRConnect — Sistem HRIS PT Daya Cipta Mandiri Solusi**
