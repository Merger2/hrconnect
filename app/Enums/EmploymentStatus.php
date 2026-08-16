<?php

namespace App\Enums;

enum EmploymentStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case RESIGNED = 'resigned';
    case TERMINATED = 'terminated';
    case DECEASED = 'deceased';
    case DELETION_REQUESTED = 'deletion_requested';
    case DELETED = 'deleted';
    case ONBOARDING = 'onboarding';
    case SUSPENDED = 'suspended';
}
