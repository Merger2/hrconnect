<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveController extends Controller
{
    public function index(Request $request): View
    {
        return view('leaves.index');
    }

    public function apply(Request $request): View
    {
        $user = $request->user();
        $employee = $user->employee;

        $leaveTypes = LeaveType::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $balances = collect();
        if ($employee) {
            $balances = LeaveBalance::with('leaveType')
                ->where('employee_id', $employee->id)
                ->where('year', now()->year)
                ->get();
        }

        return view('leaves.apply', [
            'leaveTypes' => $leaveTypes,
            'balances' => $balances,
            'employee' => $employee,
        ]);
    }
}
