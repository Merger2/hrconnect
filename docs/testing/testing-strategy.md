# HRConnect - Testing Strategy

> **Dokumen ini berisi strategi pengujian lengkap untuk HRConnect.**
> Setiap perubahan WAJIB disertai test yang sesuai.

> **ERRATA (2026-05-13):** Beberapa fix kritis yang harus dites:
> - **C1:** Test ApprovalLevel enum comparison — `$approval->level === 1` MUST fail, `$approval->level->value === 1` MUST pass
> - **C2:** Test Payroll `forceDelete()` — soft-deleted payroll regenerasi MUST NOT throw unique constraint violation
> - **C3:** Test Sanctum API auth — `POST /api/v1/auth/login` MUST return token, `Bearer {token}` MUST authenticate
> - **C4:** Test Permission enum + seeder — `$user->can('view-employees')` MUST return true for assigned roles
> - **SEC-5:** Test BusinessRuleException — MUST return HTTP 422, not 500
> - Model `LeaveBalance` (bukan `LeaveQuota`) — semua test harus referensi `LeaveBalance`

---

## 1. TESTING PYRAMID

```
                    /\
                   /  \
                  / E2E \         Browser Tests (3 files)
                 /________\
                /          \
               / Integration \   Feature Tests (36 files)
              /______________\
             /                \
            /     Unit Tests   \  Unit Tests (14 files)
           /____________________\
```

**Rasio Ideal:** 70% Unit | 20% Feature | 10% Browser

---

## 2. TEST TOOLS

| Tool | Usage | Version |
|------|-------|---------|
| Pest PHP | Test framework | v4 |
| PHPUnit | Assertion library | v12 |
| Laravel RefreshDatabase | Database reset per test | Built-in |
| Laravel Fake | Notification/Queue/Mail faking | Built-in |
| Livewire Test | Component testing | v4 |
| Pest Browser | E2E testing with Playwright | v4 |

---

## 3. FEATURE TESTS (36 files)

### 3.1 Attendance Tests (`tests/Feature/Attendance/`)

| File | What It Tests |
|------|--------------|
| `ClockInTest.php` | - Employee can clock in with valid face + GPS<br>- Cannot clock in twice in same day<br>- Face recognition failure blocks clock in<br>- GPS outside geofence blocks clock in<br>- Mocked GPS detected and blocked<br>- Clock in creates attendance record |
| `ClockOutTest.php` | - Employee can clock out after clock in<br>- Cannot clock out without clock in<br>- Face recognition on clock out<br>- GPS validation on clock out |
| `GeofenceValidationTest.php` | - Haversine calculation correct<br>- Within radius allowed<br>- Outside radius blocked<br>- Per-branch radius respected<br>- WFA geofence works |
| `AntiFakeGPSTest.php` | - `is_mocked` flag from client detected<br>- Low accuracy GPS flagged<br>- Device fingerprint tracked<br>- Repeated fake GPS blocks account |
| `AttendanceHistoryTest.php` | - Employee can view own history<br>- Filter by date range works<br>- Monthly summary correct<br>- Export to PDF works |

### 3.2 Leave Tests (`tests/Feature/Leave/`)

| File | What It Tests |
|------|--------------|
| `LeaveRequestTest.php` | - Employee can submit leave request<br>- Validation: dates, quota, reason<br>- Proof file upload (optional)<br>- Cannot submit overlapping dates<br>- Request creates approval chain |
| `LeaveBalanceTest.php` | - Balance initialized correctly on hire<br>- Balance deducted on approved leave (after L2, not on submit — ERR-002)<br>- Balance restored on rejected/cancelled leave<br>- Balance does not go negative<br>- Annual balance reset works |
| `LeaveApprovalTest.php` | - Approver can approve leave<br>- Approver can reject leave with reason<br>- Approval status updates correctly<br>- Notification sent to requester |
| `ProbationLeaveBlockTest.php` | - Employee on probation cannot take annual leave<br>- Sick leave allowed during probation<br>- Error message clear |

### 3.3 Payroll Tests (`tests/Feature/Payroll/`)

| File | What It Tests |
|------|--------------|
| `PayrollGenerationTest.php` | - Generate payroll for single employee<br>- Generate bulk payroll via queue<br>- Payroll items created correctly<br>- Duplicate period prevented |
| `PayrollCalculationTest.php` | - Basic salary correct<br>- Allowances calculated<br>- Deductions calculated<br>- Net salary = gross - deductions<br>- Overtime pay included |
| `PayrollLockTest.php` | - Payroll can be locked<br>- Locked payroll cannot be edited<br>- Lock timestamp and user recorded<br>- Only authorized user can lock |
| `BPJSAndTaxTest.php` | - BPJS Kesehatan 1% calculated<br>- BPJS Ketenagakerjaan correct rates<br>- PPh21 calculated correctly<br>- Intern exempt from BPJS and tax |
| `InternExemptTest.php` | - Intern type: no BPJS<br>- Intern type: no PPh21<br>- Intern type: no leave quota<br>- Intern salary calculated differently |

