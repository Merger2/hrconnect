<?php

namespace App\Services;

use App\Models\Appraisal;
use App\Models\AppraisalEvaluation;
use App\Models\KpiTemplate;
use App\Models\User;

class AppraisalService
{
    public function initAppraisal(User $user, int $periodMonth, int $periodYear): void
    {
        $appraisal = Appraisal::query()
            ->where('user_id', $user->id)
            ->where('period', $periodYear.'-'.str_pad((string) $periodMonth, 2, '0', STR_PAD_LEFT))
            ->first();

        if (! $appraisal) {
            return;
        }

        $existingTemplateIds = AppraisalEvaluation::query()
            ->where('appraisal_id', $appraisal->id)
            ->pluck('kpi_template_id')
            ->toArray();

        $templates = KpiTemplate::query()
            ->where('is_active', true)
            ->whereNotIn('id', $existingTemplateIds)
            ->get();

        foreach ($templates as $template) {
            AppraisalEvaluation::query()->create([
                'appraisal_id' => $appraisal->id,
                'kpi_template_id' => $template->id,
            ]);
        }
    }
}
