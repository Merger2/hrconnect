<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Attendance;
use App\Models\CashAdvance;
use App\Models\EmployeeDocumentRequest;
use App\Models\HrChecklistCase;
use App\Models\Reimbursement;
use App\Models\User;
use App\Support\AdminDashboardQueryService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class AnalyticsDashboard extends Component
{
    use AuthorizesRequests;

    public int $month;

    public int $year;

    public function mount(): void
    {
        $this->month = (int) request()->query('month', now()->month);
        $this->year = (int) request()->query('year', now()->year);
    }

    public function render(): View
    {
        Gate::authorize('viewAnalyticsDashboard');

        $selectedDate = Carbon::createFromDate($this->year, $this->month, 1);
        $admin = auth()->user();
        $employeesCount = User::query()->where('group', 'user')->managedBy($admin)->count();

        $queryService = app(AdminDashboardQueryService::class);

        return view('livewire.admin.analytics-dashboard', [
            'month' => $this->month,
            'year' => $this->year,
            'summary' => $queryService->monthlySummary($admin, $selectedDate, $employeesCount),
            'metrics' => $queryService->monthlyMetrics($admin, $selectedDate),
            'trend' => $queryService->chartData($admin, $selectedDate, 'month_1'),
            'divisionStats' => $this->computeDivisionStats($admin, $selectedDate),
            'lateBuckets' => $this->computeLateBuckets($admin, $selectedDate),
            'absentStats' => $this->computeAbsentStats($admin, $selectedDate),
            'regionDistribution' => $this->computeRegionDistribution($admin),
            'genderDemographics' => $this->computeGenderDemographics($admin),
            'headcountStats' => $this->computeHeadcountStats($admin),
            'topDiligent' => $this->computeTopDiligent($admin, $selectedDate),
            'topLate' => $this->computeTopLate($admin, $selectedDate),
            'topEarlyLeavers' => $this->computeTopEarlyLeavers($admin, $selectedDate),
            'estimatedPayroll' => $this->computeEstimatedPayroll($admin),
            'operationsMetrics' => $this->computeOperationsMetrics($admin),
            'workHoursPerDay' => 8,
        ]);
    }

    /**
     * @return array{labels:string[],data:int[]}
     */
    private function computeDivisionStats(User $admin, Carbon $selectedDate): array
    {
        $monthStart = $selectedDate->copy()->startOfMonth();
        $monthEnd = $selectedDate->copy()->endOfMonth();

        $rows = Attendance::query()
            ->managedBy($admin)
            ->selectRaw('divisions.name as division_name, COUNT(*) as count')
            ->join('employees', 'attendances.employee_id', '=', 'employees.id')
            ->join('users', 'employees.user_id', '=', 'users.id')
            ->join('divisions', 'employees.division_id', '=', 'divisions.id')
            ->whereBetween('attendances.date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->whereIn('attendances.status', ['present', 'late'])
            ->groupBy('divisions.name')
            ->orderByDesc('count')
            ->get();

        return [
            'labels' => $rows->pluck('division_name')->toArray(),
            'data' => $rows->pluck('count')->toArray(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function computeLateBuckets(User $admin, Carbon $selectedDate): array
    {
        $monthStart = $selectedDate->copy()->startOfMonth();
        $monthEnd = $selectedDate->copy()->endOfMonth();

        $lateRecords = Attendance::query()
            ->managedBy($admin)
            ->where('status', 'late')
            ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->count();

        return [
            'total_late' => $lateRecords,
            'monthly_late' => $lateRecords,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function computeAbsentStats(User $admin, Carbon $selectedDate): array
    {
        $monthStart = $selectedDate->copy()->startOfMonth();
        $monthEnd = $selectedDate->copy()->endOfMonth();

        $absentByStatus = Attendance::query()
            ->managedBy($admin)
            ->selectRaw('status, COUNT(*) as count')
            ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->whereIn('status', ['sick', 'excused'])
            ->groupBy('status')
            ->pluck('count', 'status');

        return [
            'sick' => (int) ($absentByStatus['sick'] ?? 0),
            'excused' => (int) ($absentByStatus['excused'] ?? 0),
        ];
    }

    /**
     * @return array<int, array{region:string,lat:float,lng:float,name:string}>
     */
    private function computeRegionDistribution(User $admin): array
    {
        return User::query()
            ->where('group', 'user')
            ->managedBy($admin)
            ->whereNotNull('kabupaten_id')
            ->with('kabupaten:id,name,latitude,longitude')
            ->get()
            ->filter(fn (User $u) => $u->kabupaten && $u->kabupaten->latitude && $u->kabupaten->longitude)
            ->map(fn (User $u) => [
                'region' => $u->kabupaten->name,
                'lat' => (float) $u->kabupaten->latitude,
                'lng' => (float) $u->kabupaten->longitude,
                'name' => $u->name,
            ])
            ->values()
            ->toArray();
    }

    /**
     * @return array{male:int,female:int}
     */
    private function computeGenderDemographics(User $admin): array
    {
        $counts = User::query()
            ->where('group', 'user')
            ->managedBy($admin)
            ->selectRaw("
                SUM(CASE WHEN gender = 'male' THEN 1 ELSE 0 END) as male,
                SUM(CASE WHEN gender = 'female' THEN 1 ELSE 0 END) as female
            ")
            ->first();

        return [
            'male' => (int) ($counts->male ?? 0),
            'female' => (int) ($counts->female ?? 0),
        ];
    }

    /**
     * @return array{labels:string[],data:int[]}
     */
    private function computeHeadcountStats(User $admin): array
    {
        $rows = User::query()
            ->where('group', 'user')
            ->managedBy($admin)
            ->join('employees', 'users.id', '=', 'employees.user_id')
            ->join('divisions', 'employees.division_id', '=', 'divisions.id')
            ->selectRaw('divisions.name as division_name, COUNT(*) as count')
            ->groupBy('divisions.name')
            ->orderByDesc('count')
            ->get();

        return [
            'labels' => $rows->pluck('division_name')->toArray(),
            'data' => $rows->pluck('count')->toArray(),
        ];
    }

    /**
     * @return Collection<int, object{name:string,avg_check_in:int}>
     */
    private function computeTopDiligent(User $admin, Carbon $selectedDate): Collection
    {
        $monthStart = $selectedDate->copy()->startOfMonth();
        $monthEnd = $selectedDate->copy()->endOfMonth();

        $employees = Attendance::query()
            ->managedBy($admin)
            ->selectRaw('users.name, AVG(EXTRACT(EPOCH FROM attendances.clock_in)) as avg_time')
            ->join('employees', 'attendances.employee_id', '=', 'employees.id')
            ->join('users', 'employees.user_id', '=', 'users.id')
            ->whereBetween('attendances.date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->whereNotNull('attendances.clock_in')
            ->groupBy('users.id', 'users.name')
            ->orderBy('avg_time')
            ->take(5)
            ->get()
            ->map(fn ($row) => (object) [
                'name' => $row->name,
                'avg_check_in' => (int) $row->avg_time,
            ]);

        return $employees;
    }

    /**
     * @return Collection<int, object{name:string,late_count:int}>
     */
    private function computeTopLate(User $admin, Carbon $selectedDate): Collection
    {
        $monthStart = $selectedDate->copy()->startOfMonth();
        $monthEnd = $selectedDate->copy()->endOfMonth();

        $employees = Attendance::query()
            ->managedBy($admin)
            ->selectRaw('users.name, COUNT(*) as late_count')
            ->join('employees', 'attendances.employee_id', '=', 'employees.id')
            ->join('users', 'employees.user_id', '=', 'users.id')
            ->whereBetween('attendances.date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->where('attendances.status', 'late')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('late_count')
            ->take(5)
            ->get()
            ->map(fn ($row) => (object) [
                'name' => $row->name,
                'late_count' => (int) $row->late_count,
            ]);

        return $employees;
    }

    /**
     * @return Collection<int, object{name:string,early_leave_count:int}>
     */
    private function computeTopEarlyLeavers(User $admin, Carbon $selectedDate): Collection
    {
        $monthStart = $selectedDate->copy()->startOfMonth();
        $monthEnd = $selectedDate->copy()->endOfMonth();

        $employees = Attendance::query()
            ->managedBy($admin)
            ->join('employees', 'attendances.employee_id', '=', 'employees.id')
            ->join('users', 'employees.user_id', '=', 'users.id')
            ->join('shifts', 'attendances.shift_id', '=', 'shifts.id')
            ->whereBetween('attendances.date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->whereNotNull('attendances.clock_out')
            ->whereRaw('attendances.clock_out < shifts.end_time')
            ->selectRaw('users.name, COUNT(*) as early_leave_count')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('early_leave_count')
            ->take(5)
            ->get()
            ->map(fn ($row) => (object) [
                'name' => $row->name,
                'early_leave_count' => (int) $row->early_leave_count,
            ]);

        return $employees;
    }

    private function computeEstimatedPayroll(User $admin): float
    {
        return (float) User::query()
            ->where('group', 'user')
            ->managedBy($admin)
            ->join('employees', 'users.id', '=', 'employees.user_id')
            ->sum('employees.basic_salary');
    }

    /**
     * @return array{pending_reimbursements:int,pending_cash_advances:int,pending_document_requests:int,pending_hr_tasks:int}
     */
    private function computeOperationsMetrics(User $admin): array
    {
        return [
            'pending_reimbursements' => Reimbursement::query()
                ->where('status', 'pending')
                ->count(),
            'pending_cash_advances' => CashAdvance::query()
                ->whereIn('status', ['pending', 'pending_finance'])
                ->count(),
            'pending_document_requests' => EmployeeDocumentRequest::query()
                ->whereIn('status', ['pending', 'processing'])
                ->count(),
            'pending_hr_tasks' => HrChecklistCase::query()
                ->whereHas('tasks', fn (Builder $q) => $q->whereNull('completed_at'))
                ->count(),
        ];
    }
}
