# Studi Kasus API — HRConnect

> Panduan penggunaan API berdasarkan flow bisnis nyata.
> Base URL: `http://localhost:8000/api/v1`

---

## 1. Flow Auth (Login → 2FA → Profile)

**Skenario:** Karyawan login ke aplikasi. Jika 2FA aktif, lanjut challenge.

### Step 1: Login
```
POST /auth/login
Content-Type: application/json

{
    "email": "karyawan@company.com",
    "password": "password123",
    "device_name": "Laptop Kantor"
}
```

**Response sukses (tanpa 2FA):**
```json
{
    "status": "success",
    "data": {
        "token": "1|abc123...",
        "user": {
            "id": 1,
            "name": "Budi Santoso",
            "email": "karyawan@company.com",
            "roles": ["employee"],
            "permissions": ["view_own_attendance"]
        }
    }
}
```

**Response jika 2FA aktif:**
```json
{
    "status": "success",
    "data": {
        "two_factor_required": true,
        "challenge_id": "randomChallengeString..."
    }
}
```

### Step 2 (opsional): 2FA Challenge
```
POST /auth/2fa/challenge
Content-Type: application/json

{
    "challenge_id": "randomChallengeString...",
    "code": "123456"
}
```

**Response:**
```json
{
    "status": "success",
    "data": {
        "token": "1|abc123...",
        "user": { ... }
    }
}
```

Simpan `token` untuk dipakai di header `Authorization: Bearer {token}` di semua endpoint selanjutnya.

---

## 2. Flow Clock-In (Absen Pagi)

**User Story:** "Budi ingin absen masuk pakai face recognition, lalu absen pulang di sore hari."

### Step 1: Daftar Wajah (sekali saja)
```
POST /face/register
Authorization: Bearer {token}
Content-Type: application/json

{
    "embedding": [0.012, -0.034, 0.078, ...]  // 128 angka float dari face-api.js
}
```

**Response:**
```json
{
    "status": "success",
    "message": "Data wajah berhasil didaftarkan"
}
```

### Step 2: Clock In (tiap pagi)
```
POST /attendance/clock-in
Authorization: Bearer {token}
Content-Type: application/json

{
    "latitude": -6.2088,
    "longitude": 106.8456,
    "accuracy": 8.5,
    "is_mocked": false,
    "embedding": [0.012, -0.034, ...]
}
```

**Fallback PIN (jika face gagal):**
```json
{
    "latitude": -6.2088,
    "longitude": 106.8456,
    "pin": "123456"
}
```

**Response sukses (201):**
```json
{
    "status": "success",
    "message": "Clock-in berhasil",
    "data": {
        "id": 42,
        "clock_in": "2026-06-06T07:55:00+07:00",
        "status": "present",
        "verification_method": "face",
        "face_similarity_score": 0.92
    }
}
```

**Error scenarios:**
- `409` `AlreadyClockedInException` — udah clock-in hari ini
- `422` `FaceNotRecognizedException` — wajah tidak cocok (fallback ke PIN)
- `422` `GeofenceViolationException` — di luar radius kantor
- `422` `AntiFakeGPSException` — GPS curang (mock/accuracy > 100m)

### Step 3: Cek Status Hari Ini
```
GET /attendance/today
Authorization: Bearer {token}
```

**Response:**
```json
{
    "status": "success",
    "data": {
        "has_clocked_in": true,
        "has_clocked_out": false,
        "attendance": {
            "id": 42,
            "clock_in": "2026-06-06T07:55:00+07:00",
            "clock_out": null,
            "status": "present",
            "late_minutes": 0
        }
    }
}
```

### Step 4: Clock Out (sore hari)
```
POST /attendance/clock-out
Authorization: Bearer {token}
Content-Type: application/json

{
    "latitude": -6.2088,
    "longitude": 106.8456,
    "embedding": [0.012, -0.034, ...]
}
```

**Response:**
```json
{
    "status": "success",
    "message": "Clock-out berhasil",
    "data": {
        "clock_in": "2026-06-06T07:55:00+07:00",
        "clock_out": "2026-06-06T17:02:00+07:00",
        "work_duration_hours": 9.12
    }
}
```

---

## 3. Flow Cuti (Pengajuan → Approval)

**User Story:** "Budi ingin cuti 3 hari. Dia cek quota dulu, ajukan, trus managernya approve."

