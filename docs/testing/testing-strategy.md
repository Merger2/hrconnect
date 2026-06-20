# HRConnect — Testing Strategy

> **Strategi pengujian lengkap untuk HRConnect.**
> Setiap perubahan WAJIB disertai test yang sesuai.

---

## 1. Testing Pyramid

```
                    /\
                   /  \
                  / API \          Feature Tests (47 files)
                 /________\
                /          \
               /   Unit     \     Unit Tests (26 files)
              /______________\
             /                \
            /  PostgreSQL Int.  \  Integration Tests (1 file)
           /____________________\
```

**Rasio aktual:** 64% Feature | 35% Unit | 1% Integration

---

## 2. Test Tools

| Tool | Usage | Version |
|------|-------|---------|
| Pest PHP | Test framework | v4 |
| PHPUnit | Assertion library | v12 |
| Laravel RefreshDatabase | Database reset per test | Built-in |
| Laravel Fake | Notification/Queue/Mail faking | Built-in |
| Livewire Test | Component testing | v4 |
| Mockery | Mocking framework | Built-in |

---

## 3. Feature Tests (47 files)

### 3.1 API Proof Tests (`tests/Feature/Api/`)

| File | Tests | Covers |
|------|------:|--------|
| `AuthProofTest.php` | Login, 2FA, TOTP, rate limit, forgot-password, password expiry, logout-all |
| `AttendanceProofTest.php` | 30 | Clock-in/out face+GPS+WFA, PIN fallback, duplicate, geofence 403, low accuracy, scope, status filter |
| `LeaveProofTest.php` | Leave CRUD, auth, permission, owner access, weekend dates |
| `LeaveAndOvertimeTest.php` | 15 | Sick proof, probation, cancel, L1+L2 approval, double-action, wrong approver, rejection |
| `OvertimeProofTest.php` | Overtime CRUD, auth, permission, owner access |
| `ReimbursementProofTest.php` | Reimbursement CRUD, auth, permission, state conflict |
| `ApprovalProofTest.php` | Pending approvals, approve/reject, auth, validation |
| `PayrollProofTest.php` | 20 | List, show, generate, payslip gating, exports, permission |
| `PayrollExportTest.php` | Payroll export formats |
| `EmployeeProofTest.php` | Employee CRUD, auth, permission |
| `FaceProofTest.php` | Face register/verify, auth, validation |
| `KnowledgeBaseProofTest.php` | 14 | Chat mock mode, upload partialMock, delete, auth/permission |
| `KnowledgeBaseEndpointTest.php` | KB upload/download edge cases |

### 3.2 API Integration Tests (`tests/Feature/Api/`)

| File | Tests | Covers |
|------|------:|--------|
| `ControllerHttpTest.php` | HTTP response codes for all controllers |
| `EndpointsTest.php` | 57 | 401 smoke tests for all 51 protected routes |
| `SecurityRegressionTest.php` | Security regression scenarios |
| `OpenApiContractTest.php` | 25 | OpenAPI spec structure, route completeness, security contract, pagination contract, operationId, smoke tests |
| `WebRouteSmokeTest.php` | 18 | Fortify auth pages, dashboard, settings |

### 3.3 Auth Tests (`tests/Feature/Auth/`)

| File | Covers |
|------|--------|
| `AuthenticationTest.php` | Fortify login flow |
| `EmailVerificationTest.php` | Email verification |
| `PasswordConfirmationTest.php` | Password confirmation |
| `PasswordResetTest.php` | Forgot password flow |
| `RegistrationTest.php` | Registration (disabled in production) |
| `TwoFactorChallengeTest.php` | 2FA/TOTP challenge |

### 3.4 Service Tests (`tests/Feature/Services/`)

| File | Covers |
|------|--------|
| `AttendanceServiceTest.php` | Clock-in/out business logic |
| `EmployeeTerminationServiceTest.php` | Termination happy path |
| `KnowledgeBaseServiceTest.php` | RAG chat, fallback keyword search, confidence |
| `PayrollExportServiceTest.php` | XLSX export creation |
| `PayslipPdfServiceTest.php` | PDF template building |

### 3.5 Other Feature Tests

