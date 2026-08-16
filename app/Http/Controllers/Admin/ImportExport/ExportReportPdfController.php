<?php

namespace App\Http\Controllers\Admin\ImportExport;

use App\Http\Controllers\Controller;
use App\Support\ImportExportRunService;
use Illuminate\Http\Request;

class ExportReportPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $this->authorize('exportAdminReports');

        if (false) {
            return to_route('admin.dashboard')
                ->with('flash.banner', __('Advanced Reporting is an Enterprise Feature 🔒. Please Upgrade.'))
                ->with('flash.bannerStyle', 'danger');
        }

        $validated = $request->validate([
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
        ]);

        // ⚠️ JANGAN tertukar: signature service adalah (year, month). Regresi
        // 2026-08-16: controller mengirim (month, year) → meta run tahun=8
        // bulan=2026, report salah periode diam-diam.
        $run = app(ImportExportRunService::class)->queueMonthlyAttendanceReport(
            $request->user(),
            (int) ($validated['year'] ?? now()->year),
            (int) ($validated['month'] ?? now()->month),
        );

        return to_route('admin.dashboard')
            ->with('flash.banner', "Monthly report export queued in background. Track progress from run #{$run->id}.")
            ->with('flash.bannerStyle', 'success');
    }
}
