<?php

namespace App\Http\Controllers\Api;

use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Models\Overtime;
use App\Services\ApprovalService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * OvertimeController — request/list/cancel lembur.
 */
class OvertimeController extends Controller
{
    public function __construct(
        protected ApprovalService $approvalService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'description' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda belum terhubung dengan data karyawan.',
            ], 404);
        }

        // Validasi durasi max 4 jam (UU Cipta Kerja)
        $start = Carbon::parse($data['date'].' '.$data['start_time']);
        $end = Carbon::parse($data['date'].' '.$data['end_time']);
        $hours = $end->floatDiffInHours($start);

        if ($hours > 4) {
            return response()->json([
                'status' => 'error',
                'message' => 'Durasi lembur tidak boleh lebih dari 4 jam per hari (UU Cipta Kerja).',
            ], 422);
        }

        $overtime = Overtime::create([
            'employee_id' => $employee->id,
            'date' => $data['date'],
            'start_time' => $start,
            'end_time' => $end,
            'description' => $data['description'],
            'status' => RequestStatus::PENDING,
            'total_hours' => round($hours, 2),
        ]);

        $this->approvalService->createApprovalWorkflow($overtime);

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan lembur berhasil dikirim',
            'data' => $this->formatOvertime($overtime->fresh(['approvals'])),
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['nullable', 'string'],
            'period' => ['nullable', 'regex:/^\d{4}-\d{2}$/'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $this->authorize('viewAny', Overtime::class);

        $user = $request->user();
        $perPage = (int) $request->input('per_page', 20);

        $query = Overtime::query()->orderBy('date', 'desc');

        if (! $user->hasRole(['super-admin', 'hr-manager'])) {
            if ($user->can('approve_overtimes_l1') && $user->employee) {
                $query->where(function ($q) use ($user) {
                    $q->where('employee_id', $user->employee->id)
                        ->orWhereHas('employee', fn ($e) => $e->where('parent_id', $user->employee->id));
                });
            } elseif ($user->employee) {
                $query->where('employee_id', $user->employee->id);
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('period')) {
            [$year, $month] = explode('-', $request->input('period'));
            $query->whereYear('date', (int) $year)->whereMonth('date', (int) $month);
        }

        $paginated = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $paginated->getCollection()->map(fn (Overtime $o) => $this->formatOvertime($o)),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    public function show(Request $request, Overtime $overtime): JsonResponse
    {
        $this->authorize('view', $overtime);

        return response()->json([
            'status' => 'success',
            'data' => $this->formatOvertime($overtime->load(['approvals.approver:id,full_name'])),
        ]);
    }

    public function destroy(Request $request, Overtime $overtime): JsonResponse
    {
        $this->authorize('delete', $overtime);

        $overtime->update(['status' => RequestStatus::CANCELLED]);
        $overtime->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan lembur berhasil dibatalkan',
        ]);
    }

    private function formatOvertime(Overtime $overtime): array
    {
        return [
            'id' => $overtime->id,
            'employee_id' => $overtime->employee_id,
            'date' => $overtime->date instanceof Carbon
                ? $overtime->date->toDateString()
                : (string) $overtime->date,
            'start_time' => $overtime->start_time?->toIso8601String(),
            'end_time' => $overtime->end_time?->toIso8601String(),
            'total_hours' => (float) $overtime->total_hours,
            'description' => $overtime->description,
            'status' => $overtime->status?->value,
            'amount' => $overtime->amount,
            'created_at' => $overtime->created_at?->toIso8601String(),
        ];
    }
}
