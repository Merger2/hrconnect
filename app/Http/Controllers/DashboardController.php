<?php

namespace App\Http\Controllers;

use App\Enums\ReimbursementStatus;
use App\Models\Attendance;
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
        $isAdmin = $user->hasRole(['super-admin', 'hr-manager', 'manager', 'finance']);
        $stats = Cache::flexible('dashboard.stats.'.$user->id, [60, 120], function () use ($user, $isAdmin) {
            $data = ['is_admin' => $isAdmin];

            if ($isAdmin) {
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

                if ($employee) {
                    $today = now()->toDateString();
                    $data['hadir_hari_ini'] = Attendance::where('employee_id', $employee->id)
                        ->whereDate('clock_in', $today)->exists();
                    $data['cuti_anda'] = Leave::where('employee_id', $employee->id)
                        ->where('status', 'pending')->count();
                    $data['pengajuan_anda'] = Reimbursement::where('employee_id', $employee->id)
                        ->where('status', ReimbursementStatus::PENDING)->count();
                } else {
                    $data['hadir_hari_ini'] = false;
                    $data['cuti_anda'] = 0;
                    $data['pengajuan_anda'] = 0;
                }
            }

            return $data;
        });

        return view('dashboard', $stats);
    }
}
