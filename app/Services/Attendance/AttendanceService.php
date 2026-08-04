<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\Domain\Attendance\AttendanceRiskScorer;
use App\Enums\ApprovalStatus;
use App\Enums\AttendanceStatus;
use App\Enums\VerificationMethod;
use App\Exceptions\AlreadyClockedInException;
use App\Exceptions\AntiFakeGPSException;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\FaceNotRecognizedException;
use App\Exceptions\FaceNotRegisteredException;
use App\Exceptions\NotClockedInException;
use App\Models\Attendance;
use App\Models\Employee;
use App\Services\Security\FaceRecognitionService;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceService
{
    public function __construct(
        protected GeofenceService $geofenceService,
        protected FaceRecognitionService $faceRecognitionService,
        protected AttendanceRiskScorer $riskScorer,
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
            $geofenceResult = $this->geofenceService->validateLocation($employee->branch, $data);
            $data['_geofence_distance'] = $geofenceResult['distance'] ?? null;
        }

        // Face-only policy (PRD §1/§4): tanpa PIN fallback.
        // Wajah wajib terdaftar & terverifikasi; gagal = tolak + arahkan ke koreksi HR.
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
                    'status_wfa' => $isWfa ? ApprovalStatus::PENDING->value : null,
                    'wfa_note' => $data['wfa_note'] ?? null,
                    // B-34: WFA attendance should not be penalized with late_minutes
                    'late_minutes' => $isWfa ? 0 : $lateMinutes,
                    'verification_method' => $verificationMethod,
                    'face_similarity_score' => $faceSimilarityScore,
                    'photo_selfie_in' => $data['photo_selfie'] ?? null,
                    'status' => $status,
                ]);

                $geofenceRadius = $employee->branch?->radius;
                $riskResult = $this->riskScorer->score($attendance, $employee->shift, 'check_in', [
                    'gps_accuracy' => $data['accuracy'] ?? null,
                    'gps_variance' => $data['gps_variance'] ?? null,
                    'distance' => $data['_geofence_distance'] ?? null,
                    'radius' => $geofenceRadius,
                    'face_confidence' => $faceSimilarityScore,
                    'face_verification_failed' => $verificationMethod !== VerificationMethod::FACE_VERIFIED->value && ! empty($data['face_embedding']),
                    'mock_location_detected' => ($data['is_mocked'] ?? false) === true,
                    'source' => 'web',
                ]);
                $attendance->forceFill([
                    'risk_score' => $riskResult['score'],
                    'risk_level' => $riskResult['level'],
                    'risk_factors' => $riskResult['factors'],
                ])->save();

                return $attendance;
            });
        } catch (UniqueConstraintViolationException $e) {
            throw new AlreadyClockedInException('Anda sudah melakukan absensi hari ini');
        } catch (QueryException $e) {
            throw $e;
        }
    }

    /**
     * Face-only verification (PRD §1/§4) — tanpa PIN fallback.
     * Mengembalikan [verification_method, similarity_score|null].
     *
     * - Face belum terdaftar → throw BusinessRuleException (arahkan ke HRD untuk registrasi).
     * - Face terdaftar tapi tanpa/tidak match embedding → throw BusinessRuleException
     *   (tolak; user dapat mengajukan koreksi absensi ke HRD).
     * - Payload `pin` DIABAIKAN: tidak diproses, tidak divalidasi, tidak disimpan.
     *
     * Pakai getRawOriginal() untuk hindari trigger pgvector cast saat unit test.
     */
    private function resolveVerification(Employee $employee, array $data): array
    {
        if (! $this->faceRecognitionService->hasFaceEnrolled($employee)) {
            throw new BusinessRuleException(
                'Wajah Anda belum terdaftar. Hubungi HRD untuk registrasi wajah sebelum melakukan absensi.'
            );
        }

        if (empty($data['face_embedding'])) {
            throw new BusinessRuleException(
                'Verifikasi wajah diperlukan untuk absensi. Silakan coba lagi, atau ajukan koreksi absensi ke HRD.'
            );
        }

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
            // Race: embedding hilang antara cek dan verifikasi → tolak (bukan PIN fallback)
            Log::warning('Face embedding hilang saat verifikasi: '.$e->getMessage());

            throw new BusinessRuleException(
                'Verifikasi wajah gagal. Silakan coba lagi, atau ajukan koreksi absensi ke HRD.'
            );
        } catch (FaceNotRecognizedException $e) {
            Log::warning('Verifikasi wajah gagal: '.$e->getMessage());

            throw new BusinessRuleException(
                'Verifikasi wajah gagal. Silakan coba lagi, atau ajukan koreksi absensi ke HRD.'
            );
        }
    }

    public function clockOut(Employee $employee, array $data): Attendance
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
            if (! $employee->branch) {
                throw new BusinessRuleException('Data lokasi kerja Anda belum diatur. Hubungi HRD.');
            }
            $this->geofenceService->validateLocation($employee->branch, $data);
        }

        [$verificationMethod, $faceSimilarityScore] = $this->resolveClockOutVerification($employee, $data);

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

    /**
     * Verifikasi face-only untuk clock-out (PRD §1/§4) — tanpa PIN fallback.
     * Mengembalikan [verification_method, similarity_score|null].
     *
     * - Face belum terdaftar → throw BusinessRuleException (arahkan ke HRD untuk registrasi).
     * - Face gagal/tidak match → throw BusinessRuleException (tolak; koreksi via HRD).
     * - Payload `pin` DIABAIKAN: tidak diproses, tidak divalidasi.
     */
    private function resolveClockOutVerification(Employee $employee, array $data): array
    {
        if (! $this->faceRecognitionService->hasFaceEnrolled($employee)) {
            throw new BusinessRuleException(
                'Wajah Anda belum terdaftar. Hubungi HRD untuk registrasi wajah sebelum melakukan absensi.'
            );
        }

        if (empty($data['face_embedding'])) {
            throw new BusinessRuleException(
                'Verifikasi wajah diperlukan untuk clock-out. Silakan coba lagi, atau ajukan koreksi absensi ke HRD.'
            );
        }

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
            // Race: embedding hilang antara cek dan verifikasi → tolak (bukan PIN fallback)
            Log::warning('Face embedding hilang saat clock-out: '.$e->getMessage());

            throw new BusinessRuleException(
                'Verifikasi wajah gagal. Silakan coba lagi, atau ajukan koreksi absensi ke HRD.'
            );
        } catch (FaceNotRecognizedException $e) {
            Log::warning('Verifikasi wajah clock-out gagal: '.$e->getMessage());

            throw new BusinessRuleException(
                'Verifikasi wajah gagal. Silakan coba lagi, atau ajukan koreksi absensi ke HRD.'
            );
        }
    }
}
