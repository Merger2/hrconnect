# API Contracts — HRConnect HRIS

**Versi:** 2.0
**Tanggal:** 31 Mei 2026
**Status:** Final — sinkron dengan backend implementasi (Sanctum installed, routes/api.php bootstrap, Policies enforced)

## Deskripsi

Dokumen ini mendefinisikan **seluruh endpoint REST API** HRConnect HRIS. Semua endpoint pakai prefix `/api/v1`, response JSON standar, dan auth Sanctum Bearer token (kecuali `auth/login` dan health check).

Total: **47 endpoint** terbagi 11 modul.

---

## 1. Konvensi

### 1.1 Base URL

| Environment | URL |
|-------------|-----|
| Development | `http://localhost:8000/api/v1` |
| Staging     | `https://staging.hrconnect.521tech.com/api/v1` |
| Production  | `https://hrconnect.521tech.com/api/v1` |

### 1.2 Authentication

| Mekanisme | Detail |
|-----------|--------|
| Type | Laravel Sanctum (Personal Access Token) |
| Header | `Authorization: Bearer {token}` |
| Issuance | Via `POST /api/v1/auth/login` (atau `createToken()` di tinker) |
| Expiration | **Never expire** (`config/sanctum.php` → `expiration = null`) — PWA reuse sampai logout/revoke |
| Revoke | `POST /api/v1/auth/logout` (revoke current token) atau `POST /api/v1/auth/logout-all` (revoke semua device) |
| Stateful (cookie) | Aktif via `statefulApi()` middleware untuk SPA same-origin (CSRF-protected) |

**Public endpoints (tidak butuh token):**
- `POST /auth/login`
- `POST /auth/forgot-password`
- `POST /auth/reset-password`
- `GET /health`

### 1.3 Format ID

Semua `id` di request/response adalah **bigint integer**. Contoh: `"id": 123` (bukan `"id": "uuid-string"`).

### 1.4 Format Response

**Success (200/201):**
```json
{
  "status": "success",
  "message": "Operasi berhasil",
  "data": { ... }
}
```

**Error (4xx/5xx):**
```json
{
  "status": "error",
  "message": "Pesan error untuk user",
  "errors": {
    "field_name": ["Pesan validasi spesifik"]
  }
}
```

**Pagination (cursor-based atau page-based):**
```json
{
  "status": "success",
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 95
  },
  "links": {
    "first": "/api/v1/...?page=1",
    "last": "/api/v1/...?page=5",
    "prev": null,
    "next": "/api/v1/...?page=2"
  }
}
```

### 1.5 Rate Limiting

Sumber: SRS §3.3 REQ-PERF-12 s/d 16. Default per IP+user.

| Endpoint Group | Limit | Window |
|----------------|-------|--------|
| `POST /auth/login` | 5 | 1 menit |
| `POST /face/verify`, `/face/register` | 10 | 1 menit |
| `POST /attendance/clock-in`, `/clock-out` | 5 | 5 menit |
| `POST /knowledgebase/chat` | 20 | 1 menit |
| Endpoint lain | 60 | 1 menit |

