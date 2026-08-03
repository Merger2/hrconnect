<?php

namespace App\Queries\Attendance;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;

class AdminAttendanceGridQuery
{
    public function employees(
        User $actor,
        Carbon $start,
        Carbon $end,
        ?string $division,
        ?string $jobTitle,
        ?string $search,
        string $riskFilter,
    ): LengthAwarePaginator {
        $employees = User::query()
            ->where('group', 'user')
            ->managedBy($actor)
            ->when($search, function (Builder $query) use ($search): void {
                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery->where('name', 'like', '%'.$search.'%')
                        ->orWhere('nip', 'like', '%'.$search.'%');
                });
            })
            ->when($division, fn (Builder $query) => $query->where('division_id', $division))
            ->when($jobTitle, fn (Builder $query) => $query->where('job_title_id', $jobTitle))
            ->when($riskFilter !== 'all', function (Builder $query) use ($start, $end, $riskFilter): void {
                $query->whereHas('attendances', function (Builder $attendanceQuery) use ($start, $end, $riskFilter): void {
                    $attendanceQuery->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')]);

                    match ($riskFilter) {
                        'high' => $attendanceQuery->where('risk_level', 'high'),
                        'medium_high' => $attendanceQuery->whereIn('risk_level', ['medium', 'high']),
                        'suspicious' => $attendanceQuery->whereIn('risk_level', ['high', 'suspicious']),
                        default => null,
                    };
                });
            })
            ->with(['employee.division', 'employee.position'])
            ->orderBy('name')
            ->paginate(20);

        $this->attachAttendances($employees, $start, $end);

        return $employees;
    }

    private function attachAttendances(LengthAwarePaginator $employees, Carbon $start, Carbon $end): void
    {
        $userIds = $employees->getCollection()->pluck('id');
        $attendancesByUser = $userIds->isEmpty()
            ? collect()
            : Attendance::query()
                ->select('attendances.*')
                ->addSelect('employees.user_id')
                ->with('shift:id,name')
                ->join('employees', 'attendances.employee_id', '=', 'employees.id')
                ->whereIn('employees.user_id', $userIds)
                ->whereBetween('attendances.date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                ->get()
                ->map(fn (Attendance $attendance) => $this->decorateAttendanceForGrid($attendance))
                ->groupBy('user_id');

        $employees->getCollection()->transform(function (User $user) use ($attendancesByUser): User {
            $user->setRelation('attendances', new EloquentCollection($attendancesByUser->get($user->id, collect())->all()));

            return $user;
        });
    }

    private function decorateAttendanceForGrid(Attendance $attendance): Attendance
    {
        $attendance->setAttribute('coordinates', $attendance->lat_lng);
        $attendance->setAttribute('lat', $attendance->latitude_in);
        $attendance->setAttribute('lng', $attendance->longitude_in);

        if ($attendance->attachment) {
            $attendance->setAttribute('attachment', $attendance->attachment_url);
        }

        if ($attendance->shift) {
            $attendance->setAttribute('shift', $attendance->shift->name);
        }

        return $attendance;
    }
}
