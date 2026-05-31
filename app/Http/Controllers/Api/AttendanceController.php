<?php

namespace App\Http\Controllers\Api;

use App\Enums\AttendanceStatus;
use App\Enums\WfaStatus;
use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ClockInRequest;
use App\Models\Attendance;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AttendanceController — clock-in/out, today, history, WFA approve.
 *
 * Authorization via AttendancePolicy (auto-discovery Laravel 11+).
 */
class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService,
    ) {}

    /**
     * POST /attendance/clock-in
     */
    public function clockIn(ClockInRequest $request): JsonResponse
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda belum terhubung dengan data karyawan.',
            ], 404);
        }

        // Map 'embedding' (api-contracts.md naming) ke 'face_embedding'
        // (AttendanceService internal naming).
        $payload = $request->validated();
        if (isset($payload['embedding'])) {
            $payload['face_embedding'] = $payload['embedding'];
        }

        $attendance = $this->attendanceService->clockIn($employee, $payload);

        return response()->json([
            'status' => 'success',
            'message' => 'Clock-in berhasil',
            'data' => [
                'id' => $attendance->id,
                'employee_id' => $attendance->employee_id,
                'date' => $attendance->date?->toDateString(),
                'clock_in' => $attendance->clock_in?->toIso8601String(),
                'is_wfa' => (bool) $attendance->is_wfa,
                'status' => $attendance->status?->value,
                'verification_method' => $attendance->verification_method,
                'face_similarity_score' => $attendance->face_similarity_score,
                'late_minutes' => $attendance->late_minutes,
            ],
        ], 201);
    }

    /**
     * POST /attendance/clock-out
     */
    public function clockOut(Request $request): JsonResponse
    {
        $data = $request->validate([
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'is_mocked' => ['nullable', 'boolean'],
            'embedding' => ['nullable', 'array', 'size:128'],
            'embedding.*' => ['numeric', 'between:-1.5,1.5'],
            'pin' => ['nullable', 'regex:/^\d{6}$/'],
            'verification_method' => ['nullable', 'in:face_verified,pin_verified,manual'],
            'photo_selfie' => ['nullable', 'string'],
        ]);

        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda belum terhubung dengan data karyawan.',
            ], 404);
        }

        if (isset($data['embedding'])) {
            $data['face_embedding'] = $data['embedding'];
        }

        $verificationMethod = $data['verification_method'] ?? 'face_verified';

        $attendance = $this->attendanceService->clockOut($employee, $data, $verificationMethod);

        $clockIn = $attendance->clock_in instanceof Carbon ? $attendance->clock_in : Carbon::parse($attendance->clock_in);
        $clockOut = $attendance->clock_out instanceof Carbon ? $attendance->clock_out : Carbon::parse($attendance->clock_out);
        $duration = $clockOut->floatDiffInHours($clockIn);

        return response()->json([
            'status' => 'success',
            'message' => 'Clock-out berhasil',
            'data' => [
                'id' => $attendance->id,
                'clock_in' => $attendance->clock_in?->toIso8601String(),
                'clock_out' => $attendance->clock_out?->toIso8601String(),
                'work_duration_hours' => round(abs($duration), 2),
            ],
        ]);
    }

    /**
     * GET /attendance/today
     */
    public function today(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda belum terhubung dengan data karyawan.',
            ], 404);
        }

        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'has_clocked_in' => $attendance && $attendance->clock_in !== null,
                'has_clocked_out' => $attendance && $attendance->clock_out !== null,
                'attendance' => $attendance ? [
                    'id' => $attendance->id,
                    'date' => $attendance->date?->toDateString(),
                    'clock_in' => $attendance->clock_in?->toIso8601String(),
                    'clock_out' => $attendance->clock_out?->toIso8601String(),
                    'is_wfa' => (bool) $attendance->is_wfa,
                    'status' => $attendance->status?->value,
                    'late_minutes' => $attendance->late_minutes,
                ] : null,
            ],
        ]);
    }

    /**
     * GET /attendance?period=YYYY-MM
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'period' => ['nullable', 'regex:/^\d{4}-\d{2}$/'],
            'status' => ['nullable', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $this->authorize('viewAny', Attendance::class);

        $employee = $request->user()->employee;
        $period = $request->input('period') ?: now()->format('Y-m');
        $perPage = (int) $request->input('per_page', 20);

        [$year, $month] = explode('-', $period);

        $query = Attendance::query()
            ->whereYear('date', (int) $year)
            ->whereMonth('date', (int) $month)
            ->orderBy('date', 'desc');

        // Employee → diri sendiri saja (kecuali HR/Manager dengan permission)
        if (! $request->user()->can('manage_attendances') && $employee) {
            $query->where('employee_id', $employee->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $paginated = $query->paginate($perPage);

        $data = $paginated->getCollection()->map(fn (Attendance $a) => [
            'id' => $a->id,
            'date' => $a->date?->toDateString(),
            'clock_in' => $a->clock_in?->toIso8601String(),
            'clock_out' => $a->clock_out?->toIso8601String(),
            'is_wfa' => (bool) $a->is_wfa,
            'status' => $a->status?->value,
            'late_minutes' => $a->late_minutes,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $data,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * POST /attendance/{attendance}/approve-wfa
     */
    public function approveWfa(Request $request, Attendance $attendance): JsonResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();

        if (! $user->can('approve_wfa')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses untuk approve WFA.',
            ], 403);
        }

        // Cek manager hierarchy via parent_id
        $employee = $attendance->employee;
        if ($user->employee?->id !== $employee?->parent_id && ! $user->hasRole(['super-admin', 'hr-manager'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda hanya bisa approve WFA tim Anda.',
            ], 403);
        }

        if (! $attendance->is_wfa) {
            throw new BusinessRuleException('Attendance ini bukan record WFA.');
        }

        $newStatusWfa = $data['decision'] === 'approve' ? WfaStatus::APPROVED : WfaStatus::REJECTED;
        $newStatus = $data['decision'] === 'approve' ? $attendance->status : AttendanceStatus::ABSENT;

        $attendance->update([
            'status_wfa' => $newStatusWfa,
            'status' => $newStatus,
            'exception_notes' => $data['notes'] ?? $attendance->exception_notes,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Status WFA berhasil diperbarui',
            'data' => [
                'id' => $attendance->id,
                'status_wfa' => $attendance->status_wfa?->value,
                'status' => $attendance->status?->value,
            ],
        ]);
    }
}
