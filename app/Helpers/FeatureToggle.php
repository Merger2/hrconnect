<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class FeatureToggle
{
    /**
     * Determine if a feature is enabled or accessible for the current user.
     *
     * This method checks against permissions and/or configuration.
     *
     * @param  string  $featureName  The name of the feature (e.g., 'reporting', 'payroll').
     * @param  User|null  $user  The user to check permissions for, defaults to authenticated user.
     */
    public static function isEnabled(string $featureName, ?User $user = null): bool
    {
        $user = $user ?? Auth::user();

        if (! $user) {
            return false;
        }

        // --- Core Logic: Check Permissions ---
        // This is the primary mechanism for controlling feature visibility.
        // We map feature names to specific permissions.

        $permissionMap = [
            'reporting' => 'viewOperationalReports',
            'payroll' => 'view_payrolls',
            'cash_advance' => 'manage_cash_advances',
            'analytics' => 'view_analytics_dashboard',
            'appraisal' => 'view_admin_appraisals',
            'assets' => 'view_assets',
            'document_requests' => 'view_admin_document_requests',
            // Add more features and their corresponding permissions here
        ];

        $permission = $permissionMap[$featureName] ?? null;

        if ($permission && Gate::allows($permission, $user)) {
            return true;
        }

        // --- Fallback Logic: Check Configuration (if no specific permission) ---
        // This can be used for features that are simply toggled on/off application-wide.
        // Example: config('hrconnect.features.reporting_enabled')

        // For now, if no specific permission is found or allowed, and no config fallback,
        // we'll consider it disabled.
        return false;
    }

    /**
     * Determine if a feature is disabled or not accessible.
     * This is the inverse of isEnabled().
     */
    public static function isDisabled(string $featureName, ?User $user = null): bool
    {
        return ! static::isEnabled($featureName, $user);
    }
}
