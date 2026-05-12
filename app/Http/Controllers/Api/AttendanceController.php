<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ClockInRequest;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService,
    ) {}

    /**
     * Clock-In — Absensi Masuk (WFO / WFA)
     */
    public function clockIn(ClockInRequest $request): JsonResponse
    {
        $employee = auth()->user()->employee;

        if (! $employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda belum terhubung dengan data karyawan.',
            ], 400);
        }

        $attendance = $this->attendanceService->clockIn(
            $employee,
            $request->validated()
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Clock-In berhasil.',
            'data' => [
                'id' => $attendance->id,
                'clock_in' => $attendance->clock_in,
                'status' => $attendance->status->value,
                'late_minutes' => $attendance->late_minutes,
                'verification_method' => $attendance->verification_method,
                'is_wfa' => $attendance->is_wfa,
            ],
        ], 201);
    }
}
