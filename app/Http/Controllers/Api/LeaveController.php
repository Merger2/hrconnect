<?php

namespace App\Http\Controllers\Api;

use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ListLeaveRequest;
use App\Http\Requests\Api\StoreLeaveRequest;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Services\LeaveService;
use Carbon\Carbon;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

#[Group('Leave')]
class LeaveController extends Controller
{
    public function __construct(
        protected LeaveService $leaveService,
    ) {}

    #[Endpoint(title: 'Create Leave', description: 'Submit a new leave request with type, dates, and reason. Flow: Leave (Step 1/3) → Approval.')]
    #[BodyParameter(name: 'leave_type_id', description: 'Leave type ID from leave_types table', required: true, type: 'integer')]
    #[BodyParameter(name: 'start_date', description: 'Leave start date (Y-m-d)', required: true, type: 'string', format: 'date')]
    #[BodyParameter(name: 'end_date', description: 'Leave end date (Y-m-d)', required: true, type: 'string', format: 'date')]
    #[BodyParameter(name: 'day_type', description: 'full_day, morning, or afternoon', required: true, type: 'string')]
    #[BodyParameter(name: 'reason', description: 'Leave reason (min 10 chars)', required: true, type: 'string')]
    #[BodyParameter(name: 'proof_file', description: 'Supporting document (jpg/jpeg/png/pdf, max 5MB)', required: false, type: 'string', format: 'binary')]
    public function store(StoreLeaveRequest $request): JsonResponse
    {
        $this->authorize('create', Leave::class);

        $data = $request->validated();

        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda belum terhubung dengan data karyawan.',
            ], 404);
        }

        if ($request->hasFile('proof_file')) {
            $data['proof_file'] = $request->file('proof_file')
                ->store('leaves/proofs', 'public');
        }

        $leave = $this->leaveService->applyLeave($employee, $data);

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan cuti berhasil dikirim',
            'data' => $this->formatLeave($leave->fresh(['leaveType', 'approvals'])),
        ], 201);
    }

    #[Endpoint(title: 'List Leaves', description: 'Paginated leave list with status/year filters. Manager sees own + team. Flow: Leave (history).')]
    #[QueryParameter(name: 'status', description: 'Filter by status', type: 'string')]
    #[QueryParameter(name: 'year', description: 'Filter by year', type: 'integer')]
    #[QueryParameter(name: 'employee_id', description: 'Filter by employee (HR only)', type: 'integer')]
    #[QueryParameter(name: 'page', description: 'Page number', type: 'integer')]
    #[QueryParameter(name: 'per_page', description: 'Items per page (max 100)', type: 'integer')]
    public function index(ListLeaveRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Leave::class);

        $user = $request->user();
        $perPage = (int) $request->input('per_page', 20);

        $query = Leave::with(['leaveType:id,name,code'])
            ->orderBy('created_at', 'desc');

        // Filter ownership: HR Manager all, Manager team, Employee self
        if (! $user->hasRole(['super-admin', 'hr-manager'])) {
            if ($user->can('approve_leaves_l1') && $user->employee) {
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

        if ($request->filled('year')) {
            $query->whereYear('start_date', (int) $request->input('year'));
        }

        if ($request->filled('employee_id') && $user->hasRole(['super-admin', 'hr-manager'])) {
            $query->where('employee_id', $request->input('employee_id'));
        }

        $paginated = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $paginated->getCollection()->map(fn (Leave $l) => $this->formatLeave($l)),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    #[Endpoint(title: 'Get Leave', description: 'Get leave detail with type and approvals. Flow: Leave (detail).')]
    public function show(Request $request, Leave $leave): JsonResponse
    {
        $this->authorize('view', $leave);

        return response()->json([
            'status' => 'success',
            'data' => $this->formatLeave($leave->load(['leaveType', 'approvals.approver:id,full_name', 'employee:id,full_name'])),
        ]);
    }

    #[Endpoint(title: 'Cancel Leave', description: 'Cancel a pending leave request (sets status to cancelled). Flow: Leave (cancel).')]
    public function destroy(Request $request, Leave $leave): JsonResponse
    {
        $this->authorize('delete', $leave);

        DB::transaction(function () use ($leave): void {
            $leave->update(['status' => RequestStatus::CANCELLED]);
            $leave->delete();
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan cuti berhasil dibatalkan',
        ]);
    }

    #[Endpoint(title: 'Leave Quota', description: 'Get current year leave balances (quota, used, available). Flow: Leave (Step 0/3) — check quota before submitting.')]
    #[QueryParameter(name: 'year', description: 'Year (defaults to current)', type: 'integer')]
    public function quota(Request $request): JsonResponse
    {
        $year = (int) $request->input('year', now()->year);
        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda belum terhubung dengan data karyawan.',
            ], 404);
        }

        $balances = LeaveBalance::with('leaveType:id,name,code,deducts_from_quota')
            ->where('employee_id', $employee->id)
            ->where('year', $year)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $balances->map(fn (LeaveBalance $b) => [
                'leave_type' => $b->leaveType ? [
                    'id' => $b->leaveType->id,
                    'name' => $b->leaveType->name,
                    'code' => $b->leaveType->code,
                    'deducts_from_quota' => (bool) $b->leaveType->deducts_from_quota,
                ] : null,
                'year' => $b->year,
                'quota' => (float) $b->quota,
                'used' => (float) $b->used,
                'carry_forward' => (float) $b->carry_forward,
                'carry_forward_deadline' => $b->carry_forward_deadline?->toDateString(),
                'available' => (float) $b->available(),
            ]),
        ]);
    }

    private function formatLeave(Leave $leave): array
    {
        return [
            'id' => $leave->id,
            'employee_id' => $leave->employee_id,
            'leave_type' => $leave->leaveType ? [
                'id' => $leave->leaveType->id,
                'name' => $leave->leaveType->name,
                'code' => $leave->leaveType->code,
            ] : null,
            'start_date' => $leave->start_date instanceof Carbon
                ? $leave->start_date->toDateString()
                : (string) $leave->start_date,
            'end_date' => $leave->end_date instanceof Carbon
                ? $leave->end_date->toDateString()
                : (string) $leave->end_date,
            'day_type' => $leave->day_type instanceof \BackedEnum ? $leave->day_type->value : $leave->day_type,
            'total_days' => (float) $leave->total_days,
            'reason' => $leave->reason,
            'proof_file' => $leave->proof_file,
            'status' => $leave->status?->value,
            'approvals' => $leave->relationLoaded('approvals')
                ? $leave->approvals->map(fn ($a) => [
                    'level' => $a->level instanceof \BackedEnum ? $a->level->value : $a->level,
                    'status' => $a->status?->value,
                    'approver' => $a->approver ? [
                        'id' => $a->approver->id,
                        'full_name' => $a->approver->full_name,
                    ] : null,
                    'notes' => $a->notes,
                ])
                : null,
            'created_at' => $leave->created_at?->toIso8601String(),
        ];
    }
}
