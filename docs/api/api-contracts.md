# API Contracts — HRConnect HRIS

**Versi:** 3.0
**Tanggal:** 2026-06-18
**Status:** Final — sinkron dengan implementasi (51 routes, 13 controllers, ~695 tests)

## 1. Konvensi

### 1.1 Base URL

| Environment | URL |
|-------------|-----|
| Development | `http://localhost:8000/api/v1` |
| Production  | `https://hrconnect.company.com/api/v1` |

### 1.2 Authentication

| Mekanisme | Detail |
|-----------|--------|
| Type | Laravel Sanctum (Personal Access Token) |
| Header | `Authorization: Bearer {token}` |
| Issuance | Via `POST /api/v1/auth/login` (atau `createToken()` di tinker) |
| Expiration | **Never expire** (`config/sanctum.php` → `expiration = null`). Token hidup sampai logout/revoke. |
| Revoke | `POST /api/v1/auth/logout` (revoke current token) atau `POST /api/v1/auth/logout-all` (revoke semua token user) |
| Logout-all | Semua token dihapus dari DB. Setelah revoke, client harus login ulang. |
| Password Expiry | Password di-expire via `password_changed_at`. Saat change-password atau reset, field updated. |

**Public endpoints (no token):**
- `GET /health`
- `POST /auth/login`
- `POST /auth/2fa/challenge`
- `POST /auth/forgot-password`

**Throttle:**
| Endpoint | Limit |
|----------|-------|
| `POST /auth/login` | 5/min |
| `POST /auth/2fa/challenge` | 5/min |
| `POST /auth/forgot-password` | 5/min |
| `POST /face/*` | 10/min |
| `POST /attendance/clock-*` | 5/5min |
| `POST /leave` | 10/min |
| `POST /overtime` | 10/min |
| `POST /reimbursement` | 10/min |
| `POST /knowledgebase/chat` | 20/min |

### 1.3 Response Envelope

**Success (data):** `GET /employees/{id}`, `GET /profile`, dll.
```json
{
  "status": "success",
  "data": { ... }
}
```

**Success (list with pagination):** `GET /employees`, `GET /leave`, dll.
```json
{
  "status": "success",
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 95
  }
}
```

**Success (action with data):** `POST /leave`, `POST /attendance/clock-in`, dll.
```json
{
  "status": "success",
  "message": "Pengajuan cuti berhasil dikirim",
  "data": { ... }
}
```

**Success (action without data):** `DELETE /leave/{id}`, `POST /auth/logout`, dll.
```json
{
  "status": "success",
  "message": "Logout berhasil"
}
```

**Error:**
```json
{
  "status": "error",
  "message": "Pesan error untuk user"
}
```

**Validation Error (422 — Laravel default):**
```json
{
  "message": "Terjadi kesalahan validasi.",
  "errors": {
    "field_name": ["Pesan validasi spesifik"]
  }
}
```

**Health Check:**
```json
{
  "status": "ok",
  "timestamp": "2026-06-18T10:00:00+07:00",
  "version": "1.0.0",
  "environment": "production",
  "services": {
    "database": "connected",
    "cache": "connected",
    "queue": "connected",
    "storage": "writable"
  }
}
```

### 1.4 Error Codes

| Status Code | Key | Module | Deskripsi |
|-------------|-----|--------|-----------|
| 200 | — | Generic | Success |
| 201 | — | Generic | Created |
| 202 | — | Payroll | Accepted (async job) |
| 401 | — | Generic | Unauthenticated (token invalid/missing) |
| 403 | — | Generic | Forbidden (policy reject) |
| 404 | — | Generic | Resource not found |
| 409 | `ALREADY_CLOCKED_IN` | Attendance | Sudah absen masuk hari ini |
| 409 | `ALREADY_CLOCKED_OUT` | Attendance | Sudah absen pulang |
| 409 | `NOT_CLOCKED_IN` | Attendance | Belum clock-in / sudah clock-out |
| 422 | `VALIDATION_ERROR` | Generic | Field validation failed |
| 422 | `INVALID_CREDENTIALS` | Auth | Email/password salah |
| 422 | `BUSINESS_RULE_VIOLATION` | Generic | Aturan bisnis (kuota habis, retroaktif, dll) |
| 422 | `INVALID_PIN` | Face | PIN fallback salah |
| 422 | `FACE_NOT_REGISTERED` | Face | Employee belum register face |
| 422 | `FACE_NOT_RECOGNIZED` | Face | Similarity < threshold |
| 422 | `MOCK_GPS_DETECTED` | Attendance | Fake GPS terdeteksi |
| 422 | `OUTSIDE_GEOFENCE` | Attendance | Di luar radius geofence |
| 422 | `PAYROLL_LOCKED` | Payroll | Status PUBLISHED/PAID |
| 429 | `RATE_LIMIT_EXCEEDED` | Generic | Too many requests |
| 500 | `INTERNAL_ERROR` | Generic | Unexpected server error |

