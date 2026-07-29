<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DivisionResource;
use App\Models\Division;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Division')]
class DivisionController extends Controller
{
    #[Endpoint(title: 'List Divisions', description: 'Paginated division list.')]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Division::class);

        $perPage = (int) $request->input('per_page', 50);

        $divisions = Division::with('branch:id,name')
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => DivisionResource::collection($divisions->getCollection())->resolve($request),
            'meta' => [
                'current_page' => $divisions->currentPage(),
                'last_page' => $divisions->lastPage(),
                'per_page' => $divisions->perPage(),
                'total' => $divisions->total(),
            ],
        ]);
    }

    #[Endpoint(title: 'Get Division', description: 'Get division detail.')]
    public function show(Request $request, Division $division): JsonResponse
    {
        $this->authorize('view', $division);

        return response()->json([
            'status' => 'success',
            'data' => DivisionResource::make($division->load('branch:id,name'))->resolve($request),
        ]);
    }
}
