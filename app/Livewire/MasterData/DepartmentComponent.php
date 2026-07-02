<?php

declare(strict_types=1);

namespace App\Livewire\MasterData;

use App\Models\Branch;
use App\Models\Department;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class DepartmentComponent extends Component
{
    use WithPagination;

    public ?string $name = null;

    public ?string $code = null;

    public ?int $branch_id = null;

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
            'code' => ['required', 'string', 'max:20', Rule::unique('departments')->ignore($this->selectedId)],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ];
    }

    public function canManage(): bool
    {
        return Gate::allows('manage_departments');
    }

    public function showCreating(): void
    {
        $this->resetErrorBag();
        $this->resetForm();
        $this->creating = true;
    }

    public function create(): void
    {
        Gate::authorize('manage_departments');
        $this->validate();
        Department::create([
            'name' => trim($this->name),
            'code' => trim($this->code),
            'branch_id' => $this->branch_id,
        ]);
        $this->creating = false;
        $this->resetForm();
        $this->dispatch('toast', variant: 'success', text: __('Departemen berhasil ditambahkan.'));
    }

    public function edit(int $id): void
    {
        $this->resetErrorBag();
        $dept = Department::findOrFail($id);
        $this->name = $dept->name;
        $this->code = $dept->code;
        $this->branch_id = $dept->branch_id;
        $this->selectedId = $id;
        $this->editing = true;
    }

    public function update(): void
    {
        Gate::authorize('manage_departments');
        $this->validate();
        $dept = Department::findOrFail($this->selectedId);
        $dept->update([
            'name' => trim($this->name),
            'code' => trim($this->code),
            'branch_id' => $this->branch_id,
        ]);
        $this->editing = false;
        $this->resetForm();
        $this->dispatch('toast', variant: 'success', text: __('Departemen berhasil diperbarui.'));
    }

    public function confirmDeletion(int $id): void
    {
        $dept = Department::findOrFail($id);
        $this->deleteName = $dept->name;
        $this->confirmingDeletion = true;
        $this->selectedId = $id;
    }

    public function delete(): void
    {
        Gate::authorize('manage_departments');
        $dept = Department::findOrFail($this->selectedId);
        $dept->delete();
        $this->confirmingDeletion = false;
        $this->selectedId = null;
        $this->deleteName = null;
        $this->resetPage();
        $this->dispatch('toast', variant: 'success', text: __('Departemen berhasil dihapus.'));
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
        $this->branch_id = null;
        $this->selectedId = null;
        $this->creating = false;
        $this->editing = false;
    }

    public function render()
    {
        $departments = Department::query()
            ->with('branch')
            ->when(filled($this->search), fn ($q) => $q->where('name', 'ilike', '%'.trim($this->search).'%'))
            ->orderBy('name')
            ->paginate($this->perPage);

        $branches = Branch::orderBy('name')->get();

        return view('livewire.master-data.department', [
            'departments' => $departments,
            'branches' => $branches,
        ]);
    }
}
