<?php

namespace App\Enums;

enum EmploymentStatus: string
{
    case ACTIVE = 'active';
    case RESIGNED = 'resigned';
    case DELETION_REQUESTED = 'deletion_requested';
    case DELETED = 'deleted';
    case ONBOARDING = 'onboarding';
    case SUSPENDED = 'suspended';
    case TERMINATED = 'terminated';
}
