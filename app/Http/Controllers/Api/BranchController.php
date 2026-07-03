<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use App\Models\Company;
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

        $perPage = min((int) $request->input('per_page', 50), 100);
        $search = $request->input('search');

        $branches = Branch::with('company:id,name')
            ->when($search, fn ($q) => $q->where('name', 'ilike', "%{$search}%"))
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

    #[Endpoint(title: 'Create Branch', description: 'Create a new branch.')]
    public function store(Request $request): JsonResponse
    {
        $this->authorize('manage_branches');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|min:-90|max:90',
            'longitude' => 'nullable|numeric|min:-180|max:180',
            'radius' => 'nullable|integer|min:10|max:5000',
        ]);

        $branch = Branch::create($validated + [
            'company_id' => $request->input('company_id') ?? Company::first()?->id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => __('Cabang berhasil ditambahkan.'),
            'data' => BranchResource::make($branch)->resolve($request),
        ], 201);
    }

    #[Endpoint(title: 'Update Branch', description: 'Update an existing branch.')]
    public function update(Request $request, Branch $branch): JsonResponse
    {
        $this->authorize('manage_branches');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|min:-90|max:90',
            'longitude' => 'nullable|numeric|min:-180|max:180',
            'radius' => 'nullable|integer|min:10|max:5000',
        ]);

        $branch->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => __('Cabang berhasil diperbarui.'),
            'data' => BranchResource::make($branch)->resolve($request),
        ]);
    }

    #[Endpoint(title: 'Delete Branch', description: 'Delete a branch.')]
    public function destroy(Request $request, Branch $branch): JsonResponse
    {
        $this->authorize('manage_branches');

        $branch->delete();

        return response()->json([
            'status' => 'success',
            'message' => __('Cabang berhasil dihapus.'),
        ]);
    }
}