Header response saat throttled:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 0
Retry-After: 47
```

### 1.6 HTTP Status Codes

Sumber: AGENTS.md "Custom exceptions in `App\Exceptions\`" + error-handling-strategy.md §11.0.

| Status | Makna | Kapan Dipakai |
|--------|-------|---------------|
| `200 OK` | Success | GET / PUT / POST sukses (resource exists) |
| `201 Created` | Created | POST yang membuat resource baru |
| `204 No Content` | Sukses tanpa body | DELETE sukses |
| `401 Unauthorized` | Token invalid/missing | Auth required tapi tidak ada / expired |
| `403 Forbidden` | Tidak punya permission | Policy reject (IDOR fix) |
| `404 Not Found` | Resource tidak ada | findOrFail() throws |
| `409 Conflict` | State conflict | `AlreadyClockedInException`, `NotClockedInException`, `AlreadyClockedOutException` |
| `422 Unprocessable Entity` | Validation / business rule | `BusinessRuleException`, `FaceNotRegisteredException`, ValidationException |
| `429 Too Many Requests` | Rate limit | RateLimiter::tooManyAttempts |
| `500 Internal Server Error` | Bug | Generic uncaught — should never happen |

### 1.7 Custom Exception → HTTP Mapping

| Exception | HTTP | Code |
|-----------|------|------|
| `BusinessRuleException` | 422 | `BUSINESS_RULE_VIOLATION` |
| `FaceNotRegisteredException` | 422 | `FACE_NOT_REGISTERED` |
| `FaceNotRecognizedException` | 422 | `FACE_NOT_RECOGNIZED` |
| `AntiFakeGPSException` | 422 | `MOCK_GPS_DETECTED` |
| `GeofenceViolationException` | 422 | `OUTSIDE_GEOFENCE` |
| `InvalidPinException` | 422 | `INVALID_PIN` |
| `AlreadyClockedInException` | 409 | `ALREADY_CLOCKED_IN` |
| `AlreadyClockedOutException` | 409 | `ALREADY_CLOCKED_OUT` |
| `NotClockedInException` | 409 | `NOT_CLOCKED_IN` |

---

## 2. Authentication (5 endpoint)

### 2.1 Login

```
POST /api/v1/auth/login
```

**Public** (tidak butuh token).

**Request:**
```json
{
  "email": "john@company.com",
  "password": "Password123!",
  "device_name": "Chrome on iPhone"
}
```

**Validation:**
- `email`: required, email
- `password`: required, min:8
- `device_name`: required, string, max:255

**Response 200 (no 2FA):**
```json
{
  "status": "success",
  "message": "Login berhasil",
  "data": {
    "token": "1|abcdef12345...",
    "user": {
      "id": 12,
      "name": "John Doe",
      "email": "john@company.com",
      "roles": ["employee"],
      "permissions": ["view_dashboard", "view_attendances", "view_payslip"]
    }
  }
}
```

**Response 200 (2FA enabled):**
```json
{
  "status": "success",
  "message": "Lanjutkan dengan kode 2FA",
  "data": {
    "two_factor_required": true,
    "challenge_id": "session-key-abc123"
  }
}
```

**Response 422 (kredensial salah):**
```json
{
  "status": "error",
  "message": "Email atau password salah.",
  "errors": {
    "email": ["Kredensial tidak cocok."]
  }
}
```

**Errors:**
- 422 `VALIDATION_ERROR`
- 422 `INVALID_CREDENTIALS`
- 429 `RATE_LIMIT_EXCEEDED`

---

### 2.2 2FA Challenge

```
POST /api/v1/auth/2fa/challenge
```

**Public** — gunakan setelah login mengembalikan `two_factor_required = true`.

**Request:**
```json
{
  "challenge_id": "session-key-abc123",
  "code": "123456"
}
```

**Validation:**
- `challenge_id`: required (dari response login)
- `code`: required, regex `/^\d{6}$|^[a-z0-9]{8}$/i` (6 digit TOTP atau 8-char recovery code)

**Response 200:**
```json
{
  "status": "success",
  "message": "Verifikasi 2FA berhasil",
  "data": {
    "token": "1|abcdef12345...",
    "user": { "id": 12, "name": "John Doe", "email": "john@company.com", "roles": ["employee"], "permissions": [...] }
  }
}
```

---

### 2.3 Logout (Current Device)

```
POST /api/v1/auth/logout
```

**Auth:** required. Revoke **token saat ini** saja.

**Response 200:**
```json
{
  "status": "success",
  "message": "Logout berhasil"
}
```

---

### 2.4 Logout All Devices

```
POST /api/v1/auth/logout-all
```

**Auth:** required. Revoke **semua token** user (semua device).

**Response 200:**
```json
{
  "status": "success",
  "message": "Berhasil logout dari semua perangkat",
  "data": { "revoked_count": 3 }
}
```

---

### 2.5 Forgot Password

```
POST /api/v1/auth/forgot-password
```

**Public.**

**Request:**
```json
{ "email": "john@company.com" }
```

**Response 200 (selalu — anti-enumeration):**
```json
{
  "status": "success",
  "message": "Jika email terdaftar, link reset password akan dikirim."
}
```

---

## 3. Profile & Identity (4 endpoint)

### 3.1 Get Current User

```
GET /api/v1/user
```

**Auth:** required.

**Response 200:**
```json
{
  "status": "success",
  "data": {
    "id": 12,
    "name": "John Doe",
    "email": "john@company.com",
    "email_verified_at": "2026-01-15T08:00:00Z",
    "two_factor_enabled": false,
    "password_changed_at": "2026-03-01T09:30:00Z",
    "roles": ["employee"],
    "permissions": [...]
  }
}
```

### 3.2 Get Employee Profile

```
GET /api/v1/profile
```

**Auth:** required. Return profil Employee user saat ini.

**Response 200:**
```json
{
  "status": "success",
  "data": {
    "id": 8,
    "user_id": 12,
    "employee_number": "EMP-2024-001",
    "full_name": "John Doe",
    "email": "john@company.com",
    "phone": "0812****5678",
    "join_date": "2024-01-15",
    "employment_type": "permanent",
    "status": "active",
    "marital_status": "married",
    "gender": "L",
    "blood_type": "O+",
    "branch": { "id": 1, "name": "Jakarta Pusat" },
    "department": { "id": 3, "name": "Engineering" },
    "position": { "id": 7, "name": "Software Engineer", "grade": 5 },
    "shift": { "id": 1, "name": "Office Hour 09-17" },
    "manager": { "id": 4, "full_name": "Jane Smith" },
    "face_registered": true,
    "pin_set": true,
    "bank_name": "BCA",
    "bank_account_number": "****7890"
  }
}
```

**Catatan PII:** `phone`, `nik`, `npwp`, `bank_account_number` ditampilkan ter-mask. Field penuh hanya available via `GET /employees/{id}` oleh HR Manager.

### 3.3 Update Profile

```
PUT /api/v1/profile
```

**Auth:** required. Self-update profil personal saja (employment fields hanya HR yang boleh edit).

**Request:**
```json
{
  "phone": "+6281234567891",
  "address_detail": "Jl. Sudirman No. 2, Jakarta",
  "bank_name": "Mandiri",
  "bank_account_number": "0987654321"
}
```

**Validation:**
- `phone`: nullable, regex `/^(\+62|0)\d{9,12}$/`
- `address_detail`: nullable, max:500
- `bank_name`: nullable, in:BCA,Mandiri,BRI,BNI,Danamon,...
- `bank_account_number`: nullable, regex `/^\d{8,18}$/`

**Response 200:**
```json
{
  "status": "success",
  "message": "Profil berhasil diperbarui",
  "data": { ...same as 3.2 }
}
```

### 3.4 Change Password

```
POST /api/v1/profile/change-password
```

**Request:**
```json
{
  "current_password": "OldPassword123!",
  "password": "NewSecurePass456!",
  "password_confirmation": "NewSecurePass456!"
}
```

**Validation:**
- `current_password`: required, valid current
- `password`: required, min:8, mixedCase, numbers, confirmed

**Response 200:**
```json
{
  "status": "success",
  "message": "Password berhasil diubah. Token lama tetap valid."
}
```

---

## 4. Face Recognition (2 endpoint)

### 4.1 Register Face

```
POST /api/v1/face/register
```

**Auth:** required (employee mendaftarkan wajahnya sendiri).

**Request:**
```json
{
  "embedding": [0.123, -0.045, 0.789, ..., 0.234]
}
```

**Validation:**
- `embedding`: required, array, size:128 (FaceNet 128D dari face-api.js)
- Setiap nilai: numeric, between:-1.5,1.5

**Response 200:**
```json
{
  "status": "success",
  "message": "Data wajah berhasil didaftarkan",
  "data": {
    "employee_id": 8,
    "face_registered_at": "2026-05-31T13:31:00Z"
  }
}
```

**Errors:**
- 422 `INVALID_EMBEDDING_DIMENSION` (count !== 128)
- 422 `INVALID_EMBEDDING_VALUES` (non-numeric)

### 4.2 Verify Face

```
POST /api/v1/face/verify
```

**Auth:** required. Test endpoint untuk PWA verifikasi sebelum clock-in (validasi wajah tanpa create attendance).

**Request:**
```json
{
  "embedding": [0.123, ..., 0.234]
}
```

**Response 200:**
```json
{
  "status": "success",
  "message": "Wajah dikenali",
  "data": {
    "valid": true,
    "similarity_percentage": 92.5
  }
}
```

**Errors:**
- 422 `FACE_NOT_REGISTERED` (employee belum register)
- 422 `FACE_NOT_RECOGNIZED` (similarity < 85%)
- 429 `RATE_LIMIT_EXCEEDED` (max 10/menit)

---

## 5. Attendance (5 endpoint)

### 5.1 Clock-In

```
POST /api/v1/attendance/clock-in
```

**Auth:** required.

**Request (WFO):**
```json
{
  "is_wfa": false,
  "latitude": -6.2088,
  "longitude": 106.8456,
  "accuracy": 10.5,
  "is_mocked": false,
  "embedding": [0.1, ..., 0.2],
  "photo_selfie": "data:image/jpeg;base64,...."
}
```

**Request (WFA):**
```json
{
  "is_wfa": true,
  "wfa_note": "Mengerjakan dokumen klien dari rumah karena hujan",
  "latitude": -6.21,
  "longitude": 106.85,
  "accuracy": 50,
  "is_mocked": false,
  "embedding": [0.1, ..., 0.2],
  "photo_selfie": "data:image/jpeg;base64,..."
}
```

**Request (PIN fallback — face tidak tersedia):**
```json
{
  "is_wfa": false,
  "latitude": -6.2088,
  "longitude": 106.8456,
  "accuracy": 10.5,
  "is_mocked": false,
  "pin": "123456",
  "photo_selfie": "data:image/jpeg;base64,...."
}
```

**Validation:**
- `is_wfa`: boolean (default false)
- `wfa_note`: required_if:is_wfa,true, min:20, max:500
- `latitude`: required, numeric, between:-90,90
- `longitude`: required, numeric, between:-180,180
- `accuracy`: required, numeric, min:0
- `is_mocked`: boolean
- `embedding`: nullable, array, size:128
- `pin`: required_without:embedding, regex `/^\d{6}$/`
- `photo_selfie`: nullable, base64 image (max 2MB after decode)

**Response 201:**
```json
{
  "status": "success",
  "message": "Clock-in berhasil",
  "data": {
    "id": 1024,
    "employee_id": 8,
    "date": "2026-05-31",
    "clock_in": "2026-05-31T08:05:12+07:00",
    "is_wfa": false,
    "status": "on_time",
    "verification_method": "face_verified",
    "face_similarity_score": 92.5,
    "late_minutes": 0,
    "distance_meters": 23.5
  }
}
```

**Errors:**
- 409 `ALREADY_CLOCKED_IN`
- 422 `MOCK_GPS_DETECTED` (is_mocked=true atau accuracy>100)
- 422 `OUTSIDE_GEOFENCE` (jarak > radius)
- 422 `FACE_NOT_REGISTERED` (jika tanpa pin)
- 422 `FACE_NOT_RECOGNIZED`
- 422 `INVALID_PIN`
- 422 `BUSINESS_RULE_VIOLATION` (mis. WFA note <20 char)

### 5.2 Clock-Out

```
POST /api/v1/attendance/clock-out
```

**Request:**
```json
{
  "latitude": -6.2088,
  "longitude": 106.8456,
  "accuracy": 10.5,
  "is_mocked": false,
  "embedding": [0.1, ..., 0.2],
  "verification_method": "face_verified",
  "photo_selfie": "data:image/jpeg;base64,..."
}
```

**Response 200:**
```json
{
  "status": "success",
  "message": "Clock-out berhasil",
  "data": {
    "id": 1024,
    "clock_out": "2026-05-31T17:15:00+07:00",
    "work_duration_hours": 9.17,
    "overtime_linked": false
  }
}
```

**Errors:**
- 409 `NOT_CLOCKED_IN`
- 409 `ALREADY_CLOCKED_OUT`
- 422 `OUTSIDE_GEOFENCE` (WFO only)

### 5.3 Today Status

```
GET /api/v1/attendance/today
```

**Response 200:**
```json
{
  "status": "success",
  "data": {
    "has_clocked_in": true,
    "has_clocked_out": false,
    "attendance": {
      "id": 1024,
      "date": "2026-05-31",
      "clock_in": "2026-05-31T08:05:12+07:00",
      "clock_out": null,
      "is_wfa": false,
      "status": "on_time",
      "late_minutes": 0
    }
  }
}
```

### 5.4 Attendance History

```
GET /api/v1/attendance?period=2026-05&page=1&per_page=31
```

**Query Parameters:**
- `period`: optional, format YYYY-MM (default bulan ini)
- `page`, `per_page`: pagination
- `status`: optional, filter by AttendanceStatus

**Response 200:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1024,
      "date": "2026-05-31",
      "clock_in": "2026-05-31T08:05:12+07:00",
      "clock_out": "2026-05-31T17:15:00+07:00",
      "is_wfa": false,
      "status": "on_time",
      "late_minutes": 0,
      "work_duration_hours": 9.17
    }
  ],
  "meta": { ... }
}
```

