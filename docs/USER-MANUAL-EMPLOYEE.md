# User Manual HRConnect — Employee

**PT Daya Cipta Mandiri Solusi**  
**Versi:** 1.0 | **Tanggal:** 28 Agustus 2026

---

## Daftar Isi

1. [Login](#1-login)
2. [Dashboard](#2-dashboard)
3. [Absensi (Clock-In/Out)](#3-absensi-clock-inout)
4. [Riwayat Absensi](#4-riwayat-absensi)
5. [Ajukan Cuti](#5-ajukan-cuti)
6. [Lembur](#6-lembur)
7. [Reimbursement](#7-reimbursement)
8. [Tukar Shift](#8-tukar-shift)
9. [Work From Home](#9-work-from-home)
10. [Pengajuan Dokumen](#10-pengajuan-dokumen)
11. [Kasbon](#11-kasbon)
12. [Payslip/Gaji](#12-payslipgaji)
13. [Knowledge Base](#13-knowledge-base)
14. [Notifikasi](#14-notifikasi)
15. [Profil Saya](#15-profil-saya)

---

## 1. Login

1. Buka browser → akses `http://localhost:8000`
2. Masukkan **email** dan **password**
3. Klik **Masuk**

> **Akun Demo:** `employee@hrconnect.test` / `password`

---

## 2. Dashboard

Dashboard menampilkan ringkasan:

| Widget | Keterangan |
|--------|------------|
| **Status Hari Ini** | Sudah clock-in/belum |
| **Jadwal Minggu Ini** | Jam kerja hari ini |
| **Sisa Cuti** | Kuota cuti tahunan tersisa |
| **Notifikasi** | Pesan terbaru |

---

## 3. Absensi (Clock-In/Out)

### 3.1 Clock-In

1. Klik menu **Absen** di sidebar
2. Pastikan **GPS aktif**
3. Arahkan wajah ke kamera
4. Tunggu hingga berhasil

### 3.2 Clock-Out

1. Klik menu **Absen**
2. Pilih **Clock Out**
3. Lakukan face recognition

### 3.3 Syarat Absensi

| Syarat | Keterangan |
|--------|------------|
| **GPS** | Harus aktif, radius 50m dari kantor |
| **Wajah** | Harus terlihat jelas, cahaya cukup |
| **Waktu** | Sesuai jam kerja (08:00-17:00) |

---

## 4. Riwayat Absensi

1. Klik menu **Riwayat Absensi**
2. Filter berdasarkan **bulan**
3. Klik tanggal untuk detail

| Kolom | Keterangan |
|-------|------------|
| **Tanggal** | Tanggal absensi |
| **Jam Masuk** | Waktu clock-in |
| **Jam Keluar** | Waktu clock-out |
| **Status** | Hadir, Terlambat, Alpa |
| **Keterangan** | Catatan tambahan |

---

## 5. Ajukan Cuti

### 5.1 Cara Mengajukan

1. Klik menu **Ajukan Cuti**
2. Pilih **Jenis Cuti**
3. Pilih **Tanggal Mulai** dan **Tanggal Selesai**
4. Masukkan **Alasan**
5. Klik **Ajukan**

### 5.2 Status Pengajuan

| Status | Artinya |
|--------|---------|
| **Menunggu** | Belum diproses |
| **Disetujui Atasan** | Sudah disetujui manager |
| **Disetujui Final** | Sudah disetujui HR |
| **Ditolak** | Tidak disetujui |

---

## 6. Lembur

### 6.1 Cara Mengajukan

1. Klik menu **Lembur**
2. Klik **Ajukan Lembur**
3. Pilih **Tanggal**
4. Masukkan **Jam Mulai** dan **Jam Selesai**
5. Masukkan **Deskripsi** pekerjaan
6. Klik **Kirim**

### 6.2 Upah Lembur

| Hari | Tarif |
|------|-------|
| Hari Kerja | 1.5x per jam |
| Hari Libur | 2x per jam |
| Malam (18:00-24:00) | +0.5x per jam |

---

## 7. Reimbursement

### 7.1 Cara Mengajukan

1. Klik menu **Reimbursement**
2. Klik **Ajukan Reimbursement**
3. Pilih **Kategori** (Transport, Makan, ATK, dll)
4. Masukkan **Judul**, **Tanggal**, **Jumlah**
5. Upload **Bukti** (struk/foto)
6. Klik **Kirim**

### 7.2 Kategori

| Kategori | Maksimum/Bulan |
|----------|----------------|
| Transport | Rp 500.000 |
| Makan | Rp 300.000 |
| ATK | Rp 200.000 |
| Internet | Rp 150.000 |

---

## 8. Tukar Shift

1. Klik menu **Tukar Shift**
2. Klik **Ajukan Tukar Shift**
3. Pilih **Tanggal** yang ingin ditukar
4. Pilih **Target** (karyawan yang akan ditukar)
5. Masukkan **Alasan**
6. Klik **Kirim**

---

## 9. Work From Home

1. Klik menu **WFH**
2. Klik **Ajukan WFH**
3. Pilih **Tanggal**
4. Masukkan **Alasan**
5. Klik **Kirim**

---

## 10. Pengajuan Dokumen

1. Klik menu **Dokumen**
2. Klik **Ajukan Dokumen**
3. Pilih **Jenis Dokumen** (SK Kerja, Surat Gaji, dll)
4. Masukkan **Keterangan**
5. Klik **Kirim**

---

## 11. Kasbon

1. Klik menu **Kasbon**
2. Lihat **Riwayat Kasbon** saya
3. Ajukan kasbon baru (jika diperlukan)

---

## 12. Payslip/Gaji

1. Klik menu **Gaji** atau akses `/payroll`
2. Pilih **Periode** gaji
3. Klik **Download Payslip** untuk PDF

### 12.1 Komponen Gaji

| Komponen | Keterangan |
|----------|------------|
| Gaji Pokok | Gaji bulanan |
| Tunjangan | Transport, makan, dll |
| Lembur | Upah lembur |
| Potongan | PPh21, BPJS, kasbon |

---

## 13. Knowledge Base

### 13.1 Cari Artikel

1. Klik menu **Knowledge Base**
2. Ketik **kata kunci** di pencarian
3. Klik artikel yang relevan

### 13.2 Chat AI

1. Klik menu **KB Chat**
2. Ketik **pertanyaan**
3. Tekan **Enter**
4. AI akan menjawab

---

## 14. Notifikasi

1. Klik icon **lonceng** di pojok kanan atas
2. Lihat notifikasi terbaru
3. Klik untuk detail

---

## 15. Profil Saya

1. Klik icon **profil** di pojok kanan atas
2. Pilih **Profil Saya**
3. Edit data yang diperlukan
4. Klik **Simpan**

### 15.1 Ganti Password

1. Buka **Profil Saya**
2. Scroll ke **Ubah Password**
3. Masukkan password lama dan baru
4. Klik **Simpan**

---

## Kontak IT Support

- **Email:** it@hrconnect.test
- **Telepon:** (021) 1234-5678
- **WhatsApp:** 0812-3456-7890

---

**HRConnect — Sistem HRIS PT Daya Cipta Mandiri Solusi**
