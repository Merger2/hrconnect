# HRConnect - Error Handling Strategy

> **Dokumen ini berisi strategi penanganan error untuk semua skenario kegagalan di HRConnect.**

---

## 1. FACE RECOGNITION FAILURE

### Skenario 1: Kamera Tidak Tersedia / Permission Ditolak
**Trigger:** Browser memblokir akses kamera, HP tidak punya kamera depan, user menolak permission

**Fallback: Tier 2 - GPS + PIN**
```
1. Sistem detect kamera gagal > 3 detik
2. Tampilkan pesan: "Kamera tidak tersedia. Menggunakan verifikasi PIN."
3. User input 6-digit PIN (dikirim ke email/notifikasi sebelumnya)
4. Validasi PIN di server
5. Clock in berhasil dengan status: face_bypassed
6. Log tercatat: { bypass_reason: "camera_unavailable", method: "pin" }
```

**Kode Implementasi:**
```php
// app/Services/AttendanceService.php
public function clockIn(Employee $employee, array $data): Attendance
{
    // Tier 1: Face Recognition
    if ($this->hasValidFace($data)) {
        return $this->clockInWithFace($employee, $data);
    }

    // Tier 2: GPS + PIN
    if ($this->hasValidPin($data)) {
        $this->logBypass($employee, 'pin_verified');
        return $this->clockInWithGPS($employee, $data);
    }

    // Tier 3: Manual Request
    return $this->createManualClockInRequest($employee, $data);
}
```

### Skenario 2: Face Similarity di Bawah Threshold
**Trigger:** Similarity score < 0.85 (wajah tidak cocok dengan template)

**Handling:**
```
1. Retry otomatis 2x (total 3 attempts)
2. Jika masih gagal → fallback ke PIN verification
3. Jika PIN juga gagal → manual request ke supervisor
4. Log: { face_attempts: 3, similarity_scores: [0.72, 0.68, 0.71] }
```

### Skenario 3: Pencahayaan Buruk
**Trigger:** Kamera bisa akses tapi gambar terlalu gelap/terang

**Handling:**
```
1. Client-side detect brightness level via canvas
2. Jika terlalu gelap → tampilkan warning: "Pencahayaan kurang. Pindah ke area terang."
3. Allow retry dengan brightness feedback
4. Jika tetap gagal → fallback ke PIN
```

### Skenario 4: Karyawan Belum Register Wajah (Face Enrollment)
**Trigger:** Karyawan baru belum melakukan registrasi wajah, tapi coba clock in

> **⚠️ STATUS IMPLEMENTASI (B12 — 2026-05-31):** Tier-fallback Face → PIN di bawah ini **belum diimplementasikan** di `AttendanceService::clockIn()`. Saat ini `FaceNotRegisteredException` dilempar tanpa fallback ke PIN — tracking di `AGENTS.md` Known Critical Bugs B12 dan `planning/task.md` §0.2 / §2.34. Fix-nya: catch exception di awal flow, cek `$data['pin']`, dan jalankan `clockInWithGPS()` jika PIN valid.

**Handling:**
```
1. BLOCK clock in via face recognition
2. Error: "Wajah belum terdaftar. Hubungi HRD untuk registrasi wajah."
3. Tombol clock in disabled, tampilkan info box
4. Auto-redirect ke halaman face registration jika ada
5. Fallback: bisa clock in via PIN verification (sementara)
6. Log tercatat: { bypass_reason: "face_not_enrolled", method: "pin" }
```

**Kode Implementasi:**
```php
// app/Services/AttendanceService.php
public function clockIn(Employee $employee, array $data): Attendance
{
    // Cek apakah wajah sudah terdaftar
    if (!$employee->face_embedding) {
        if ($this->hasValidPin($data)) {
            $this->logBypass($employee, 'face_not_enrolled');
            return $this->clockInWithGPS($employee, $data);
        }

        throw new \App\Exceptions\FaceNotRegisteredException();
    }

    // Lanjut face recognition seperti biasa...
}
```

---

## 2. GPS / GEOLOCATION FAILURE