### 5.5 Approve WFA (Manager)

```
POST /api/v1/attendance/{id}/approve-wfa
```

**Auth:** required + permission `approve_wfa`. Policy `AttendancePolicy::approveWfa` cek parent_id.

**Request:**
```json
{ "decision": "approve", "notes": "OK, dokumen sudah saya review" }
```

**Validation:**
- `decision`: required, in:approve,reject
- `notes`: nullable, max:500

**Response 200:**
```json
{
  "status": "success",
  "message": "Status WFA berhasil diperbarui",
  "data": {
    "id": 1024,
    "status_wfa": "approved",
    "status": "on_time"
  }
}
```

---

## 6. Leave (5 endpoint)

### 6.1 Request Leave

```
POST /api/v1/leave
```

**Request:**
```json
{
  "leave_type_id": 1,
  "start_date": "2026-06-10",
  "end_date": "2026-06-12",
  "day_type": "full_day",
  "reason": "Liburan keluarga ke Bali",
  "proof_file": null
}
```

**Validation:**
- `leave_type_id`: required, exists:leave_types,id
- `start_date`: required, date, after_or_equal:today-3days
- `end_date`: required, date, after_or_equal:start_date
- `day_type`: required, in:full_day,morning,afternoon
- `reason`: required, min:10, max:1000
- `proof_file`: nullable, file, mimes:jpg,png,pdf, max:5120 (5MB) — wajib jika `leave_type.code = sick`

