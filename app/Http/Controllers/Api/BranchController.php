<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Branch')]
class BranchController extends Controller
{
    #[Endpoint(title: 'List Branches', description: 'Paginated branch list.')]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Branch::class);

        $perPage = (int) $request->input('per_page', 50);

        $branches = Branch::with('company:id,name')
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => BranchResource::collection($branches->getCollection())->resolve($request),
            'meta' => [
                'current_page' => $branches->currentPage(),
                'last_page' => $branches->lastPage(),
                'per_page' => $branches->perPage(),
                'total' => $branches->total(),
            ],
        ]);
    }

    #[Endpoint(title: 'Get Branch', description: 'Get branch detail.')]
    public function show(Request $request, Branch $branch): JsonResponse
    {
        $this->authorize('view', $branch);

        return response()->json([
            'status' => 'success',
            'data' => BranchResource::make($branch->load('company:id,name'))->resolve($request),
        ]);
    }
}
