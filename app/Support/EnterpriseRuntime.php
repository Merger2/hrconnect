<?php

namespace App\Support;

/**
 * Detects whether enterprise-only runtime components are present in the
 * current build. Tests use this to skip enterprise-specific assertions when
 * the underlying Livewire component does not exist.
 */
class EnterpriseRuntime
{
    public static function sourceAvailable(?string $probeClass = null): bool
    {
        if ($probeClass === null) {
            return true;
        }

        return class_exists($probeClass);
    }
}