### Skenario 1: GPS Accuracy Buruk (> 100m) tapi Tidak Mocked
**Trigger:** Sinyal GPS lemah, accuracy tinggi (misal: dalam gedung)

**Handling:**
```
1. Accept clock-in tapi flag sebagai "low_accuracy"
2. Warning: "Sinyal GPS lemah. Absensi tercatat untuk review supervisor."
3. Supervisor notification otomatis
4. Rule: < 3x/bulan → auto-approved, > 3x → butuh approval manual
5. Log: { gps_accuracy: 150.5, flagged: true }
```

### Skenario 2: GPS Mocked Terdeteksi
**Trigger:** `is_mocked = true` dari browser Geolocation API

**Handling:**
```
1. BLOCK clock-in
2. Error: "Lokasi tidak valid. Pastikan GPS asli aktif."
3. Log security event
4. Jika 3x dalam seminggu → auto-flag akun untuk review HRD
5. NO fallback untuk mocked GPS (security risk)
```

### Skenario 3: GPS Permission Ditolak
**Trigger:** User menolak permission lokasi

**Handling:**
```
1. Error: "Akses lokasi diperlukan untuk absensi."
2. Tidak ada fallback tanpa GPS
3. User harus enable location permission
4. Fallback ke manual request ke supervisor
```

---

## 3. RAG / KNOWLEDGE BASE API FAILURE

### Skenario 1: Gemini Embedding API Down (PDF Embedding)
**Trigger:** Gemini Embedding API (text-embedding-004) timeout / rate limited / down saat generate embeddings

**Handling:**
```
1. Queue job (ProcessKnowledgeBaseEmbedding) gagal
2. Auto-retry 3x dengan exponential backoff (30s, 60s, 120s)
3. Setelah 3x gagal → status: "pending_retry"
4. Admin notification via email
5. KB tetap accessible via text search (pg_trgm) tanpa embeddings
6. Admin bisa trigger re-index manual saat API pulih
```

**Kode Implementasi:**
```php
// app/Jobs/ProcessKnowledgeBaseEmbedding.php
public $tries = 3;
public $backoff = [30, 60, 120];

public function handle(): void
{
    try {
        $this->generateEmbeddings();
    } catch (Exception $e) {
        if ($this->attempts() >= 3) {
            $this->knowledgeBase->update(['status' => 'pending_retry']);
            Admin::each(fn($admin) => $admin->notify(
                new KnowledgeBaseIndexFailedNotification($this->knowledgeBase)
            ));
        }
        throw $e; // Re-throw untuk retry
    }
}
```

### Skenario 2: Gemini API Timeout (RAG Chat)
**Trigger:** Gemini API tidak merespons dalam 10 detik

**Handling:**
```
1. Timeout setelah 10 detik
2. Return pesan: "AI sedang sibuk. Menggunakan pencarian teks."
3. Fallback: pg_trgm text search (tanpa AI)
4. Return hasil text search sebagai jawaban
5. Log error untuk monitoring
6. UI: Show "Mode offline" badge
```

**Kode Implementasi:**
```php
// app/Livewire/Admin/KnowledgeBase/Chat.php
public function sendMessage(): void
{
    try {
        $response = Http::timeout(10)->post($this->geminiUrl, [...]);
        $this->addAIMessage($response->json('text'));
    } catch (ConnectException|RequestTimeoutException $e) {
        // Fallback to text search
        $results = KnowledgeBase::whereRaw(
            "content ILIKE ?", ["%{$this->message}%"]
        )->limit(3)->get();

        $this->addAIMessage(
            "AI sedang maintenance. Berikut dokumen terkait:\n\n" .
            $results->map(fn($r) => "• {$r->title}")->join("\n")
        );

        Log::warning('Gemini API timeout, using text search fallback');
    }
}
```

### Skenario 3: Demo Mock Mode
**Trigger:** `RAG_MOCK_MODE=true` di .env saat presentasi

**Handling:**
```
1. Bypass Gemini API completely
2. Return pre-canned responses dari local cache
3. UI tetap sama, user tidak tahu ini mock
4. Pre-load 2-3 sample Q&A sebelum demo
```

