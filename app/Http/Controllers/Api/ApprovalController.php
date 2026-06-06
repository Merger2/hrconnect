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
use Illuminate\Http\JsonResponse;

#[Group('Approvals')]
class ApprovalController extends Controller
{
    public function __construct(
        protected ApprovalService $approvalService,
    ) {}

    #[Endpoint(title: 'Pending Approvals', description: 'List pending approvals for the current user as approver.')]
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

        $query = Approval::with(['approvable', 'approver:id,full_name'])
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

    #[Endpoint(title: 'Approve', description: 'Approve a pending approval request.')]
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

    #[Endpoint(title: 'Reject', description: 'Reject a pending approval request with reason.')]
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