### Step 1: Cek Quota Cuti
```
GET /leave/quota?year=2026
Authorization: Bearer {token}
```

**Response:**
```json
{
    "status": "success",
    "data": [
        {
            "leave_type": { "id": 1, "name": "Cuti Tahunan", "code": "annual" },
            "year": 2026,
            "quota": 12,
            "used": 2,
            "available": 10
        },
        {
            "leave_type": { "id": 2, "name": "Cuti Sakit", "code": "sick" },
            "available": 3
        }
    ]
}
```

### Step 2: Ajukan Cuti
```
POST /leave
Authorization: Bearer {token}
Content-Type: application/json

{
    "leave_type_id": 1,
    "start_date": "2026-06-15",
    "end_date": "2026-06-17",
    "day_type": "full_day",
    "reason": "Liburan keluarga ke Bali"
}
```

**Response (201):**
```json
{
    "status": "success",
    "message": "Pengajuan cuti berhasil dikirim",
    "data": {
        "id": 10,
        "leave_type": { "name": "Cuti Tahunan" },
        "start_date": "2026-06-15",
        "end_date": "2026-06-17",
        "total_days": 3,
        "status": "pending",
        "approvals": [
            {
                "level": "L1_supervisor",
                "status": "pending"
            }
        ]
    }
}
```

### Step 3: Manager Lihat Pending Approvals
```
GET /approvals/pending
Authorization: Bearer {token_manager}
```

**Response:**
```json
{
    "status": "success",
    "data": [
        {
            "approval_id": 5,
            "approvable_type": "Leave",
            "submitter": { "id": 1, "full_name": "Budi Santoso" },
            "level": "L1_supervisor",
            "submitted_at": "2026-06-06T10:00:00+07:00"
        }
    ]
}
```

### Step 4a: Manager Approve
```
POST /approvals/{approval_id}/approve
Authorization: Bearer {token_manager}
Content-Type: application/json

{
    "notes": "Silakan liburan, jangan lupa ganti sabun"
}
```

### Step 4b: Manager Reject (jika perlu)
```
POST /approvals/{approval_id}/reject
Authorization: Bearer {token_manager}
Content-Type: application/json

{
    "rejection_reason": "Tahap produksi, cuti ditunda dulu ya"
}
```

**Response:**
```json
{
    "status": "success",
    "message": "Persetujuan berhasil",
    "data": {
        "approval_id": 5,
        "status": "approved",
        "is_final": true
    }
}
```

---

## 4. Flow Lembur (Overtime)

**User Story:** "Budi lembur 2 jam buat ngerjain project deadline."

### Step 1: Ajukan Lembur
```
POST /overtime
Authorization: Bearer {token}
Content-Type: application/json

{
    "date": "2026-06-06",
    "start_time": "18:00",
    "end_time": "20:00",
    "description": "Menyelesaikan laporan bulanan"
}
```

**Response (201):**
```json
{
    "status": "success",
    "data": {
        "id": 15,
        "total_hours": 2,
        "status": "pending",
        "approvals": [...]
    }
}
```

### Step 2: Manager Approve/Reject (sama kayak flow cuti)
Lihat `/approvals/pending` → `/approvals/{id}/approve` atau `reject`.

---

## 5. Flow Payroll (Generate → Download Slip Gaji)

**User Story:** "Finance manager generate payroll bulan Mei, trus Budi download slip gajinya."

### Step 1: (Finance) Generate Payroll
```
POST /payroll/generate
Authorization: Bearer {token_finance}
Content-Type: application/json

{
    "period": "2026-05",
    "employee_ids": null  // null = semua karyawan aktif
}
```

**Response (202 — Accepted):**
```json
{
    "status": "success",
    "message": "Job generate payroll telah dijalankan untuk 50 karyawan",
    "data": {
        "queued_jobs": 50,
        "queue": "payroll_high",
        "period": "2026-05"
    }
}
```

### Step 2: Cek Daftar Payroll
```
GET /payroll?year=2026
Authorization: Bearer {token_karyawan}
```

**Response:**
```json
{
    "status": "success",
    "data": [
        {
            "id": 100,
            "period": "2026-05",
            "status": "published",
            "gross_salary": 8500000,
            "net_salary": 7500000
        }
    ]
}
```

