<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class ActiveSessionGuard
{
    public function hasActiveSession(User $user): bool
    {
        return DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('last_activity', '>=', now()->subMinutes(config('session.lifetime', 120))->timestamp)
            ->exists();
    }
}
