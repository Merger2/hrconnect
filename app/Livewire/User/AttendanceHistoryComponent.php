<?php

namespace App\Livewire\User;

use App\Enums\AttendanceStatus;
use App\Livewire\Traits\AttendanceDetailTrait;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\Overtime;
use App\Models\Reimbursement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class AttendanceHistoryComponent extends Component
{
    use AttendanceDetailTrait;

    public ?string $month;

    public $selectedYear;

    public $selectedMonth;

    public function mount()
    {
        $this->selectedYear = date('Y');
        $this->selectedMonth = date('m');
        if (empty($this->selectedMonth) || ! is_numeric($this->selectedMonth)) {
            $this->selectedMonth = date('m');
        }
        if (empty($this->selectedYear) || ! is_numeric($this->selectedYear)) {
            $this->selectedYear = date('Y');
        }
        $this->month = "{$this->selectedYear}-{$this->selectedMonth}";
    }

    public function updatedSelectedYear()
    {
        $this->updateMonth();
    }

    public function updatedSelectedMonth()
    {
        $this->updateMonth();
    }

    public function updateMonth()
    {
        if (empty($this->selectedMonth) || ! is_numeric($this->selectedMonth)) {
            $this->selectedMonth = date('m');
        }
        if (empty($this->selectedYear) || ! is_numeric($this->selectedYear)) {
            $this->selectedYear = date('Y');
        }
        $this->month = "{$this->selectedYear}-{$this->selectedMonth}";
    }

    public function render()
    {
        $user = auth()->user();

        try {
            $date = Carbon::parse($this->month);
        } catch (\Exception $e) {
            // Fallback calculation date only, do NOT reset user input
            $date = now();
        }

        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        // Start from the beginning of the week (Sunday)
        $startGrid = $startOfMonth->copy()->startOfWeek(Carbon::SUNDAY);
        // End at the end of the week (Saturday)
        $endGrid = $endOfMonth->copy()->endOfWeek(Carbon::SATURDAY);

        $dates = [];
        $current = $startGrid->copy();

        while ($current <= $endGrid) {
            $dates[] = $current->copy();
            $current->addDay();
        }

        $cached = Cache::remember(
            "attendance-$user->id-$date->month-$date->year",
            now()->addMinutes(1),
            function () use ($user, $date) {
                return Attendance::whereHas('employee', fn ($q) => $q->where('user_id', $user->id))
                    ->whereYear('date', $date->year)
                    ->whereMonth('date', $date->month)
                    ->get(['id', 'status', 'date', 'clock_in', 'clock_out', 'lat_in', 'long_in', 'lat_out', 'long_out', 'photo_selfie_in', 'photo_selfie_out', 'note', 'approval_status'])
                    // toArray() men-serialize atribut date cast ke ISO-UTC (mis.
                    // 2026-03-31T17:00Z untuk 2026-04-01 WIB) — hydrate dari cache
                    // lalu menggeser tanggal -1 hari → hitungan absen & tampilan
                    // kalender salah. Normalisasi ke 'Y-m-d' agar round-trip cache
                    // aman terhadap timezone.
                    ->map(fn (Attendance $attendance) => [
                        ...$attendance->toArray(),
                        'date' => $attendance->date->format('Y-m-d'),
                    ])
                    ->all();
            }
        ) ?? [];

        $attendances = Attendance::hydrate($cached);
        $attendanceByDate = $attendances->keyBy(fn (Attendance $attendance) => $attendance->date->format('Y-m-d'));

        // Query requests: leaves, overtimes, reimbursements
        $userId = $user->id;
        $leaveRequests = Leave::whereHas('employee', fn ($q) => $q->where('user_id', $userId))
            ->whereBetween('start_date', [$startOfMonth, $endOfMonth])
            ->with('leaveType', 'approvals')
            ->get()
            ->map(fn ($leave) => [
                'type' => 'leave',
                'title' => $leave->leaveType?->name ?? 'Cuti',
                'date' => $leave->start_date,
                'end_date' => $leave->end_date,
                'status' => $leave->status,
                'reason' => $leave->reason,
                'approvals' => $leave->approvals->map(fn ($a) => [
                    'level' => $a->pivot->level ?? $a->level ?? null,
                    'status' => $a->pivot->status ?? $a->status ?? null,
                    'approved_by' => $a->pivot->approved_by ?? $a->approved_by ?? null,
                ])->toArray(),
            ])
            ->toArray();

        $overtimeRequests = Overtime::whereHas('employee', fn ($q) => $q->where('user_id', $userId))
            ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->with('approvals')
            ->get()
            ->map(fn ($ot) => [
                'type' => 'overtime',
                'title' => 'Lembur',
                'date' => $ot->date,
                'status' => $ot->status,
                'description' => $ot->description,
                'approvals' => $ot->approvals->map(fn ($a) => [
                    'level' => $a->pivot->level ?? $a->level ?? null,
                    'status' => $a->pivot->status ?? $a->status ?? null,
                ])->toArray(),
            ])
            ->toArray();

        $reimbursementRequests = Reimbursement::whereHas('employee', fn ($q) => $q->where('user_id', $userId))
            ->whereBetween('expense_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->with('category', 'approvals')
            ->get()
            ->map(fn ($r) => [
                'type' => 'reimbursement',
                'title' => $r->category?->name ?? 'Reimbursement',
                'date' => $r->expense_date,
                'status' => $r->status,
                'amount' => $r->amount,
                'approvals' => $r->approvals->map(fn ($a) => [
                    'level' => $a->pivot->level ?? $a->level ?? null,
                    'status' => $a->pivot->status ?? $a->status ?? null,
                ])->toArray(),
            ])
            ->toArray();

        $requests = array_merge($leaveRequests, $overtimeRequests, $reimbursementRequests);

        // Build request lookup by date for calendar indicators
        $requestsByDate = [];
        foreach ($requests as $req) {
            $reqDate = $req['date'] ?? null;
            if ($reqDate) {
                $requestsByDate[$reqDate][] = $req;
            }
        }

        // Calculate Counts
        $presentCount = $attendances->where('status', AttendanceStatus::ON_TIME->value)->count();
        $lateCount = $attendances->where('status', AttendanceStatus::LATE->value)->count();
        $excusedCount = $attendances->where('status', AttendanceStatus::EXCUSED->value)->count();
        $sickCount = $attendances->where('status', AttendanceStatus::SICK->value)->count();

        // Map additional attributes...
        $attendances->transform(function (Attendance $v) {
            $v->setAttribute('coordinates', $v->lat_lng);

            return $v;
        });

        $attendanceToday = $attendances->firstWhere(fn ($v, $_) => $v['date'] === Carbon::now()->format('Y-m-d'));

        // Get holidays for this month (including recurring)
        $holidays = Holiday::where(function ($query) use ($startOfMonth, $endOfMonth) {
            $query->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->orWhere(function ($q) use ($startOfMonth) {
                    $q->where('is_recurring', true)
                        ->whereMonth('date', $startOfMonth->month);
                });
        })->get()->keyBy(function ($holiday) use ($startOfMonth) {
            // For recurring holidays, use current year's date as key
            if ($holiday->is_recurring) {
                return $startOfMonth->year.'-'.$holiday->date->format('m-d');
            }

            return $holiday->date->format('Y-m-d');
        });

        $monthDates = collect($dates)->filter(fn (Carbon $day) => $day->month === $date->month);
        $workingDays = $monthDates->filter(function (Carbon $day) use ($holidays) {
            return ! $day->isWeekend() && ! isset($holidays[$day->format('Y-m-d')]);
        });

        $absentCount = $workingDays->filter(function (Carbon $day) use ($attendanceByDate) {
            if (! $day->isBefore(today())) {
                return false;
            }

            $attendance = $attendanceByDate->get($day->format('Y-m-d'));

            if (! $attendance) {
                return true;
            }

            return $attendance->status === AttendanceStatus::ABSENT->value;
        })->count();

        return view('livewire.user.attendance-history', [
            'attendances' => $attendances,
            'attendanceToday' => $attendanceToday,
            'dates' => $dates,
            'currentMonth' => $date->month,
            'displayMonth' => $date,
            'holidays' => $holidays,
            'workingDaysCount' => $workingDays->count(),
            'counts' => [
                AttendanceStatus::ON_TIME->value => $presentCount,
                AttendanceStatus::LATE->value => $lateCount,
                AttendanceStatus::EXCUSED->value => $excusedCount,
                AttendanceStatus::SICK->value => $sickCount,
                AttendanceStatus::ABSENT->value => $absentCount,
            ],
            'requests' => $requests,
            'requestsByDate' => $requestsByDate,
        ]);
    }
}
