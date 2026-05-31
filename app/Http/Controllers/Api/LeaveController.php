<?php

namespace App\Http\Controllers\Api;

use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Services\LeaveService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * LeaveController — request/list/cancel cuti + view kuota.
 *
 * Authorization via LeavePolicy.
 */
class LeaveController extends Controller
{
    public function __construct(
        protected LeaveService $leaveService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'day_type' => ['required', 'in:full_day,morning,afternoon'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'proof_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

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

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['nullable', 'string'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'employee_id' => ['nullable', 'integer'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

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

    public function show(Request $request, Leave $leave): JsonResponse
    {
        $this->authorize('view', $leave);

        return response()->json([
            'status' => 'success',
            'data' => $this->formatLeave($leave->load(['leaveType', 'approvals.approver:id,full_name', 'employee:id,full_name'])),
        ]);
    }

    public function destroy(Request $request, Leave $leave): JsonResponse
    {
        $this->authorize('delete', $leave);

        $leave->update(['status' => RequestStatus::CANCELLED]);
        $leave->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan cuti berhasil dibatalkan',
        ]);
    }

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
