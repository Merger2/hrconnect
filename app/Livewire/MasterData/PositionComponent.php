<?php

declare(strict_types=1);

namespace App\Livewire\MasterData;

use App\Models\Department;
use App\Models\Position;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class PositionComponent extends Component
{
    use WithPagination;

    public ?string $name = null;

    public ?string $code = null;

    public ?int $department_id = null;

    public ?int $grade = null;

    public ?float $basic_salary = null;

    public ?float $allowance_jabatan = null;

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
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', Rule::unique('positions')->ignore($this->selectedId)],
            'department_id' => ['nullable', 'exists:departments,id'],
            'grade' => ['nullable', 'integer', 'min:1'],
            'basic_salary' => ['nullable', 'numeric', 'min:0'],
            'allowance_jabatan' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function canManage(): bool
    {
        return Gate::allows('manage_positions');
    }

    public function showCreating(): void
    {
        $this->resetErrorBag();
        $this->resetForm();
        $this->creating = true;
    }

    public function create(): void
    {
        Gate::authorize('manage_positions');
        $this->validate();
        Position::create([
            'name' => trim($this->name),
            'code' => trim($this->code),
            'department_id' => $this->department_id,
            'grade' => $this->grade,
            'basic_salary' => $this->basic_salary,
            'allowance_jabatan' => $this->allowance_jabatan ?? 0,
        ]);
        $this->creating = false;
        $this->resetForm();
        $this->dispatch('toast', variant: 'success', text: __('Jabatan berhasil ditambahkan.'));
    }

    public function edit(int $id): void
    {
        $this->resetErrorBag();
        $pos = Position::findOrFail($id);
        $this->name = $pos->name;
        $this->code = $pos->code;
        $this->department_id = $pos->department_id;
        $this->grade = $pos->grade;
        $this->basic_salary = $pos->basic_salary;
        $this->allowance_jabatan = $pos->allowance_jabatan;
        $this->selectedId = $id;
        $this->editing = true;
    }

    public function update(): void
    {
        Gate::authorize('manage_positions');
        $this->validate();
        $pos = Position::findOrFail($this->selectedId);
        $pos->update([
            'name' => trim($this->name),
            'code' => trim($this->code),
            'department_id' => $this->department_id,
            'grade' => $this->grade,
            'basic_salary' => $this->basic_salary,
            'allowance_jabatan' => $this->allowance_jabatan ?? 0,
        ]);
        $this->editing = false;
        $this->resetForm();
        $this->dispatch('toast', variant: 'success', text: __('Jabatan berhasil diperbarui.'));
    }

    public function confirmDeletion(int $id): void
    {
        $pos = Position::findOrFail($id);
        $this->deleteName = $pos->name;
        $this->confirmingDeletion = true;
        $this->selectedId = $id;
    }

    public function delete(): void
    {
        Gate::authorize('manage_positions');
        $pos = Position::findOrFail($this->selectedId);
        $pos->delete();
        $this->confirmingDeletion = false;
        $this->selectedId = null;
        $this->deleteName = null;
        $this->resetPage();
        $this->dispatch('toast', variant: 'success', text: __('Jabatan berhasil dihapus.'));
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    private function resetForm(): void
    {
        $this->name = null;
        $this->code = null;
        $this->department_id = null;
        $this->grade = null;
        $this->basic_salary = null;
        $this->allowance_jabatan = null;
        $this->selectedId = null;
        $this->creating = false;
        $this->editing = false;
    }

    public function render()
    {
        $positions = Position::query()
            ->with('department.branch')
            ->when(filled($this->search), fn ($q) => $q->where('name', 'ilike', '%'.trim($this->search).'%'))
            ->orderBy('name')
            ->paginate($this->perPage);

        $departments = Department::orderBy('name')->get();

        return view('livewire.master-data.position', [
            'positions' => $positions,
            'departments' => $departments,
        ]);
    }
}
