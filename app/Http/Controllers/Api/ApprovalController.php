<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApprovalStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ApproveRequest;
use App\Http\Requests\Api\PendingApprovalsRequest;
use App\Http\Requests\Api\RejectRequest;
use App\Models\Approval;
use App\Models\Leave;
use App\Models\Overtime;
use App\Models\Reimbursement;
use App\Services\ApprovalService;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Approvals')]
class ApprovalController extends Controller
{
    public function __construct(
        protected ApprovalService $approvalService,
    ) {}

    #[Endpoint(title: 'Pending Approvals', description: 'List pending approvals for the current user as approver. Flow: Approval (Step 2/2) — manager reviews pending requests from Leave, Overtime, Reimbursement.')]
    #[QueryParameter(name: 'type', description: 'Filter by type (leave, overtime, reimbursement, wfa)', type: 'string')]
    #[QueryParameter(name: 'page', description: 'Page number', type: 'integer')]
    #[QueryParameter(name: 'per_page', description: 'Items per page (max 100)', type: 'integer')]
    public function pending(PendingApprovalsRequest $request): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if (! $employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda belum terhubung dengan data karyawan.',
            ], 404);
        }

        $perPage = (int) $request->input('per_page', 20);

        $query = Approval::with([
            'approver:id,full_name',
            'approvable' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                Leave::class => ['employee:id,full_name'],
                Overtime::class => ['employee:id,full_name'],
                Reimbursement::class => ['employee:id,full_name'],
            ]),
        ])
            ->where('approver_id', $employee->id)
            ->where('status', ApprovalStatus::PENDING)
            ->orderBy('created_at', 'desc');

        if ($request->filled('type')) {
            $morphMap = [
                'leave' => Leave::class,
                'overtime' => Overtime::class,
                'reimbursement' => Reimbursement::class,
            ];

            $type = $request->input('type');
            if (isset($morphMap[$type])) {
                $query->where('approvable_type', $morphMap[$type]);
            }
        }

        $paginated = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $paginated->getCollection()->map(fn (Approval $a) => [
                'approval_id' => $a->id,
                'level' => $a->level instanceof \BackedEnum ? $a->level->value : $a->level,
                'approvable_type' => class_basename($a->approvable_type),
                'approvable_id' => $a->approvable_id,
                'submitter' => $a->approvable?->employee ? [
                    'id' => $a->approvable->employee->id,
                    'full_name' => $a->approvable->employee->full_name,
                ] : null,
                'submitted_at' => $a->approvable?->created_at?->toIso8601String(),
                'created_at' => $a->created_at?->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    #[Endpoint(title: 'Approve', description: 'Approve a pending approval request. Flow: Approval (approve action).')]
    #[BodyParameter(name: 'notes', description: 'Approval notes (optional)', required: false, type: 'string')]
    public function approve(ApproveRequest $request, Approval $approval): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if (! $employee || $approval->approver_id !== $employee->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda bukan approver untuk request ini.',
            ], 403);
        }

        if ($approval->status !== ApprovalStatus::PENDING) {
            return response()->json([
                'status' => 'error',
                'message' => 'Approval ini sudah diproses sebelumnya.',
            ], 409);
        }

        $this->approvalService->approve($approval, $request->validated('notes') ?? '');

        $approval->refresh();

        return response()->json([
            'status' => 'success',
            'message' => 'Persetujuan berhasil',
            'data' => [
                'approval_id' => $approval->id,
                'status' => $approval->status?->value,
                'is_final' => $this->isFinalApproval($approval),
            ],
        ]);
    }

    #[Endpoint(title: 'Reject', description: 'Reject a pending approval request with reason. Flow: Approval (reject action).')]
    #[BodyParameter(name: 'rejection_reason', description: 'Reason for rejection (min 10 chars)', required: true, type: 'string')]
    public function reject(RejectRequest $request, Approval $approval): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if (! $employee || $approval->approver_id !== $employee->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda bukan approver untuk request ini.',
            ], 403);
        }

        if ($approval->status !== ApprovalStatus::PENDING) {
            return response()->json([
                'status' => 'error',
                'message' => 'Approval ini sudah diproses sebelumnya.',
            ], 409);
        }

        $this->approvalService->reject($approval, $request->validated('rejection_reason'));

        $approval->refresh();

        return response()->json([
            'status' => 'success',
            'message' => 'Penolakan berhasil',
            'data' => [
                'approval_id' => $approval->id,
                'status' => $approval->status?->value,
            ],
        ]);
    }

    #[Endpoint(title: 'Approval Detail', description: 'Get full detail of an approval including the request data and approval chain.')]
    public function show(Request $request, Approval $approval): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if (! $employee || $approval->approver_id !== $employee->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda bukan approver untuk request ini.',
            ], 403);
        }

        $approval->load([
            'approver:id,full_name',
            'approvable' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                Leave::class => ['employee:id,full_name', 'leaveType:id,name,code,is_paid'],
                Overtime::class => ['employee:id,full_name'],
                Reimbursement::class => ['employee:id,full_name', 'category:id,name'],
            ]),
        ]);

        $allApprovals = $approval->approvable->approvals()
            ->with('approver:id,full_name')
            ->orderBy('level')
            ->get();

        $approvable = $approval->approvable;
        $approvableData = null;

        if ($approvable instanceof Leave) {
            $approvableData = [
                'type' => 'leave',
                'id' => $approvable->id,
                'leave_type' => $approvable->leaveType ? [
                    'id' => $approvable->leaveType->id,
                    'name' => $approvable->leaveType->name,
                    'is_paid' => $approvable->leaveType->is_paid,
                ] : null,
                'start_date' => $approvable->start_date?->toDateString(),
                'end_date' => $approvable->end_date?->toDateString(),
                'day_type' => $approvable->day_type?->value,
                'day_type_label' => $approvable->day_type?->label(),
                'total_days' => (float) $approvable->total_days,
                'reason' => $approvable->reason,
                'proof_file' => $approvable->proof_file,
                'status' => $approvable->status?->value,
            ];
        } elseif ($approvable instanceof Overtime) {
            $approvableData = [
                'type' => 'overtime',
                'id' => $approvable->id,
                'date' => $approvable->date?->toDateString(),
                'start_time' => $approvable->start_time?->toIso8601String(),
                'end_time' => $approvable->end_time?->toIso8601String(),
                'total_hours' => (float) $approvable->total_hours,
                'description' => $approvable->description,
                'amount' => (float) $approvable->amount,
                'status' => $approvable->status?->value,
            ];
        } elseif ($approvable instanceof Reimbursement) {
            $approvableData = [
                'type' => 'reimbursement',
                'id' => $approvable->id,
                'category' => $approvable->category ? [
                    'id' => $approvable->category->id,
                    'name' => $approvable->category->name,
                ] : null,
                'title' => $approvable->title,
                'amount' => (float) $approvable->amount,
                'expense_date' => $approvable->expense_date?->toDateString(),
                'description' => $approvable->description,
                'receipt_file' => $approvable->receipt_file,
                'status' => $approvable->status?->value,
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'approval_id' => $approval->id,
                'level' => $approval->level instanceof \BackedEnum ? $approval->level->value : $approval->level,
                'level_label' => $approval->level instanceof \BackedEnum ? $approval->level->label() : null,
                'status' => $approval->status instanceof \BackedEnum ? $approval->status->value : $approval->status,
                'notes' => $approval->notes,
                'approved_at' => $approval->approved_at?->toIso8601String(),
                'created_at' => $approval->created_at?->toIso8601String(),
                'submitter' => $approvable?->employee ? [
                    'id' => $approvable->employee->id,
                    'full_name' => $approvable->employee->full_name,
                ] : null,
                'approvable' => $approvableData,
                'approval_chain' => $allApprovals->map(fn (Approval $a) => [
                    'id' => $a->id,
                    'level' => $a->level instanceof \BackedEnum ? $a->level->value : $a->level,
                    'level_label' => $a->level instanceof \BackedEnum ? $a->level->label() : null,
                    'status' => $a->status instanceof \BackedEnum ? $a->status->value : $a->status,
                    'notes' => $a->notes,
                    'approved_at' => $a->approved_at?->toIso8601String(),
                    'approver' => $a->approver ? [
                        'id' => $a->approver->id,
                        'full_name' => $a->approver->full_name,
                    ] : null,
                ]),
            ],
        ]);
    }

    #[Endpoint(title: 'Approval History', description: 'List previously processed (approved/rejected) approvals for the current user as approver.')]
    #[QueryParameter(name: 'type', description: 'Filter by type (leave, overtime, reimbursement)', type: 'string')]
    #[QueryParameter(name: 'page', description: 'Page number', type: 'integer')]
    #[QueryParameter(name: 'per_page', description: 'Items per page (max 100)', type: 'integer')]
    public function history(PendingApprovalsRequest $request): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if (! $employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda belum terhubung dengan data karyawan.',
            ], 404);
        }

        $perPage = (int) $request->input('per_page', 20);

        $query = Approval::with([
            'approver:id,full_name',
            'approvable' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                Leave::class => ['employee:id,full_name'],
                Overtime::class => ['employee:id,full_name'],
                Reimbursement::class => ['employee:id,full_name'],
            ]),
        ])
            ->where('approver_id', $employee->id)
            ->where('status', '!=', ApprovalStatus::PENDING)
            ->orderBy('updated_at', 'desc');

        if ($request->filled('type')) {
            $morphMap = [
                'leave' => Leave::class,
                'overtime' => Overtime::class,
                'reimbursement' => Reimbursement::class,
            ];

            $type = $request->input('type');
            if (isset($morphMap[$type])) {
                $query->where('approvable_type', $morphMap[$type]);
            }
        }

        $paginated = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $paginated->getCollection()->map(fn (Approval $a) => [
                'approval_id' => $a->id,
                'level' => $a->level instanceof \BackedEnum ? $a->level->value : $a->level,
                'level_label' => $a->level instanceof \BackedEnum ? $a->level->label() : null,
                'approvable_type' => class_basename($a->approvable_type),
                'approvable_id' => $a->approvable_id,
                'submitter' => $a->approvable?->employee ? [
                    'id' => $a->approvable->employee->id,
                    'full_name' => $a->approvable->employee->full_name,
                ] : null,
                'submitted_at' => $a->approvable?->created_at?->toIso8601String(),
                'status' => $a->status instanceof \BackedEnum ? $a->status->value : $a->status,
                'notes' => $a->notes,
                'approved_at' => $a->approved_at?->toIso8601String(),
                'updated_at' => $a->updated_at?->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    private function isFinalApproval(Approval $approval): bool
    {
        $approvable = $approval->approvable;

        if (! $approvable) {
            return false;
        }

        // Approval dianggap final kalau status approvable sudah APPROVED.
        $status = $approvable->status ?? null;

        if ($status instanceof \BackedEnum) {
            return in_array($status->value, ['approved', 'paid'], true);
        }

        return in_array($status, ['approved', 'paid'], true);
    }
}
