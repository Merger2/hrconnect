<?php

namespace App\Http\Controllers\Api;

use App\Enums\ReimbursementStatus;
use App\Http\Controllers\Controller;
use App\Models\Reimbursement;
use App\Services\ApprovalService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ReimbursementController — request/list/cancel reimbursement.
 *
 * L2 approval = Finance (bukan HR Manager). Lihat ReimbursementPolicy.
 */
class ReimbursementController extends Controller
{
    public function __construct(
        protected ApprovalService $approvalService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:reimbursement_categories,id'],
            'title' => ['nullable', 'string', 'max:200'],
            'amount' => ['required', 'integer', 'min:1'],
            'description' => ['required', 'string', 'min:10', 'max:1000'],
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda belum terhubung dengan data karyawan.',
            ], 404);
        }

        $receiptPath = $request->file('receipt')->store('reimbursements', 'public');

        $reimbursement = Reimbursement::create([
            'employee_id' => $employee->id,
            'category_id' => $data['category_id'],
            'title' => $data['title'] ?? mb_substr($data['description'], 0, 100),
            'amount' => $data['amount'],
            'description' => $data['description'],
            'expense_date' => $data['expense_date'],
            'receipt_file' => $receiptPath,
            'status' => ReimbursementStatus::PENDING,
        ]);

        $this->approvalService->createApprovalWorkflow($reimbursement);

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan reimbursement berhasil dikirim',
            'data' => $this->formatReimbursement($reimbursement->fresh(['approvals'])),
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

        $this->authorize('viewAny', Reimbursement::class);

        $user = $request->user();
        $perPage = (int) $request->input('per_page', 20);

        $query = Reimbursement::query()->orderBy('created_at', 'desc');

        if (! $user->hasRole(['super-admin', 'hr-manager', 'finance'])) {
            if ($user->can('approve_reimbursements_l1') && $user->employee) {
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
            $query->whereYear('expense_date', (int) $year)
                ->whereMonth('expense_date', (int) $month);
        }

        $paginated = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $paginated->getCollection()->map(fn (Reimbursement $r) => $this->formatReimbursement($r)),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    public function show(Request $request, Reimbursement $reimbursement): JsonResponse
    {
        $this->authorize('view', $reimbursement);

        return response()->json([
            'status' => 'success',
            'data' => $this->formatReimbursement(
                $reimbursement->load(['approvals.approver:id,full_name', 'category:id,name'])
            ),
        ]);
    }

    public function destroy(Request $request, Reimbursement $reimbursement): JsonResponse
    {
        $this->authorize('delete', $reimbursement);

        $reimbursement->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan reimbursement berhasil dibatalkan',
        ]);
    }

    private function formatReimbursement(Reimbursement $r): array
    {
        return [
            'id' => $r->id,
            'employee_id' => $r->employee_id,
            'category_id' => $r->category_id,
            'category' => $r->relationLoaded('category') && $r->category ? [
                'id' => $r->category->id,
                'name' => $r->category->name,
            ] : null,
            'title' => $r->title,
            'amount' => (int) $r->amount,
            'description' => $r->description,
            'expense_date' => $r->expense_date instanceof Carbon
                ? $r->expense_date->toDateString()
                : (string) $r->expense_date,
            'receipt_file' => $r->receipt_file,
            'status' => $r->status?->value,
            'payroll_id' => $r->payroll_id,
            'created_at' => $r->created_at?->toIso8601String(),
        ];
    }
}
