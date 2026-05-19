# HRConnect - Security Configuration

> **Dokumen ini berisi konfigurasi keamanan lengkap untuk HRConnect.**
> Semua pengaturan keamanan harus diterapkan sesuai spesifikasi ini.

---

## 1. AUTHENTICATION

### 1.1 Laravel Fortify
```php
// config/fortify.php
'features' => [
    Features::registration(),
    Features::resetPasswords(),
    Features::emailVerification(),
    Features::updateProfileInformation(),
    Features::updatePasswords(),
    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]),
],
```

### 1.2 Two-Factor Authentication (2FA)
- **Method:** TOTP (Time-based One-Time Password)
- **Window:** 30 seconds
- **Recovery Codes:** 8 codes, hashed
- **Enforcement:** Wajib untuk role HRD, Finance, Super Admin
- **Setup:** QR code scan via Google Authenticator / Authy

### 1.3 Google OAuth
- **Provider:** Google Identity
- **Scopes:** email, profile
- **Flow:** Link to existing account via email match
- **Verification:** Email auto-verified jika via Google

### 1.4 Force Password Change
- **Trigger:** First login after account created by admin
- **Flag:** `users.force_password_change = true`
- **Middleware:** `ForcePasswordChangeMiddleware`
- **Redirect:** `/settings/security` until changed
- **Cleared:** `force_password_change = false` after successful change

### 1.5 Password Policy
- **Minimum Length:** 8 characters
- **Requirements:** Uppercase, lowercase, number, special char
- **Breach Check:** Via HaveIBeenPwned API (opsional)
- **Expiration:** 90 days (reminder at 7 days before)
- **History:** Cannot reuse last 3 passwords

> **ERRATA (CAT-005):** PRD §4 line 155 mengatakan "**Tidak ada password expiry**". Security Config §1.5 mengatakan "90 days". **CTO Decision: Security Config MENANG.** Alasan: (1) kolom `password_changed_at` sudah ada untuk fungsi ini, (2) ISO 27001 compliance mewajibkan password rotation, (3) Force change password (PRD §4.2) butuh timestamp untuk menghitung umur password. Lihat juga task.md §1.6 untuk middleware implementasi.

---

## 2. AUTHORIZATION (RBAC)

### 2.1 Spatie Permission
```php
// Roles
- Super Admin: Full access
- HR Manager: HR modules (employees, attendance, leave, approvals)
- Finance: Payroll, loans, reimbursements
- Supervisor: Approve team requests, view team data
- Employee: Self-service only

// Permission Format: {action}-{module}
- view-employees, create-employees, edit-employees
- view-payroll, generate-payroll, publish-payroll
- view-attendance, approve-attendance
- view-leave, approve-leave
```

### 2.1.1 Permission Enum + Seeders
> **ERRATA (SEC-4):** Permission enum `App\Enums\Permission` dan `RoleAndPermissionSeeder` **BELUM DIBUAT**. Tanpa ini, `$user->can()` selalu return `false`. Buat enum, seeder, dan register ke `AuthServiceProvider`.

```php
// app/Enums/Permission.php (BELUM ADA — perlu dibuat)
enum Permission: string
{
    case VIEW_EMPLOYEES = 'view-employees';
    case CREATE_EMPLOYEES = 'create-employees';
    case EDIT_EMPLOYEES = 'edit-employees';
    case DELETE_EMPLOYEES = 'delete-employees';
    case VIEW_PAYROLL = 'view-payroll';
    case GENERATE_PAYROLL = 'generate-payroll';
    case PUBLISH_PAYROLL = 'publish-payroll';
    case VIEW_ATTENDANCE = 'view-attendance';
    case APPROVE_ATTENDANCE = 'approve-attendance';
    case VIEW_LEAVE = 'view-leave';
    case APPROVE_LEAVE = 'approve-leave';
    // ... +40 permissions lainnya
}
```

