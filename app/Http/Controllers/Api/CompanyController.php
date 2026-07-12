<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Company')]
class CompanyController extends Controller
{
    #[Endpoint(title: 'List Companies', description: 'Paginated company list.')]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Company::class);

        $perPage = min((int) $request->input('per_page', 50), 100);

        $companies = Company::select('id', 'name', 'code', 'phone', 'email', 'website', 'is_active')
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => CompanyResource::collection($companies->getCollection())->resolve($request),
            'meta' => [
                'current_page' => $companies->currentPage(),
                'last_page' => $companies->lastPage(),
                'per_page' => $companies->perPage(),
                'total' => $companies->total(),
            ],
        ]);
    }

    #[Endpoint(title: 'Get Company', description: 'Get company detail with branches.')]
    public function show(Request $request, Company $company): JsonResponse
    {
        $this->authorize('view', $company);

        return response()->json([
            'status' => 'success',
            'data' => CompanyResource::make($company->load('branches'))->resolve($request),
        ]);
    }
}
