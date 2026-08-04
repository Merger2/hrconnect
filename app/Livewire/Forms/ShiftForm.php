<?php

namespace App\Livewire\Forms;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\Employee;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\ShiftSchedule;
use App\Models\ShiftSwapRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Form;

class ShiftForm extends Form
{
    public ?Shift $shift = null;

    public string $name = '';

    public ?string $start_time = null;

    public ?string $end_time = null;

    public function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('shifts')->ignore($this->shift),
            ],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
        ];
    }

    public function setShift(Shift $shift)
    {
        $this->shift = $shift;
        $this->name = $shift->name;
        $this->start_time = $this->normalizeTime($shift->start_time);
        $this->end_time = $this->normalizeTime($shift->end_time);

        return $this;
    }

    public function store()
    {
        Gate::authorize('manageMasterData');
        $this->validate();
        Shift::create($this->payload());
        $this->reset();
    }

    public function update()
    {
        Gate::authorize('manageMasterData');
        $this->validate();
        $this->shift->update($this->payload());
        $this->reset();
    }

    public function delete()
    {
        Gate::authorize('manageMasterData');

        DB::transaction(function () {
            // Detach referensi ke shift yang di-soft-delete supaya tidak menggantung
            // (MasterDataDeleteFlowTest: attendances.shift_id harus null).
            Attendance::query()->where('shift_id', $this->shift->id)->update(['shift_id' => null]);
            Employee::query()->where('shift_id', $this->shift->id)->update(['shift_id' => null]);
            AttendanceCorrection::query()->where('requested_shift_id', $this->shift->id)->update(['requested_shift_id' => null]);
            ShiftSwapRequest::query()->where('current_shift_id', $this->shift->id)->update(['current_shift_id' => null]);
            ShiftSwapRequest::query()->where('requested_shift_id', $this->shift->id)->update(['requested_shift_id' => null]);

            // schedules dihapus (ekspektasi MasterDataDeleteFlowTest), dan
            // shift_schedules.shift_id NOT NULL + restrictOnDelete → wajib dihapus.
            Schedule::query()->where('shift_id', $this->shift->id)->delete();
            ShiftSchedule::query()->where('shift_id', $this->shift->id)->delete();

            $this->shift->delete();
        });

        $this->reset();
    }

    protected function payload(): array
    {
        return [
            'name' => trim($this->name),
            'start_time' => $this->normalizeTime($this->start_time),
            'end_time' => $this->normalizeTime($this->end_time),
        ];
    }

    protected function normalizeTime(?string $time): ?string
    {
        if (blank($time)) {
            return null;
        }

        return substr($time, 0, 5);
    }
}
