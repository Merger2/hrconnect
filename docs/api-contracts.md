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

### Response Format

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
        "join_date": "2024-01-15",
        "face_registered": true
      }
    }
  }
}
```

### 1.2 Logout
```
POST /api/v1/auth/logout
```
**Response (200):**
```json
{
  "status": "success",
  "message": "Logout berhasil"
}
```

### 1.3 Refresh Token
```
POST /api/v1/auth/refresh
```
**Response (200):**
```json
{
  "status": "success",
  "data": {
    "token": "2|def456..."
  }
}
```

### 1.4 Forgot Password
```
POST /api/v1/auth/forgot-password
```
**Request:**
```json
{
  "email": "john@company.com"
}
```
**Response (200):**
```json
{
  "status": "success",
  "message": "Link reset password telah dikirim ke email Anda"
}
```

### 1.5 Reset Password
```
POST /api/v1/auth/reset-password
```
**Request:**
```json
{
  "token": "reset-token-here",
  "email": "john@company.com",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```
**Response (200):**
```json
{
  "status": "success",
  "message": "Password berhasil diubah"
}
```

### 1.6 Change Password
```
POST /api/v1/auth/change-password
```
**Request:**
```json
{
  "current_password": "oldpassword123",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```
**Response (200):**
```json
{
  "status": "success",
  "message": "Password berhasil diubah"
}
```

### 1.7 Force Password Change Check
```
GET /api/v1/auth/check-force-change
```
**Response (200):**
```json
{
  "status": "success",
  "data": {
    "force_password_change": true,
    "password_changed_at": null
  }
}
```

### 1.8 Two-Factor Challenge
```
POST /api/v1/auth/two-factor-challenge
```
**Request:**
```json
{
  "code": "123456"
}
```
**Response (200):**
```json
{
  "status": "success",
  "data": {
    "token": "3|ghi789..."
  }
}
```

---

## 2. ATTENDANCE (Clock In/Out)

### 2.1 Clock In
```
POST /api/v1/attendance/clock-in
```
**Request:**
```json
{
  "latitude": -6.2088,
  "longitude": 106.8456,
  "face_embedding": [0.123, -0.456, ...],
  "is_wfa": false,
  "wfa_note": null
}
```
**Request (WFA):**
```json
{
  "latitude": -6.2088,
  "longitude": 106.8456,
  "face_embedding": [0.123, -0.456, ...],
  "is_wfa": true,
  "wfa_note": "Meeting dengan client di kantor pusat"
}
```
**Response (201):**
```json
{
  "status": "success",
  "message": "Clock in berhasil",
  "data": {
    "id": "uuid",
    "employee_id": "uuid",
    "clock_in": "2026-05-08 08:15:23",
    "clock_out": null,
    "status": "on_time",
    "status_wfa": null,
    "is_wfa": false,
    "wfa_note": null,
    "branch": {
      "name": "Jakarta Pusat",
      "latitude": -6.2088,
      "longitude": 106.8456,
      "radius": 100
    },
    "distance_meters": 15.5,
    "face_verified": true,
    "face_similarity_score": 0.94,
    "late_minutes": 0,
    "gps_accuracy": 5.2,
    "device_fingerprint": "abc123def456"
  }
}
```

**Response (Late):**
```json
{
  "status": "success",
  "message": "Clock in berhasil (terlambat)",
  "data": {
    "...": "same as above",
    "status": "late",
    "late_minutes": 15
  }
}
```

**Response (Error - Outside Geofence):**
```json
{
  "status": "error",
  "message": "Anda berada di luar area kantor",
  "errors": {
    "location": ["Jarak Anda 250m dari kantor. Maksimal 100m."]
  },
  "data": {
    "distance_meters": 250,
    "allowed_radius": 100,
    "branch_name": "Jakarta Pusat"
  }
}
```

**Response (Error - Face Mismatch):**
```json
{
  "status": "error",
  "message": "Verifikasi wajah gagal",
  "errors": {
    "face_embedding": ["Wajah tidak dikenali. Silakan coba lagi."]
  },
  "data": {
    "similarity_score": 0.65,
    "threshold": 0.80
  }
}
```

### 2.2 Clock Out
```
POST /api/v1/attendance/clock-out
```
**Request:**
```json
{
  "latitude": -6.2088,
  "longitude": 106.8456,
  "face_embedding": [0.123, -0.456, ...]
}
```
**Response (201):**
```json
{
  "status": "success",
  "message": "Clock out berhasil",
  "data": {
    "id": "uuid",
    "employee_id": "uuid",
    "clock_in": "2026-05-08 08:15:23",
    "clock_out": "2026-05-08 17:05:12",
    "status": "on_time",
    "total_hours": 8.83,
    "overtime_minutes": 0,
    "face_verified": true,
    "face_similarity_score": 0.92
  }
}
```

### 2.3 Attendance Status
```
GET /api/v1/attendance/status
```
**Response (200):**
```json
{
  "status": "success",
  "data": {
    "today": "2026-05-08",
    "has_clocked_in": true,
    "has_clocked_out": false,
    "clock_in": "2026-05-08 08:15:23",
    "clock_out": null,
    "status": "on_time",
    "shift": {
      "name": "Pagi",
      "start_time": "08:00",
      "end_time": "17:00",
      "late_tolerance_minutes": 5
    },
    "is_wfa": false,
    "wfa_approved": null
  }
}
```

### 2.4 Attendance History
```
GET /api/v1/attendance/history
```
**Query Parameters:**
| Parameter | Type | Required | Default |
|-----------|------|----------|---------|
| `month` | int | No | Current month |
| `year` | int | No | Current year |
| `page` | int | No | 1 |
| `per_page` | int | No | 20 |

**Response (200):**
```json
{
  "status": "success",
  "data": [
    {
      "date": "2026-05-08",
      "clock_in": "08:15:23",
      "clock_out": "17:05:12",
      "status": "on_time",
      "status_wfa": null,
      "is_wfa": false,
      "late_minutes": 0,
      "total_hours": 8.83,
      "overtime_minutes": 0,
      "branch_name": "Jakarta Pusat"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 2,
    "per_page": 20,
    "total": 22,
    "summary": {
      "on_time": 18,
      "late": 3,
      "absent": 1,
      "wfa": 2,
      "total_days": 22,
      "total_hours": 176.5
    }
  }
}
```

### 2.5 Today's Summary (Dashboard)
```
GET /api/v1/attendance/today-summary
```
**Response (200):**
```json
{
  "status": "success",
  "data": {
    "employee": {
      "name": "John Doe",
      "nik": "EMP-202601-001",
      "position": "Software Engineer"
    },
    "today": "2026-05-08",
    "shift": {
      "name": "Pagi",
      "start_time": "08:00",
      "end_time": "17:00"
    },
    "clock_in": "08:15:23",
    "clock_out": null,
    "status": "on_time",
    "late_minutes": 0,
    "worked_hours": 6.5,
    "remaining_hours": 2.33,
    "next_action": "clock_out"
  }
}
```

---

## 3. FACE RECOGNITION

### 3.1 Register Face
```
POST /api/v1/face/register
```
**Request:**
```json
{
  "face_embedding": [0.123, -0.456, ...],
  "face_photo": "base64_encoded_image_string"
}
```
**Response (201):**
```json
{
  "status": "success",
  "message": "Wajah berhasil didaftarkan",
  "data": {
    "employee_id": "uuid",
    "face_registered": true,
    "registered_at": "2026-05-08 10:30:45"
  }
}
```

### 3.2 Validate Face
```
POST /api/v1/face/validate
```
**Request:**
```json
{
  "live_embedding": [0.123, -0.456, ...],
  "employee_id": "uuid"
}
```
**Response (200):**
```json
{
  "status": "success",
  "data": {
    "matched": true,
    "similarity_score": 0.94,
    "threshold": 0.80,
    "employee_id": "uuid",
    "employee_name": "John Doe"
  }
}
```

**Response (Face Mismatch):**
```json
{
  "status": "error",
  "message": "Wajah tidak dikenali",
  "data": {
    "matched": false,
    "similarity_score": 0.65,
    "threshold": 0.80
  }
}
```

### 3.3 Check Face Registration Status
```
GET /api/v1/face/status
```
**Response (200):**
```json
{
  "status": "success",
  "data": {
    "face_registered": true,
    "registered_at": "2026-01-15 09:30:00",
    "last_verified": "2026-05-08 08:15:23",
    "verification_count": 145
  }
}
```

---

## 4. GPS / GEOFENCING

### 4.1 Validate GPS Location
```
POST /api/v1/gps/validate
```
**Request:**
```json
{
  "latitude": -6.2088,
  "longitude": 106.8456
}
```
**Response (200 - Within Geofence):**
```json
{
  "status": "success",
  "data": {
    "within_radius": true,
    "distance_meters": 15.5,
    "allowed_radius": 100,
    "branch": {
      "id": "uuid",
      "name": "Jakarta Pusat",
      "latitude": -6.2088,
      "longitude": 106.8456,
      "radius": 100,
      "address": "Jl. Sudirman No. 1"
    }
  }
}
```

**Response (Outside Geofence):**
```json
{
  "status": "error",
  "message": "Anda berada di luar area kantor",
  "data": {
    "within_radius": false,
    "distance_meters": 250,
    "allowed_radius": 100,
    "branch": {
      "id": "uuid",
      "name": "Jakarta Pusat",
      "latitude": -6.2088,
      "longitude": 106.8456,
      "radius": 100
    }
  }
}
```

### 4.2 Get Office Location
```
GET /api/v1/gps/office-location
```
**Query Parameters:**
| Parameter | Type | Required | Default |
|-----------|------|----------|---------|
| `branch_id` | uuid | No | User's branch |

**Response (200):**
```json
{
  "status": "success",
  "data": {
    "branch": {
      "id": "uuid",
      "name": "Jakarta Pusat",
      "latitude": -6.2088,
      "longitude": 106.8456,
      "radius": 100,
      "address": "Jl. Sudirman No. 1",
      "is_main": true
    }
  }
}
```

### 4.3 Detect Mock GPS
```
POST /api/v1/gps/detect-mock
```
**Request:**
```json
{
  "latitude": -6.2088,
  "longitude": 106.8456,
  "accuracy": 5.2,
  "device_fingerprint": "abc123def456",
  "mock_flags": {
    "is_mock_setting": false,
    "mock_app_detected": false,
    "location_provider": "gps"
  }
}
```
**Response (200 - Not Mocked):**
```json
{
  "status": "success",
  "data": {
    "is_mocked": false,
    "confidence": 0.95,
    "checks": {
      "accuracy_check": "pass",
      "mock_setting_check": "pass",
      "provider_check": "pass",
      "velocity_check": "pass"
    }
  }
}
```

**Response (200 - Suspected Mock):**
```json
{
  "status": "warning",
  "message": "GPS terdeteksi sebagai mock/spoof",
  "data": {
    "is_mocked": true,
    "confidence": 0.85,
    "checks": {
      "accuracy_check": "fail",
      "mock_setting_check": "fail",
      "provider_check": "pass",
      "velocity_check": "pass"
    }
  }
}
```

---

## 5. LEAVE MANAGEMENT

### 5.1 Get Leave Quota
```
GET /api/v1/leave/quota
```
**Query Parameters:**
| Parameter | Type | Required | Default |
|-----------|------|----------|---------|
| `year` | int | No | Current year |

**Response (200):**
```json
{
  "status": "success",
  "data": {
    "year": 2026,
    "quotas": [
      {
        "leave_type_id": "uuid",
        "leave_type": "Tahunan",
        "leave_type_code": "ANNUAL",
        "quota": 12,
        "used": 3,
        "remaining": 9,
        "carry_forward": 2,
        "carry_forward_deadline": "2026-03-31"
      },
      {
        "leave_type_id": "uuid",
        "leave_type": "Sakit",
        "leave_type_code": "SICK",
        "quota": null,
        "used": 1,
        "remaining": null,
        "carry_forward": 0
      }
    ],
    "is_probation": false,
    "probation_end_date": null
  }
}
```

### 5.2 Calculate Leave Days
```
POST /api/v1/leave/calculate-days
```
**Request:**
```json
{
  "leave_type_id": "uuid",
  "start_date": "2026-05-15",
  "end_date": "2026-05-17",
  "day_type": "full_day"
}
```
**Response (200):**
```json
{
  "status": "success",
  "data": {
    "total_days": 3,
    "working_days": 3,
    "weekend_days": 0,
    "holiday_days": 0,
    "holidays": [],
    "excluded_dates": []
  }
}
```

### 5.3 Submit Leave Request
```
POST /api/v1/leave
```
**Request:**
```json
{
  "leave_type_id": "uuid",
  "start_date": "2026-05-15",
  "end_date": "2026-05-17",
  "day_type": "full_day",
  "reason": "Liburan keluarga",
  "attachment": null
}
```
**Request (Sick Leave with attachment):**
```json
{
  "leave_type_id": "uuid",
  "start_date": "2026-05-15",
  "end_date": "2026-05-16",
  "day_type": "full_day",
  "reason": "Demam tinggi",
  "attachment": "base64_encoded_file"
}
```
**Response (201):**
```json
{
  "status": "success",
  "message": "Pengajuan cuti berhasil dikirim",
  "data": {
    "id": "uuid",
    "leave_type": "Tahunan",
    "start_date": "2026-05-15",
    "end_date": "2026-05-17",
    "total_days": 3,
    "day_type": "full_day",
    "status": "pending_l1",
    "approval_chain": [
      {
        "level": 1,
        "approver_name": "Manager Name",
        "approver_email": "manager@company.com",
        "status": "pending"
      },
      {
        "level": 2,
        "approver_name": "HR Manager Name",
        "approver_email": "hr@company.com",
        "status": "waiting"
      }
    ],
    "created_at": "2026-05-08 10:30:45"
  }
}
```

### 5.4 Leave History
```
GET /api/v1/leave/history
```
**Query Parameters:**
| Parameter | Type | Required | Default |
|-----------|------|----------|---------|
| `status` | string | No | all |
| `year` | int | No | Current year |
| `page` | int | No | 1 |
| `per_page` | int | No | 20 |

**Response (200):**
```json
{
  "status": "success",
  "data": [
    {
      "id": "uuid",
      "leave_type": "Tahunan",
      "start_date": "2026-05-15",
      "end_date": "2026-05-17",
      "total_days": 3,
      "day_type": "full_day",
      "reason": "Liburan keluarga",
      "status": "pending_l1",
      "status_label": "Menunggu Approval L1",
      "approver_l1": "Manager Name",
      "approver_l2": "HR Manager Name",
      "rejection_reason": null,
      "created_at": "2026-05-08 10:30:45"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 20,
    "total": 5,
    "summary": {
      "pending_l1": 1,
      "pending_l2": 0,
      "approved": 3,
      "rejected": 1
    }
  }
}
```

### 5.5 Withdraw Leave Request
```
POST /api/v1/leave/{leave_id}/withdraw
```
**Response (200):**
```json
{
  "status": "success",
  "message": "Pengajuan cuti berhasil dibatalkan",
  "data": {
    "id": "uuid",
    "status": "withdrawn",
    "status_label": "Dibatalkan"
  }
}
```

---

## 6. OVERTIME

### 6.1 Submit Overtime Request
```
POST /api/v1/overtime
```
**Request:**
```json
{
  "date": "2026-05-08",
  "start_time": "17:00",
  "end_time": "20:00",
  "description": "Menyelesaikan laporan keuangan bulanan"
}
```
**Response (201):**
```json
{
  "status": "success",
  "message": "Pengajuan lembur berhasil dikirim",
  "data": {
    "id": "uuid",
    "date": "2026-05-08",
    "start_time": "17:00",
    "end_time": "20:00",
    "duration_hours": 3,
    "description": "Menyelesaikan laporan keuangan bulanan",
    "status": "pending_l1",
    "status_label": "Menunggu Approval L1",
    "approval_chain": [
      {
        "level": 1,
        "approver_name": "Manager Name",
        "status": "pending"
      },
      {
        "level": 2,
        "approver_name": "HR Manager Name",
        "status": "waiting"
      }
    ],
    "created_at": "2026-05-08 17:30:45"
  }
}
```

### 6.2 Overtime History
```
GET /api/v1/overtime/history
```
**Query Parameters:**
| Parameter | Type | Required | Default |
|-----------|------|----------|---------|
| `status` | string | No | all |
| `month` | int | No | Current month |
| `year` | int | No | Current year |
| `page` | int | No | 1 |
| `per_page` | int | No | 20 |

**Response (200):**
```json
{
  "status": "success",
  "data": [
    {
      "id": "uuid",
      "date": "2026-05-08",
      "start_time": "17:00",
      "end_time": "20:00",
      "duration_hours": 3,
      "description": "Menyelesaikan laporan keuangan bulanan",
      "status": "approved",
      "status_label": "Disetujui",
      "approved_by_l1": "Manager Name",
      "approved_by_l2": "HR Manager Name",
      "rejection_reason": null,
      "created_at": "2026-05-08 17:30:45"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 20,
    "total": 8,
    "summary": {
      "pending_l1": 1,
      "pending_l2": 0,
      "approved": 6,
      "rejected": 1
    }
  }
}
```

### 6.3 Withdraw Overtime Request
```
POST /api/v1/overtime/{overtime_id}/withdraw
```
**Response (200):**
```json
{
  "status": "success",
  "message": "Pengajuan lembur berhasil dibatalkan"
}
```

---

## 7. PAYROLL

### 7.1 Get Payslip
```
GET /api/v1/payroll/slip
```
**Query Parameters:**
| Parameter | Type | Required | Default |
|-----------|------|----------|---------|
| `period` | string | Yes | - (YYYY-MM) |

**Response (200):**
```json
{
  "status": "success",
  "data": {
    "period": "2026-04",
    "employee": {
      "name": "John Doe",
      "nik": "EMP-202601-001",
      "position": "Software Engineer",
      "department": "Engineering"
    },
    "earnings": {
      "basic_salary": 8000000,
      "allowance_jabatan": 500000,
      "overtime_pay": 750000,
      "attendance_bonus": 200000,
      "total": 9450000
    },
    "deductions": {
      "bpjs_health": 160000,
      "bpjs_employment": 80000,
      "pph21": 250000,
      "loan_deduction": 0,
      "attendance_penalty": 0,
      "total": 490000
    },
    "take_home_pay": 8960000,
    "status": "published",
    "published_at": "2026-05-01 09:00:00",
    "is_locked": true
  }
}
```

### 7.2 Download Payslip PDF
```
GET /api/v1/payroll/slip/{period}/download
```
**Response:** PDF file stream (`application/pdf`)
**Headers:**
```
Content-Disposition: attachment; filename="payslip-2026-04.pdf"
Content-Type: application/pdf
```

### 7.3 Payroll History
```
GET /api/v1/payroll/history
```
**Query Parameters:**
| Parameter | Type | Required | Default |
|-----------|------|----------|---------|
| `year` | int | No | Current year |
| `page` | int | No | 1 |
| `per_page` | int | No | 12 |

**Response (200):**
```json
{
  "status": "success",
  "data": [
    {
      "period": "2026-04",
      "gross_salary": 9450000,
      "total_deductions": 490000,
      "net_salary": 8960000,
      "status": "published",
      "status_label": "Diterbitkan",
      "published_at": "2026-05-01 09:00:00",
      "has_pdf": true
    },
    {
      "period": "2026-03",
      "gross_salary": 9200000,
      "total_deductions": 480000,
      "net_salary": 8720000,
      "status": "published",
      "status_label": "Diterbitkan",
      "published_at": "2026-04-01 09:00:00",
      "has_pdf": true
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 12,
    "total": 4
  }
}
```

### 7.4 Payroll Adjustment (HRD Only)
```
POST /api/v1/payroll/{payroll_id}/adjust
```
**Request:**
```json
{
  "amount": -500000,
  "reason": "Karyawan tidak masuk 2 hari di bulan April, koreksi payroll bulan Mei",
  "applied_to_period": "2026-05"
}
```
**Response (201):**
```json
{
  "status": "success",
  "message": "Adjustment berhasil ditambahkan",
  "data": {
    "id": "uuid",
    "payroll_id": "uuid",
    "amount": -500000,
    "reason": "Karyawan tidak masuk 2 hari di bulan April, koreksi payroll bulan Mei",
    "applied_to_period": "2026-05",
    "created_by": "uuid",
    "created_at": "2026-05-08 10:30:45"
  }
}
```

---

## 8. KNOWLEDGEBASE AI (RAG)

### 8.1 Query AI (Chat)
```
POST /api/v1/knowledge-base/query
```
**Request:**
```json
{
  "question": "Berapa hari cuti tahunan yang saya miliki?",
  "conversation_id": "uuid"
}
```
**Response (200):**
```json
{
  "status": "success",
  "data": {
    "answer": "Anda memiliki 9 hari cuti tahunan tersisa dari total 12 hari. Sisa 2 hari cuti dari tahun sebelumnya sudah di-carry forward dan harus digunakan sebelum 31 Maret 2026.",
    "confidence": 0.92,
    "sources": [
      {
        "document": "Kebijakan Cuti Karyawan 2026",
        "page": 3,
        "excerpt": "Karyawan tetap berhak atas 12 hari cuti tahunan...",
        "relevance_score": 0.95
      },
      {
        "document": "HR Handbook",
        "page": 15,
        "excerpt": "Cuti yang tidak digunakan dapat di-carry forward maksimal 3 hari...",
        "relevance_score": 0.88
      }
    ],
    "conversation_id": "uuid",
    "response_time_ms": 1250
  }
}
```

**Response (Fallback - Text Search):**
```json
{
  "status": "success",
  "data": {
    "answer": "Berikut adalah dokumen yang relevan dengan pertanyaan Anda:",
    "confidence": 0.65,
    "sources": [
      {
        "document": "Kebijakan Cuti Karyawan 2026",
        "page": 3,
        "excerpt": "Karyawan tetap berhak atas 12 hari cuti tahunan...",
        "relevance_score": 0.78
      }
    ],
    "conversation_id": "uuid",
    "response_time_ms": 450,
    "fallback_mode": true,
    "fallback_reason": "AI service timeout"
  }
}
```

### 8.2 Search (Text Fallback)
```
GET /api/v1/knowledge-base/search
```
**Query Parameters:**
| Parameter | Type | Required | Default |
|-----------|------|----------|---------|
| `query` | string | Yes | - |
| `limit` | int | No | 5 |

**Response (200):**
```json
{
  "status": "success",
  "data": [
    {
      "id": "uuid",
      "title": "Kebijakan Cuti Karyawan 2026",
      "category": "hr_policy",
      "excerpt": "Karyawan tetap berhak atas 12 hari cuti tahunan per tahun kalender...",
      "relevance_score": 0.85
    }
  ]
}
```

---

## 9. DEVICE MANAGEMENT

### 9.1 Register Device
```
POST /api/v1/device/register
```
**Request:**
```json
{
  "device_uuid": "unique-device-identifier",
  "device_name": "Chrome on iPhone",
  "device_type": "MOBILE",
  "browser": "Chrome Mobile 120",
  "os": "iOS 17.2"
}
```
**Response (201):**
```json
{
  "status": "success",
  "message": "Perangkat berhasil didaftarkan",
  "data": {
    "id": "uuid",
    "device_uuid": "unique-device-identifier",
    "device_name": "Chrome on iPhone",
    "device_type": "MOBILE",
    "browser": "Chrome Mobile 120",
    "os": "iOS 17.2",
    "is_verified": false,
    "requires_approval": true,
    "registered_at": "2026-05-08 10:30:45"
  }
}
```

### 9.2 Check Device Status
```
GET /api/v1/device/status
```
**Response (200):**
```json
{
  "status": "success",
  "data": {
    "current_device": {
      "device_uuid": "unique-device-identifier",
      "is_verified": true,
      "verified_at": "2026-01-15 09:30:00"
    },
    "registered_devices": [
      {
        "id": "uuid",
        "device_name": "Chrome on iPhone",
        "device_type": "MOBILE",
        "is_verified": true,
        "last_used": "2026-05-08 10:30:45"
      }
    ],
    "max_devices": 3,
    "current_device_count": 1
  }
}
```

---

## 10. EMPLOYEE PROFILE

### 10.1 Get Profile
```
GET /api/v1/profile
```
**Response (200):**
```json
{
  "status": "success",
  "data": {
    "id": "uuid",
    "nik": "EMP-202601-001",
    "name": "John Doe",
    "email": "john@company.com",
    "phone": "+6281234567890",
    "address": "Jl. Merdeka No. 1, Jakarta",
    "birth_date": "1990-05-15",
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
| `LEAVE_QUOTA_EXCEEDED` | 400 | Insufficient leave quota |
| `PROBATION_BLOCK` | 403 | Probation employee can't take annual leave |
| `PAYROLL_LOCKED` | 409 | Payroll already published/locked |
| `DUPLICATE_REQUEST` | 409 | Duplicate submission detected |
| `RATE_LIMIT_EXCEEDED` | 429 | Too many requests |
| `AI_SERVICE_TIMEOUT` | 504 | AI service timeout (use fallback) |
| `INTERNAL_ERROR` | 500 | Unexpected server error |

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
'allowed_origins' => ['*'], // PWA origin
'allowed_headers' => ['Authorization', 'Content-Type', 'X-Requested-With', 'X-Device-UUID'],
'exposed_headers' => ['X-RateLimit-Limit', 'X-RateLimit-Remaining'],
'supports_credentials' => true,
```

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

*Last Updated: 2026-05-08*
*Version: 1.0.0*
*Status: Draft - Ready for Implementation*
