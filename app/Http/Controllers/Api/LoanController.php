<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ListLoanRequest;
use App\Http\Requests\Api\StoreLoanRequest;
use App\Http\Requests\Api\UpdateLoanRequest;
use App\Http\Resources\LoanResource;
use App\Models\Loan;
use App\Services\LoanService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Loan')]
class LoanController extends Controller
{
    public function __construct(
        protected LoanService $loanService,
    ) {}

    #[Endpoint(title: 'List Loans', description: 'Paginated loan list with status filter.')]
    #[QueryParameter(name: 'status', description: 'Filter by status', type: 'string')]
    public function index(ListLoanRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Loan::class);

        $user = $request->user();
        $perPage = (int) $request->input('per_page', 20);

        $query = Loan::with('employee:id,employee_number,full_name')
            ->orderBy('created_at', 'desc');

        if (! $user->hasRole(['super-admin', 'hr-manager', 'finance'])) {
            if ($user->employee) {
                $query->where('employee_id', $user->employee->id);
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $paginated = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => LoanResource::collection($paginated->getCollection())->resolve($request),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    #[Endpoint(title: 'Create Loan', description: 'Submit loan application. Flow: Loan → Approval.')]
    public function store(StoreLoanRequest $request): JsonResponse
    {
        $this->authorize('create', Loan::class);

        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda belum terhubung dengan data karyawan.',
            ], 404);
        }

        try {
            $loan = $this->loanService->createLoan($employee, $request->validated());
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan pinjaman berhasil dikirim',
            'data' => LoanResource::make($loan)->resolve($request),
        ], 201);
    }

    #[Endpoint(title: 'Get Loan', description: 'Get loan detail with installments.')]
    public function show(Request $request, Loan $loan): JsonResponse
    {
        $this->authorize('view', $loan);

        return response()->json([
            'status' => 'success',
            'data' => LoanResource::make($loan->load(['employee:id,employee_number,full_name', 'installments', 'approvals.approver:id,full_name']))->resolve($request),
        ]);
    }

    #[Endpoint(title: 'Update Loan', description: 'Update pending loan application.')]
    public function update(UpdateLoanRequest $request, Loan $loan): JsonResponse
    {
        $this->authorize('update', $loan);

        try {
            $loan = $this->loanService->updateLoan($loan, $request->validated());
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan pinjaman berhasil diperbarui',
            'data' => LoanResource::make($loan->load(['employee:id,employee_number,full_name']))->resolve($request),
        ]);
    }

    #[Endpoint(title: 'Cancel Loan', description: 'Cancel pending or approved loan.')]
    public function destroy(Request $request, Loan $loan): JsonResponse
    {
        $this->authorize('delete', $loan);

        try {
            $this->loanService->cancelLoan($loan);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Pinjaman berhasil dibatalkan',
        ]);
    }
}