```php
// database/seeders/RoleAndPermissionSeeder.php (BELUM ADA — perlu dibuat)
// 5 roles: super-admin, hr-manager, finance, supervisor, employee
// + 50+ permissions dari Permission enum
```

### 2.2 Policy Guards
- Every model has corresponding Policy
- Policy methods: `viewAny`, `view`, `create`, `update`, `delete`
- Custom methods: `approve`, `reject`, `lock`, `publish`

### 2.3 Middleware Protection
```php
// Route middleware
Route::middleware(['role:super-admin|hr-manager'])->group(...)
Route::middleware(['permission:view-payroll'])->group(...)

// In Livewire components
$this->authorize('view', $employee);
$this->authorize('approve', $leave);
```

---

## 3. DATA ENCRYPTION (CipherSweet)

### 3.1 Encrypted Fields
| Model | Field | Encryption | Blind Index |
|-------|-------|------------|-------------|
| Employee | `nik` | ✅ Yes | ✅ Yes (searchable) |
| Employee | `phone` | ✅ Yes | ✅ Yes (searchable) |
| Employee | `npwp` | ✅ Yes | ✅ Yes (searchable) |
| Employee | `bank_account_number` | ✅ Yes | ❌ No |
| Employee | `face_embedding` | ❌ No (vector) | ❌ No |

### 3.2 Configuration
```php
// config/ciphersweet.php
'backend' => 'sodium', // libsodium (default in PHP 8.5)
'key' => env('CIPHERSWEET_KEY'),

// Generate key:
// php artisan ciphersweet:generate-key
```

### 3.3 Field Index Configuration
```php
// In Employee model
use ParagonIE\CipherSweet\Contract\BackendInterface;
use ParagonIE\CipherSweet\Field\TextField;

$employee = (new EncryptedEloquentModel())
    ->setName('employees')
    ->addTextField('nik')
        ->addBlindIndex('nik_index', [
            new BlindIndex('nik_index', [], 32)
        ]);
```

---

## 4. API SECURITY

### 4.1 API Authentication
- **Method:** Laravel Sanctum tokens (SPA + Mobile API)
- **Package:** `laravel/sanctum` — **BELUM TERINSTALL** (task.md §1.3 / SEC-3)
- **Scope:** PWA face recognition, GPS sync
- **Rate Limiting:** 60 requests/minute per user
- **Token Lifetime:** 24 jam, refresh via `POST /api/v1/auth/refresh`
- **Model Trait:** `HasApiTokens` pada User model
- **Guard:** `sanctum` untuk API routes, `web` untuk Livewire

> **ERRATA (SEC-3):** Sanctum belum terinstall. Install: `composer require laravel/sanctum`, publish config, tambah `HasApiTokens` trait ke User model, migrasikan `personal_access_tokens` table. Detail di task.md §1.3.

### 4.2 Rate Limiting
```php
// routes/api.php
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

// Attendance endpoint: stricter
RateLimiter::for('attendance', function (Request $request) {
    return Limit::perMinute(10)->by($request->user()?->id);
});
```

### 4.3 CORS
```php
// config/cors.php
'paths' => ['api/*'],
'allowed_methods' => ['*'],
'allowed_origins' => [env('APP_URL')],
'allowed_headers' => ['*'],
'supports_credentials' => true,
```

---

## 5. INPUT VALIDATION

### 5.1 Form Request Validation
- All POST/PUT/PATCH requests use Form Request classes
- Validation rules defined in `rules()` method
- Custom error messages in `messages()` method
- All error messages in Bahasa Indonesia

### 5.2 File Upload Security
```php
// Face photo
'max:2048' // 2MB max
'mimes:jpeg,png,jpg'
'image'

// Knowledge Base PDF
'max:10240' // 10MB max
'mimes:pdf'

// Receipt/Proof
'max:5120' // 5MB max
'mimes:jpeg,png,jpg,pdf'
```

