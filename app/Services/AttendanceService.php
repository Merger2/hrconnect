<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\VerificationMethod;
use App\Exceptions\AlreadyClockedInException;
use App\Exceptions\AntiFakeGPSException;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\FaceNotRecognizedException;
use App\Exceptions\FaceNotRegisteredException;
use App\Exceptions\InvalidPinException;
use App\Exceptions\NotClockedInException;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AttendanceService
{
    public function __construct(
        protected GeofenceService $geofenceService,
        protected FaceRecognitionService $faceRecognitionService,
    ) {}

    public function clockIn(Employee $employee, array $data): Attendance
    {
        // Tier 0: Anti-Tuyul GPS
        if (isset($data['is_mocked']) && $data['is_mocked'] == true) {
            throw new AntiFakeGPSException('Peringatan: Aplikasi Fake GPS / Tuyul terdeteksi!');
        }

        if ($employee->hasClockedInToday()) {
            throw new AlreadyClockedInException('Anda sudah melakukan absensi masuk hari ini.');
        }

        $isWfa = $data['is_wfa'] ?? false;

        if ($isWfa) {
            if (empty($data['wfa_note']) || mb_strlen(trim($data['wfa_note'])) < 20) {
                throw new BusinessRuleException('Catatan WFA wajib diisi minimal 20 karakter');
            }
        } else {
            if (! $employee->branch) {
                throw new BusinessRuleException('Data lokasi kerja Anda belum diatur. Hubungi HRD.');
            }
            $this->geofenceService->validateLocation($employee->branch, $data);
        }

        // B12 fix: tiered verification (Face → PIN → Manual) per error-handling-strategy §1.
        // Tangani 3 skenario terpisah:
        //   1. Face embedding tidak ada → fallback PIN
        //   2. Face embedding ada tapi tidak match → fallback PIN
        //   3. Tidak ada face embedding & PIN → throw BusinessRuleException
        [$verificationMethod, $faceSimilarityScore] = $this->resolveVerification($employee, $data);

        try {
            return DB::transaction(function () use ($employee, $data, $isWfa, $verificationMethod, $faceSimilarityScore) {
                if ($employee->hasClockedInToday()) {
                    throw new AlreadyClockedInException('Data absen masuk sudah tercatat.');
                }

                $now = now();
                $lateMinutes = $employee->shift ? $employee->shift->calculateLateMinutes($now) : 0;

                $status = $lateMinutes > 0
                    ? AttendanceStatus::LATE
                    : AttendanceStatus::ON_TIME;

                $attendance = Attendance::create([
                    'employee_id' => $employee->id,
                    'shift_id' => $employee->shift_id,
                    'date' => $now->toDateString(),
                    'clock_in' => $now,
                    'lat_in' => $data['latitude'] ?? null,
                    'long_in' => $data['longitude'] ?? null,
                    'clock_in_is_mocked' => $data['is_mocked'] ?? false,
                    'clock_in_accuracy' => $data['accuracy'] ?? null,
                    'is_wfa' => $isWfa,
                    'wfa_note' => $data['wfa_note'] ?? null,
                    'late_minutes' => $lateMinutes,
                    'verification_method' => $verificationMethod,
                    'face_similarity_score' => $faceSimilarityScore,
                    'photo_selfie_in' => $data['photo_selfie'] ?? null,
                    'status' => $status,
                ]);

                return $attendance;
            });
        } catch (UniqueConstraintViolationException $e) {
            throw new AlreadyClockedInException('Anda sudah melakukan absensi hari ini');
        } catch (QueryException $e) {
            throw $e;
        }
    }

    /**
     * B12 fix: tiered verification (Face → PIN → throw).
     * Mengembalikan [verification_method, similarity_score|null].
     *
     * Tier 1: Face Recognition (jika face_embedding ada di DB & client kirim embedding).
     * Tier 2: PIN fallback (kalau face gagal/belum register & PIN dikirim).
     * Tier 3: Throw BusinessRuleException kalau tidak ada satupun yang valid.
     *
     * Pakai getRawOriginal() untuk hindari trigger pgvector cast saat unit test.
     */
    private function resolveVerification(Employee $employee, array $data): array
    {
        $hasFaceEnrolled = ! empty($employee->getRawOriginal('face_embedding'))
            || ! empty($employee->getAttributes()['face_embedding'] ?? null);
        $hasFacePayload = ! empty($data['face_embedding']);
        $hasPinPayload = ! empty($data['pin']);

        // Tier 1: Face Recognition (jika kedua sisi siap)
        if ($hasFaceEnrolled && $hasFacePayload) {
            try {
                $faceResult = $this->faceRecognitionService->verifyFace(
                    $employee,
                    $data['face_embedding']
                );

                return [
                    VerificationMethod::FACE_VERIFIED->value,
                    $faceResult['similarity_percentage'],
                ];
            } catch (FaceNotRegisteredException $e) {
                // Race: embedding hilang antara cek dan verifikasi → coba PIN
                Log::warning('Face embedding hilang saat verifikasi: '.$e->getMessage());
            } catch (FaceNotRecognizedException $e) {
                // Wajah tidak match → coba PIN. Skor tetap ditolak silently di sini,
                // surface-nya via attendance.face_similarity_score=null & verification=pin.
                Log::warning('Verifikasi wajah gagal: '.$e->getMessage());
            }
        }

        // Tier 2: PIN fallback
        if ($hasPinPayload) {
            $this->verifyPin($employee, $data['pin']);

            $bypassReason = $hasFaceEnrolled
                ? 'pin_verified_clock_in_face_failed'
                : 'pin_verified_clock_in_face_not_enrolled';
            $this->logBypass($employee, $bypassReason);

            return [VerificationMethod::PIN_VERIFIED->value, null];
        }

        // Tier 3: Tidak ada verifikasi valid
        if (! $hasFaceEnrolled) {
            throw new BusinessRuleException(
                'Wajah Anda belum terdaftar. Hubungi HRD untuk registrasi atau gunakan PIN sebagai fallback.'
            );
        }

        throw new BusinessRuleException(
            'Verifikasi gagal. Pastikan wajah terdeteksi dengan jelas atau gunakan PIN sebagai fallback.'
        );
    }

    public function clockOut(Employee $employee, array $data, string $verificationMethod = 'face_verified'): Attendance
    {
        // Tier 0: Anti-Tuyul GPS
        if (isset($data['is_mocked']) && $data['is_mocked'] == true) {
            throw new AntiFakeGPSException('Peringatan: Aplikasi Fake GPS terdeteksi saat Clock-Out!');
        }

        $attendance = $employee->getTodayActiveAttendance();

        if (! $attendance) {
            throw new NotClockedInException('Tidak ada absensi masuk hari ini atau Anda sudah melakukan clock-out.');
        }

        if (! $attendance->is_wfa) {
            // WFO: cek dulu apakah karyawan punya branch
            if (! $employee->branch) {
                throw new BusinessRuleException('Data lokasi kerja Anda belum diatur. Hubungi HRD.');
            }
            $this->geofenceService->validateLocation($employee->branch, $data);
        }

        $faceSimilarityScore = null;

        if ($verificationMethod === VerificationMethod::PIN_VERIFIED->value) {
            $this->verifyPin($employee, $data['pin'] ?? '');
            $this->logBypass($employee, 'pin_verified_clock_out');
        } else {
            $faceResult = $this->faceRecognitionService->verifyFace(
                $employee,
                $data['face_embedding'] ?? []
            );
            $faceSimilarityScore = $faceResult['similarity_percentage'];
            $verificationMethod = VerificationMethod::FACE_VERIFIED->value;
        }

        return DB::transaction(function () use ($employee, $data, $verificationMethod, $faceSimilarityScore) {
            $lockedAttendance = $employee->getTodayActiveAttendance(lockForUpdate: true);

            if (! $lockedAttendance) {
                throw new NotClockedInException('Sistem sedang memproses data absensi Anda yang lain.');
            }

            $lockedAttendance->update([
                'clock_out' => now(),
                'lat_out' => $data['latitude'] ?? null,
                'long_out' => $data['longitude'] ?? null,
                'clock_out_is_mocked' => $data['is_mocked'] ?? false,
                'clock_out_accuracy' => $data['accuracy'] ?? null,
                'clock_out_verification_method' => $verificationMethod,
                'clock_out_face_similarity_score' => $faceSimilarityScore,
                'photo_selfie_out' => $data['photo_selfie'] ?? null,
            ]);

            return $lockedAttendance->fresh();
        });
    }

    private function verifyPin(Employee $employee, string $pin): void
    {
        if (empty($employee->pin) || ! Hash::check($pin, $employee->pin)) {
            throw new InvalidPinException('PIN yang Anda masukkan salah.');
        }
    }

    private function logBypass(Employee $employee, string $method): void
    {
        activity()
            ->causedBy($employee->user)
            ->performedOn($employee)
            ->withProperties(['ip' => request()->ip(), 'method' => $method])
            ->log('Melakukan bypass absensi menggunakan '.$method);
    }
}
