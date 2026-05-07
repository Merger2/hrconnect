<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class AttendanceService
{
    public function __construct(
        protected GeofenceService $geofenceService,
        protected FaceRecognitionService $faceRecognitionService
    ) {
    }

    public function clockIn(Employee $employee, array $data): Attendance
    {
        // Tier 1: Face Recognition
        if (isset($data['face_embedding']) && $data['face_embedding']) {
            $similarity = $this->faceRecognitionService->compare(
                $employee->face_embedding,
                $data['face_embedding']
            );

            if ($similarity >= config('hrconnect.face_threshold', 0.85)) {
                return $this->createAttendance($employee, $data, $similarity);
            }
        }

        // Tier 2: PIN Verification
        if (isset($data['pin']) && $data['pin']) {
            $this->verifyPin($employee, $data['pin']);
            $this->logBypass($employee, 'pin_verified');
            return $this->createAttendance($employee, $data, null);
        }

        // Tier 3: Manual Request
        return $this->createManualRequest($employee, $data);
    }

    public function clockOut(Employee $employee, array $data): Attendance
    {
        $todayAttendance = $this->getTodayAttendance($employee);

        if (!$todayAttendance) {
            throw new \Exception('Belum melakukan clock in hari ini.');
        }

        if ($todayAttendance->clock_out) {
            throw new \Exception('Sudah melakukan clock out hari ini.');
        }

        // Face recognition for clock out
        $similarity = null;
        if (isset($data['face_embedding']) && $data['face_embedding'] && $employee->face_embedding) {
            $similarity = $this->faceRecognitionService->compare(
                $employee->face_embedding,
                $data['face_embedding']
            );
        }

        $todayAttendance->update([
            'clock_out' => now(),
            'lat_out' => $data['lat'] ?? null,
            'long_out' => $data['long'] ?? null,
            'face_similarity_score' => $similarity,
            'is_mocked_gps' => $data['is_mocked'] ?? false,
            'gps_accuracy' => $data['accuracy'] ?? null,
        ]);

        Cache::forget("attendance:today:{$employee->id}");

        return $todayAttendance;
    }

    public function validateGeofence(float $lat, float $long, ?int $branchId = null): bool
    {
        return $this->geofenceService->isWithinRadius($lat, $long, $branchId);
    }

    public function validateAntiFakeGPS(array $data): bool
    {
        if ($data['is_mocked'] ?? false) {
            return false;
        }

        if (($data['accuracy'] ?? 999) > 100) {
            return false;
        }

        return true;
    }

    public function calculateLateMinutes(Carbon $clockIn, ?Carbon $shiftStart = null): int
    {
        $shiftStart = $shiftStart ?? Carbon::parse('08:00');
        $tolerance = config('hrconnect.late_tolerance', 15);

        if ($clockIn->gt($shiftStart->copy()->addMinutes($tolerance))) {
            return $clockIn->diffInMinutes($shiftStart);
        }

        return 0;
    }

    public function getTodayAttendance(Employee $employee): ?Attendance
    {
        return Cache::remember(
            "attendance:today:{$employee->id}",
            now()->endOfDay(),
            fn() => $employee->attendances()
                ->whereDate('date', today())
                ->first()
        );
    }

    public function getMonthlySummary(Employee $employee, string $period): array
    {
        return Cache::remember(
            "attendance:monthly:{$employee->id}:{$period}",
            now()->addHour(),
            function () use ($employee, $period) {
                $date = Carbon::parse($period . '-01');
                $attendances = $employee->attendances()
                    ->whereYear('date', $date->year)
                    ->whereMonth('date', $date->month)
                    ->get();

                return [
                    'present' => $attendances->where('status', 'present')->count(),
                    'late' => $attendances->where('exception_type', 'late')->count(),
                    'sick' => $attendances->where('status', 'sick')->count(),
                    'leave' => $attendances->where('status', 'leave')->count(),
                    'absent' => $attendances->where('status', 'absent')->count(),
                    'total' => $attendances->count(),
                ];
            }
        );
    }

    protected function createAttendance(Employee $employee, array $data, ?float $similarity): Attendance
    {
        $clockIn = Carbon::parse($data['clock_in'] ?? now());
        $shift = $employee->shift;
        $lateMinutes = $this->calculateLateMinutes($clockIn, $shift?->start_time);

        $exceptionType = $lateMinutes > 0 ? 'late' : null;

        $attendance = Attendance::create([
            'employee_id' => $employee->id,
            'shift_id' => $shift?->id,
            'date' => today(),
            'clock_in' => $clockIn,
            'lat_in' => $data['lat'] ?? null,
            'long_in' => $data['long'] ?? null,
            'face_similarity_score' => $similarity,
            'status' => 'present',
            'exception_type' => $exceptionType,
            'is_mocked_gps' => $data['is_mocked'] ?? false,
            'gps_accuracy' => $data['accuracy'] ?? null,
            'device_fingerprint' => $data['device_fingerprint'] ?? null,
        ]);

        Cache::forget("attendance:today:{$employee->id}");

        return $attendance;
    }

    protected function createManualRequest(Employee $employee, array $data): Attendance
    {
        // Create attendance with pending status for supervisor approval
        $attendance = Attendance::create([
            'employee_id' => $employee->id,
            'date' => today(),
            'clock_in' => now(),
            'status' => 'pending',
            'exception_type' => 'missed_clock_in',
            'exception_notes' => 'Manual request - memerlukan persetujuan supervisor',
        ]);

        return $attendance;
    }

    protected function verifyPin(Employee $employee, string $pin): bool
    {
        // PIN verification logic - could be stored encrypted
        if (!$employee->pin) {
            throw new \Exception('PIN belum diatur. Hubungi HRD.');
        }

        if (!password_verify($pin, $employee->pin)) {
            throw new \Exception('PIN salah.');
        }

        return true;
    }

    protected function logBypass(Employee $employee, string $reason): void
    {
        activity('attendance')
            ->performedOn($employee)
            ->log("Face recognition bypassed: {$reason}");
    }
}