### 5.3 SQL Injection Prevention
- Always use Eloquent ORM or Query Builder
- No raw SQL queries
- Parameterized queries only

### 5.4 XSS Prevention
- Blade auto-escapes output: `{{ $variable }}`
- Use `{!! $variable !!}` ONLY for trusted HTML
- Sanitize user input before storing

### 5.5 CSRF Protection
- Enabled by default via `VerifyCsrfToken` middleware
- All forms include `@csrf`
- API routes excluded (use Sanctum tokens)

---

## 6. GPS & FACE RECOGNITION SECURITY

### 6.1 Anti-Fake GPS
```javascript
// Client-side detection
navigator.geolocation.getCurrentPosition(
    (position) => {
        const accuracy = position.coords.accuracy;
        const isMocked = position.coords.mocked || false;

        // Send to server
        fetch('/api/attendance/clock-in', {
            body: {
                lat: position.coords.latitude,
                long: position.coords.longitude,
                accuracy: accuracy,
                is_mocked: isMocked,
                // ...
            }
        });
    }
);
```

**Server-side validation:**
- Reject if `is_mocked = true`
- Reject if `accuracy > 100m` (too inaccurate)
- Cross-reference with Haversine calculation
- Log suspicious attempts

### 6.2 Face Recognition Security
- **Threshold:** 0.85 (85% similarity)
- **Liveness Detection:** Require blink/turn head (opsional)
- **Replay Attack Prevention:** Random challenge (opsional)
- **Embedding Storage:** Never store raw face photos publicly
- **Face Photo Access:** Only visible to HRD and employee themselves

---

## 7. SESSION SECURITY

### 7.1 Session Configuration
```php
// config/session.php
'driver' => 'database',
'lifetime' => 120, // 2 hours
'expire_on_close' => true,
'secure' => true, // HTTPS only
'http_only' => true,
'same_site' => 'lax',
```

### 7.2 Concurrent Sessions
- **Policy:** Allow multiple sessions (mobile + desktop)
- **Limit:** Max 5 active sessions per user
- **Cleanup:** Expired sessions cleaned via `sessions:cleanup` command

---

## 8. FILE STORAGE SECURITY

### 8.1 Storage Configuration
```php
// config/filesystems.php
'disks' => [
    'private' => [
        'driver' => 'local',
        'root' => storage_path('app/private'),
        'visibility' => 'private',
    ],
    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'visibility' => 'public',
    ],
],
```

### 8.2 File Access Control
- Private files: Only accessible via authenticated routes
- Public files: Avatars, company logo
- Sensitive files: Payroll PDFs, face photos → `private` disk

### 8.3 File Validation
- MIME type validation (not just extension)
- File size limits enforced
- Scan for malware (opsional, via ClamAV)

---

## 9. EXCEPTION HANDLING & HTTP CODES

### 9.1 Custom Exception Code Mapping
> **ERRATA (SEC-5):** BusinessRuleException harus return **422** (Unprocessable Entity), bukan 400. File `app/Exceptions/BusinessRuleException.php` saat ini extends `Exception` (return 500). Perlu diubah ke `HttpException` dengan code 422.

| Exception Class | HTTP Code | Use Case |
|----------------|-----------|----------|
| `BusinessRuleException` | 422 | Business rule violations (leave quota exceeded, payroll locked, etc.) |
| `FaceNotRegisteredException` | 422 | Employee has no face embedding registered (business rule, not bad request) |
| `NotClockedInException` | 409 | Clock-out attempted without clock-in (state conflict, consistent with `AlreadyClockedInException`) |
| `ValidationException` | 422 | Form request validation failures |
| `AuthenticationException` | 401 | Unauthenticated / invalid token |
| `AuthorizationException` | 403 | Insufficient permissions |
| `ModelNotFoundException` | 404 | Resource not found |