### 3.4 Loan Tests (`tests/Feature/Loan/`)

| File | What It Tests |
|------|--------------|
| `LoanRequestTest.php` | - Employee can submit loan request<br>- Monthly installment calculated<br>- Approval workflow triggered<br>- Max loan amount validation |
| `LoanInstallmentTest.php` | - Installment deducted from payroll<br>- Installment count tracked<br>- Loan settled when paid off<br>- Manual payment recording |

### 3.5 Reimbursement Tests (`tests/Feature/Reimbursement/`)

| File | What It Tests |
|------|--------------|
| `ReimbursementRequestTest.php` | - Employee can submit reimbursement<br>- Receipt file required<br>- Approval workflow triggered<br>- Amount included in payroll |

### 3.6 Approval Tests (`tests/Feature/Approval/`)

| File | What It Tests |
|------|--------------|
| `MultiLevelApprovalTest.php` | - Approval chain created correctly<br>- Level 1 must approve before Level 2<br>- All levels required for full approval<br>- Rejection at any level stops process |
| `ApprovalReassignmentTest.php` | - Approvals reassigned when HRD resigns<br>- Subordinates reassigned to new manager<br>- No orphaned approvals |

### 3.7 Employee Tests (`tests/Feature/Employee/`)

| File | What It Tests |
|------|--------------|
| `EmployeeCRUDTest.php` | - Create employee with all fields<br>- Update employee data<br>- Employee number auto-generated<br>- Delete employee (soft delete) |
| `EmployeeNumberGenerationTest.php` | - Format: EMP-YYYYMM-NNN<br>- Sequential numbering per month<br>- No duplicate numbers<br>- Reset each month |
| `ResignationTest.php` | - Employee can submit resignation<br>- HRD can approve/reject<br>- Resignation status updates<br>- Resign date recorded |
| `HandoverTest.php` | - Handover items created on resignation<br>- Each category tracked<br>- Handover completion required<br>- Subordinates reassigned |
| `ProbationTest.php` | - Probation end date set correctly<br>- Probation status updates<br>- No leave allowed during probation<br>- Probation extension possible |

### 3.8 Face Recognition Tests (`tests/Feature/FaceRecognition/`)

| File | What It Tests |
|------|--------------|
| `FaceEmbeddingTest.php` | - Face embedding generated from photo<br>- Embedding stored in database<br>- 128D vector format correct |
| `FaceSimilarityTest.php` | - Similarity score calculated<br>- Threshold comparison works<br>- Match threshold 0.85 correct<br>- Non-match below threshold |

### 3.9 Knowledge Base Tests (`tests/Feature/KnowledgeBase/`)

| File | What It Tests |
|------|--------------|
| `KnowledgeBaseCRUDTest.php` | - Create article<br>- Upload PDF file<br>- Edit article<br>- Delete article (soft delete) |
| `RagQueryTest.php` | - Query returns relevant results<br>- PDF chunked correctly<br>- Embedding generated<br>- Gemini response includes source |

### 3.10 RBAC Tests (`tests/Feature/RBAC/`)

| File | What It Tests |
|------|--------------|
| `RolePermissionTest.php` | - Roles created correctly<br>- Permissions assigned to roles<br>- Users can have multiple roles<br>- Permission check works |
| `MiddlewareTest.php` | - Role middleware blocks unauthorized<br>- Permission middleware blocks unauthorized<br>- Redirect to 403 page |

### 3.11 Device Tests (`tests/Feature/Device/`)

| File | What It Tests |
|------|--------------|
| `DeviceDetectionTest.php` | - Mobile device detected correctly<br>- Desktop device detected correctly<br>- Browser detected<br>- OS detected |
| `DeviceVerificationTest.php` | - Device registration works<br>- Verification required<br>- Verified device allows clock in |

### 3.12 Notification Tests (`tests/Feature/Notification/`)

| File | What It Tests |
|------|--------------|
| `NotificationTest.php` | - Notification sent on leave request<br>- Notification sent on approval<br>- Notification sent on payroll<br>- Mark as read works |

### 3.13 Security Tests (`tests/Feature/Security/`)

| File | What It Tests |
|------|--------------|
| `ForcePasswordChangeTest.php` | - First login requires password change<br>- Cannot skip password change<br>- Password change works |
| `CipherSweetEncryptionTest.php` | - NIK encrypted in database<br>- Phone encrypted<br>- NPWP encrypted<br>- Blind index searchable |
| `GoogleOAuthTest.php` | - Google login works<br>- Link Google to existing account<br>- Email verified via Google |

