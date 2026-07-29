<?php

namespace App\Livewire\User;

use App\Enums\AttendanceStatus;
use App\Livewire\Traits\AttendanceDetailTrait;
use App\Models\Attendance;
use App\Models\Holiday;
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
            now()->addMinutes(5),
            function () use ($user) {
                return Attendance::filter(
                    month: $this->month,
                    userId: $user->id,
                )->get(['id', 'status', 'date', 'time_in', 'time_out', 'latitude_in', 'longitude_in', 'latitude_out', 'longitude_out', 'attachment', 'note', 'approval_status'])->toArray();
            }
        ) ?? [];

        $attendances = Attendance::hydrate($cached);
        $attendanceByDate = $attendances->keyBy(fn (Attendance $attendance) => $attendance->date->format('Y-m-d'));

        // Calculate Counts
        $presentCount = $attendances->where('status', AttendanceStatus::PRESENT->value)->count();
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
                AttendanceStatus::PRESENT->value => $presentCount,
                AttendanceStatus::LATE->value => $lateCount,
                AttendanceStatus::EXCUSED->value => $excusedCount,
                AttendanceStatus::SICK->value => $sickCount,
                AttendanceStatus::ABSENT->value => $absentCount,
            ],
        ]);
    }
}