> **ERRATA (SEC-5 update):** `FaceNotRegisteredException` sebelumnya 400 → **422** (business rule violation, bukan bad request). `NotClockedInException` sebelumnya 400 → **409** (state conflict, konsisten dengan `AlreadyClockedInException` yang return 409). Lihat juga task.md §1.5.

### 9.2 ApprovalLevel Enum Comparison
> **ERRATA (C1):** Model `Approval` cast `level` ke `ApprovalLevel` enum. Perbandingan `$approval->level === 1` **SELALUS false** (enum vs int strict comparison). Gunakan `$approval->level->value === 1` atau `$approval->level === ApprovalLevel::L1_SUPERVISOR`.

### 9.3 Payroll Immutability & forceDelete
> **ERRATA (C2):** Payroll menggunakan `SoftDeletes`, tapi `$existingPayroll->delete()` hanya set `deleted_at`. Record tetap ada, menyebabkan **unique constraint violation** saat regenerasi. Gunakan `$existingPayroll->forceDelete()` sebelum create payroll baru periode yang sama.

---

## 10. LOGGING & AUDIT

### 10.1 Activity Log (Spatie Activity Log)
- Log all CRUD operations on sensitive models
- Log: user, action, model, old values, new values
- Retention: 90 days
- Models to log: Employee, Payroll, Leave, Approval, Attendance

### 10.2 Error Logging
- Laravel log channel: `daily`
- Max files: 14 days
- Error reporting: Sentry/Bugsnag (opsional)

### 10.3 Security Events
- Failed login attempts (Fortify throttling)
- Password change events
- Role/permission changes
- Payroll lock/unlock events
- Resignation submission events

---

## 11. ENVIRONMENT VARIABLES

```env
# App
APP_NAME=HRConnect
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://hrconnect.company.com

# Database
DB_CONNECTION=pgsql
DB_HOST=ep-xxx.neon.tech
DB_PORT=5432
DB_DATABASE=hrconnect
DB_USERNAME=hrconnect
DB_PASSWORD=xxx
DB_SSLMODE=require

# CipherSweet
CIPHERSWEET_KEY=base64:...

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.company.com
MAIL_PORT=587
MAIL_USERNAME=hr@company.com
MAIL_PASSWORD=xxx
MAIL_ENCRYPTION=tls

# Queue
QUEUE_CONNECTION=database

# Sanctum (API Token Auth)
SANCTUM_STATEFUL_DOMAINS=hrconnect.company.com
SESSION_DOMAIN=hrconnect.company.com

# Face Recognition
FACE_THRESHOLD=0.85

# Geofence
GEOFENCE_DEFAULT_RADIUS=100

# AI/RAG
OPENAI_API_KEY=sk-xxx
GEMINI_API_KEY=xxx
GEMINI_MODEL=gemini-2.5-pro

# Google OAuth
GOOGLE_CLIENT_ID=xxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=xxx
```

---

## 12. SECURITY CHECKLIST

### Before Deployment
- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] HTTPS enabled
- [ ] All sensitive env vars set
- [ ] CipherSweet key generated
- [ ] 2FA enforced for admin roles
- [ ] Rate limiting configured
- [ ] CORS configured
- [ ] Session secure settings
- [ ] File upload limits set
- [ ] Activity logging enabled
- [ ] Error reporting configured
- [ ] Database SSL enabled
- [ ] Backup schedule configured
- [ ] Firewall configured (UFW)
- [ ] Fail2ban installed
- [ ] Security headers set (HSTS, CSP, X-Frame-Options)
- [ ] Sanctum installed and configured (`php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`)
- [ ] Permission enum + RoleAndPermissionSeeder migrated
- [ ] BusinessRuleException returns 422 (not 400/500)

---

*Dokumen ini harus diikuti untuk memastikan keamanan aplikasi.*
*Terakhir diupdate: 2026-05-19 — Updated SEC-5: FaceNotRegistered→422, NotClockedIn→409. Added CAT-005: Password expiry 90 hari override PRD §4.*
