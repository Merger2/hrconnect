<?php

namespace App\Support;

use App\Models\Company;

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
        return [
            'open_value' => 0.0,
            'weighted_value' => 0.0,
            'overdue_follow_ups' => 0,
        ];
    }
}
