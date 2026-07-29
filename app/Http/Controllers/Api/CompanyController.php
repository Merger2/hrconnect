<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    /**
     * Get operational hours for the authenticated user's company.
     */
    public function getCompanyOperationalHours(Request $request)
    {
        $user = $request->user();

        if (! $user || ! $user->employee || ! $user->employee->company) {
            return ApiResponse::format(false, 404, 'Company not found for this user.', null);
        }

        $company = $user->employee->company;

        return ApiResponse::format(true, 200, 'Operational hours retrieved successfully.', [
            'company_name' => $company->name,
            'working_hours' => [
                'start_time' => $company->work_start_time ?? $company->office_hour_start ?? null,
                'end_time' => $company->work_end_time ?? $company->office_hour_end ?? null,
            ],
        ]);
    }

    /**
     * Get branches for the authenticated user's company, including coordinates and radius.
     */
    public function getCompanyBranches(Request $request)
    {
        $user = $request->user();

        if (! $user || ! $user->employee || ! $user->employee->company) {
            return ApiResponse::format(false, 404, 'Company not found for this user.', null);
        }

        $company = $user->employee->company;
        $branches = Branch::where('company_id', $company->id)
            ->orderBy('name')
            ->get(['id', 'name', 'address', 'latitude', 'longitude', 'radius_meters']);

        if ($branches->isEmpty()) {
            return ApiResponse::format(false, 404, 'No branches found for this company.', null);
        }

        $data = $branches->map(function ($b) {
            return [
                'branch_id' => $b->id,
                'name' => $b->name,
                'address' => $b->address,
                'latitude' => (float) $b->latitude,
                'longitude' => (float) $b->longitude,
                'radius_meters' => (float) $b->radius_meters,
            ];
        });

        return ApiResponse::format(true, 200, 'Company branches retrieved successfully.', [
            'company' => [
                'company_id' => $company->id,
                'name' => $company->name,
            ],
            'branches' => $data,
        ]);
    }
}
