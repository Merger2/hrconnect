<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ListReimbursementRequest;
use App\Http\Requests\Api\StoreReimbursementRequest;
use App\Http\Requests\Api\UpdateReimbursementRequest;
use App\Http\Resources\ReimbursementCategoryResource;
use App\Http\Resources\ReimbursementResource;
use App\Models\Reimbursement;
use App\Models\ReimbursementCategory;
use App\Services\HR\ReimbursementService;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Group('Reimbursement')]
class ReimbursementController extends Controller
{
    public function __construct(
        protected ReimbursementService $reimbursementService,
    ) {}

    #[Endpoint(title: 'Create Reimbursement', description: 'Submit reimbursement request with receipt and expense details. Flow: Reimbursement (Step 1/2) → Approval.')]
    #[BodyParameter(name: 'category_id', description: 'Reimbursement category ID', required: true, type: 'integer')]
    #[BodyParameter(name: 'title', description: 'Reimbursement title', required: false, type: 'string')]
    #[BodyParameter(name: 'amount', description: 'Amount in IDR', required: true, type: 'integer')]
    #[BodyParameter(name: 'description', description: 'Expense description (min 10 chars)', required: true, type: 'string')]
    #[BodyParameter(name: 'expense_date', description: 'Expense date (Y-m-d, today or past)', required: true, type: 'string', format: 'date')]
    #[BodyParameter(name: 'receipt', description: 'Receipt file (jpg/jpeg/png/pdf, max 5MB)', required: true, type: 'string', format: 'binary')]
    public function store(StoreReimbursementRequest $request): JsonResponse
    {
        $this->authorize('create', Reimbursement::class);

        $data = $request->validated();

        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda belum terhubung dengan data karyawan.',
            ], 404);
        }

        if ($request->hasFile('receipt')) {
            $data['receipt'] = $request->file('receipt');
        }

        try {
            $reimbursement = $this->reimbursementService->createReimbursement($employee, $data);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan reimbursement berhasil dikirim',
            'data' => ReimbursementResource::make($reimbursement->fresh(['approvals.approver:id,full_name', 'category:id,name']))->resolve($request),
        ], 201);
    }

    #[Endpoint(title: 'Update Reimbursement', description: 'Update pending reimbursement request. Flow: Reimbursement (edit).')]
    public function update(UpdateReimbursementRequest $request, Reimbursement $reimbursement): JsonResponse
    {
        $this->authorize('update', $reimbursement);

        $data = $request->validated();

        if ($request->hasFile('receipt')) {
            $data['receipt'] = $request->file('receipt');
        }

        try {
            $reimbursement = $this->reimbursementService->updateReimbursement($reimbursement, $data);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan reimbursement berhasil diperbarui',
            'data' => ReimbursementResource::make($reimbursement->load(['approvals.approver:id,full_name', 'category:id,name']))->resolve($request),
        ]);
    }

    #[Endpoint(title: 'List Reimbursements', description: 'Paginated reimbursement list with status/period filters. Flow: Reimbursement (history).')]
    #[QueryParameter(name: 'status', description: 'Filter by status', type: 'string')]
    #[QueryParameter(name: 'period', description: 'Filter by period (YYYY-MM)', type: 'string')]
    #[QueryParameter(name: 'page', description: 'Page number', type: 'integer')]
    #[QueryParameter(name: 'per_page', description: 'Items per page (max 100)', type: 'integer')]
    public function index(ListReimbursementRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Reimbursement::class);

        $user = $request->user();
        $perPage = (int) $request->input('per_page', 20);

        $query = Reimbursement::with('employee:id,employee_number,full_name', 'category:id,name')
            ->orderBy('created_at', 'desc');

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
            'data' => ReimbursementResource::collection($paginated->getCollection())->resolve($request),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    #[Endpoint(title: 'Get Reimbursement', description: 'Get reimbursement detail with approvals. Flow: Reimbursement (detail).')]
    public function show(Request $request, Reimbursement $reimbursement): JsonResponse
    {
        $this->authorize('view', $reimbursement);

        return response()->json([
            'status' => 'success',
            'data' => ReimbursementResource::make($reimbursement->load(['approvals.approver:id,full_name', 'category:id,name']))->resolve($request),
        ]);
    }

    #[Endpoint(title: 'Cancel Reimbursement', description: 'Delete pending reimbursement (soft delete). Flow: Reimbursement (cancel).')]
    public function destroy(Request $request, Reimbursement $reimbursement): JsonResponse
    {
        $this->authorize('delete', $reimbursement);

        $reimbursement->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan reimbursement berhasil dibatalkan',
        ]);
    }

    #[Endpoint(title: 'Download Receipt', description: 'Download reimbursement receipt file.')]
    public function receipt(Request $request, Reimbursement $reimbursement): StreamedResponse|JsonResponse
    {
        $this->authorize('view', $reimbursement);

        if (! $reimbursement->receipt_file || ! Storage::disk('local')->exists($reimbursement->receipt_file)) {
            return response()->json([
                'status' => 'error',
                'message' => 'File bukti tidak ditemukan.',
            ], 404);
        }

        return Storage::disk('local')->download($reimbursement->receipt_file);
    }

    #[Endpoint(title: 'List Reimbursement Categories', description: 'Get active reimbursement categories.')]
    public function categories(): JsonResponse
    {
        $categories = ReimbursementCategory::where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => ReimbursementCategoryResource::collection($categories),
        ]);
    }
}
