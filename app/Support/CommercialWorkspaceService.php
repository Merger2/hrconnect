<?php

namespace App\Support;

use App\Models\SalesOpportunity;

class CommercialWorkspaceService
{
    /**
     * Get sales summary for given company IDs.
     *
     * @param  list<int>  $companyIds
     * @return array{open_value:float,weighted_value:float,overdue_follow_ups:int}
     */
    public function salesSummaryForCompanies(array $companyIds): array
    {
        if ($companyIds === []) {
            return [
                'open_value' => 0.0,
                'weighted_value' => 0.0,
                'overdue_follow_ups' => 0,
            ];
        }

        $activeStages = [
            SalesOpportunity::STAGE_LEAD,
            SalesOpportunity::STAGE_QUALIFIED,
            SalesOpportunity::STAGE_PROPOSAL,
        ];

        $row = SalesOpportunity::query()
            ->whereIn('company_id', $companyIds)
            ->whereIn('stage', $activeStages)
            ->selectRaw('COALESCE(SUM(expected_value), 0) as open_value')
            ->selectRaw('COALESCE(SUM(expected_value * probability / 100), 0) as weighted_value')
            ->selectRaw('COALESCE(SUM(CASE WHEN follow_up_at IS NOT NULL AND follow_up_at < ? THEN 1 ELSE 0 END), 0) as overdue_follow_ups', [now()->toDateString()])
            ->first();

        return [
            'open_value' => round((float) ($row?->open_value ?? 0), 2),
            'weighted_value' => round((float) ($row?->weighted_value ?? 0), 2),
            'overdue_follow_ups' => (int) ($row?->overdue_follow_ups ?? 0),
        ];
    }
}