### 1.5 Pagination

Semua list endpoint (`index`) menggunakan pagination Laravel `paginate()`.

**Request params:**
| Param | Type | Default | Max | Description |
|-------|------|---------|-----|-------------|
| `page` | int | 1 | — | Halaman |
| `per_page` | int | 20 | 100 | Items per halaman |

**Response meta:**
```json
"meta": {
  "current_page": 1,
  "last_page": 5,
  "per_page": 20,
  "total": 95
}
```

### 1.6 IDs

Semua `id` di request/response adalah **integer** (bigint). Contoh: `"id": 123`.

### 1.7 Date & Time Format

| Type | Format | Example |
|------|--------|---------|
| Date | `Y-m-d` | `2026-06-18` |
| DateTime | ISO 8601 | `2026-06-18T10:00:00+07:00` |
| Time | `H:i:s` | `09:00:00` |

### 1.8 Money / Amount

Semua amount dalam **IDR (Rupiah)**, integer tanpa desimal. Contoh: `"amount": 150000` (Rp150.000).

---

## 2. Auth (6 endpoints)

| Method | Endpoint | Auth | Throttle | Deskripsi |
|--------|----------|------|----------|-----------|
| POST | `/auth/login` | Public | 5/min | Login, dapat token. Return `challenge_id` jika 2FA aktif. |
| POST | `/auth/2fa/challenge` | Public | 5/min | Validasi TOTP code, dapat token. |
| POST | `/auth/forgot-password` | Public | 5/min | Kirim email reset password. |
| POST | `/auth/logout` | Sanctum | — | Revoke current token. |
| POST | `/auth/logout-all` | Sanctum | — | Revoke semua token user. |
| GET | `/user` | Sanctum | — | Current user info + roles + permissions. |

### 2.1 Login

```json
// Request
{ "email": "user@company.com", "password": "secret", "device_name": "mobile-app" }

// Response 200 (tanpa 2FA)
{ "status": "success", "message": "Login berhasil", "data": { "token": "1|abc123...", "user": { ... } } }

// Response 200 (dengan 2FA)
{ "status": "success", "message": "Lanjutkan dengan kode 2FA", "data": { "two_factor_required": true, "challenge_id": "random-string" } }
```

### 2.2 2FA Challenge

```json
// Request
{ "challenge_id": "random-string", "code": "123456" }

// Response 200
{ "status": "success", "message": "2FA berhasil", "data": { "token": "1|abc123...", "user": { ... } } }
```

### 2.3 Logout

```json
// Response 200
{ "status": "success", "message": "Logout berhasil" }
```

### 2.4 Logout All

```json
// Response 200
{ "status": "success", "message": "Semua sesi diterminasi", "data": { "revoked_count": 3 } }
```

---

## 3. Profile (3 endpoints)

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| GET | `/profile` | Sanctum | Profil lengkap employee saat ini |
| PUT | `/profile` | Sanctum | Update profil (phone, address, bank) |
| POST | `/profile/change-password` | Sanctum | Ganti password |

### 3.1 Get Profile

```json
// Response 200
{
  "status": "success",
  "data": {
    "id": 1,
    "user_id": 1,
    "employee_number": "EMP-001",
    "full_name": "John Doe",
    "phone": "0812****7890",
    "address_detail": "Jl. Sudirman No. 1",
    "birth_date": "1990-01-01",
    "gender": "L",
    "marital_status": "single",
    "education_level": "bachelor",
    "institution_name": "Univ Indonesia",
    "major": "Computer Science",
    "graduation_year": 2015,
    "join_date": "2025-01-01",
    "status": "active",
    "bank_name": "BCA",
    "bank_account_number": "****7890",
    "face_registered": true,
    "pin_set": false,
    "company": { "id": 1, "name": "PT Example" },
    "branch": { "id": 1, "name": "Jakarta" },
    "department": { "id": 1, "name": "Engineering" },
    "position": { "id": 1, "name": "Software Engineer", "grade": 5 },
    "manager": { "id": 2, "full_name": "Jane Smith" }
  }
}
```

