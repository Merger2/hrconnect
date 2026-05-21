<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\VerificationMethod;
use App\Exceptions\AlreadyClockedInException;
use App\Exceptions\AntiFakeGPSException;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\FaceNotRecognizedException;
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

        $verificationMethod = VerificationMethod::MANUAL->value;
        $faceSimilarityScore = null;

        if ($employee->face_embedding && ! empty($data['face_embedding'])) {
            try {
                $faceResult = $this->faceRecognitionService->verifyFace(
                    $employee,
                    $data['face_embedding']
                );
                $verificationMethod = VerificationMethod::FACE_VERIFIED->value;
                $faceSimilarityScore = $faceResult['similarity_percentage'];
            } catch (FaceNotRecognizedException $e) {
                Log::warning('Verifikasi wajah gagal: '.$e->getMessage());
            }
        }

        if ($verificationMethod === VerificationMethod::MANUAL->value && ! empty($data['pin'])) {
            $this->verifyPin($employee, $data['pin']);
            $this->logBypass($employee, 'pin_verified_clock_in');
            $verificationMethod = VerificationMethod::PIN_VERIFIED->value;
        }

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
        if (! Hash::check($pin, $employee->pin)) {
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
