<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\HandoverAssetRequest;
use App\Http\Requests\Api\ListAssetRequest;
use App\Http\Requests\Api\ReturnAssetRequest;
use App\Http\Requests\Api\StoreAssetRequest;
use App\Http\Requests\Api\UpdateAssetRequest;
use App\Http\Resources\AssetHandoverResource;
use App\Http\Resources\AssetResource;
use App\Models\Asset;
use App\Models\AssetHandover;
use App\Models\Employee;
use App\Services\AssetService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

#[Group('Asset')]
class AssetController extends Controller
{
    public function __construct(
        protected AssetService $assetService,
    ) {}

    #[Endpoint(title: 'List Assets', description: 'Paginated asset list with status/category/search filters.')]
    #[QueryParameter(name: 'status', description: 'Filter by status', type: 'string')]
    #[QueryParameter(name: 'category', description: 'Filter by category', type: 'string')]
    #[QueryParameter(name: 'search', description: 'Search by name or serial number', type: 'string')]
    public function index(ListAssetRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Asset::class);

        $perPage = (int) $request->input('per_page', 20);

        $query = Asset::orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $likeOp = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
                $q->where('name', $likeOp, "%{$search}%")
                    ->orWhere('serial_number', $likeOp, "%{$search}%")
                    ->orWhere('code', $likeOp, "%{$search}%");
            });
        }

        $paginated = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => AssetResource::collection($paginated->getCollection())->resolve($request),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    #[Endpoint(title: 'Create Asset', description: 'Add new company asset.')]
    public function store(StoreAssetRequest $request): JsonResponse
    {
        $this->authorize('create', Asset::class);

        try {
            $asset = $this->assetService->createAsset($request->validated());
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Aset berhasil ditambahkan',
            'data' => AssetResource::make($asset)->resolve($request),
        ], 201);
    }

    #[Endpoint(title: 'Get Asset', description: 'Get asset detail with handover history.')]
    public function show(Request $request, Asset $asset): JsonResponse
    {
        $this->authorize('view', $asset);

        return response()->json([
            'status' => 'success',
            'data' => AssetResource::make($asset->load(['handovers.employee:id,employee_number,full_name']))->resolve($request),
        ]);
    }

    #[Endpoint(title: 'Update Asset', description: 'Update asset details.')]
    public function update(UpdateAssetRequest $request, Asset $asset): JsonResponse
    {
        $this->authorize('update', $asset);

        try {
            $asset = $this->assetService->updateAsset($asset, $request->validated());
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Aset berhasil diperbarui',
            'data' => AssetResource::make($asset)->resolve($request),
        ]);
    }

    #[Endpoint(title: 'Delete Asset', description: 'Delete asset (soft).')]
    public function destroy(Request $request, Asset $asset): JsonResponse
    {
        $this->authorize('delete', $asset);

        try {
            $this->assetService->deleteAsset($asset);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Aset berhasil dihapus',
        ]);
    }

    #[Endpoint(title: 'Handover Asset', description: 'Assign asset to an employee.')]
    public function handover(HandoverAssetRequest $request, Asset $asset): JsonResponse
    {
        $this->authorize('update', $asset);

        $employee = Employee::find($request->input('employee_id'));

        if (! $employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Karyawan tidak ditemukan.',
            ], 404);
        }

        try {
            $handover = $this->assetService->handover($asset, $employee, $request->validated());
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Aset berhasil diserahkan',
            'data' => AssetHandoverResource::make($handover->load(['employee:id,employee_number,full_name']))->resolve($request),
        ], 201);
    }

    #[Endpoint(title: 'Return Asset', description: 'Return asset from employee handover.')]
    public function return(ReturnAssetRequest $request, AssetHandover $handover): JsonResponse
    {
        $this->authorize('update', $handover->asset);

        try {
            $handover = $this->assetService->returnAsset($handover, $request->validated());
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Aset berhasil dikembalikan',
            'data' => AssetHandoverResource::make($handover)->resolve($request),
        ]);
    }
}