**Response 201:**
```json
{
  "status": "success",
  "message": "Pengajuan cuti berhasil dikirim",
  "data": {
    "id": 512,
    "employee_id": 8,
    "leave_type": { "id": 1, "name": "Cuti Tahunan", "code": "annual" },
    "start_date": "2026-06-10",
    "end_date": "2026-06-12",
    "day_type": "full_day",
    "total_days": 3,
    "reason": "Liburan keluarga ke Bali",
    "status": "pending",
    "approvals": [
      { "level": 1, "approver_id": 4, "approver_name": "Jane Smith", "status": "pending" },
      { "level": 2, "approver_id": 2, "approver_name": "HR Manager", "status": "pending" }
    ]
  }
}
```

**Errors:**
- 422 `BUSINESS_RULE_VIOLATION` (kuota tidak cukup, end_date<start_date, retroaktif > H+3, overlap dengan pengajuan lain, probation tidak boleh, sakit tanpa bukti)

### 6.2 List Leave (Self / Tim / All)

```
GET /api/v1/leave?status=pending&year=2026&page=1
```

**Query Parameters:**
- `status`: optional, in:pending,approved_l1,approved,rejected,cancelled
- `year`: optional, default current year
- `employee_id`: optional (HR/Manager — filter by tim)
- `page`, `per_page`: pagination

