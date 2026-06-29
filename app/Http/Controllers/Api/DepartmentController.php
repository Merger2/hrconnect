<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Department')]
class DepartmentController extends Controller
{
    #[Endpoint(title: 'List Departments', description: 'Paginated department list.')]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Department::class);

        $perPage = (int) $request->input('per_page', 50);

        $departments = Department::with('branch:id,name')
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => DepartmentResource::collection($departments->getCollection())->resolve($request),
            'meta' => [
                'current_page' => $departments->currentPage(),
                'last_page' => $departments->lastPage(),
                'per_page' => $departments->perPage(),
                'total' => $departments->total(),
            ],
        ]);
    }

    #[Endpoint(title: 'Get Department', description: 'Get department detail.')]
    public function show(Request $request, Department $department): JsonResponse
    {
        $this->authorize('view', $department);

        return response()->json([
            'status' => 'success',
            'data' => DepartmentResource::make($department->load('branch:id,name'))->resolve($request),
        ]);
    }
}
