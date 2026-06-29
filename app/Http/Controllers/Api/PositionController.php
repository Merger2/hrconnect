<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PositionResource;
use App\Models\Position;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Position')]
class PositionController extends Controller
{
    #[Endpoint(title: 'List Positions', description: 'Paginated position list.')]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Position::class);

        $perPage = (int) $request->input('per_page', 50);

        $positions = Position::with('department:id,name')
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => PositionResource::collection($positions->getCollection())->resolve($request),
            'meta' => [
                'current_page' => $positions->currentPage(),
                'last_page' => $positions->lastPage(),
                'per_page' => $positions->perPage(),
                'total' => $positions->total(),
            ],
        ]);
    }

    #[Endpoint(title: 'Get Position', description: 'Get position detail.')]
    public function show(Request $request, Position $position): JsonResponse
    {
        $this->authorize('view', $position);

        return response()->json([
            'status' => 'success',
            'data' => PositionResource::make($position->load('department:id,name'))->resolve($request),
        ]);
    }
}
