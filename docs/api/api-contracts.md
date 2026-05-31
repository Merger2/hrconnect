# API Contracts - HRConnect HRIS

## Deskripsi
Dokumen ini mendefinisikan seluruh API endpoint untuk HRConnect HRIS, mencakup PWA (mobile), Livewire frontend, dan integrasi eksternal. Semua endpoint API menggunakan prefix `/api/v1/` dan mengembalikan response JSON.

---

## Konvensi

### Base URL
- Development: `http://localhost:8000/api/v1`
- Production: `https://hrconnect.example.com/api/v1`

### Authentication
- Semua endpoint API (kecuali `/login`, `/forgot-password`) memerlukan Bearer token
- Token dikirim via header: `Authorization: Bearer {token}`
- Token expires dalam 24 jam (configurable via `config/sanctum.php`)
- Refresh token via `POST /api/v1/auth/refresh`

> **ERRATA (SEC-3):** `laravel/sanctum` **belum terinstall**. Auth section di atas adalah spesifikasi target. Sebelum Sanctum terinstall, semua API endpoint akan return 401. Install: `composer require laravel/sanctum` + publish config + add `HasApiTokens` trait ke User model. Detail di task.md §1.3.

> **ID Note:** Semua ID field di API response menampilkan `"id": "uuid"` sebagai contoh, tapi database menggunakan **bigint auto-increment**. API Resources akan menampilkan ID sebagai integer (`"id": 1`), bukan UUID. Untuk V2, bisa ditambahkan UUID public ID jika diperlukan.

### Response Format

> **ID Convention:** Semua `id` field di response adalah **bigint integer** (bukan UUID). Contoh: `"id": 1`, bukan `"id": "uuid"`. Response di bawah menampilkan `"id": "uuid"` sebagai placeholder — implementasi aktual menggunakan auto-increment integer.

> **Enum Values:**
> - `employment_type`: `permanent`, `contract`, `probation`, `intern` (4 values)
> - `request_status` (leave/overtime): `pending`, `approved_l1`, `approved`, `rejected`, `cancelled`
> - `approval_status`: `pending`, `approved`, `rejected`
> - `payroll_status`: `draft`, `published`, `paid`

> **ERRATA (C1):** `Approval` model cast `level` ke `ApprovalLevel` enum. Perbandingan `$approval->level === 1` SELALUS false. Gunakan `$approval->level->value === 1` atau `$approval->level === ApprovalLevel::L1_SUPERVISOR`. API response `approval_chain.level` mengembalikan integer value (1, 2, 3, 4).

**Success (200/201):**
```json
{
  "status": "success",
  "message": "Clock in berhasil",
  "data": { ... }
}
```

