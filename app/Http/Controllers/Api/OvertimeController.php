<?php

namespace App\Http\Controllers\Api;

use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ListOvertimeRequest;
use App\Http\Requests\Api\StoreOvertimeRequest;
use App\Models\Overtime;
use App\Services\OvertimeService;
use Carbon\Carbon;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

#[Group('Overtime')]
class OvertimeController extends Controller
{
    public function __construct(
        protected OvertimeService $overtimeService,
    ) {}

    #[Endpoint(title: 'Create Overtime', description: 'Submit overtime request with date, time range, and description (max 4h/day, 18h/week). Flow: Overtime (Step 1/2) → Approval.')]
    #[BodyParameter(name: 'date', description: 'Overtime date (Y-m-d, today or future)', required: true, type: 'string', format: 'date')]
    #[BodyParameter(name: 'start_time', description: 'Start time (H:i)', required: true, type: 'string', format: 'time')]
    #[BodyParameter(name: 'end_time', description: 'End time (H:i). If earlier than start_time, it is treated as next-day overtime.', required: true, type: 'string', format: 'time')]
    #[BodyParameter(name: 'description', description: 'Overtime reason (min 10 chars)', required: true, type: 'string')]
    public function store(StoreOvertimeRequest $request): JsonResponse
    {
        $data = $request->validated();

        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda belum terhubung dengan data karyawan.',
            ], 404);
        }

        $overtime = $this->overtimeService->createOvertime($employee, $data);

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan lembur berhasil dikirim',
            'data' => $this->formatOvertime($overtime->fresh(['approvals'])),
        ], 201);
    }

    #[Endpoint(title: 'List Overtimes', description: 'Paginated overtime list with status/period filters. Flow: Overtime (history).')]
    #[QueryParameter(name: 'status', description: 'Filter by status', type: 'string')]
    #[QueryParameter(name: 'period', description: 'Filter by period (YYYY-MM)', type: 'string')]
    #[QueryParameter(name: 'page', description: 'Page number', type: 'integer')]
    #[QueryParameter(name: 'per_page', description: 'Items per page (max 100)', type: 'integer')]
    public function index(ListOvertimeRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Overtime::class);

        $user = $request->user();
        $perPage = (int) $request->input('per_page', 20);

        // A-6: Eager load employee to prevent N+1
        $query = Overtime::with('employee:id,employee_number,full_name')
            ->orderBy('date', 'desc');

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

    #[Endpoint(title: 'Get Overtime', description: 'Get overtime detail with approvals. Flow: Overtime (detail).')]
    public function show(Request $request, Overtime $overtime): JsonResponse
    {
        $this->authorize('view', $overtime);

        return response()->json([
            'status' => 'success',
            'data' => $this->formatOvertime($overtime->load(['approvals.approver:id,full_name'])),
        ]);
    }

    #[Endpoint(title: 'Cancel Overtime', description: 'Cancel pending overtime request. Flow: Overtime (cancel).')]
    public function destroy(Request $request, Overtime $overtime): JsonResponse
    {
        $this->authorize('delete', $overtime);

        DB::transaction(function () use ($overtime): void {
            $overtime->update(['status' => RequestStatus::CANCELLED]);
            $overtime->delete();
        });

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
