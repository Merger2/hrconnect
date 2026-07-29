<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Exports\PayrollWorkbookExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportPayrollReportController extends Controller
{
    public function __invoke(Request $request)
    {
        $this->authorize('view_payrolls');

        $validated = $request->validate([
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'status' => ['nullable', 'in:all,draft,approved,paid'],
            'division' => ['nullable', 'integer', 'exists:divisions,id'],
            'job_title' => ['nullable', 'integer', 'exists:job_titles,id'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $filename = 'payroll-summary-report-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(new PayrollWorkbookExport($request->user(), $validated), $filename);
    }
}
