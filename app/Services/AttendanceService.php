<?php

namespace App\Services;

use App\Exceptions\AlreadyClockedInException;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Exceptions\InvalidPinException;
use App\Exceptions\FaceNotRecognizedException;
class AttendanceService
{
    // Implementasi logika untuk layanan kehadiran (attendance)
    public function __construct(
        protected GeofenceService $geofenceService,
        protected FaceRecognitionService $faceRecognitionService,
    ){}

    public function clockIn(Employee $employee, array $data): Attendance
    {
       //FAIL-FAST: Cek Double Clock-In
        if ($this->hasClockedInToday($employee)) {
            throw new AlreadyClockedInException('Anda sudah melakukan absensi masuk hari ini.');
        }
        //VALIDASI GEOFENCE
        $isWfa = $data['is_wfa'] ?? false;
        if (!$isWfa) {
            $this->geofenceService->validateLocation($employee->branch, $data);
        }
        //DATABASE TRANSACTION
        return DB::transaction(function () use ($employee, $data, $isWfa) {
            $verificationMethod = 'manual';

            //VERFIKASI BIOMETRIK WAJAH
            if (!empty($data['face_embedding'])) {
                try {
                    $this->faceRecognitionService->verifyFace($employee, $data['face_embedding']);
                    $verificationMethod = 'face_verified';
                } catch (FaceNotRecognizedException $e) {
                    Log::warning("Verifikasi wajah gagal untuk NIK {$employee->nik}: " . $e->getMessage());
                }
            }

            // Fallback ke PIN jika verifikasi wajah gagal atau tidak tersedia
           if ($verificationMethod === 'manual' && !empty($data['pin'])) {
                $this->verifyPin($employee, $data['pin']);
                $this->logBypass($employee, 'pin_verified');
                $verificationMethod = 'pin_verified';
            }

            // status penentuan
            $status = ($verificationMethod === 'manual') ? 'pending' : 'present';
            // delegasi fat model untuk menyimpan data absensi
            $lateMinutes = $employee->shift->calculateLateMinutes(now());

            // menyimpan data absensi
            $attendance = Attendance::create([
                'employee_id'         => $employee->id,
                'shift_id'            => $employee->shift_id,
                'date'                => today()->toDateString(),
                'clock_in'            => now(),
                'clock_in_latitude'   => $data['latitude'] ?? null,
                'clock_in_longitude'  => $data['longitude'] ?? null,
                'is_wfa'              => $isWfa,
                'wfa_note'            => $data['wfa_note'] ?? null,
                'late_minutes'        => $lateMinutes,
                'verification_method' => $verificationMethod,
                'status'              => $status,
            ]);

            // cache invalidation 
            if (Cache::supportsTags()) {
                Cache::tags(['attendance', "employee:{$employee->id}"])->flush();
            }
            Cache::forget("attendance:employee:{$employee->id}:date:" . today()->toDateString());
            return $attendance;
        });
    }

    /**
     * Helper: Cek absen hari ini
     */
    private function hasClockedInToday(Employee $employee): bool
    {
        return Attendance::where('employee_id', $employee->id)
            ->where('date', today()->toDateString())
            ->whereNotNull('clock_in')
            ->exists();
    }

    /**
     * Helper: Verifikasi PIN
     */
    private function verifyPin(Employee $employee, string $pin): void
    {
        if (!Hash::check($pin, $employee->user->password)) {
            throw new InvalidPinException('PIN yang Anda masukkan salah.');
        }
    }

    /**
     * Helper: Log aktivitas bypass
     */
    private function logBypass(Employee $employee, string $method): void
    {
        activity()
            ->causedBy($employee->user)
            ->performedOn($employee)
            ->withProperties(['ip' => request()->ip(), 'method' => $method])
            ->log('Melakukan bypass absensi menggunakan PIN');
    }
}