### Step 3: Detail Payroll
```
GET /payroll/{id}
Authorization: Bearer {token}
```

### Step 4: Download Slip Gaji (PDF)
```
GET /payroll/{id}/payslip
Authorization: Bearer {token}
```
Ini download file PDF langsung.

### Step 5 (Finance): Export Excel
```
POST /payroll/export/monthly
Authorization: Bearer {token_finance}
Content-Type: application/json

{
    "period": "2026-05"
}
```

Export lain:
- `POST /payroll/export/1721-a1` — bukti potong PPh 21
- `POST /payroll/export/bpjs` — laporan BPJS

---

## 6. Flow Termination (PHK / Resign)

**User Story:** "HR terminate karyawan dan dapat perhitungan financials."

### Step 1: Terminate PKWTT (Resign/Dismissed/Deceased)
```
POST /employees/{employee_id}/terminate
Authorization: Bearer {token_hr}
Content-Type: application/json

{
    "type": "resign",
    "reason": "Mengundurkan diri karena pindah kota",
    "date": "2026-06-30"
}
```

**Response — termasuk financial_summary (Pesangon, Uang Penghargaan, dll):**
```json
{
    "status": "success",
    "data": {
        "id": 1,
        "full_name": "Budi Santoso",
        "status": "resigned",
        "termination_type": "resign",
        "resign_date": "2026-06-30",
        "face_cleared": true,
        "financial_summary": {
            "pesangon": 0,
            "uang_penghargaan_masa_kerja": 4000000,
            "sisa_cuti": 2000000,
            "total": 6000000
        }
    }
}
```

### Step 2: Proses Kontrak Berakhir (Batch PKWT)
```
POST /employees/terminate/contract-end
Authorization: Bearer {token_hr}
Content-Type: application/json

{
    "date": "2026-06-30"
}
```

**Response:**
```json
{
    "status": "success",
    "message": "3 karyawan kontrak di-terminasi",
    "data": {
        "processed_count": 3
    }
}
```

---

## 7. Flow Knowledge Base (RAG)

**User Story:** "HR upload manual kebijakan perusahaan, lalu karyawan bisa tanya-tanya ke AI."

### Step 1: Upload Dokumen (PDF)
```
POST /knowledgebase
Authorization: Bearer {token_hr}
Content-Type: multipart/form-data

{
    "title": "Kebijakan Cuti 2026",
    "category": "policy",
    "file": {file_pdf}
}
```

**Response (201):**
```json
{
    "status": "success",
    "message": "KnowledgeBase berhasil di-upload. Embedding sedang diproses async.",
    "data": {
        "id": 5,
        "title": "Kebijakan Cuti 2026",
        "category": "policy",
        "status": "processing"
    }
}
```

### Step 2: Tanya AI
```
POST /knowledgebase/chat
Authorization: Bearer {token}
Content-Type: application/json

{
    "question": "Berapa maksimal cuti tahunan dalam setahun?"
}
```

**Response:**
```json
{
    "status": "success",
    "data": {
        "answer": "Berdasarkan dokumen Kebijakan Cuti 2026, maksimal cuti tahunan adalah 12 hari kerja per tahun.",
        "sources": [
            {
                "title": "Kebijakan Cuti 2026",
                "score": 0.95
            }
        ]
    }
}
```

---

## Postman Tips

### Import
1. `php artisan scramble:export` — generate `api.json`
2. Postman → File → Import → Upload `api.json`
3. Set `BASE_URL` variable: `http://localhost:8000/api/v1`

### Environment Variables
Bikin environment di Postman:
| Variable | Initial Value | Type |
|----------|--------------|------|
| `base_url` | `http://localhost:8000/api/v1` | default |
| `token` | (kosong, isi setelah login) | secret |

Collection → Pre-request Script:
```js
pm.request.headers.add({
    key: 'Authorization',
    value: `Bearer ${pm.environment.get('token')}`
});
```

### Flow Collections
Import `api.json` lalu group request secara manual di Postman:
1. **Auth** — Login → 2FA → Profile
2. **Clock In** — Register Face → Clock In → Today → Clock Out
3. **Cuti** — Quota → Submit → Lihat Approval → Approve
4. **Payroll** — Generate → List → Download
5. **Termination** — Terminate → Contract End