**Kode Implementasi:**
```php
// config/hrconnect.php
'rag_mock_mode' => env('RAG_MOCK_MODE', false),

// app/Services/KnowledgeBaseService.php
public function query(string $question): string
{
    if (config('hrconnect.rag_mock_mode')) {
        return $this->getMockResponse($question);
    }
    return $this->callGeminiAPI($question);
}
```

---

## 4. QUEUE JOB FAILURE

### Skenario: Job Gagal Setelah Semua Retry
**Trigger:** Job gagal 3x (max tries)

**Handling:**
```
1. Job masuk ke `failed_jobs` table
2. Admin notification via email
3. Admin bisa retry dari Horizon/dashboard
4. Data integrity terjaga (job tidak partial)
5. Log lengkap dengan stack trace
```

**Affected Jobs:**
- GenerateFaceEmbedding
- ProcessPayrollGeneration
- SendNotificationJob
- ProcessKnowledgeBaseEmbedding
- ProcessEmployeeHandover

---

## 5. PAYROLL CALCULATION ERROR

### Skenario: Bug Ditemukan Setelah Payroll Published
**Trigger:** Karyawan komplain gaji tidak sesuai, ternyata ada bug di kalkulasi

**Handling: Adjustment-Only Approach (LOCKED PERMANEN)**
```
RULE: Payroll published TIDAK BISA di-edit, di-unpublish, atau dihapus. PERMANEN.

Jika ada error:
1. Buat adjustment record di payroll_adjustments table
2. Adjustment ditambahkan ke payroll bulan berikutnya
3. Contoh: Kurang bayar Rp 500K di Mei → tambah Rp 500K di Juni
4. Contoh: Lebih bayar Rp 200K di Mei → potong Rp 200K di Juni
5. Audit trail lengkap di activity_log
6. Notification ke karyawan: "Ada penyesuaian gaji bulan ini"

TIDAK ADA emergency unpublish. TIDAK ADA rollback.
```

> **ERRATA (C2):** Payroll menggunakan `SoftDeletes`. Saat regenerate, `$existingPayroll->delete()` hanya set `deleted_at` — record tetap ada dan menyebabkan **unique constraint violation** (`employee_id + period`). Gunakan `$existingPayroll->forceDelete()` sebelum create payroll baru untuk periode yang sama.

**Kode Implementasi:**
```php
// app/Models/Payroll.php
public function adjust(int $amount, string $reason, ?User $createdBy = null): PayrollAdjustment
{
    if ($this->is_published) {
        // Payroll sudah published → adjustment untuk bulan berikutnya
        return PayrollAdjustment::create([
            'payroll_id' => $this->id,
            'amount' => $amount,
            'reason' => $reason,
            'created_by' => $createdBy?->id,  // nullable — system-generated adjustments
            'applied_to_period' => $this->period->copy()->addMonth(),
        ]);
    }

    // Belum published → bisa edit normal
    $this->update([
        'basic_salary' => $this->basic_salary + $amount,
    ]);

    return PayrollAdjustment::create([
        'payroll_id' => $this->id,
        'amount' => $amount,
        'reason' => $reason,
        'created_by' => $createdBy?->id,
        'applied_to_period' => $this->period,
    ]);
}

// TIDAK ADA method unpublish() atau canEmergencyUnpublish()
// Payroll yang sudah published = LOCKED PERMANEN
```

> **ERRATA (§4.1h):** `payroll_adjustments.created_by` harus **nullable**, karena system-generated adjustment tidak punya user. Migration: `$table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()`. ERD sudah diupdate.

**Kode Migration untuk PayrollAdjustments:**
```php
// database/migrations/..._create_payroll_adjustments_table.php
Schema::create('payroll_adjustments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('payroll_id')->constrained()->restrictOnDelete();
    $table->decimal('amount', 15, 2); // Positif = tambah, Negatif = potong (§2.4)
    $table->text('reason');
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); // §4.1h
    $table->date('applied_to_period');
    $table->timestamps();
});
```

---

## 6. EMAIL NOTIFICATION FAILURE

### Skenario: Mail Server Down
**Trigger:** SMTP connection gagal