| File | Covers |
|------|--------|
| `PoliciesTest.php` | 36 | Policy boundary tests (all 8 policies) |
| `RoleAndPermissionSeederTest.php` | 217 | 5-role × 44-permission data-driven matrix |
| `ConsoleCommandsTest.php` | 25 | 4 scheduled commands with 3 bugfixes |
| `PiiCipherSweetTest.php` | 14 | CipherSweet encryption, blind index, encryptedUnique, PII audit |
| `CacheIntegrationTest.php` | 8 | TaxConfig, CompanySetting, Holiday, BpjsConfig cachedAll |
| `ObserverTest.php` | 2 | CompanySettingObserver cache invalidation |
| `CheckPasswordExpiredTest.php` | Password expiry middleware |
| `PasswordExpiryTest.php` | 7 | Change-password resets clock, forgot-password sets timestamp, logout-all |
| `SanctumApiTest.php` | Sanctum token auth |
| `SuperAdminSeederTest.php` | Super admin seeder |
| `Middleware/GeofenceValidationTest.php` | 8 | Geofence middleware |
| `Notifications/NotificationTest.php` | Notification dispatch |
| `Settings/ProfileUpdateTest.php` | Profile update |
| `Settings/SecurityTest.php` | Security settings |
| `DashboardTest.php` | Dashboard access |

---

## 4. Unit Tests (26 files)

### 4.1 Service Tests (`tests/Unit/Services/`)

| File | Covers |
|------|--------|
| `FaceRecognitionServiceTest.php` | 6 | Dimension, validation, no-face, non-numeric |
| `GeofenceServiceTest.php` | 11 | Haversine, within-radius, edge cases |
| `LeaveServiceTest.php` | Leave balance validation, probation block |
| `ApprovalServiceTest.php` | Approval chain, next approver |
| `OvertimeServiceTest.php` | Creation, overnight logic, cancel |
| `ReimbursementServiceTest.php` | Reimbursement business logic |
| `EmbeddingServiceTest.php` | 6 | Chunking, format, Pinecone stub |
| `GeminiClientTest.php` | 7 | Mock mode, retry logic |
| `ProfileServiceTest.php` | 5 | Get profile, update, change password |

### 4.2 Payroll Tests (`tests/Unit/`)

| File | Covers |
|------|--------|
| `PayrollCalculatorCoreTest.php` | 41 | Overtime pay, BPJS, PPh21, THR, pesangon, leave cash-out |
| `PayrollCalculatorTerCategoryTest.php` | PPh21 TER category calculation |
| `OvertimeRateTest.php` | Overtime rate tiers |
| `TerminationCalculationTest.php` | Termination payout calculation |

### 4.3 Job Tests (`tests/Unit/Jobs/`)

| File | Covers |
|------|--------|
| `JobTest.php` | 9 | Basic job dispatch |
| `JobEdgeCaseTest.php` | Failed handler, reimbursement PAID→APPROVED rollback |

### 4.4 Other Unit Tests

| File | Covers |
|------|--------|
| `ArchitectureTest.php` | Laravel architecture presets |
| `EmployeeFillableTest.php` | Employee model fillable/hidden |
| `LeaveDateRangeValidationTest.php` | Leave date overlap validation |
| `AttendancePinFallbackTest.php` | PIN fallback logic |
| `Phase3BugFixesTest.php` | Phase 3 regression bugfixes |
| `RegressionModelTest.php` | Model regression checks |
| `Http/Requests/FormRequestValidationTest.php` | 6 untested FormRequest validation rules |
| `Requests/ListRequestValidationTest.php` | List endpoint request validation |
| `Middleware/DeviceDetectionTest.php` | 8 | UA-parsing middleware |

---

## 5. PostgreSQL Integration Tests

| File | Tests | Covers |
|------|------:|--------|
| `PostgresEnvironmentTest.php` | 19 | pgvector 128D/768D, HNSW index, nearest neighbor ranking, CipherSweet, constraints, payroll locking, FaceRecognitionService threshold override, similarity_percentage, FaceNotRegisteredException |

**Run:** `php artisan test --configuration=phpunit.pgsql.xml --compact`

---

## 6. Test Conventions

### 6.1 Naming
- Feature API: `test('describes behavior', function () { })`
- Use Pest `it()` / `test()` syntax throughout

