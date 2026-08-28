# User Manual HRConnect — Finance

**PT Daya Cipta Mandiri Solusi**  
**Versi:** 1.0 | **Tanggal:** 28 Agustus 2026

---

## Daftar Isi

1. [Login](#1-login)
2. [Dashboard Finance](#2-dashboard-finance)
3. [Payroll](#3-payroll)
4. [Reimbursement](#4-reimbursement)
5. [Kasbon](#5-kasbon)
6. [Laporan Keuangan](#6-laporan-keuangan)
7. [PPh21 & BPJS](#7-pph21--bpjs)
8. [Akses Employee](#8-akses-employee)

---

## 1. Login

1. Buka browser → akses `http://localhost:8000`
2. Masukkan **email** dan **password**
3. Klik **Masuk**

> **Akun Demo:** `finance@hrconnect.test` / `Finance1234!!`

---

## 2. Dashboard Finance

Dashboard menampilkan:

| Widget | Keterangan |
|--------|------------|
| **Payroll Bulan Ini** | Status payroll aktif |
| **Reimbursement Pending** | Pengajuan menunggu approval |
| **Kasbon Aktif** | Pinjaman yang belum lunas |
| **Notifikasi** | Pesan terbaru |

---

## 3. Payroll

### 3.1 Generate Payroll

1. Klik menu **Payroll** atau akses `/admin/payrolls`
2. Klik **Generate Payroll**
3. Pilih **Periode** (bulan/tahun)
4. Review komponen gaji
5. Klik **Proses**

### 3.2 Review Payroll

1. Buka daftar payroll
2. Klik periode yang ingin direview
3. Review:
   - Gaji pokok
   - Tunjangan
   - Lembur
   - Potongan (PPh21, BPJS, kasbon)
4. Klik **Approve** atau **Reject**

### 3.3 Download Payslip

1. Buka payroll yang sudah di-approve
2. Klik **Download Payslip** (PDF)
3. Kirim ke karyawan

### 3.4 Komponen Gaji

| Komponen | Keterangan |
|----------|------------|
| **Gaji Pokok** | Sesuai jabatan |
| **Tunjangan Transport** | Rp 500.000/bulan |
| **Tunjangan Makan** | Rp 300.000/bulan |
| **Tunjangan Komunikasi** | Rp 200.000/bulan |
| **Upah Lembur** | Sesuai jam lembur |
| **Potongan PPh21** | pajak penghasilan |
| **Potongan BPJS** | Kesehatan + Ketenagakerjaan |
| **Potongan Kasbon** | Angsuran kasbon |

---

## 4. Reimbursement

### 4.1 Approval Reimbursement

1. Klik menu **Reimbursement** atau akses `/admin/reimbursements`
2. Lihat daftar pengajuan **Menunggu**
3. Klik pengajuan untuk detail
4. Review bukti expense
5. Klik **Approve** atau **Reject**

### 4.2 Status Reimbursement

| Status | Keterangan |
|--------|------------|
| **Menunggu** | Belum diproses |
| **Disetujui Atasan** | Disetujui manager |
| **Menunggu Finance** | Menunggu approval finance |
| **Disetujui Final** | Sudah disetujui |
| **Dibayarkan** | Sudah dicairkan |

### 4.3 Link to Payroll

1. Reimbursement yang disetujui otomatis masuk payroll
2. Buka payroll → cek komponen reimbursement

---

## 5. Kasbon

### 5.1 Manajemen Kasbon

1. Klik menu **Kasbon** atau akses `/admin/manage-kasbon`
2. Lihat daftar kasbon aktif
3. Review pengajuan baru

### 5.2 approve Kasbon

1. Klik pengajuan kasbon
2. Review jumlah dan tujuan
3. Klik **Approve** atau **Reject**

### 5.3 Pelunasan

1. Buka kasbon yang sudah disetujui
2. Record pembayaran angsuran
3. Update status pelunasan

---

## 6. Laporan Keuangan

1. Klik menu **Laporan** atau akses `/admin/reports`
2. Pilih **Jenis Laporan**:
   - Laporan Payroll
   - Laporan Reimbursement
   - Laporan Kasbon
   - Laporan PPh21
3. Pilih **Periode**
4. Klik **Download** (PDF/Excel)

---

## 7. PPh21 & BPJS

### 7.1 PPh21 (Pajak Penghasilan)

- Dihitung otomatis berdasarkan **TER**
- Golongan PTKP disesuaikan dengan status karyawan
- Laporan terintegrasi dengan DJP

### 7.2 BPJS

| Komponen | Perusahaan | Karyawan |
|----------|------------|----------|
| Kesehatan | 4% | 1% |
| Ketenagakerjaan | 0.88% | 0.3% |

---

## 8. Akses Employee

Sebagai Finance, kamu juga bisa mengakses semua fitur Employee:

| Fitur | Akses |
|-------|-------|
| Absensi | [OK] Clock-in/out |
| Cuti | [OK] Ajukan cuti |
| Lembur | [OK] Ajukan lembur |
| Reimbursement | [OK] Ajukan reimbursement |
| Payslip | [OK] Lihat/download |
| Knowledge Base | [OK] Cari artikel & chat AI |

---

## Kontak IT Support

- **Email:** it@hrconnect.test
- **Telepon:** (021) 1234-5678
- **WhatsApp:** 0812-3456-7890

---

**HRConnect — Sistem HRIS PT Daya Cipta Mandiri Solusi**