**Response 200:**
```json
{
  "status": "success",
  "data": [ ...leave objects ],
  "meta": { ... }
}
```

### 6.3 Get Leave Detail

```
GET /api/v1/leave/{id}
```

Policy `LeavePolicy::view` enforce ownership/team/HR.

**Response 200:** Full leave object with approval timeline.

### 6.4 Cancel Leave (Owner, status PENDING only)

```
DELETE /api/v1/leave/{id}
```

Policy `LeavePolicy::delete` cek owner + status=PENDING.

**Response 200:**
```json
{
  "status": "success",
  "message": "Pengajuan cuti berhasil dibatalkan"
}
```

### 6.5 Get Leave Quota

```
GET /api/v1/leave/quota?year=2026
```

**Response 200:**
```json
{
  "status": "success",
  "data": [
    {
      "leave_type": { "id": 1, "name": "Cuti Tahunan", "code": "annual", "deducts_from_quota": true },
      "year": 2026,
      "quota": 12,
      "used": 3,
      "carry_forward": 2,
      "carry_forward_deadline": "2026-03-31",
      "available": 11
    }
  ]
}
```

---

## 7. Overtime (4 endpoint)

### 7.1 Request Overtime

```
POST /api/v1/overtime
```

**Request:**
```json
{
  "date": "2026-06-01",
  "start_time": "17:30",
  "end_time": "20:00",
  "description": "Menyelesaikan deployment hotfix sprint 12"
}
```

