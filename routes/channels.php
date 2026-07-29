<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel(
    'App.Models.User.{user}',
    fn (User $authenticatedUser, User $user): bool => $authenticatedUser->is($user),
);

Broadcast::channel('collaboration.company.{companyId}', function (User $user, int $companyId): bool {
    if (! $user->can('viewCollaborationWorkspace')) {
        return false;
    }

    return $user->isSuperadmin
        || $user->company_id === null
        || (int) $user->company_id === $companyId;
});
