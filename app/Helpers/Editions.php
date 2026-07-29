<?php

namespace App\Helpers;

class Editions
{
    public static function attendanceLocked(): bool
    {
        return false;
    }

    public static function payrollLocked(): bool
    {
        return false;
    }

    public static function assetLocked(): bool
    {
        return false;
    }

    public static function documentRequestsLocked(): bool
    {
        return false;
    }

    public static function appraisalLocked(): bool
    {
        return false;
    }
}