**Validation:**
- `date`: required, date, after_or_equal:today
- `start_time`, `end_time`: required, format H:i, end > start
- `description`: required, min:10, max:500
- Total durasi: max 4 jam/hari

**Response 201:** Overtime object dengan status `pending` dan approval workflow.

### 7.2 List Overtime
```
GET /api/v1/overtime?status=approved&period=2026-06
```

### 7.3 Get Overtime Detail
```
GET /api/v1/overtime/{id}
```

### 7.4 Cancel Overtime (PENDING only)
```
DELETE /api/v1/overtime/{id}
```

---

## 8. Reimbursement (4 endpoint)

### 8.1 Request Reimbursement

```
POST /api/v1/reimbursement
```

**Request (multipart/form-data):**
```
category_id: 2
amount: 350000
description: Reimbursement transport ke client meeting
expense_date: 2026-05-29
receipt: <file binary>
```

**Validation:**
- `category_id`: required, exists:reimbursement_categories,id
- `amount`: required, integer, min:1
- `description`: required, min:10
- `expense_date`: required, date, before_or_equal:today
- `receipt`: required, image|file, max:5120 (5MB)

**Response 201:** Reimbursement object dengan approval workflow.

### 8.2 List Reimbursement
```
GET /api/v1/reimbursement?status=pending&period=2026-05
```

### 8.3 Get Reimbursement Detail
```
GET /api/v1/reimbursement/{id}
```

### 8.4 Cancel Reimbursement
```
DELETE /api/v1/reimbursement/{id}
```

---

## 9. Approval Workflow (3 endpoint)

### 9.1 List Pending Approvals (Manager/HR/Finance)

```
GET /api/v1/approvals/pending?type=leave
```

**Query Parameters:**
- `type`: optional, in:leave,overtime,reimbursement,wfa

**Response 200:**
```json
{
  "status": "success",
  "data": [
    {
      "approval_id": 4502,
      "level": 1,
      "approvable_type": "leave",
      "approvable_id": 512,
      "submitter": { "id": 8, "full_name": "John Doe" },
      "details": { ...leave/overtime/reimbursement summary },
      "submitted_at": "2026-05-30T10:00:00Z"
    }
  ]
}
```

### 9.2 Approve Request

```
POST /api/v1/approvals/{approval_id}/approve
```

**Request:**
```json
{ "notes": "OK, sudah saya review" }
```

Policy: `LeavePolicy::approveLevel1` / `approveLevel2` (cek parent_id + permission).

**Response 200:**
```json
{
  "status": "success",
  "message": "Persetujuan berhasil",
  "data": {
    "approval_id": 4502,
    "status": "approved",
    "is_final": true
  }
}
```

### 9.3 Reject Request

```
POST /api/v1/approvals/{approval_id}/reject
```

**Request:**
```json
{ "rejection_reason": "Bukan urgensi tinggi, silakan reschedule ke minggu depan" }
```

**Validation:** `rejection_reason`: required, min:10, max:500

**Response 200:** Updated approval object dengan status=rejected.

---

## 10. Payroll (4 endpoint)

### 10.1 List Own Payroll History (Employee)

```
GET /api/v1/payroll?year=2026
```

Policy: `PayrollPolicy::viewAny` + filter self.

