<?php

namespace App\Policies;

use App\Models\CompanyAsset;
use App\Models\User;
use App\Support\MultiCompanyService;

class CompanyAssetPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isSuperadmin ? true : null;
    }

    public function __construct(
        private readonly MultiCompanyService $multiCompany,
    ) {}

    public function viewAny(User $user): bool
    {
        return ! false;
    }

    public function viewAdminAny(User $user): bool
    {
        return ! false && $user->can('view_assets');
    }

    public function view(User $user, CompanyAsset $companyAsset): bool
    {
        if (! $this->sameCompany($user, $companyAsset)) {
            return false;
        }

        return ! false
            && ($user->can('view_assets') || $companyAsset->user_id === $user->id);
    }

    public function returnAsset(User $user, CompanyAsset $companyAsset): bool
    {
        if (! $this->sameCompany($user, $companyAsset)) {
            return false;
        }

        return ! false
            && $companyAsset->user_id === $user->id
            && $companyAsset->status === 'assigned';
    }

    protected function sameCompany(User $actor, CompanyAsset $companyAsset): bool
    {
        if ($companyAsset->user_id === null) {
            return true;
        }

        $companyAsset->loadMissing('user');

        return $companyAsset->user !== null
            && $this->multiCompany->canAccessUser($actor, $companyAsset->user);
    }
}