**Handling:**
```
1. Job (SendNotificationJob) gagal → auto-retry 3x
2. Setelah gagal semua → simpan ke database notifications
3. User bisa lihat notifikasi saat login (in-app notification bell)
4. Admin notification via log error
5. Mail queue akan diproses ulang saat server pulih
```

---

## 7. DATABASE CONNECTION FAILURE

### Skenario: PostgreSQL Down
**Trigger:** Database connection timeout / refused

**Handling:**
```
1. Laravel automatically throws QueryException
2. Custom error handler catch exception
3. Show maintenance page: "Sistem sedang maintenance. Coba beberapa saat lagi."
4. Log error untuk admin
5. Auto-retry untuk read-only queries (cache fallback)
```

### Skenario: Neon PostgreSQL Cold Start
**Trigger:** Database idle beberapa saat, Neon serverless "sleep" → koneksi pertama 2-3 detik delay

**Handling:**
```
1. Set DB_CONNECT_TIMEOUT=10 di .env (lebih panjang dari default)
2. Tambahkan connect_timeout di database.php config
3. Warm-up script: ping health endpoint setiap 5 menit via cron
4. UI: Tampilkan skeleton loader saat loading pertama
5. Pre-connect saat login → user tidak notice delay saat navigasi
6. Sebelum demo: jalankan warm-up script minimal 30 menit
```

**Kode Implementasi:**
```env
# .env
DB_CONNECT_TIMEOUT=10
```

```php
// config/database.php
'pgsql' => [
    'driver' => 'pgsql',
    'host' => env('DB_HOST'),
    'port' => env('DB_PORT'),
    'database' => env('DB_DATABASE'),
    'username' => env('DB_USERNAME'),
    'password' => env('DB_PASSWORD'),
    'charset' => 'utf8',
    'prefix' => '',
    'prefix_indexes' => true,
    'search_path' => 'public',
    'sslmode' => 'require',
    'options' => [
        PDO::ATTR_TIMEOUT => 10, // Connect timeout
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ],
],
```

```bash
# scripts/warmup.sh - dijalankan via cron setiap 5 menit
#!/bin/bash
while true; do
    curl -s https://hrconnect.company.com/health > /dev/null
    sleep 300
done
```

```bash
# crontab -e
*/5 * * * * curl -s https://hrconnect.company.com/health > /dev/null 2>&1
```

---

## 8. FILE UPLOAD FAILURE

### Skenario: File Terlalu Besar / Tipe Tidak Valid
**Trigger:** User upload file > limit atau tipe salah

**Handling:**
```
Face Photo:
- Max 2MB
- Hanya jpeg, png, jpg
- Error: "Foto harus berformat JPG/PNG dan maksimal 2MB"

KnowledgeBase PDF:
- Max 10MB
- Hanya PDF
- Error: "File harus berformat PDF dan maksimal 10MB"

Receipt/Proof:
- Max 5MB
- jpeg, png, jpg, pdf
- Error: "File harus berformat JPG/PNG/PDF dan maksimal 5MB"
```

---

## 9. CONCURRENT SESSION LIMIT

### Skenario: User Login dari > 5 Device
**Trigger:** User sudah login di 5 device, coba login di device ke-6

**Handling:**
```
1. Error: "Anda sudah login di 5 perangkat. Silakan logout dari perangkat lain."
2. Tampilkan list device aktif (nama device, last active, location)
3. User bisa pilih device mana yang di-logout
4. Setelah logout 1 device → bisa login di device baru
```

---

## 10. GOOGLE OAUTH TOKEN EXPIRED