**PII masking:** `phone`, `bank_account_number` di-mask (`****`). NIK/NPWP tidak ada di response ini.

### 3.2 Update Profile

```json
// Request
{ "phone": "+628123456789", "address_detail": "Jl. Baru No. 2", "bank_name": "Mandiri", "bank_account_number": "0987654321" }

// Response 200
{ "status": "success", "message": "Profil berhasil diperbarui", "data": { ...ProfileResource } }
```

### 3.3 Change Password

```json
// Request
{ "current_password": "oldpass", "new_password": "newpass123", "new_password_confirmation": "newpass123" }

// Response 200
{ "status": "success", "message": "Password berhasil diubah" }
```

---

## 4. Face Recognition (2 endpoints)

| Method | Endpoint | Auth | Throttle | Deskripsi |
|--------|----------|------|----------|-----------|
| POST | `/face/register` | Sanctum | 10/min | Daftarkan embedding wajah (128D vector) |
| POST | `/face/verify` | Sanctum | 10/min | Verifikasi wajah saat clock-in |

### 4.1 Register Face

```json
// Request
{ "embedding": [0.0123, -0.0456, ...] }  // 128 elemen float

// Response 200
{ "status": "success", "message": "Wajah berhasil didaftarkan", "data": { "employee_id": 1, "face_registered_at": "2026-06-18T10:00:00+07:00" } }
```

### 4.2 Verify Face

```json
// Request
{ "embedding": [0.0123, -0.0456, ...] }  // 128 elemen float

// Response 200
{ "status": "success", "message": "Wajah dikenali", "data": { "valid": true, "similarity_percentage": 92.5 } }
```

---

## 5. Attendance (5 endpoints)

| Method | Endpoint | Auth | Throttle | Deskripsi |
|--------|----------|------|----------|-----------|
| POST | `/attendance/clock-in` | Sanctum | 5/5min | Absen masuk (PIN/GPS/face/WFA) |
| POST | `/attendance/clock-out` | Sanctum | 5/5min | Absen pulang |
| GET | `/attendance/today` | Sanctum | — | Status absensi hari ini |
| GET | `/attendance` | Sanctum | — | Riwayat absensi (paginated) |
| POST | `/attendance/{id}/approve-wfa` | Sanctum | — | Approve WFA oleh manager |

### 5.1 Clock In

```json
// Request (PIN)
{ "method": "pin", "pin": "123456", "latitude": -6.2, "longitude": 106.8 }

// Request (GPS)
{ "method": "gps", "latitude": -6.2, "longitude": 106.8 }

// Request (WFA)
{ "method": "wfa", "wfa_reason": "Sakit ringan, kerja dari rumah", "latitude": -6.2, "longitude": 106.8 }

// Response 201
{
  "status": "success",
  "message": "Absen masuk berhasil",
  "data": {
    "id": 1,
    "employee_id": 1,
    "date": "2026-06-18",
    "clock_in": "08:00:00",
    "is_wfa": false,
    "status": "present",
    "verification_method": "pin",
    "face_similarity_score": null,
    "late_minutes": 0
  }
}
```

**Verification flow:** Face (auto) → PIN fallback → Manual.

### 5.2 Clock Out

```json
// Request
{ "latitude": -6.2, "longitude": 106.8 }

// Response 200
{
  "status": "success",
  "message": "Absen pulang berhasil",
  "data": {
    "id": 1,
    "clock_in": "08:00:00",
    "clock_out": "17:00:00",
    "work_duration_hours": 9.0
  }
}
```

### 5.3 Today Status

```json
// Response 200
{
  "status": "success",
  "data": {
    "has_clocked_in": true,
    "has_clocked_out": false,
    "attendance": { "id": 1, "clock_in": "08:00:00", "is_wfa": false, "status": "present" }
  }
}
```

### 5.4 WFA Approval

```json
// Request
{ "notes": "Disetujui" }

// Response 200
{ "status": "success", "message": "WFA berhasil di-approve", "data": { "id": 1, "status_wfa": "approved", "status": "present" } }
```

---

## 6. Leave (5 endpoints)