**Error (4xx/5xx):**
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "latitude": ["Latitude wajib diisi"]
  }
}
```

**Pagination:**
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

### Rate Limiting
| Endpoint Category | Limit | Window |
|------------------|-------|--------|
| Authentication | 5 requests | 1 menit |
| Face Validation | 10 requests | 1 menit |
| Clock In/Out | 5 requests | 5 menit |
| KnowledgeBase Query | 20 requests | 1 menit |
| General API | 60 requests | 1 menit |

---

## 1. AUTHENTICATION (Fortify + Sanctum)

### 1.1 Login
```
POST /api/v1/auth/login
```
**Request:**
```json
{
  "email": "john@company.com",
  "password": "password123",
  "device_name": "Chrome on iPhone"
}
```
**Response (200):**
```json
{
  "status": "success",
  "message": "Login berhasil",
  "data": {
    "token": "1|abc123...",
    "user": {
      "id": "uuid",
      "name": "John Doe",
      "email": "john@company.com",
      "roles": ["employee"],
      "permissions": ["view_attendances", "view_leaves"],
      "employee": {
        "id": "uuid",
        "nik": "EMP-202601-001",
        "position": "Software Engineer",
        "department": "Engineering",
        "branch": "Jakarta Pusat",
"employment_type": "permanent",
      "face_registered": true,
      "join_date": "2024-01-15",
    "employment_type": "permanent",
    "probation_end_date": null,
    "position": "Software Engineer",
    "department": "Engineering",
    "branch": "Jakarta Pusat",
    "manager": "Jane Smith",
    "bank_name": "BCA",
    "bank_account_number": "1234567890",
    "face_registered": true,
    "profile_photo": null
  }
}
```

### 10.2 Update Profile
```
PUT /api/v1/profile
```
**Request:**
```json
{
  "phone": "+6281234567891",
  "address": "Jl. Sudirman No. 2, Jakarta",
  "bank_name": "Mandiri",
  "bank_account_number": "0987654321"
}
```
**Response (200):**
```json
{
  "status": "success",
  "message": "Profil berhasil diperbarui",
  "data": {
    "id": "uuid",
    "phone": "+6281234567891",
    "address": "Jl. Sudirman No. 2, Jakarta",
    "bank_name": "Mandiri",
    "bank_account_number": "0987654321"
  }
}
```

### 10.3 Family Members
```
GET /api/v1/profile/family
```
**Response (200):**
```json
{
  "status": "success",
  "data": [
    {
      "id": "uuid",
      "name": "Jane Doe",
      "relationship": "Istri",
      "birth_date": "1992-03-20",
      "phone": "+6281234567892",
      "is_dependent": true
    }
  ]
}
```

### 10.4 Add Family Member
```
POST /api/v1/profile/family
```
**Request:**
```json
{
  "name": "Baby Doe",
  "relationship": "Anak",
  "birth_date": "2025-12-01",
  "phone": null,
  "is_dependent": true
}
```
**Response (201):**
```json
{
  "status": "success",
  "message": "Anggota keluarga berhasil ditambahkan",
  "data": {
    "id": "uuid",
    "name": "Baby Doe",
    "relationship": "Anak",
    "birth_date": "2025-12-01"
  }
}
```

---

## 11. NOTIFICATIONS

### 11.1 Get Notifications
```
GET /api/v1/notifications
```
**Query Parameters:**
| Parameter | Type | Required | Default |
|-----------|------|----------|---------|
| `filter` | string | No | all (all/unread) |
| `limit` | int | No | 20 |
| `offset` | int | No | 0 |

**Response (200):**
```json
{
  "status": "success",
  "data": [
    {
      "id": "uuid",
      "type": "leave_approved",
      "title": "Cuti Disetujui",
      "message": "Pengajuan cuti Anda tanggal 15-17 Mei 2026 telah disetujui oleh Manager",
      "data": {
        "leave_id": "uuid",
        "approver_name": "Manager Name",
        "approval_level": 1
      },
      "read_at": null,
      "created_at": "2026-05-08 10:30:45"
    }
  ],
  "meta": {
    "total": 5,
    "unread": 2,
    "limit": 20,
    "offset": 0
  }
}
```

### 11.2 Mark as Read
```
POST /api/v1/notifications/{notification_id}/read
```
**Response (200):**
```json
{
  "status": "success",
  "message": "Notifikasi ditandai sebagai telah dibaca"
}
```

### 11.3 Mark All as Read
```
POST /api/v1/notifications/read-all
```
**Response (200):**
```json
{
  "status": "success",
  "message": "Semua notifikasi ditandai sebagai telah dibaca",
  "data": {
    "marked_count": 5
  }
}
```

---

## 12. HEALTH CHECK

### 12.1 System Health
```
GET /api/v1/health
```
**Response (200):**
```json
{
  "status": "ok",
  "timestamp": "2026-05-08T10:30:45+07:00",
  "services": {
    "database": "connected",
    "cache": "connected",
    "queue": "connected",
    "storage": "writable",
    "face_api": "available",
    "ai_service": "available"
  },
  "version": "1.0.0",
  "environment": "production"
}
```

---

## Error Codes Reference

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `VALIDATION_ERROR` | 422 | Validation failed |
| `UNAUTHORIZED` | 401 | Invalid/missing token |
| `FORBIDDEN` | 403 | Insufficient permissions |
| `NOT_FOUND` | 404 | Resource not found |
| `FACE_NOT_REGISTERED` | 400 | Employee hasn't registered face |
| `FACE_MISMATCH` | 400 | Face doesn't match stored embedding |
| `OUTSIDE_GEOFENCE` | 400 | GPS outside allowed radius |
| `MOCK_GPS_DETECTED` | 400 | Fake GPS detected |
| `ALREADY_CLOCKED_IN` | 400 | Already clocked in today |
| `NOT_CLOCKED_IN` | 400 | Must clock in before clock out |
| `LEAVE_QUOTA_EXCEEDED` | 422 | Insufficient leave quota (business rule violation) |
| `PROBATION_BLOCK` | 403 | Probation employee can't take annual leave |
| `PAYROLL_LOCKED` | 409 | Payroll already published/locked |
| `DUPLICATE_REQUEST` | 409 | Duplicate submission detected |
| `RATE_LIMIT_EXCEEDED` | 429 | Too many requests |
| `AI_SERVICE_TIMEOUT` | 504 | AI service timeout (use fallback) |
| `INTERNAL_ERROR` | 500 | Unexpected server error |

> **ERRATA (SEC-5):** `BusinessRuleException` harus return **422** (Unprocessable Entity), bukan 400 atau 500. Semua error code di atas yang melanggar business rule (LEAVE_QUOTA_EXCEEDED, PROBATION_BLOCK) menggunakan 403/422, bukan 400. Implementasi saat ini perlu diperbaiki: `BusinessRuleException` extends `Exception` (return 500), harus diubah ke `HttpException` dengan code 422.

> **ERRATA (C2):** Payroll yang sudah `published` TIDAK BISA di-unpublish. Saat regenerate payroll untuk periode yang sama, gunakan `$existingPayroll->forceDelete()` (bukan `delete()`) karena SoftDeletes menyebabkan unique constraint violation.

---

## Security Headers

All API responses include:
```
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
Strict-Transport-Security: max-age=31536000; includeSubDomains
Content-Security-Policy: default-src 'self'
```

## CORS Configuration

```php
// config/cors.php
'paths' => ['api/v1/*'],
'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
'allowed_origins' => [env('APP_URL')], // PWA origin — JANGAN gunakan ['*'] di production
'allowed_headers' => ['Authorization', 'Content-Type', 'X-Requested-With', 'X-Device-UUID'],
'exposed_headers' => ['X-RateLimit-Limit', 'X-RateLimit-Remaining'],
'supports_credentials' => true,
```

> **Security Note:** `allowed_origins` harus restrict ke domain PWA saja. Jangan gunakan `['*']` di production karena bisa menyebabkan CSRF attack vector pada API yang menggunakan cookie auth.

## PWA-Specific Headers

PWA clients should send:
```
X-Device-UUID: unique-device-identifier
X-App-Version: 1.0.0
X-Platform: ios|android|web
```

## Webhook Endpoints (Future V2)

| Endpoint | Purpose |
|----------|---------|
| `POST /api/v1/webhooks/google-oauth` | Google OAuth callback |
| `POST /api/v1/webhooks/email-bounce` | Email delivery status |
| `POST /api/v1/webhooks/payment` | Payment gateway callback (for BPJS) |

---

*Last Updated: 2026-05-31*
*Version: 1.1.0 — Errata applied (C1, C2, SEC-3, SEC-5, LeaveBalance naming, EmploymentType 4 values)*
*Status: Draft - Ready for Implementation*
