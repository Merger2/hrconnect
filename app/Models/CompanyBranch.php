<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperCompanyBranch
 */
#[Fillable(['company_id', 'name', 'code', 'type', 'address', 'status'])]
class CompanyBranch extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const TYPE_BRANCH = 'branch';

    public const TYPE_STORE = 'store';

    public const TYPE_OFFICE = 'office';

    public const TYPE_WAREHOUSE = 'warehouse';

    public const TYPE_SITE = 'site';

    protected function casts(): array
    {
        return [];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
