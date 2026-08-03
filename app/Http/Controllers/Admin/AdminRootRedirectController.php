<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminRootRedirectController extends Controller
{
    /**
     * Ordered candidate admin pages with the gate required to access them.
     * Falls back to the first page the signed-in admin is actually permitted
     * to open, so limited (role-scoped) admins are not dumped on a 403.
     */
    private const CANDIDATES = [
        ['gate' => 'viewAdminDashboard', 'route' => 'admin.dashboard'],
        ['gate' => 'manageAdminNotifications', 'route' => 'admin.notifications'],
        ['gate' => 'manageRbac', 'route' => 'admin.roles.permissions'],
        ['gate' => 'viewEmployees', 'route' => 'admin.employees'],
        ['gate' => 'manageCashAdvances', 'route' => 'admin.manage-kasbon'],
        ['gate' => 'viewAdminAppraisals', 'route' => 'admin.appraisals'],
    ];

    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        foreach (self::CANDIDATES as $candidate) {
            if ($user?->can($candidate['gate'])) {
                return redirect()->route($candidate['route']);
            }
        }

        return redirect()->route($user?->preferredAdminRouteName() ?? 'home');
    }
}
