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

    // --- Branch (office location) ---
    public ?float $branchLatitude = null;

    public ?float $branchLongitude = null;

    public ?int $branchRadius = null;

    public ?string $branchName = null;

    // --- Clock in/out times (formatted strings for Alpine, avoids deferred model issues) ---
    public ?string $clockInTime = null;

    public ?string $clockOutTime = null;

    // --- WFA modal ---
    public bool $showWfaModal = false;

    public string $wfaNote = '';

    /** @var array|null Face descriptor stored after successful face capture */
    public ?array $wfaFaceDescriptor = null;

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
            Setting::getValue('attendance.require_face_verification', false),
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
            $this->clockInTime = $this->attendance->clock_in?->format('H:i');
            $this->clockOutTime = $this->attendance->clock_out?->format('H:i');
        } else {
            $this->clockInTime = null;
            $this->clockOutTime = null;
        }

        // Today's schedule/shift
        $todaySchedule = Schedule::query()
            ->with('shift')
            ->where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        $shift = $this->attendance?->shift
            ?? $todaySchedule?->shift
            ?? ($todaySchedule?->is_off ? null : $employee?->shift)
            ?? $this->defaultMorningShift();

        $this->todayShiftSummary = [
            'is_off' => (bool) ($todaySchedule?->is_off ?? false),
            'name' => $shift?->name,
            'start' => $shift?->formatted_start_time,
            'end' => $shift?->formatted_end_time,
            'duration' => $shift?->duration_label,
            'end_time' => $shift?->end_time,
        ];

        // Branch / office location
        $branch = $employee?->branch;
        $this->branchLatitude = $branch?->latitude;
        $this->branchLongitude = $branch?->longitude;
        $this->branchRadius = $branch?->radius;
        $this->branchName = $branch?->name;

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
     * Attempt clock in. Face-only (PRD §1/§4): jika wajah belum terdaftar,
     * tampilkan error yang mengarahkan ke HRD — tidak ada fallback PIN.
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

        if (! $user->hasFaceRegistered()) {
            $this->errorMessage = __('Wajah Anda belum terdaftar. Hubungi HRD untuk registrasi wajah.');
            $this->isLoading = false;

            return;
        }

        // Face-only: trigger face capture via Alpine
        // isLoading stays true until doClockInWithFace() is called
        $this->dispatch('trigger-face-capture', action: 'clock_in');
        // Face timeout: jika verifikasi tidak merespons dalam 20s, tampilkan error (bukan PIN)
        $this->dispatch('face-verification-timeout', timeoutMs: 20000, action: 'clock_in');
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
            // Face-only: tidak ada fallback PIN — tampilkan error & arahkan ke koreksi HR
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
     * Start clock-out. Face-only: jika wajah belum terdaftar, tampilkan error
     * yang mengarahkan ke HRD — tidak ada fallback PIN.
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

        if (! $user->hasFaceRegistered()) {
            $this->errorMessage = __('Wajah Anda belum terdaftar. Hubungi HRD untuk registrasi wajah.');
            $this->isLoading = false;

            return;
        }

        // Face-only: trigger face capture via Alpine
        $this->dispatch('trigger-face-capture', action: 'clock_out');
        // Face timeout: jika verifikasi tidak merespons dalam 20s, tampilkan error (bukan PIN)
        $this->dispatch('face-verification-timeout', timeoutMs: 20000, action: 'clock_out');
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
            // Face-only: tidak ada fallback PIN — tampilkan error & arahkan ke koreksi HR
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
     *
     * Primary (satu-satunya) method: face recognition — tanpa fallback PIN.
     * Jika wajah belum terdaftar → error yang mengarahkan ke HRD.
     */
    public function startWfaClockIn(): void
    {
        $this->showWfaModal = false;
        $this->wfaNote = '';
        $this->wfaFaceDescriptor = null;
        $this->errorMessage = null;

        $user = Auth::user();

        if (! $user->hasFaceRegistered()) {
            $this->errorMessage = __('Wajah Anda belum terdaftar. Hubungi HRD untuk registrasi wajah.');
            $this->isLoading = false;

            return;
        }

        // Face mode: trigger face capture FIRST, then show note modal
        $this->isLoading = true;
        $this->dispatch('trigger-face-capture', action: 'wfa');
        $this->dispatch('face-verification-timeout', timeoutMs: 20000, action: 'wfa');
    }

    /**
     * Called after face is captured for WFA.
     * Stores the descriptor and shows the note modal (without PIN).
     */
    public function doWfaClockInWithFace(array $faceDescriptor): void
    {
        $this->wfaFaceDescriptor = $faceDescriptor;
        $this->showWfaModal = true;
        $this->isLoading = false;
    }

    public function submitWfaClockIn(): void
    {
        // Face-only: verifikasi wajah sudah dilakukan sebelum modal dibuka (note saja yang divalidasi)
        $this->validate(['wfaNote' => ['required', 'string', 'min:20', 'max:500']]);

        $this->errorMessage = null;
        $this->isLoading = true;

        try {
            $user = Auth::user();
            $employee = $user->employee;

            if (! $employee) {
                throw new BusinessRuleException(__('Employee record not found.'));
            }

            if (empty($this->wfaFaceDescriptor)) {
                throw new BusinessRuleException('Verifikasi wajah diperlukan untuk absensi WFA.');
            }

            $data = [
                'is_wfa' => true,
                'wfa_note' => $this->wfaNote,
                'face_embedding' => $this->wfaFaceDescriptor,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'accuracy' => $this->accuracy,
            ];

            $this->attendanceService->clockIn($employee, $data);

            $this->showWfaModal = false;
            $this->wfaNote = '';
            $this->wfaFaceDescriptor = null;
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
                ->whereIn('name', ['Office Hour', 'Flexible', 'Morning'])
                ->orderByRaw("CASE name WHEN 'Office Hour' THEN 1 WHEN 'Flexible' THEN 2 ELSE 3 END")
                ->first()
            ?? Shift::query()
                ->orderBy('start_time')
                ->first();
    }
}
