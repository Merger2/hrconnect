<?php

namespace App\Actions\Hr;

use App\Models\HrChecklistCase;
use App\Models\User;
use App\Support\HrChecklistService;
use Illuminate\Support\Carbon;

class CreateChecklistCaseForEmployeeStatus
{
    public function __construct(
        protected HrChecklistService $checklists,
    ) {}

    public function handle(User $employee, User $actor, string $status, Carbon|string $effectiveDate): ?HrChecklistCase
    {
        return $this->checklists->createCaseForEmployeeStatus($employee, $actor, $status, $effectiveDate);
    }
}
