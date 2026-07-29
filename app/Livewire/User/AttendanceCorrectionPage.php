<?php

namespace App\Livewire\User;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\Shift;
use App\Support\AttendanceCorrectionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class AttendanceCorrectionPage extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    protected AttendanceCorrectionService $correctionService;

    public bool $showCreateModal = false;

    public string $statusFilter = 'all';

    public string $search = '';

    public string $attendanceDate = '';

    public bool $includeRequestedTimeIn = false;

    public bool $includeRequestedTimeOut = false;

    public bool $includeRequestedShift = false;

    public ?string $requestedTimeIn = null;

    public ?string $requestedTimeOut = null;

    public $requestedShiftId = null;

    public string $reason = '';

    public function boot(AttendanceCorrectionService $correctionService): void
    {
        $this->correctionService = $correctionService;
    }

    public function mount(?string $date = null): void
    {
        $this->authorize('viewAny', AttendanceCorrection::class);
        $dateParam = $date ?? request()->query('date');
        $this->attendanceDate = $dateParam ?? now()->toDateString();

        if ($dateParam) {
            $this->createWithDate($dateParam);
        }
    }

    public function create(): void
    {
        $this->createWithDate(now()->toDateString());
    }

    public function createWithDate(string $date): void
    {
        $this->authorize('create', AttendanceCorrection::class);

        $this->resetErrorBag();
        $this->showCreateModal = true;
        $this->attendanceDate = $date;
        $this->includeRequestedTimeIn = false;
        $this->includeRequestedTimeOut = false;
        $this->includeRequestedShift = false;
        $this->requestedTimeIn = null;
        $this->requestedTimeOut = null;
        $this->requestedShiftId = null;
        $this->reason = '';
        $this->updatedAttendanceDate();
    }

    public function closeModal(): void
    {
        $this->resetErrorBag();
        $this->showCreateModal = false;
    }

    public function updatedIncludeRequestedTimeIn(bool $value): void
    {
        if (! $value) {
            $this->requestedTimeIn = null;

            return;
        }

        $this->requestedTimeIn ??= $this->defaultRequestedDateTime('in');
    }

    public function updatedIncludeRequestedTimeOut(bool $value): void
    {
        if (! $value) {
            $this->requestedTimeOut = null;

            return;
        }

        $this->requestedTimeOut ??= $this->defaultRequestedDateTime('out');
    }

    public function updatedIncludeRequestedShift(bool $value): void
    {
        if (! $value) {
            $this->requestedShiftId = null;
        }
    }

    public function updatedAttendanceDate(): void
    {
        if ($this->includeRequestedTimeIn) {
            $this->requestedTimeIn = $this->defaultRequestedDateTime('in');
        }

        if ($this->includeRequestedTimeOut) {
            $this->requestedTimeOut = $this->defaultRequestedDateTime('out');
        }
    }

    public function updatedRequestedShiftId(): void
    {
        if ($this->includeRequestedTimeIn) {
            $this->requestedTimeIn = $this->defaultRequestedDateTime('in');
        }

        if ($this->includeRequestedTimeOut) {
            $this->requestedTimeOut = $this->defaultRequestedDateTime('out');
        }
    }

    public function save(): void
    {
        $this->authorize('create', AttendanceCorrection::class);

        $validated = $this->validate([
            'attendanceDate' => ['required', 'date'],
            'includeRequestedTimeIn' => ['boolean'],
            'includeRequestedTimeOut' => ['boolean'],
            'includeRequestedShift' => ['boolean'],
            'requestedTimeIn' => ['nullable', 'date_format:Y-m-d H:i'],
            'requestedTimeOut' => ['nullable', 'date_format:Y-m-d H:i'],
            'requestedShiftId' => ['nullable', 'exists:shifts,id'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        [$requestedTimeIn, $requestedTimeOut, $requestType] = $this->validateRequestPayload();

        $this->correctionService->submit(auth()->user(), [
            'attendance_date' => $validated['attendanceDate'],
            'request_type' => $requestType,
            'requested_time_in' => $requestedTimeIn,
            'requested_time_out' => $requestedTimeOut,
            'requested_shift_id' => $this->includeRequestedShift ? $validated['requestedShiftId'] : null,
            'reason' => $validated['reason'],
        ]);

        $this->closeModal();
        $this->dispatch('refresh-notifications');
        session()->flash('success', __('Attendance correction request submitted successfully.'));
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $this->authorize('viewAny', AttendanceCorrection::class);

        $corrections = AttendanceCorrection::query()
            ->with(['attendance.shift', 'requestedShift', 'headApprover'])
            ->where('user_id', auth()->id())
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->search !== '', function ($query) {
                $query->where(function ($nested) {
                    $nested->where('reason', 'like', '%'.$this->search.'%')
                        ->orWhere('request_type', 'like', '%'.$this->search.'%');
                });
            })
            ->latest('attendance_date')
            ->latest('created_at')
            ->paginate(10);

        $existingAttendance = Attendance::query()
            ->with('shift')
            ->whereHas('employee', fn ($q) => $q->where('user_id', auth()->id()))
            ->whereDate('date', $this->attendanceDate)
            ->first();

        return view('livewire.user.attendance-correction-page', [
            'corrections' => $corrections,
            'existingAttendance' => $existingAttendance,
            'snapshotTimeIn' => $this->attendanceSnapshotDateTime($existingAttendance, 'in'),
            'snapshotTimeOut' => $this->attendanceSnapshotDateTime($existingAttendance, 'out'),
            'shifts' => Shift::query()->orderBy('name')->get(),
        ])->layout('layouts.app');
    }

    private function validateRequestPayload(): array
    {
        $attendance = Attendance::query()
            ->whereHas('employee', fn ($q) => $q->where('user_id', auth()->id()))
            ->whereDate('date', $this->attendanceDate)
            ->first();

        $messages = [];

        if (! $this->includeRequestedTimeIn && ! $this->includeRequestedTimeOut && ! $this->includeRequestedShift) {
            $messages['includeRequestedTimeIn'] = __('Select at least one correction to request.');
        }

        if ($this->includeRequestedTimeIn && ! $this->requestedTimeIn) {
            $messages['requestedTimeIn'] = __('Requested check in time is required.');
        }

        if ($this->includeRequestedTimeOut && ! $this->requestedTimeOut) {
            $messages['requestedTimeOut'] = __('Requested check out time is required.');
        }

        if ($this->includeRequestedShift && ! $this->requestedShiftId) {
            $messages['requestedShiftId'] = __('Please choose the corrected shift.');
        }

        $requestedTimeIn = $this->includeRequestedTimeIn
            ? $this->buildRequestedDateTime($this->requestedTimeIn)
            : null;

        $requestedTimeOut = $this->includeRequestedTimeOut
            ? $this->buildRequestedDateTime($this->requestedTimeOut)
            : null;

        if ($this->includeRequestedTimeOut && ! $this->includeRequestedTimeIn && ! $attendance?->time_in) {
            $messages['attendanceDate'] = __('A check out correction requires an existing or requested check in record.');
        }

        if ($this->includeRequestedShift && ! $attendance && ! $this->includeRequestedTimeIn && ! $this->includeRequestedTimeOut) {
            $messages['attendanceDate'] = __('A shift correction requires an existing attendance record.');
        }

        $referenceTimeIn = $requestedTimeIn ?? $this->attendanceSnapshotDateTime($attendance, 'in');

        if ($requestedTimeOut && $referenceTimeIn && $requestedTimeOut->lte($referenceTimeIn)) {
            $messages['requestedTimeOut'] = __('Requested check out time must be later than check in time.');
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }

        return [
            $requestedTimeIn?->format('Y-m-d H:i:s'),
            $requestedTimeOut?->format('Y-m-d H:i:s'),
            $this->inferRequestType($attendance, $requestedTimeIn, $requestedTimeOut),
        ];
    }

    private function inferRequestType(?Attendance $attendance, ?Carbon $requestedTimeIn, ?Carbon $requestedTimeOut): string
    {
        return $this->correctionService->inferRequestType(
            $attendance,
            $requestedTimeIn,
            $requestedTimeOut,
            $this->includeRequestedShift,
        );
    }

    private function buildRequestedDateTime(?string $dateTime): ?Carbon
    {
        if (! $dateTime) {
            return null;
        }

        return Carbon::parse($dateTime);
    }

    private function currentAttendance(): ?Attendance
    {
        return Attendance::query()
            ->with('shift')
            ->whereHas('employee', fn ($q) => $q->where('user_id', auth()->id()))
            ->whereDate('date', $this->attendanceDate)
            ->first();
    }

    private function defaultRequestedDateTime(string $direction): string
    {
        return $this->correctionService->defaultRequestedDateTime(
            $this->currentAttendance(),
            $this->attendanceDate,
            $this->requestedShiftId,
            $direction,
        );
    }

    private function attendanceSnapshotDateTime(?Attendance $attendance, string $direction): ?Carbon
    {
        return $this->correctionService->snapshotDateTime($attendance, $direction, $this->attendanceDate);
    }
}
