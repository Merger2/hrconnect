<?php

namespace App\Support\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait ScopesCompaniesByActor
{
    public function scopeCompanies(Builder $query, User $user): Builder
    {
        if ($user->isSuperadmin) {
            return $query;
        }

        return $query->where('id', $user->company_id);
    }
}