---

## 4. UNIT TESTS (14 files)

### 4.1 Enum Tests (`tests/Unit/Enums/`)

| File | What It Tests |
|------|--------------|
| `EmploymentTypeTest.php` | - Enum values correct<br>- Labels correct<br>- Helper methods work |
| `ApprovalStatusTest.php` | - Enum values correct<br>- Status flow logic |

### 4.2 Service Tests (`tests/Unit/Services/`)

| File | What It Tests |
|------|--------------|
| `AttendanceServiceTest.php` | - clockIn() creates record<br>- clockOut() updates record<br>- validateGeofence() correct<br>- calculateLateMinutes() correct |
| `PayrollCalculatorServiceTest.php` | - calculateBasicSalary() correct<br>- calculateBPJS() correct rates<br>- calculatePPh21() correct<br>- calculateNetSalary() correct |
| `GeofenceServiceTest.php` | - Haversine formula correct<br>- isWithinRadius() correct<br>- Edge cases handled |
| `LeaveServiceTest.php` | - requestLeave() validates<br>- validateLeaveBalance() correct (validates on submit, deducts after L2 approval — ERR-002)<br>- deductLeaveBalance() correct<br>- isProbationBlocked() correct (EmploymentType has 4 values: permanent, contract, probation, intern) |
| `ApprovalServiceTest.php` | - createApprovalChain() correct<br>- getNextApprover() correct<br>- approve() updates status |

### 4.3 Model Tests (`tests/Unit/Models/`)

| File | What It Tests |
|------|--------------|
| `EmployeeTest.php` | - Relationships work<br>- Scopes work<br>- Accessors/mutators work<br>- Employee number generated |
| `AttendanceTest.php` | - Relationships work<br>- Status calculated correctly<br>- Late minutes calculated |
| `PayrollTest.php` | - Relationships work<br>- Lock status works<br>- Net salary calculated |
| `LeaveTest.php` | - Relationships work<br>- Total days calculated<br>- Overlapping dates detected |

### 4.4 Observer Tests (`tests/Unit/Observers/`)

| File | What It Tests |
|------|--------------|
| `EmployeeObserverTest.php` | - creating: employee number generated<br>- created: default quota assigned<br>- updating: resignation triggers handover |
| `AttendanceObserverTest.php` | - creating: geofence validated<br>- created: cache updated |
| `LeaveObserverTest.php` | - creating: quota validated<br>- updated: quota deducted if approved |

---

## 5. BROWSER TESTS (3 files - Opsional)

| File | What It Tests |
|------|--------------|
| `ClockInBrowserTest.php` | - Full clock in flow in browser<br>- Face capture UI works<br>- GPS permission requested |
| `LeaveRequestBrowserTest.php` | - Full leave request flow<br>- Form validation shown<br>- Success message displayed |
| `PayrollViewBrowserTest.php` | - Payroll slip renders correctly<br>- PDF download works |

---

## 6. TEST CONVENTIONS

### 6.1 Naming
- Feature: `test_employee_can_clock_in()`
- Unit: `test_haversine_calculates_correct_distance()`
- Use `it()` syntax for Pest: `it('calculates correct distance', function () { })`

### 6.2 Structure
```php
it('allows clock in with valid face and gps', function () {
    // Arrange
    $employee = Employee::factory()->create();
    $this->actingAs($employee->user);

    // Act
    $response = $this->post(route('attendance.clock-in'), [
        'lat' => -6.2088,
        'long' => 106.8456,
        'face_embedding' => '...',
    ]);

    // Assert
    $response->assertRedirect();
    expect(Attendance::count())->toBe(1);
});
```

### 6.3 Factories
- Always use factories for test data
- Check if factory has custom states before manually setting
- Use `fake()` for random data

### 6.4 Database
- Use `RefreshDatabase` trait for feature tests
- Do NOT use `DatabaseTransactions` for queue tests

### 6.5 Mocking
- Fake notifications: `Notification::fake()`
- Fake queues: `Queue::fake()`
- Fake mail: `Mail::fake()`
- Fake storage: `Storage::fake()`

---

## 7. RUNNING TESTS

```bash
# Run all tests
php artisan test --compact

# Run specific file
php artisan test tests/Feature/Attendance/ClockInTest.php

# Run with filter
php artisan test --filter=clock_in

# Run with coverage
php artisan test --coverage

# Run only feature tests
php artisan test tests/Feature

# Run only unit tests
php artisan test tests/Unit
```

---

## 8. COVERAGE TARGETS

