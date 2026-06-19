<?php

namespace App\Observers;

use App\Models\CompanySetting;
use Illuminate\Support\Facades\Cache;

class CompanySettingObserver
{
    public function saved(CompanySetting $companySetting): void
    {
        Cache::forget("settings:{$companySetting->key}");
    }

    public function deleted(CompanySetting $companySetting): void
    {
        Cache::forget("settings:{$companySetting->key}");
    }
}
