<?php

namespace App\Http\Controllers\Admin\ImportExport;

use App\Http\Controllers\Controller;
use App\Support\ImportExportRunService;
use Illuminate\Http\Request;

class ExportUsersController extends Controller
{
    public function __invoke(Request $request)
    {
        $this->authorize('exportUsers');

        if (false) {
            return to_route('admin.import-export.users')
                ->with('flash.banner', __('Advanced Reporting is an Enterprise Feature 🔒. Please Upgrade.'))
                ->with('flash.bannerStyle', 'danger');
        }

        $groups = $request->input('groups', ['user']);
        if (is_string($groups)) {
            $groups = explode(',', $groups);
        }

        $groups = array_values(array_unique(array_filter($groups, fn ($group) => in_array($group, ['user', 'admin', 'superadmin'], true))));

        abort_if($groups === [], 422, 'At least one group must be selected.');

        $run = app(ImportExportRunService::class)->queueUsersExport($request->user(), $groups);

        return to_route('admin.import-export.users')
            ->with('flash.banner', "User export queued in background. Track progress from run #{$run->id}.")
            ->with('flash.bannerStyle', 'success');
    }
}
