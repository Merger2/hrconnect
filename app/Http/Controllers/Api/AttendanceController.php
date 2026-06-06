<?php

namespace App\Http\Controllers\Api;

use App\Enums\AttendanceStatus;
use App\Enums\WfaStatus;
use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ClockInRequest;
use App\Http\Requests\Api\ClockOutRequest;
use App\Models\Attendance;
use App\Services\AttendanceService;
use Carbon\CarbonImmutable;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Attendance')]
class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService,
    ) {}

    #[Endpoint(title: 'Clock In', description: 'Record attendance clock-in with GPS and face verification. Flow: Clock In (Step 2/4) — Register Face → Clock In → Today → Clock Out.')]
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

    #[Endpoint(title: 'Clock Out', description: 'Record attendance clock-out with optional GPS and face verification. Flow: Clock In (Step 4/4) — Register Face → Clock In → Today → Clock Out.')]
    #[BodyParameter(name: 'latitude', description: 'GPS latitude', required: false, type: 'number')]
    #[BodyParameter(name: 'longitude', description: 'GPS longitude', required: false, type: 'number')]
    #[BodyParameter(name: 'accuracy', description: 'GPS accuracy in meters', required: false, type: 'number')]
    #[BodyParameter(name: 'is_mocked', description: 'GPS mock detection flag', required: false, type: 'boolean')]
    #[BodyParameter(name: 'embedding', description: 'Face embedding 128D array for verification', required: false, type: 'array')]
    #[BodyParameter(name: 'pin', description: '6-digit PIN as fallback verification', required: false, type: 'string')]
    #[BodyParameter(name: 'verification_method', description: 'Verification method override', required: false, type: 'string')]
    #[BodyParameter(name: 'photo_selfie', description: 'Base64 selfie photo', required: false, type: 'string')]
    public function clockOut(ClockOutRequest $request): JsonResponse
    {
        $data = $request->validated();

        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda belum terhubung dengan data karyawan.',
            ], 404);
        }

        if (isset($data['embedding'])) {
            $data['face_embedding'] = $data['embedding'];
            unset($data['embedding']);
        }

        $attendance = $this->attendanceService->clockOut($employee, $data);

        $clockIn = $attendance->clock_in instanceof CarbonImmutable ? $attendance->clock_in : CarbonImmutable::parse($attendance->clock_in);
        $clockOut = $attendance->clock_out instanceof CarbonImmutable ? $attendance->clock_out : CarbonImmutable::parse($attendance->clock_out);
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

    #[Endpoint(title: 'Today', description: 'Get today\'s attendance status (clocked in/out). Flow: Clock In (Step 3/4) — Register Face → Clock In → Today → Clock Out.')]
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

    #[Endpoint(title: 'List Attendances', description: 'Paginated attendance list with period/status filters. Flow: Attendance Management.')]
    #[QueryParameter(name: 'period', description: 'Filter by period (YYYY-MM)', type: 'string')]
    #[QueryParameter(name: 'status', description: 'Filter by status', type: 'string')]
    #[QueryParameter(name: 'page', description: 'Page number', type: 'integer')]
    #[QueryParameter(name: 'per_page', description: 'Items per page (max 100)', type: 'integer')]
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

    #[Endpoint(title: 'Approve WFA', description: 'Approve or reject WFA request for a specific attendance. Flow: WFA Approval.')]
    #[BodyParameter(name: 'decision', description: 'Approve or reject', required: true, type: 'string')]
    #[BodyParameter(name: 'notes', description: 'Approval notes', required: false, type: 'string')]
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
