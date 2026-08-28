# User Manual HRConnect — Manager

**PT Daya Cipta Mandiri Solusi**  
**Versi:** 1.0 | **Tanggal:** 28 Agustus 2026

---

## Daftar Isi

1. [Login](#1-login)
2. [Dashboard Manager](#2-dashboard-manager)
3. [Approval Subordinate](#3-approval-subordinate)
4. [Riwayat Approval](#4-riwayat-approval)
5. [Inbox Manager](#5-inbox-manager)
6. [Laporan Tim](#6-laporan-tim)
7. [Kasbon Tim](#7-kasbon-tim)
8. [Akses Employee](#8-akses-employee)

---

## 1. Login

1. Buka browser → akses `http://localhost:8000`
2. Masukkan **email** dan **password**
3. Klik **Masuk**

> **Akun Demo:** `manager@hrconnect.test` / `Manager1234!!`

---

## 2. Dashboard Manager

Dashboard menampilkan:

| Widget | Keterangan |
|--------|------------|
| **Approval Pending** | Jumlah pengajuan menunggu approval |
| **Tim Hari Ini** | Status kehadiran subordinate |
| **Ringkasan Cuti** | Pengajuan cuti tim |
| **Notifikasi** | Pesan terbaru |

---

## 3. Approval Subordinate

### 3.1 Approval Cuti

1. Klik menu **Approval** atau akses `/approvals`
2. Lihat daftar pengajuan **Menunggu**
3. Klik pengajuan untuk detail
4. Klik **Setuju** atau **Tolak**
5. Masukkan **catatan** (jika menolak)

### 3.2 Approval Lembur

1. Buka menu **Approval** → **Lembur**
2. Review jam lembur dan deskripsi
3. **Setuju** atau **Tolak**

### 3.3 Approval Reimbursement

1. Buka menu **Approval** → **Reimbursement**
2. Review bukti expense
3. **Setuju** atau **Tolak**

### 3.4 Approval Shift Swap

1. Buka menu **Approval** → **Tukar Shift**
2. Review permintaan tukar shift
3. **Setuju** atau **Tolak**

---

## 4. Riwayat Approval

1. Buka menu **Approval** → **Riwayat**
2. Lihat semua approval yang sudah diproses
3. Filter berdasarkan **tanggal** atau **status**

| Kolom | Keterangan |
|-------|------------|
| **Tanggal** | Tanggal pengajuan |
| **Karyawan** | Nama subordinate |
| **Jenis** | Cuti, Lembur, Reimbursement |
| **Status** | Disetuju/Ditolak |
| **Catatan** | Keterangan |

---

## 5. Inbox Manager

1. Klik menu **Inbox** atau akses `/admin/inbox`
2. Lihat pesan masuk dari tim
3. Balas atau follow-up

---

## 6. Laporan Tim

1. Klik menu **Laporan** atau akses `/admin/reports`
2. Pilih **Jenis Laporan**:
   - Laporan Kehadiran
   - Laporan Lembur
   - Laporan Cuti
3. Pilih **Periode**
4. Klik **Download** untuk export

---

## 7. Kasbon Tim

1. Klik menu **Kasbon Tim** atau akses `/team-kasbon`
2. Lihat pengajuan kasbon subordinate
3. **Setuju** atau **Tolak**

---

## 8. Akses Employee

Sebagai Manager, kamu juga bisa mengakses semua fitur Employee:

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