**Response 200:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 4012,
      "period": "2026-05",
      "status": "published",
      "gross_salary": 8500000,
      "total_deduction": 1200000,
      "net_salary": 7300000,
      "published_at": "2026-05-25T15:00:00Z"
    }
  ]
}
```

### 10.2 Get Payroll Detail

```
GET /api/v1/payroll/{id}
```

Policy: `PayrollPolicy::view`.

**Response 200:**
```json
{
  "status": "success",
  "data": {
    "id": 4012,
    "period": "2026-05",
    "status": "published",
    "basic_salary": 7000000,
    "total_allowance": 1500000,
    "gross_salary": 8500000,
    "overtime_pay": 0,
    "pph21": 425000,
    "bpjs_health": 85000,
    "bpjs_employment": 170000,
    "loan_deduction": 0,
    "attendance_penalty": 0,
    "total_deduction": 1200000,
    "net_salary": 7300000,
    "items": [
      { "type": "income", "name": "Gaji Pokok", "amount": 7000000 },
      { "type": "income", "name": "Tunjangan Jabatan", "amount": 1500000 },
      { "type": "deduction", "name": "PPh 21", "amount": 425000 }
    ]
  }
}
```

### 10.3 Download Payslip PDF

```
GET /api/v1/payroll/{id}/payslip
```

Policy: `PayrollPolicy::downloadPayslip` (status PUBLISHED/PAID, employee = self atau Finance/Super Admin).

**Header:**
```
X-Confirm-Password: <hashed-confirmation>
```

**Response 200:** Binary PDF stream dengan header:
```
Content-Type: application/pdf
Content-Disposition: attachment; filename="payslip-202605-EMP2024001.pdf"
```

### 10.4 Generate Payroll (Finance batch)

```
POST /api/v1/payroll/generate
```

Permission: `process_payroll`.

**Request:**
```json
{ "period": "2026-05", "employee_ids": null }
```

**Validation:**
- `period`: required, regex `/^\d{4}-\d{2}$/`
- `employee_ids`: nullable, array of employee IDs (kalau null = semua aktif)

**Response 202 Accepted (async):**
```json
{
  "status": "success",
  "message": "Job generate payroll telah dijalankan untuk 47 karyawan",
  "data": {
    "queued_jobs": 47,
    "queue": "payroll_high"
  }
}
```

---

## 11. Employee Directory (HR-only) (5 endpoint)

### 11.1 List Employees

```
GET /api/v1/employees?branch_id=1&department_id=3&search=jane&page=1
```

Permission: `view_employees`.

### 11.2 Get Employee Detail

```
GET /api/v1/employees/{id}
```

Policy: `EmployeePolicy::view`. PII fields visible untuk HR Manager.

### 11.3 Create Employee

```
POST /api/v1/employees
```

Permission: `manage_employees`. Full PII payload.

### 11.4 Update Employee

```
PUT /api/v1/employees/{id}
```

### 11.5 Delete (Soft) Employee

```
DELETE /api/v1/employees/{id}
```

Soft delete via SoftDeletes trait. forceDelete hanya `super-admin`.

---

## 12. KnowledgeBase RAG (3 endpoint)

> **Status:** Service belum diimplementasi (Sprint 27). Endpoint terdokumentasi sebagai target spec.

### 12.1 Chat AI Query

```
POST /api/v1/knowledgebase/chat
```

**Request:**
```json
{ "question": "Berapa hari cuti tahunan saya?" }
```

**Validation:** `question`: required, min:5, max:500.

**Response 200:**
```json
{
  "status": "success",
  "data": {
    "answer": "Cuti tahunan Anda 12 hari per tahun (dapat di-prorate kalau gabung mid-year). Sisa kuota cek di GET /leave/quota.",
    "sources": [
      { "knowledge_base_id": 5, "title": "Buku Saku Karyawan", "page": 12, "snippet": "..." }
    ],
    "model": "gemini-2.5-flash",
    "fallback": false,
    "response_time_ms": 1850
  }
}
```

**Errors:**
- 504 `AI_SERVICE_TIMEOUT` → fallback `pg_trgm` keyword search

### 12.2 Upload PDF (HR only)

```
POST /api/v1/knowledgebase
```

Permission: `manage_knowledgebase`. Multipart upload PDF max 10MB.

### 12.3 Delete Knowledge Base

```
DELETE /api/v1/knowledgebase/{id}
```

---

## 13. Notifications (4 endpoint)

> **Status:** `app/Notifications/` folder belum ada. Endpoint spec target.

### 13.1 List Notifications

```
GET /api/v1/notifications?filter=unread&limit=20
```

### 13.2 Mark as Read

```
POST /api/v1/notifications/{id}/read
```

### 13.3 Mark All as Read

```
POST /api/v1/notifications/read-all
```

### 13.4 Unread Count

```
GET /api/v1/notifications/unread-count
```

---

## 14. Health Check (1 endpoint)

### 14.1 System Health

```
GET /api/v1/health
```

**Public** (tidak butuh auth). Untuk cron warm-up + monitoring.

**Response 200:**
```json
{
  "status": "ok",
  "timestamp": "2026-05-31T13:31:28+07:00",
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

**Response 503 (sebagian service down):**
```json
{
  "status": "degraded",
  "services": { "database": "connected", "queue": "down" }
}
```

---

## 15. Error Code Reference

| Code | HTTP | Module | Deskripsi |
|------|------|--------|-----------|
| `VALIDATION_ERROR` | 422 | Generic | Field validation failed |
| `INVALID_CREDENTIALS` | 422 | Auth | Email/password salah |
| `UNAUTHORIZED` | 401 | Generic | Token invalid/missing |
| `FORBIDDEN` | 403 | Generic | Policy reject |
| `NOT_FOUND` | 404 | Generic | Resource tidak ada |
| `ALREADY_CLOCKED_IN` | 409 | Attendance | Sudah absen masuk |
| `ALREADY_CLOCKED_OUT` | 409 | Attendance | Sudah absen pulang |
| `NOT_CLOCKED_IN` | 409 | Attendance | Belum clock-in / sudah clock-out |
| `MOCK_GPS_DETECTED` | 422 | Attendance | Fake GPS |
| `OUTSIDE_GEOFENCE` | 422 | Attendance | Di luar radius |
| `FACE_NOT_REGISTERED` | 422 | Face | Employee belum register |
| `FACE_NOT_RECOGNIZED` | 422 | Face | Similarity < 85% |
| `INVALID_PIN` | 422 | Face | PIN fallback salah |
| `INVALID_EMBEDDING_DIMENSION` | 422 | Face | Vector bukan 128D |
| `BUSINESS_RULE_VIOLATION` | 422 | Generic | Aturan bisnis (kuota, retroaktif, dll) |
| `PAYROLL_LOCKED` | 422 | Payroll | Status PUBLISHED/PAID |
| `RATE_LIMIT_EXCEEDED` | 429 | Generic | Throttle |
| `AI_SERVICE_TIMEOUT` | 504 | KnowledgeBase | Gemini API down → fallback pg_trgm |
| `INTERNAL_ERROR` | 500 | Generic | Bug |

---

## 16. Security Headers

Semua response API include:
```
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
Strict-Transport-Security: max-age=31536000; includeSubDomains
Referrer-Policy: strict-origin-when-cross-origin
```

CORS (config/cors.php):
```php
'paths' => ['api/v1/*', 'sanctum/csrf-cookie'],
'allowed_origins' => [env('APP_URL'), env('PWA_URL')],
'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
'allowed_headers' => ['Authorization', 'Content-Type', 'X-Requested-With', 'X-Device-UUID', 'X-CSRF-TOKEN'],
'supports_credentials' => true,
```

PWA wajib kirim header tambahan:
```
X-Device-UUID: <unique-device-id>
X-App-Version: 1.0.0
X-Platform: ios | android | web
```

---

## 17. Implementation Status (per 2026-05-31)

| Module | Endpoint | Routes | Controller | Service | Tests |
|--------|----------|--------|------------|---------|-------|
| Auth | 5 | ⚠️ stub `/user` saja | ❌ | ✅ Fortify | ⚠️ partial |
| Profile | 4 | ❌ | ❌ | ✅ via Eloquent | ❌ |
| Face | 2 | ❌ | ❌ | ✅ FaceRecognitionService | ✅ Phase3BugFixesTest |
| Attendance | 5 | ❌ | ❌ | ✅ AttendanceService | ✅ AttendancePinFallbackTest |
| Leave | 5 | ❌ | ❌ | ✅ LeaveService | ✅ LeaveDateRangeValidationTest |
| Overtime | 4 | ❌ | ❌ | ✅ via PayrollCalculator | ✅ OvertimeRateTest |
| Reimbursement | 4 | ❌ | ❌ | ⚠️ partial | ❌ |
| Approval | 3 | ❌ | ❌ | ✅ ApprovalService | ❌ |
| Payroll | 4 | ❌ | ❌ | ✅ PayrollCalculatorService | ✅ TerCategoryTest |
| Employee | 5 | ❌ | ❌ | ✅ via Eloquent | ❌ |
| KnowledgeBase | 3 | ❌ | ❌ | ❌ Sprint 27 | ❌ |
| Notifications | 4 | ❌ | ❌ | ❌ Folder belum ada | ❌ |
| Health | 1 | ❌ | ❌ | — | ❌ |
| **TOTAL** | **47** | **1/47 (~2%)** | **0/47** | **~85% siap** | ~30% |

**Service-layer backend siap ~85%, tapi entry point API (routes + controller) belum diimplementasi.** Implementasi endpoint dijadwalkan di sesi Sprint 30 (sprint-branch-strategy.md).

---

*Last Updated: 2026-05-31 (rewrite total v2.0)*
*Status: Final spec — implementasi endpoint berlanjut di Sprint 30*
