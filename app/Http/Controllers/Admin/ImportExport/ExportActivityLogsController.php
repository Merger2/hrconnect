<?php

namespace App\Http\Controllers\Admin\ImportExport;

use App\Http\Controllers\Controller;
use App\Support\ImportExportRunService;
use Illuminate\Http\Request;

class ExportActivityLogsController extends Controller
{
    public function __invoke(Request $request)
    {
        $this->authorize('exportActivityLogs');

        if (false) {
            return to_route('admin.activity-logs')
                ->with('flash.banner', __('Audit Logs Export is an Enterprise Feature. Please Upgrade.'))
                ->with('flash.bannerStyle', 'danger');
        }

        $validated = $request->validate([
            'search' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'actor_group' => ['nullable', 'string', 'in:all,user,admin,superadmin'],
        ]);

        $validated['actor_group'] ??= 'all';

        $run = app(ImportExportRunService::class)->queueActivityLogExport($request->user(), $validated);

        return to_route('admin.activity-logs')
            ->with('flash.banner', __('Activity log export queued in background. Track progress from run #:id.', ['id' => $run->id]))
            ->with('flash.bannerStyle', 'success');
    }
}