| Method | Endpoint | Auth | Throttle | Deskripsi |
|--------|----------|------|----------|-----------|
| POST | `/leave` | Sanctum | 10/min | Ajukan cuti (dengan optional proof file) |
| GET | `/leave` | Sanctum | — | Daftar cuti (paginated, filter by status/year) |
| GET | `/leave/{id}` | Sanctum | — | Detail cuti |
| DELETE | `/leave/{id}` | Sanctum | — | Batalkan cuti (soft delete) |
| GET | `/leave/quota` | Sanctum | — | Sisa kuota cuti tahun ini |

### 6.1 Create Leave

```json
// Request (multipart/form-data)
{
  "leave_type_id": 1,
  "start_date": "2026-07-01",
  "end_date": "2026-07-03",
  "day_type": "full_day",
  "reason": "Cuti tahunan liburan keluarga.",
  "proof_file": (file: jpg/jpeg/png/pdf, max 5MB, optional)
}

// Response 201
{ "status": "success", "message": "Pengajuan cuti berhasil dikirim", "data": { ...LeaveResource } }
```

### 6.2 Leave Quota

```json
// Response 200
{
  "status": "success",
  "data": [
    {
      "leave_type": { "id": 1, "name": "Cuti Tahunan", "code": "ANNUAL", "deducts_from_quota": true },
      "year": 2026,
      "quota": 12.0,
      "used": 3.0,
      "carry_forward": 0.0,
      "carry_forward_deadline": "2026-03-31",
      "available": 9.0
    }
  ]
}
```

---

## 7. Overtime (4 endpoints)

| Method | Endpoint | Auth | Throttle | Deskripsi |
|--------|----------|------|----------|-----------|
| POST | `/overtime` | Sanctum | 10/min | Ajukan lembur |
| GET | `/overtime` | Sanctum | — | Daftar lembur (paginated, filter by status/period) |
| GET | `/overtime/{id}` | Sanctum | — | Detail lembur |
| DELETE | `/overtime/{id}` | Sanctum | — | Batalkan lembur |

---

## 8. Reimbursement (4 endpoints)

| Method | Endpoint | Auth | Throttle | Deskripsi |
|--------|----------|------|----------|-----------|
| POST | `/reimbursement` | Sanctum | 10/min | Ajukan reimbursement (dengan receipt file) |
| GET | `/reimbursement` | Sanctum | — | Daftar reimbursement (paginated, filter by status/period) |
| GET | `/reimbursement/{id}` | Sanctum | — | Detail reimbursement |
| DELETE | `/reimbursement/{id}` | Sanctum | — | Batalkan reimbursement |

### 8.1 Create Reimbursement

```json
// Request (multipart/form-data)
{
  "category_id": 1,
  "amount": 150000,
  "description": "Biaya pengobatan rawat jalan.",
  "expense_date": "2026-06-01",
  "receipt": (file: jpg/jpeg/png/pdf, max 5MB, required)
}

// Response 201
{ "status": "success", "message": "Pengajuan reimbursement berhasil dikirim", "data": { ...ReimbursementResource } }
```

---

## 9. Approval (3 endpoints)

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| GET | `/approvals/pending` | Sanctum | Daftar approval pending untuk user |
| POST | `/approvals/{id}/approve` | Sanctum | Setujui approval |
| POST | `/approvals/{id}/reject` | Sanctum | Tolak approval |

---

## 10. Payroll (7 endpoints)

| Method | Endpoint | Auth | Permission | Deskripsi |
|--------|----------|------|------------|-----------|
| GET | `/payroll` | Sanctum | — | Daftar payroll (paginated, filter by year/employee) |
| GET | `/payroll/{id}` | Sanctum | — | Detail payroll |
| GET | `/payroll/{id}/payslip` | Sanctum | `download_payslip` | Download payslip PDF (file) |
| POST | `/payroll/generate` | Sanctum | `process_payroll` | Generate payroll (async, queued) |
| POST | `/payroll/export/monthly` | Sanctum | `process_payroll` | Export monthly payroll (Excel file) |
| POST | `/payroll/export/1721-a1` | Sanctum | `process_payroll` | Export tax form 1721-A1 (Excel file) |
| POST | `/payroll/export/bpjs` | Sanctum | `process_payroll` | Export BPJS (Excel file) |

### 10.1 Payslip Download

```
GET /api/v1/payroll/{id}/payslip
```
Returns PDF binary file. Status must be PUBLISHED or PAID.

