# User Manual HRConnect — Super Admin

**PT Daya Cipta Mandiri Solusi**  
**Versi:** 1.0 | **Tanggal:** 28 Agustus 2026

---

## Daftar Isi

1. [Login](#1-login)
2. [Dashboard Super Admin](#2-dashboard-super-admin)
3. [Pengaturan Sistem](#3-pengaturan-sistem)
4. [Role & Permission](#4-role--permission)
5. [Master Data](#5-master-data)
6. [Manajemen Karyawan](#6-manajemen-karyawan)
7. [Payroll & Settings](#7-payroll--settings)
8. [Activity Logs](#8-activity-logs)
9. [User Sessions](#9-user-sessions)
10. [System Maintenance](#10-system-maintenance)
11. [Operational Health](#11-operational-health)
12. [Akses Lainnya](#12-akses-lainnya)

---

## 1. Login

1. Buka browser → akses `http://localhost:8000`
2. Masukkan **email** dan **password**
3. Klik **Masuk**

> **Akun Demo:** `admin@hrconnect.local` / `ChangeMe!2026`

---

## 2. Dashboard Super Admin

Dashboard menampilkan:

| Widget | Keterangan |
|--------|------------|
| **Total Karyawan** | Jumlah karyawan aktif |
| **Kehadiran Hari Ini** | Persentase hadir |
| **System Health** | Status server |
| **Activity Logs** | Aktivitas terbaru |
| **Notifikasi** | Pesan terbaru |

---

## 3. Pengaturan Sistem

### 3.1 Pengaturan Umum

1. Klik menu **Pengaturan** atau akses `/admin/settings`
2. Edit:
   - **Nama Perusahaan**
   - **Logo Perusahaan**
   - **Alamat**
   - **Telepon**
   - **Email**
3. Klik **Simpan**

### 3.2 Pengaturan KPI

1. Klik menu **KPI** atau akses `/admin/settings/kpi`
2. Atur **target KPI** per departemen
3. Simpan perubahan

---

## 4. Role & Permission

### 4.1 Daftar Role

1. Klik menu **Role & Permission** atau akses `/admin/roles-permissions`
2. Lihat daftar role yang ada

### 4.2 Edit Permission

1. Klik role yang ingin diedit
2. Centang/centang **permission** yang diinginkan
3. Klik **Simpan**

### 4.3 Permission yang Tersedia

| Permission | Keterangan |
|------------|------------|
| `view_dashboard` | Lihat dashboard |
| `manage_employees` | Kelola karyawan |
| `manage_attendances` | Kelola absensi |
| `manage_leaves` | Kelola cuti |
| `manage_overtime` | Kelola lembur |
| `manage_payroll` | Kelola payroll |
| `manage_reimbursements` | Kelola reimbursement |
| `manage_schedules` | Kelola jadwal |
| `manage_settings` | Kelola pengaturan |
| `view_reports` | Lihat laporan |
| `view_knowledgebase` | Akses knowledge base |

---

## 5. Master Data

### 5.1 Divisi

1. Klik menu **Master Data** → **Divisi**
2. Tambah/edit/hapus divisi
3. Contoh: IT, HR, Finance, Operations

### 5.2 Jabatan

1. Klik menu **Master Data** → **Jabatan**
2. Tambah/edit/hapus jabatan
3. Contoh: Staff, Supervisor, Manager, Director

### 5.3 Shift

1. Klik menu **Master Data** → **Shift**
2. Tambah/edit/hapus shift
3. Contoh: Office Hour (08:00-17:00), Shift Pagi, Shift Malam

### 5.4 Jenis Cuti

1. Klik menu **Master Data** → **Jenis Cuti**
2. Tambah/edit/hapus jenis cuti
3. Contoh: Cuti Tahunan, Cuti Sakit, Cuti Melahirkan

### 5.5 Hak Cuti

1. Klik menu **Master Data** → **Hak Cuti**
2. Atur kuota cuti per karyawan/jabatan
3. Simpan perubahan

### 5.6 Pendidikan

1. Klik menu **Master Data** → **Pendidikan**
2. Tambah/edit data pendidikan karyawan

---

## 6. Manajemen Karyawan

*(Sama seperti Admin/HR, tambahan:)*

### 6.1 Aset Perusahaan

1. Klik menu **Aset** atau akses `/admin/assets`
2. Tambah/edit aset
3. Assign aset ke karyawan
4. Record handover/return

### 6.2 Appraisal

1. Klik menu **Appraisal** atau akses `/admin/appraisals`
2. Buat penilaian kinerja
3. Review hasil appraisal

### 6.3 Custom Forms

1. Klik menu **Custom Forms** atau akses `/admin/custom-forms`
2. Buat form custom
3. Assign ke karyawan

---

## 7. Payroll & Settings

### 7.1 Pengaturan Payroll

1. Klik menu **Pengaturan Payroll** atau akses `/admin/payrolls/settings`
2. Atur:
   - Komponen gaji
   - Tunjangan
   - Potongan
   - BPJS
   - PPh21

### 7.2 Generate Payroll

1. Klik menu **Payroll** atau akses `/admin/payrolls`
2. Klik **Generate Payroll**
3. Pilih **periode**
4. Review dan **Approve**

---

## 8. Activity Logs

1. Klik menu **Activity Logs** atau akses `/admin/activity-logs`
2. Lihat seluruh aktivitas pengguna
3. Filter berdasarkan:
   - **User**
   - **Tanggal**
   - **Aksi** (create, update, delete)
   - **Model** (User, Leave, Overtime, dll)

---

## 9. User Sessions

1. Klik menu **Sessions** atau akses `/admin/user-sessions`
2. Lihat sesi aktif pengguna
3. **Force logout** jika diperlukan

---

## 10. System Maintenance

1. Klik menu **Maintenance** atau akses `/admin/system-maintenance`
2. Tools tersedia:
   - **Cache Clear**
   - **Queue Clear**
   - **Backup Database**
   - **Optimize**

---

## 11. Operational Health

1. Klik menu **Operational Health** atau akses `/admin/operational-health`
2. Monitor:
   - Status server
   - Database connection
   - Queue worker
   - Storage usage

---

## 12. Akses Lainnya

Sebagai Super Admin, kamu juga bisa mengakses semua fitur:

| Fitur | Akses |
|-------|-------|
| Absensi | [OK] Semua |
| Cuti | [OK] Semua |
| Lembur | [OK] Semua |
| Reimbursement | [OK] Semua |
| Payroll | [OK] Semua |
| Knowledge Base | [OK] Semua |
| Import/Export | [OK] Semua |
| Reports | [OK] Semua |

---

## Kontak IT Support

- **Email:** it@hrconnect.test
- **Telepon:** (021) 1234-5678
- **WhatsApp:** 0812-3456-7890

---

**HRConnect — Sistem HRIS PT Daya Cipta Mandiri Solusi**