| Category | Target |
|----------|--------|
| Overall | > 80% |
| Services | > 90% |
| Models | > 85% |
| Observers | > 80% |
| Controllers/Livewire | > 75% |
| Policies | > 90% |

---

## 9. DATABASE TESTING STRATEGY

### 9.1 Dual Environment

Aplikasi HRConnect berjalan di **PostgreSQL** (production) dengan ekstensi `pgvector`, `pg_trgm`, dan `pgcrypto`, tapi test suite (phpunit.xml) menggunakan **SQLite in-memory** untuk kecepatan.

| Environment | Database | Tujuan |
|------------|----------|--------|
| Production/Development | PostgreSQL + pgvector | Data persist, vector search, full feature |
| Testing (default) | SQLite `:memory:` | Unit & Feature test cepat (< 2 detik) |
| Testing (opsional) | PostgreSQL `hrconnect_testing` | Integration test untuk vector similarity search |

**Default:** `php artisan test` menggunakan SQLite. Cukup untuk 95% test case.

### 9.2 Defensive Migration Pattern

Semua migration yang menggunakan fitur PostgreSQL-specific **WAJIB** di-guard dengan `DB::getDriverName()`:

```php
// ✅ BENAR — guard dengan driver check
if (DB::getDriverName() === 'pgsql') {
    Schema::ensureVectorExtensionExists();
}

// ❌ SALAH — akan crash di SQLite
Schema::ensureVectorExtensionExists();
```

```php
// ✅ BENAR — fallback kolom vector
if (DB::getDriverName() === 'pgsql') {
    $table->vector('embedding', dimensions: 1536)->nullable();
} else {
    $table->text('embedding')->nullable();
}
```

```php
// ✅ BENAR — guard HNSW index
if (DB::getDriverName() === 'pgsql') {
    DB::statement('CREATE INDEX kb_embedding_hnsw_idx ON knowledge_bases USING hnsw (embedding vector_cosine_ops)');
}
```

### 9.3 PostgreSQL-Specific Features di HRConnect

| Feature | File Migration | Guard Status |
|---------|---------------|--------------|
| `pgvector` extension | create_users_table | ✅ Guarded (CAT-018) |
| `pg_trgm` extension | create_users_table | ✅ Guarded |
| `pgcrypto` extension | create_users_table | ✅ Guarded |
| `vector(128)` column | create_employees_table | ✅ Guarded (CAT-018) |
| `vector(1536)` column | create_knowledge_bases_table | ✅ Guarded (CAT-018) |
| HNSW index | create_knowledge_bases_table | ✅ Guarded (CAT-018) |
| `jsonb` column | create_knowledge_bases_table | ⚠️ Not guarded (SQLite has json) |

### 9.4 UniqueConstraintViolationException — Dilarang Hardcode Error Code

**DILARANG** menangkap `QueryException` dan mengecek hardcoded error code seperti `$e->getCode() === '23505'`. Error code `23505` hanya berlaku di PostgreSQL; SQLite menggunakan code berbeda (`19` / `23000`).

**WAJIB** menggunakan `Illuminate\Database\UniqueConstraintViolationException` yang otomatis menangkap unique constraint violation di semua driver:

```php
// ❌ SALAH — PostgreSQL-only
} catch (QueryException $e) {
    if ($e->getCode() === '23505') { ... }
}

// ✅ BENAR — Cross-database compatible
} catch (UniqueConstraintViolationException $e) {
    throw new AlreadyClockedInException('...');
} catch (QueryException $e) {
    throw $e;
}
```

### 9.5 Vector/AI Test — Mocking Approach

Fitur AI (face recognition similarity search, knowledge base RAG) **tidak bisa di-test secara real** di environment SQLite karena SQLite tidak mendukung `vector` column type.

Pendekatan testing:

| Test Type | Pendekatan | Environment |
|-----------|-----------|-------------|
| Controller/Service logic | Mock `FaceRecognitionService` dan `RagService` | SQLite |
| Model creation & fillable | `RefreshDatabase` — kolom `embedding` jadi `text` di SQLite | SQLite |
| Vector similarity search | Skip atau gunakan PostgreSQL testing DB (`hrconnect_testing`) | PostgreSQL |
| Face embedding format | Unit test serialization/deserialization dengan data dummy | SQLite |

Contoh mock di Pest:
```php
it('allows clock in with valid face verification', function () {
    $faceService = Mockery::mock(FaceRecognitionService::class);
    $faceService->shouldReceive('verifyFace')
        ->once()
        ->andReturn(['similarity_percentage' => 95.5]);
    
    // ... test logic
});
```

---

*Dokumen ini harus diikuti saat menulis test.*
*Terakhir diupdate: 2026-05-20 — Added §9 Database Testing Strategy (CAT-018, CAT-019)*
