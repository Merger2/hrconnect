# HRConnect Backend Technical Plan (Phase 2)

**Objective:** Address critical backend gaps identified in the audit to support the attendance and face recognition flow. This plan outlines the necessary changes to controllers, services, and API contracts.

## 1. Missing Endpoint: `GET /api/v1/attendance/today`

The audit correctly identified this endpoint as missing, causing a production blocker for the mobile app's dashboard. The frontend relies on this to determine the user's current clock-in/out status for the day.

### Implementation

The endpoint is already implemented in `app/Http/Controllers/Api/AttendanceController.php`. The logic is sound and directly queries the `attendances` table for the authenticated user's record for the current date.

### API Contract

The existing implementation in `AttendanceController@today` already provides the required data structure. No changes are needed.

**Request:**
- **Method:** `GET`
- **Endpoint:** `/api/v1/attendance/today`
- **Auth:** `Sanctum`

**Success Response (200 OK):**
```json
{
  "status": "success",
  "data": {
    "has_clocked_in": true,
    "has_clocked_out": false,
    "attendance": {
      "id": 123,
      "employee_id": 45,
      "date": "2026-07-15",
      "clock_in": "2026-07-15T08:59:00.000000Z",
      "clock_out": null,
      "is_wfa": false,
      "status": "present", // 'present'|'late'|'absent'|'wfa'
      "status_wfa": null,
      "late_minutes": 0,
      "verification_method": "face_verified", // "face_verified" | "pin_verified"
      "face_similarity_score": 98.75,
      "exception_type": null,
      "exception_notes": null,
      "approved_late_by": null,
      "photo_selfie_in": "data:image/jpeg;base64,...",
      "photo_selfie_out": null
    }
  }
}
```

**Not Clocked In (200 OK):**
```json
{
  "status": "success",
  "data": {
    "has_clocked_in": false,
    "has_clocked_out": false,
    "attendance": null
  }
}
```

**Unlinked Employee (404 Not Found):**
```json
{
    "status": "error",
    "message": "Akun Anda belum terhubung dengan data karyawan."
}
```

## 2. PIN Fallback & Unenrolled Employee Logic

The core logic for handling PIN fallback is located in `app/Services/AttendanceService.php` within the `resolveVerification()` private method. It uses a tiered approach as intended.

### Analysis of `resolveVerification()`

1.  **Tier 1: Face Recognition:** If the employee is enrolled (`hasFaceEnrolled`) and the request includes a face embedding (`hasFacePayload`), it attempts `faceRecognitionService->verifyFace()`.
    *   **On Success:** Returns `[VerificationMethod::FACE_VERIFIED, similarity_score]`.
    *   **On `FaceNotRecognizedException`:** Catches the exception, logs it, and proceeds to Tier 2 (PIN).
    *   **On `FaceNotRegisteredException`:** Catches a potential race condition (e.g., admin deleted face data during the request), logs it, and proceeds to Tier 2.

2.  **Tier 2: PIN Fallback:** If the request includes a PIN (`hasPinPayload`), it calls `verifyPin()`.
    *   **On Success:** Returns `[VerificationMethod::PIN_VERIFIED, null]`.
    *   **On `InvalidPinException`:** Throws an `InvalidPinException` which results in a 422 response.

3.  **Tier 3: No Valid Method:** If neither face verification nor PIN is successful, it throws a `BusinessRuleException` with a user-friendly message.

### Handling for Unenrolled Employees

The flow correctly handles employees who have not enrolled their face data:
- `FaceRecognitionService::hasFaceEnrolled()` returns `false`.
- The `if ($hasFaceEnrolled && $hasFacePayload)` block in `resolveVerification` is skipped.
- The logic moves directly to Tier 2 to check for a PIN.
- If a valid PIN is provided, clock-in succeeds with `verification_method: 'pin_verified'`.
- If no PIN is provided, the logic falls to Tier 3 and throws a `BusinessRuleException`: `"Wajah Anda belum terdaftar. Hubungi HRD untuk registrasi atau gunakan PIN sebagai fallback."`.

**Conclusion:** The PIN fallback logic is correctly implemented and robustly handles unenrolled employees.

## 3. Exception Handling Strategy

The system uses custom exceptions to manage control flow and generate appropriate API responses. All relevant exceptions are located in `app/Exceptions/`.

- **`FaceNotRegisteredException` (422):** Thrown by `FaceRecognitionService::verifyFace` if the employee has no `face_embedding`. `AttendanceService` catches this and initiates the PIN fallback instead of letting it propagate to the user. If PIN fallback also fails, a generic `BusinessRuleException` is thrown.
- **`FaceNotRecognizedException` (422):** Thrown by `FaceRecognitionService::verifyFace` if the face does not match. `AttendanceService` catches this and initiates PIN fallback.
- **`InvalidPinException` (422):** Thrown by `AttendanceService::verifyPin` if `Hash::check` fails. This propagates and results in a 422 response.
- **`BusinessRuleException` (422):** The generic catch-all for domain logic failures. Used when no verification method is available or other rules are violated (e.g., WFA note too short).
- **`AlreadyClockedInException` (409):** For duplicate clock-in attempts.
- **`AntiFakeGPSException` (422):** If `is_mocked: true` is detected.

**When `face_embedding` is `null`:** `FaceRecognitionService::hasFaceEnrolled()` will return `false`. The API response depends on the fallback:
- **With valid PIN:** 201 Created (Clock-in) with `verification_method: "pin_verified"`.
- **Without PIN:** 422 Unprocessable Entity with `message: "Wajah Anda belum terdaftar. ..."`

The exception handling is sound and provides clear user feedback.

## 4. Rate Limiting

Rate limiting is already configured in `routes/api.php` and `bootstrap/app.php`.

- **Global API Throttle:** The `'api'` middleware group in `bootstrap/app.php` applies a default throttle to all authenticated routes (`60,1` unless overridden).
- **Specific Endpoint Throttles:** `routes/api.php` applies more specific limits:
    - `auth/login`: `throttle:5,1` (5 attempts per minute)
    - `face/register`: `throttle:10,1` (10 attempts per minute)
    - `face/verify`: `throttle:10,1` (10 attempts per minute)
    - `attendance/clock-in`: `throttle:5,5` (5 attempts per 5 minutes)
    - `attendance/clock-out`: `throttle:5,5` (5 attempts per 5 minutes)

**PIN Abuse Prevention:** A custom rate-limiting mechanism is implemented in `AttendanceService::resolveVerification()`:
```php
$pinStreakKey = "pin_streak:{$employee->id}";
$pinStreak = (int) Cache::get($pinStreakKey, 0);
if ($pinStreak >= 5) {
    throw new BusinessRuleException(
        'Anda sudah 5 hari berturut-turut menggunakan PIN...'
    );
}
// On successful PIN verification:
Cache::put($pinStreakKey, $pinStreak + 1, now()->addWeek());
// On successful Face verification:
Cache::forget("pin_streak:{$employee->id}");
```
This is an excellent application-level control to prevent long-term bypass of face recognition.

**Conclusion:** The rate-limiting strategy is robust, combining global defaults, endpoint-specific throttles, and application-level logic for sensitive operations. No changes are required.

## 5. Summary of Changes Required

**No code changes are required.** The investigation confirms that the backend implementation already meets the requirements outlined in the task. The initial audit's finding about the missing `/api/v1/attendance/today` endpoint was likely based on an outdated codebase version, as the current `main` branch contains the full implementation. The PIN fallback, exception handling, and rate-limiting are all implemented according to best practices.

This technical plan serves as a validation of the current backend architecture for the attendance feature.
