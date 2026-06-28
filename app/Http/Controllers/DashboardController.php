<?php

namespace App\Http\Controllers;

use App\Enums\ReimbursementStatus;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Reimbursement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;

        $stats = Cache::flexible('dashboard.stats', [60, 120], function () use ($employee) {
            $base = Employee::query();

            if ($employee && ! $user->hasRole(['super-admin', 'hr-manager'])) {
                $base = $base->where('company_id', $employee->company_id);
            }

            return [
                'total_employees' => (clone $base)->count(),
                'active_employees' => (clone $base)->where('status', 'active')->count(),
                'pending_approvals' => Reimbursement::where('status', ReimbursementStatus::PENDING)->count()
                    + Leave::where('status', 'pending')->count(),
            ];
        });

        return view('dashboard', $stats);
    }
}
