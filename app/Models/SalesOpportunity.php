<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperSalesOpportunity
 */
#[Fillable(['company_id', 'project_id', 'name', 'stage', 'expected_value', 'probability', 'expected_close_at', 'follow_up_at', 'notes'])]
class SalesOpportunity extends Model
{
    use HasFactory, SoftDeletes;

    public const STAGE_LEAD = 'lead';

    public const STAGE_QUALIFIED = 'qualified';

    public const STAGE_PROPOSAL = 'proposal';

    public const STAGE_NEGOTIATION = 'negotiation';

    public const STAGE_CLOSED_WON = 'closed_won';

    public const STAGE_CLOSED_LOST = 'closed_lost';

    protected function casts(): array
    {
        return [
            'expected_value' => 'decimal:2',
            'probability' => 'decimal:2',
            'expected_close_at' => 'date',
            'follow_up_at' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