### 10.2 Payroll Export

```
POST /api/v1/payroll/export/{type}
```
Returns XLSX binary file. Permission: `process_payroll`.

### 10.3 Payroll Generate

```json
// Request
{ "period": "2026-06", "employee_ids": null }

// Response 202
{ "status": "success", "message": "Generate payroll dijadwalkan untuk 47 karyawan", "data": { "queued_jobs": 47, "queue": "payroll_high", "period": "2026-06" } }
```

---

## 11. Employee Directory (8 endpoints)

| Method | Endpoint | Auth | Permission | Deskripsi |
|--------|----------|------|------------|-----------|
| GET | `/employees` | Sanctum | `view_employees` | List employees (paginated, filterable) |
| GET | `/employees/{id}` | Sanctum | `view_employees` | Detail employee (PII ter-mask) |
| GET | `/employees/{id}/pii` | Sanctum | `manage_employees` | Detail employee dengan PII penuh (audit logged) |
| POST | `/employees` | Sanctum | `manage_employees` | Create employee |
| PUT | `/employees/{id}` | Sanctum | `manage_employees` | Update employee |
| DELETE | `/employees/{id}` | Sanctum | `manage_employees` | Soft-delete employee |
| POST | `/employees/{id}/terminate` | Sanctum | `manage_employees` | Terminate employee |
| POST | `/employees/terminate/contract-end` | Sanctum | `manage_employees` | Mass terminate kontrak berakhir |

### 11.1 PII Endpoint

```
GET /api/v1/employees/{id}/pii
```

**Permission:** `manage_employees` (HR Manager / Super Admin).
**Audit:** Setiap akses dicatat di `activity_log` dengan log_name `security`.

```json
// Response 200
{
  "status": "success",
  "data": {
    "id": 1,
    "employee_number": "EMP-001",
    "full_name": "John Doe",
    "phone": "081234567890",
    "nik": "3276010101990001",
    "npwp": "12.345.678.9-012.345",
    "bank_name": "BCA",
    "bank_account_number": "1234567890"
  }
}
```

---

## 12. KnowledgeBase (3 endpoints)

| Method | Endpoint | Auth | Permission | Throttle | Deskripsi |
|--------|----------|------|------------|----------|-----------|
| POST | `/knowledgebase/chat` | Sanctum | — | 20/min | Chat dengan RAG AI |
| POST | `/knowledgebase` | Sanctum | `manage_knowledgebase` | — | Upload PDF (max 10MB) |
| DELETE | `/knowledgebase/{id}` | Sanctum | `manage_knowledgebase` | — | Hapus dokumen |

### 12.1 Chat

```json
// Request
{ "question": "Apa kebijakan cuti tahunan?" }

// Response 200
{
  "status": "success",
  "data": {
    "answer": "Cuti tahunan 12 hari per tahun...",
    "sources": [
      { "id": 1, "title": "Buku Saku", "snippet": "...", "page_number": 5, "source_document": "kb_abc123.pdf" }
    ],
    "fallback": false,
    "model": "gemini-2.5-flash"
  }
}
```

### 12.2 Upload PDF

```json
// Request (multipart/form-data)
{
  "title": "Buku Saku Karyawan 2026",
  "category": "hr_policy",
  "file": (PDF file, max 10MB)
}

// Response 201
{ "status": "success", "message": "KnowledgeBase berhasil di-upload...", "data": { "id": 1, "title": "...", "category": "hr_policy", "status": "processing", "source_document": "kb_abc.pdf" } }
```

---

## 13. Health (1 endpoint)

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| GET | `/health` | Public | System health check |

---

## 14. Enum Reference

Semua status/indicator enum memiliki `color()` yang return salah satu dari 5 warna Flux: `success`, `warning`, `danger`, `info`, `zinc`.

### 14.1 Employee Status

| Value | Label | Color |
|-------|-------|-------|
| `active` | Aktif | success |
| `inactive` | Non-Aktif | danger |
| `resigned` | Resign | warning |
| `terminated` | Di-terminasi | danger |
| `contract` | Kontrak | info |

### 14.2 Attendance Status

| Value | Label | Color |
|-------|-------|-------|
| `present` | Hadir | success |
| `late` | Terlambat | warning |
| `absent` | Absen | danger |
| `half_day` | Setengah Hari | info |
| `wfa` | WFA | info |

### 14.3 Leave / Overtime / Reimbursement Status

