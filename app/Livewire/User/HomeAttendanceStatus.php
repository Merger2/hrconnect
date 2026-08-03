<?php

namespace App\Livewire\User;

use App\Contracts\AttendanceServiceInterface;
use App\Models\Attendance;
use App\Models\Overtime;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\Shift;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * @deprecated Tidak terdaftar di route / Blade mana pun.
 */
class HomeAttendanceStatus extends Component
{
    protected AttendanceServiceInterface $attendanceService;

    public $hasCheckedIn = false;

    public $hasCheckedOut = false;

    public $attendance = null;

    public $approvedAbsence = null;

    public $requiresFaceEnrollment = false;

    public $overtime = null;

    public bool $hasApprovedOvertime = false;

    public array $todayShiftSummary = [];

    public function boot(AttendanceServiceInterface $attendanceService): void
    {
        $this->attendanceService = $attendanceService;
    }

    public function mount()
    {
        $this->checkAttendanceStatus();
    }

    public function checkAttendanceStatus()
    {
        $user = Auth::user();
        $today = now()->format('Y-m-d');
        $attendanceLocked = false;
        $faceVerificationRequired = ! $attendanceLocked && filter_var(
            Setting::getValue('attendance.require_face_verification', false),
            FILTER_VALIDATE_BOOLEAN
        );

        // Check for mandatory face enrollment (Open Core Logic)
        $shouldRequireFaceEnrollment = ! $attendanceLocked && (
            filter_var(
                Setting::getValue('attendance.require_face_enrollment', false),
                FILTER_VALIDATE_BOOLEAN
            ) || $this->attendanceService->shouldEnforceFaceEnrollment() || $faceVerificationRequired
        );

        if ($shouldRequireFaceEnrollment && ! $user->hasFaceRegistered()) {
            $this->requiresFaceEnrollment = true;
        }

        $this->attendance = Attendance::with('shift')
            ->whereHas('employee', fn ($q) => $q->where('user_id', $user->id))
            ->where('date', $today)
            ->first();

        $todaySchedule = Schedule::query()
            ->with('shift')
            ->where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        $shift = $this->attendance?->shift
            ?? $todaySchedule?->shift
            ?? ($todaySchedule?->is_off ? null : $this->defaultMorningShift());

        $this->todayShiftSummary = [
            'is_off' => (bool) ($todaySchedule?->is_off ?? false),
            'name' => $shift?->name,
            'start' => $shift?->formatted_start_time,
            'end' => $shift?->formatted_end_time,
            'duration' => $shift?->duration_label,
        ];

        if ($this->attendance) {
            $this->hasCheckedIn = ! is_null($this->attendance->time_in);
            $this->hasCheckedOut = ! is_null($this->attendance->time_out);

            // Check for approved absence
            if (in_array($this->attendance->status, ['sick', 'excused', 'permission', 'leave']) &&
                $this->attendance->approval_status === Attendance::STATUS_APPROVED
            ) {
                $this->approvedAbsence = $this->attendance;
            }
        }

        // Only approved overtime should affect the home attendance state.
        $approvedOvertime = Overtime::whereHas('employee', fn ($q) => $q->where('user_id', $user->id))
            ->whereDate('date', $today)
            ->where('status', 'approved')
            ->latest('updated_at')
            ->first();

        $this->overtime = $approvedOvertime;
        $this->hasApprovedOvertime = $approvedOvertime !== null;
    }

    public function render()
    {
        return view('livewire.user.home-attendance-status');
    }

    private function defaultMorningShift(): ?Shift
    {
        // Shifts are still global master data; scope this query by company/branch
        // once the shifts table gets tenant columns.
        return Shift::query()
            ->where('name', 'Shift Pagi')
            ->first()
            ?? Shift::query()
                ->where('name', 'like', '%Pagi%')
                ->orderBy('start_time')
                ->first()
            ?? Shift::query()
                ->where('name', 'like', '%Morning%')
                ->orderBy('start_time')
                ->first()
            ?? Shift::query()
                ->orderBy('start_time')
                ->first();
    }
}