### 6.2 Structure
```php
it('allows clock in with valid face and gps', function () {
    $employee = Employee::factory()->create();
    $this->actingAs($employee->user);

    $response = $this->post(route('attendance.clock-in'), [
        'lat' => -6.2088,
        'long' => 106.8456,
    ]);

    $response->assertOk();
    expect(Attendance::count())->toBe(1);
});
```

### 6.3 Factories
- Use factories for test data; use existing custom states
- Use `fake()` for random data
- 8 factories created: Approval, CompanySetting, Device, FamilyDetail, PayrollAdjustment, PayrollItem, ShiftSchedule, KnowledgeBase

### 6.4 Database
- Use `RefreshDatabase` trait for feature tests
- SQLite in-memory for 95% of tests
- PostgreSQL for vector/integration tests via `phpunit.pgsql.xml`

### 6.5 Mocking
- `Notification::fake()` for notifications
- `Queue::fake()` for queueable jobs
- `Storage::fake()` for file uploads
- `Http::fake()` for external API calls
- AI SDK `HrKnowledgeBaseAgent::fake()` for RAG mock mode

---

## 7. Running Tests

```bash
# Full SQLite suite
composer test

# Fast SQLite
php artisan test --compact

# Focused test
php artisan test --compact --filter=Name

# PostgreSQL integration
composer test:pgsql

# Lint auto-fix
vendor/bin/pint --dirty --format agent

# Route audit
php artisan route:list --path=api --except-vendor
```

---

## 8. Coverage Summary (Current)

| Area | Tests | Assertions |
|------|------:|----------:|
| API Endpoint Proofs | ~200 | ~500 |
| Auth/Web routes | ~50 | ~80 |
| Services | ~80 | ~200 |
| Policies & Permissions | ~250 | ~300 |
| Payroll Calculator | ~50 | ~150 |
| PII/CipherSweet | 14 | 36 |
| Cache/Observers | 10 | 20 |
| Console Commands | 25 | 41 |
| PostgreSQL Integration | 19 | 43 |
| OpenAPI Contract | 25 | 617 |
| **Total** | **1,018** | **3,516** |

---

## 9. Database Testing Strategy

### 9.1 Dual Environment

| Environment | Database | Tujuan |
|------------|----------|--------|
| Production | PostgreSQL + pgvector | Data persist, vector search, full feature |
| Testing (default) | SQLite `:memory:` | Unit & Feature test cepat |
| Testing (PG) | PostgreSQL `hrconnect_testing` | Vector similarity, constraint, CipherSweet tests |

### 9.2 Defensive Migration Pattern

Semua migration yang menggunakan fitur PostgreSQL-specific **WAJIB** di-guard:

```php
if (DB::getDriverName() === 'pgsql') {
    $table->vector('face_embedding', dimensions: 128)->nullable();
} else {
    $table->text('face_embedding')->nullable();
}
```

### 9.3 PostgreSQL-Specific Features

| Feature | Guard Status |
|---------|--------------|
| `pgvector` extension | ✅ Guarded |
| `pg_trgm` extension | ✅ Guarded |
| `pgcrypto` extension | ✅ Guarded |
| `vector(128)` column | ✅ Guarded |
| `vector(768)` column | ✅ Guarded |
| HNSW index | ✅ Guarded |
| Unique constraints | ✅ Via `UniqueConstraintViolationException` (cross-database) |

### 9.4 UniqueConstraintViolationException

**JANGAN** hardcode SQL error code `23505` (PostgreSQL-only). Gunakan:

```php
catch (UniqueConstraintViolationException $e) { ... }
```

### 9.5 AI/Vector Test Approach

| Test Type | Pendekatan | Environment |
|-----------|-----------|-------------|
| Controller/Service logic | Mock service class | SQLite |
| Model creation | `RefreshDatabase` — vector column fallback ke `text` | SQLite |
| Vector similarity search | Real pgvector query | PostgreSQL |
| RAG chat | AI SDK `HrKnowledgeBaseAgent::fake()` | SQLite |

---

*Dokumen ini mencerminkan test suite aktual per 2026-06-20.*
*73 test files, 1,018 tests, 3,516 assertions (SQLite) + 19 tests, 43 assertions (PostgreSQL).*
