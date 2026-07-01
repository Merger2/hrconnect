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
        $stats = Cache::flexible('dashboard.stats.'.$user->id, [60, 120], function () use ($user) {
            $data = [];

            if ($user->hasRole(['super-admin', 'hr-manager', 'manager', 'finance'])) {
                $base = Employee::query();

                if ($user->employee && ! $user->hasRole(['super-admin', 'hr-manager'])) {
                    $base = $base->where('company_id', $user->employee->company_id);
                }

                $data['total_employees'] = (clone $base)->count();
                $data['active_employees'] = (clone $base)->where('status', 'active')->count();
                $data['pending_approvals'] = Reimbursement::where('status', ReimbursementStatus::PENDING)->count()
                    + Leave::where('status', 'pending')->count();
            } else {
                $employee = $user->employee;
                $data['total_employees'] = 1;
                $data['active_employees'] = $employee?->status === 'active' ? 1 : 0;
                $data['pending_approvals'] = $employee
                    ? Leave::where('employee_id', $employee->id)->where('status', 'pending')->count()
                    : 0;
            }

            return $data;
        });

        return view('dashboard', $stats);
    }
}
