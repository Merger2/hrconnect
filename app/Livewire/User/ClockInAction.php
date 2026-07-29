<?php

namespace App\Livewire\User;

use App\Exceptions\BusinessRuleException;
use App\Models\Attendance;
use App\Models\Overtime;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\Shift;
use App\Services\Attendance\AttendanceService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ClockInAction extends Component
{
    use AuthorizesRequests;

    // --- Attendance state ---
    public ?Attendance $attendance = null;

    public bool $hasCheckedIn = false;

    public bool $hasCheckedOut = false;

    public bool $isLoading = false;

    public ?string $errorMessage = null;

    public ?string $successMessage = null;

    // --- Shift ---
    public array $todayShiftSummary = [];

    public bool $hasApprovedOvertime = false;

    // --- Face enrollment check ---
    public bool $requiresFaceEnrollment = false;

    // --- GPS ---
    public ?float $latitude = null;

    public ?float $longitude = null;

    public ?float $accuracy = null;

    public bool $isGpsLoading = false;

    public ?string $gpsError = null;

    // --- PIN modal ---
    public bool $showPinModal = false;

    public string $pin = '';

    public string $pinAction = ''; // 'clock_in' or 'clock_out'

    // --- WFA modal ---
    public bool $showWfaModal = false;

    public string $wfaNote = '';

    protected AttendanceService $attendanceService;

    public function boot(AttendanceService $attendanceService): void
    {
        $this->attendanceService = $attendanceService;
    }

    public function mount(): void
    {
        $this->refreshStatus();
    }

    public function refreshStatus(): void
    {
        $user = Auth::user();
        $employee = $user->employee;
        $today = now()->format('Y-m-d');

        // Face enrollment check
        $faceVerificationRequired = filter_var(
            Setting::getValue('attendance.require_face_verification', true),
            FILTER_VALIDATE_BOOLEAN
        );
        $shouldRequireEnrollment = filter_var(
            Setting::getValue('attendance.require_face_enrollment', false),
            FILTER_VALIDATE_BOOLEAN
        );

        $this->requiresFaceEnrollment = ($shouldRequireEnrollment || $faceVerificationRequired)
            && ! $user->hasFaceRegistered();

        // Today's attendance
        $this->attendance = Attendance::with('shift')
            ->whereHas('employee', fn ($q) => $q->where('user_id', $user->id))
            ->where('date', $today)
            ->first();

        if ($this->attendance) {
            $this->hasCheckedIn = ! is_null($this->attendance->clock_in);
            $this->hasCheckedOut = ! is_null($this->attendance->clock_out);
        }

        // Today's schedule/shift
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
            'end_time' => $shift?->end_time,
        ];

        // Approved overtime
        $approvedOvertime = Overtime::whereHas('employee', fn ($q) => $q->where('user_id', $user->id))
            ->whereDate('date', $today)
            ->where('status', 'approved')
            ->latest('updated_at')
            ->first();

        $this->hasApprovedOvertime = $approvedOvertime !== null;
    }

    public function setGps(float $latitude, float $longitude, ?float $accuracy = null): void
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->accuracy = $accuracy;
        $this->isGpsLoading = false;
        $this->gpsError = null;
    }

    /**
     * Attempt clock in. If face is enrolled, trigger face verification.
     * Otherwise show PIN modal.
     */
    public function startClockIn(): void
    {
        $this->errorMessage = null;
        $this->successMessage = null;
        $this->isLoading = true;

        $user = Auth::user();
        $employee = $user->employee;

        if (! $employee) {
            $this->errorMessage = __('Employee record not found.');
            $this->isLoading = false;

            return;
        }

        $hasFaceEnrolled = $user->hasFaceRegistered();

        if ($hasFaceEnrolled) {
            // Face-verified: trigger face capture via Alpine
            // isLoading stays true until doClockInWithFace() is called
            $this->dispatch('trigger-face-capture', action: 'clock_in');
            // Face timeout: if FaceEnrollment doesn't respond within 20s, offer PIN fallback
            $this->dispatch('face-verification-timeout', timeoutMs: 20000, action: 'clock_in');

            return;
        }

        // No face enrolled: show PIN modal
        $this->pinAction = 'clock_in';
        $this->pin = '';
        $this->showPinModal = true;
        $this->isLoading = false;
    }

    /**
     * Complete clock-in with face descriptor (called from Alpine after face capture).
     */
    public function doClockInWithFace(array $faceDescriptor): void
    {
        $this->errorMessage = null;
        $this->isLoading = true;

        try {
            $this->doClockIn(['face_embedding' => $faceDescriptor]);
        } catch (\Throwable $e) {
            // If face fails, fallback to PIN
            $this->pinAction = 'clock_in';
            $this->pin = '';
            $this->showPinModal = true;
            $this->errorMessage = $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Complete clock-in with PIN (called from PIN modal).
     */
    public function doClockInWithPin(): void
    {
        $this->validate(['pin' => ['required', 'string', 'min:4', 'max:8']]);

        $this->errorMessage = null;
        $this->isLoading = true;

        try {
            $this->doClockIn(['pin' => $this->pin]);
            $this->showPinModal = false;
            $this->pin = '';
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    private function doClockIn(array $verificationData): void
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (! $employee) {
            throw new BusinessRuleException(__('Employee record not found.'));
        }

        $data = array_merge($verificationData, [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'accuracy' => $this->accuracy,
            'is_mocked' => false,
        ]);

        $this->attendanceService->clockIn($employee, $data);
        $this->refreshStatus();
        $this->successMessage = __('Check In successful!');
        $this->dispatch('refresh-notifications');
    }

    /**
     * Start clock-out with face or PIN.
     */
    public function startClockOut(): void
    {
        $this->errorMessage = null;
        $this->successMessage = null;
        $this->isLoading = true;

        $user = Auth::user();
        $employee = $user->employee;

        if (! $employee) {
            $this->errorMessage = __('Employee record not found.');
            $this->isLoading = false;

            return;
        }

        $hasFaceEnrolled = $user->hasFaceRegistered();

        if ($hasFaceEnrolled) {
            // Face-verified: trigger face capture via Alpine
            $this->dispatch('trigger-face-capture', action: 'clock_out');
            // Face timeout: if FaceEnrollment doesn't respond within 20s, offer PIN fallback
            $this->dispatch('face-verification-timeout', timeoutMs: 20000, action: 'clock_out');

            return;
        }

        $this->pinAction = 'clock_out';
        $this->pin = '';
        $this->showPinModal = true;
        $this->isLoading = false;
    }

    /**
     * Complete clock-out with face descriptor.
     */
    public function doClockOutWithFace(array $faceDescriptor): void
    {
        $this->errorMessage = null;
        $this->isLoading = true;

        try {
            $this->doClockOut(['face_embedding' => $faceDescriptor]);
        } catch (\Throwable $e) {
            $this->pinAction = 'clock_out';
            $this->pin = '';
            $this->showPinModal = true;
            $this->errorMessage = $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Complete clock-out with PIN.
     */
    public function doClockOutWithPin(): void
    {
        $this->validate(['pin' => ['required', 'string', 'min:4', 'max:8']]);

        $this->errorMessage = null;
        $this->isLoading = true;

        try {
            $this->doClockOut(['pin' => $this->pin]);
            $this->showPinModal = false;
            $this->pin = '';
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    private function doClockOut(array $verificationData): void
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (! $employee) {
            throw new BusinessRuleException(__('Employee record not found.'));
        }

        $data = array_merge($verificationData, [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'accuracy' => $this->accuracy,
            'is_mocked' => false,
        ]);

        $this->attendanceService->clockOut($employee, $data);
        $this->refreshStatus();
        $this->successMessage = __('Check Out successful!');
        $this->dispatch('refresh-notifications');
    }

    /**
     * Handle WFA clock-in.
     */
    public function startWfaClockIn(): void
    {
        $this->showWfaModal = true;
        $this->wfaNote = '';
        $this->errorMessage = null;
    }

    public function submitWfaClockIn(): void
    {
        $this->validate(['wfaNote' => ['required', 'string', 'min:20', 'max:500']]);

        $this->errorMessage = null;
        $this->isLoading = true;

        try {
            $user = Auth::user();
            $employee = $user->employee;

            if (! $employee) {
                throw new BusinessRuleException(__('Employee record not found.'));
            }

            $this->attendanceService->clockIn($employee, [
                'is_wfa' => true,
                'wfa_note' => $this->wfaNote,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'accuracy' => $this->accuracy,
            ]);

            $this->showWfaModal = false;
            $this->wfaNote = '';
            $this->refreshStatus();
            $this->successMessage = __('WFA Check In successful!');
            $this->dispatch('refresh-notifications');
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    public function dismissError(): void
    {
        $this->errorMessage = null;
    }

    public function dismissSuccess(): void
    {
        $this->successMessage = null;
    }

    public function render()
    {
        return view('livewire.user.clock-in-action');
    }

    private function defaultMorningShift(): ?Shift
    {
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
