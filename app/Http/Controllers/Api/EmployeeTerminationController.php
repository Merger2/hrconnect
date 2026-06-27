<?php

namespace App\Http\Controllers\Api;

use App\Enums\TerminationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TerminateEmployeeRequest;
use App\Models\Employee;
use App\Services\EmployeeTerminationService;
use Carbon\CarbonImmutable;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Employees')]
class EmployeeTerminationController extends Controller
{
    public function __construct(
        protected EmployeeTerminationService $terminationService,
    ) {}

    #[Endpoint(title: 'Terminate Employee', description: 'Terminate an active employee with reason and effective date. Clears face_embedding, soft-deletes user (except deceased). Flow: Termination (Step 1/2) — Terminate PKWTT → Process Contract Ends.')]
    #[BodyParameter(name: 'type', description: 'Termination type: resign, dismissed, deceased, contract_end', required: true, type: 'string')]
    #[BodyParameter(name: 'reason', description: 'Termination reason', required: false, type: 'string')]
    #[BodyParameter(name: 'date', description: 'Effective termination date (Y-m-d, defaults to today)', required: false, type: 'string', format: 'date')]
    public function terminate(TerminateEmployeeRequest $request, Employee $employee): JsonResponse
    {
        $data = $request->validated();

        $terminated = $this->terminationService->terminate(
            $employee,
            TerminationType::from($data['type']),
            $data['reason'] ?? null,
            isset($data['date']) ? CarbonImmutable::parse($data['date']) : null,
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Karyawan berhasil di-terminasi',
            'data' => [
                'id' => $terminated->id,
                'full_name' => $terminated->full_name,
                'status' => $terminated->status?->value,
                'termination_type' => $terminated->termination_type,
                'termination_reason' => $terminated->termination_reason,
                'resign_date' => $terminated->resign_date?->toDateString(),
                'deceased_date' => $terminated->deceased_date?->toDateString(),
                'face_cleared' => empty($terminated->getRawOriginal('face_embedding')),
                'financial_summary' => $terminated->financial_summary ?? [],
            ],
        ]);
    }

    #[Endpoint(title: 'Process Contract Ends', description: 'Batch-terminate all contractors whose contract_end_date <= reference date. Flow: Termination (Step 2/2) — Terminate PKWTT → Process Contract Ends.')]
    #[BodyParameter(name: 'date', description: 'Reference date (Y-m-d, defaults to today)', required: false, type: 'string', format: 'date')]
    public function processContractEnd(Request $request): JsonResponse
    {
        $request->validate([
            'date' => ['nullable', 'date'],
        ]);

        $user = $request->user();

        if (! $user->can('manage_employees')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses untuk terminasi massal kontrak.',
            ], 403);
        }

        $date = $request->filled('date') ? CarbonImmutable::parse($request->input('date')) : null;
        $count = $this->terminationService->processContractEnd($date);

        return response()->json([
            'status' => 'success',
            'message' => "{$count} karyawan kontrak di-terminasi",
            'data' => [
                'processed_count' => $count,
            ],
        ]);
    }
}
