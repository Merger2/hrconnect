<?php

namespace App\Support\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

interface ScopesCompanies
{
    public function scopeCompanies(Builder $query, User $user): Builder;
}