> **TIDAK DIIMPLEMENTASI —** Google OAuth tidak digunakan di HRConnect. Verifikasi email via Gmail SMTP sebagai gantinya. Lihat error handling SMTP di §6.

    return $next($request);
}
```

---

## 11. ERROR LOGGING & MONITORING

### 11.0 Custom Exception HTTP Code Reference
> **ERRATA (SEC-5):** Semua custom exception harus return HTTP code yang benar. `BusinessRuleException` saat ini extends `Exception` (return 500). Harus diubah ke `HttpException` dengan code 422.

> **ERRATA (SEC-5 update):** `FaceNotRegisteredException` sebelumnya 400 → **422** (business rule violation, bukan bad request). `NotClockedInException` sebelumnya 400 → **409** (state conflict, konsisten dengan `AlreadyClockedInException` yang return 409). Lihat juga security-config.md §9.1 dan task.md §1.5.

| Exception Class | HTTP Code | When Thrown |
|----------------|-----------|-------------|
| `BusinessRuleException` | **422** | Business rule violations: leave quota exceeded, payroll locked, WFA note too short, etc. |
| `FaceNotRegisteredException` | **422** | Employee has no face embedding (business rule, not bad request) |
| `NotClockedInException` | **409** | Clock-out without clock-in (state conflict, consistent with `AlreadyClockedInException`) |
| `ModelNotFoundException` | 404 | Resource not found |
| `AuthenticationException` | 401 | Invalid/missing token |
| `AuthorizationException` | 403 | Insufficient permissions |
| `ValidationException` | 422 | Form request validation |

> **ERRATA (C1):** `Approval` model cast `level` ke `ApprovalLevel` enum. Perbandingan `$approval->level === 1` **SELALUS false** (PHP strict comparison: enum !== int). Gunakan `$approval->level->value === 1` atau `$approval->level === ApprovalLevel::L1_SUPERVISOR`. Bug ini ada di `ApprovalService::processAutoApprovals()` dan menyebabkan `APPROVED_L1` status tidak pernah tercapai.

### 11.1 Sanctum API Auth Failure
> **ERRATA (SEC-3):** `laravel/sanctum` belum terinstall. Saat ini, panggilan ke `/api/v1/*` akan return 401 (unauthenticated) karena tidak ada token validation. Install Sanctum sebelum API bisa digunakan (task.md §1.3).

### Log Levels
```
DEBUG:   Face similarity scores, GPS accuracy values
INFO:    Successful clock-in/out, leave requests, approvals
WARNING: Failed face attempts, low GPS accuracy, API timeouts
ERROR:   API failures, queue job failures, database errors
CRITICAL: Security events (mocked GPS, multiple failed logins)
```

### Log Channels
```
daily:       Default application log
security:    Security events (GPS spoofing, failed auth)
api:         External API calls (Gemini)
queue:       Queue job processing
audit:       Payroll changes, role changes, approvals
```

### Monitoring Dashboard (Opsional)
```
- Failed jobs count (last 24h)
- API error rate (last 24h)
- Clock-in success rate (today)
- Average face similarity score (today)
- Queue processing time (last hour)
- Database response time (for cold start detection)
```

---

## SUMMARY TABLE

| Skenario | Fallback | Retry | Log Level | User Message |
|----------|----------|-------|-----------|-------------|
| Kamera gagal | PIN verification | 0 | WARNING | "Verifikasi PIN diperlukan" |
| Face similarity rendah | PIN setelah 3x gagal | 3x | WARNING | "Wajah tidak terdeteksi" |
| Face belum terdaftar | PIN sementara | 0 | WARNING (422) | "Wajah belum terdaftar, hubungi HRD" |
| GPS accuracy buruk | Accept + flag | 0 | WARNING | "Sinyal GPS lemah" |
| GPS mocked | BLOCK | 0 | CRITICAL | "Lokasi tidak valid" |
| Gemini Embedding API down | Retry + text search | 3x | ERROR | "AI sedang maintenance" |
| Gemini API timeout | Text search fallback | 0 | ERROR | "AI sedang sibuk" |
| Queue job gagal | DB notification | 3x | ERROR | Admin notified |
| Payroll bug | Adjustment bulan berikut | 0 | CRITICAL | "Ada penyesuaian gaji" |
| Mail server down | In-app notification | 3x | ERROR | Notification di bell |
| Database down | Maintenance page | 0 | CRITICAL | "Sistem maintenance" |
| Neon cold start | Warm-up cron | 0 | WARNING | Skeleton loader (UI) |
| Google OAuth expired | — (tidak dipakai) | — | — | — |

---

*Dokumen ini harus diikuti untuk handling semua error scenario.*
*Terakhir diupdate: 2026-05-31 — G1: Skenario 1 OpenAI→Gemini Embedding API. G17: Status implementasi B12 di Skenario 4.*
