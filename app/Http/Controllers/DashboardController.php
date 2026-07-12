<?php

namespace App\Http\Controllers;

use App\Enums\ApprovalStatus;
use App\Enums\PayrollStatus;
use App\Enums\ReimbursementStatus;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Overtime;
use App\Models\Payroll;
use App\Models\Reimbursement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $data = Cache::flexible('dashboard.stats.'.$user->id, [60, 120], function () use ($user) {
            $role = $user->roles->first()?->name;

            return match ($role) {
                'super-admin', 'hr-manager' => $this->hrStats($user),
                'manager' => $this->managerStats($user),
                'finance' => $this->financeStats($user),
                default => $this->employeeStats($user),
            };
        });

        return view('dashboard', $data);
    }

    private function hrStats($user): array
    {
        $base = Employee::query();
        if ($user->employee && ! $user->hasRole('super-admin')) {
            $base = $base->where('company_id', $user->employee->company_id);
        }

        return [
            'role' => 'hr',
            'total_employees' => (clone $base)->count(),
            'active_employees' => (clone $base)->where('status', 'active')->count(),
            'pending_approvals' => Leave::where('status', ApprovalStatus::PENDING)->count()
                + Reimbursement::where('status', ReimbursementStatus::PENDING)->count()
                + Overtime::where('status', ApprovalStatus::PENDING)->count(),
            'hadir_hari_ini' => Attendance::whereDate('clock_in', today())->count(),
        ];
    }

    private function managerStats($user): array
    {
        $employee = $user->employee;
        $teamIds = $employee ? Employee::where('parent_id', $employee->id)->pluck('id') : collect();

        return [
            'role' => 'manager',
            'team_size' => $teamIds->count(),
            'team_pending' => Leave::whereIn('employee_id', $teamIds)->where('status', ApprovalStatus::PENDING)->count()
                + Reimbursement::whereIn('employee_id', $teamIds)->where('status', ReimbursementStatus::PENDING)->count()
                + Overtime::whereIn('employee_id', $teamIds)->where('status', ApprovalStatus::PENDING)->count(),
            'cuti_anda' => $employee ? Leave::where('employee_id', $employee->id)->where('status', ApprovalStatus::PENDING)->count() : 0,
            'hadir_hari_ini' => $employee && Attendance::where('employee_id', $employee->id)->whereDate('clock_in', today())->exists(),
        ];
    }

    private function financeStats($user): array
    {
        return [
            'role' => 'finance',
            'pending_reimbursements' => Reimbursement::where('status', ReimbursementStatus::PENDING)->count(),
            'pending_payrolls' => Payroll::where('status', PayrollStatus::DRAFT)->count(),
            'cuti_anda' => $user->employee ? Leave::where('employee_id', $user->employee->id)->where('status', ApprovalStatus::PENDING)->count() : 0,
            'hadir_hari_ini' => $user->employee && Attendance::where('employee_id', $user->employee->id)->whereDate('clock_in', today())->exists(),
        ];
    }

    private function employeeStats($user): array
    {
        $employee = $user->employee;

        return [
            'role' => 'employee',
            'hadir_hari_ini' => $employee && Attendance::where('employee_id', $employee->id)->whereDate('clock_in', today())->exists(),
            'cuti_anda' => $employee ? Leave::where('employee_id', $employee->id)->where('status', ApprovalStatus::PENDING)->count() : 0,
            'pengajuan_anda' => $employee ? Reimbursement::where('employee_id', $employee->id)->where('status', ReimbursementStatus::PENDING)->count() : 0,
        ];
    }
}
