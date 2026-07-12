<?php

declare(strict_types=1);

namespace App\Livewire\MasterData;

use App\Models\Shift;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ShiftComponent extends Component
{
    use WithPagination;

    public ?string $name = null;

    public ?string $start_time = null;

    public ?string $end_time = null;

    public ?int $late_tolerance_minutes = 15;

    public bool $is_active = true;

    public ?string $deleteName = null;

    public bool $creating = false;

    public bool $editing = false;

    public bool $confirmingDeletion = false;

    public ?int $selectedId = null;

    public string $search = '';

    public int $perPage = 10;

    protected $queryString = ['search' => ['except' => '']];

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('shifts')->ignore($this->selectedId)],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'late_tolerance_minutes' => ['required', 'integer', 'min:0', 'max:120'],
            'is_active' => ['boolean'],
        ];
    }

    public function canManage(): bool
    {
        return Gate::allows('manage_shifts');
    }

    public function showCreating(): void
    {
        $this->resetErrorBag();
        $this->resetForm();
        $this->creating = true;
    }

    public function create(): void
    {
        Gate::authorize('manage_shifts');
        $this->validate();
        Shift::create([
            'name' => trim($this->name),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'late_tolerance_minutes' => $this->late_tolerance_minutes,
            'is_active' => $this->is_active,
        ]);
        $this->creating = false;
        $this->resetForm();
        $this->dispatch('toast', variant: 'success', text: __('Shift berhasil ditambahkan.'));
    }

    public function edit(int $id): void
    {
        $this->resetErrorBag();
        $shift = Shift::findOrFail($id);
        $this->name = $shift->name;
        $this->start_time = $shift->start_time instanceof \DateTimeInterface ? $shift->start_time->format('H:i') : substr((string) $shift->start_time, 0, 5);
        $this->end_time = $shift->end_time instanceof \DateTimeInterface ? $shift->end_time->format('H:i') : substr((string) $shift->end_time, 0, 5);
        $this->late_tolerance_minutes = $shift->late_tolerance_minutes;
        $this->is_active = (bool) $shift->is_active;
        $this->selectedId = $id;
        $this->editing = true;
    }

    public function update(): void
    {
        Gate::authorize('manage_shifts');
        $this->validate();
        $shift = Shift::findOrFail($this->selectedId);
        $shift->update([
            'name' => trim($this->name),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'late_tolerance_minutes' => $this->late_tolerance_minutes,
            'is_active' => $this->is_active,
        ]);
        $this->editing = false;
        $this->resetForm();
        $this->dispatch('toast', variant: 'success', text: __('Shift berhasil diperbarui.'));
    }

    public function confirmDeletion(int $id): void
    {
        $shift = Shift::findOrFail($id);
        $this->deleteName = $shift->name;
        $this->confirmingDeletion = true;
        $this->selectedId = $id;
    }

    public function delete(): void
    {
        Gate::authorize('manage_shifts');
        $shift = Shift::findOrFail($this->selectedId);

        if ($shift->attendances()->count() > 0) {
            $this->dispatch('toast', variant: 'error', text: __('Shift tidak bisa dihapus karena masih punya data absensi.'));
            $this->confirmingDeletion = false;

            return;
        }

        $shift->delete();
        $this->dispatch('toast', variant: 'success', text: __('Shift berhasil dihapus.'));
        $this->confirmingDeletion = false;
        $this->selectedId = null;
        $this->deleteName = null;
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function toggleActive(int $id): void
    {
        Gate::authorize('manage_shifts');
        $shift = Shift::findOrFail($id);
        $shift->update(['is_active' => ! $shift->is_active]);
        $this->dispatch('toast', variant: 'success', text: $shift->is_active ? __('Shift diaktifkan.') : __('Shift dinonaktifkan.'));
    }

    private function resetForm(): void
    {
        $this->name = null;
        $this->start_time = null;
        $this->end_time = null;
        $this->late_tolerance_minutes = 15;
        $this->is_active = true;
        $this->selectedId = null;
        $this->creating = false;
        $this->editing = false;
    }

    public function render()
    {
        $shifts = Shift::query()
            ->when(filled($this->search), function ($q) {
                $search = '%'.trim($this->search).'%';
                $q->where('name', DB::getDriverName() === 'pgsql' ? 'ilike' : 'like', $search);
            })
            ->orderBy('start_time')
            ->paginate($this->perPage);

        return view('livewire.master-data.shift', ['shifts' => $shifts]);
    }
}