| Value | Label | Color |
|-------|-------|-------|
| `pending` | Pending | warning |
| `approved` | Disetujui | success |
| `rejected` | Ditolak | danger |
| `cancelled` | Dibatalkan | zinc |
| `paid`| Dibayar (Reimbursement) | success |

### 14.4 WFA Status

| Value | Label | Color |
|-------|-------|-------|
| `pending` | Pending | warning |
| `approved` | Disetujui | success |
| `rejected` | Ditolak | danger |

### 14.5 Payroll Status

| Value | Label | Color |
|-------|-------|-------|
| `draft` | Draft | info |
| `published` | Published | success |
| `paid` | Dibayar | success |

### 14.6 KnowledgeBase Status

| Value | Label | Color |
|-------|-------|-------|
| `processing` | Processing | warning |
| `ready` | Siap | success |
| `failed` | Gagal | danger |

### 14.7 Gender

| Value | Label |
|-------|-------|
| `L` | Laki-laki |
| `P` | Perempuan |

### 14.8 Marital Status

| Value | Label |
|-------|-------|
| `single` | Belum Menikah |
| `married` | Menikah |
| `divorced` | Cerai |

### 14.9 Blood Type

`A+`, `A-`, `B+`, `B-`, `AB+`, `AB-`, `O+`, `O-`

### 14.10 Education Level

`sd`, `smp`, `sma/smk`, `d1`, `d2`, `d3`, `d4`, `s1`, `s2`, `s3`

### 14.11 Day Type

| Value | Label |
|-------|-------|
| `full_day` | Full Day |
| `morning` | Pagi |
| `afternoon` | Siang |

### 14.12 Approval Level

| Value | Label |
|-------|-------|
| `l1_supervisor` | L1 Supervisor |
| `l2_manager` | L2 Manager |

### 14.13 Salary Type

| Value | Label |
|-------|-------|
| `monthly` | Bulanan |
| `daily` | Harian |

---

## 15. File Upload Contract

### 15.1 Upload Endpoints

| Endpoint | Field | Max Size | Allowed Types | Storage | Disk |
|----------|-------|----------|---------------|---------|------|
| `POST /leave` | `proof_file` | 5MB | jpg, jpeg, png, pdf | `storage/app/public/leaves/proofs/` | public |
| `POST /reimbursement` | `receipt` | 5MB | jpg, jpeg, png, pdf | `storage/app/public/reimbursements/` | public |
| `POST /knowledgebase` | `file` | 10MB | pdf | `storage/app/private/knowledgebase/` | local |

### 15.2 Download Endpoints

| Endpoint | Response Type | Auth | Storage |
|----------|--------------|------|---------|
| `GET /payroll/{id}/payslip` | PDF binary | `download_payslip` | `storage/app/private/payslips/` |
| `POST /payroll/export/monthly` | XLSX binary | `process_payroll` | `storage/app/private/exports/` |
| `POST /payroll/export/1721-a1` | XLSX binary | `process_payroll` | `storage/app/private/exports/` |
| `POST /payroll/export/bpjs` | XLSX binary | `process_payroll` | `storage/app/private/exports/` |

### 15.3 Request Format

Semua upload menggunakan **multipart/form-data** (bukan JSON). File field dikirim sebagai binary part.

### 15.4 Error Handling

- **File terlalu besar:** 422 validation error
- **Tipe file tidak sesuai:** 422 validation error
- **Gagal simpan disk:** 500 `{ status: 'error', message: 'Gagal menyimpan file: ...' }`
- **Storage failure (orphan prevention):** File otomatis dihapus jika operasi DB gagal setelahnya

---

## 16. Request Headers

### 16.1 Standard Headers

| Header | Required | Description |
|--------|----------|-------------|
| `Authorization` | For protected endpoints | `Bearer {token}` |
| `Accept` | Optional | `application/json` (default) |
| `Content-Type` | For POST/PUT | `application/json` or `multipart/form-data` |

### 16.2 Device Tracking Headers (Optional)

| Header | Description |
|--------|-------------|
| `X-Device-UUID` | Unique device identifier |
| `X-Platform` | `ios`, `android`, or `web` |
| `X-App-Version` | App version string |
| `User-Agent` | Standard user agent |

---

*Last Updated: 2026-06-18*
*Status: Final — sinkron dengan implementasi backend (51 API routes, 13 controllers, 695 tests)*
