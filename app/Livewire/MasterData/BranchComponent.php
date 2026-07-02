<?php

declare(strict_types=1);

namespace App\Livewire\MasterData;

use App\Models\Branch;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class BranchComponent extends Component
{
    use WithPagination;

    public ?string $name = null;

    public ?string $address = null;

    public ?string $deleteName = null;

    public ?string $deleteAddress = null;

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
            'name' => ['required', 'string', 'max:255', Rule::unique('branches')->ignore($this->selectedId)],
            'address' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function canManage(): bool
    {
        return Gate::allows('manage_branches');
    }

    public function showCreating(): void
    {
        $this->resetErrorBag();
        $this->resetForm();
        $this->creating = true;
    }

    public function create(): void
    {
        Gate::authorize('manage_branches');
        $this->validate();
        Branch::create(['name' => trim($this->name), 'address' => $this->address ? trim($this->address) : null]);
        $this->creating = false;
        $this->resetForm();
        $this->dispatch('toast', variant: 'success', text: __('Cabang berhasil ditambahkan.'));
    }

    public function edit(int $id): void
    {
        $this->resetErrorBag();
        $branch = Branch::findOrFail($id);
        $this->name = $branch->name;
        $this->address = $branch->address;
        $this->selectedId = $id;
        $this->editing = true;
    }

    public function update(): void
    {
        Gate::authorize('manage_branches');
        $this->validate();
        $branch = Branch::findOrFail($this->selectedId);
        $branch->update(['name' => trim($this->name), 'address' => $this->address ? trim($this->address) : null]);
        $this->editing = false;
        $this->resetForm();
        $this->dispatch('toast', variant: 'success', text: __('Cabang berhasil diperbarui.'));
    }

    public function confirmDeletion(int $id): void
    {
        $branch = Branch::findOrFail($id);
        $this->deleteName = $branch->name;
        $this->deleteAddress = $branch->address;
        $this->confirmingDeletion = true;
        $this->selectedId = $id;
    }

    public function delete(): void
    {
        Gate::authorize('manage_branches');
        $branch = Branch::findOrFail($this->selectedId);
        $branch->delete();
        $this->confirmingDeletion = false;
        $this->selectedId = null;
        $this->deleteName = null;
        $this->resetPage();
        $this->dispatch('toast', variant: 'success', text: __('Cabang berhasil dihapus.'));
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
        $this->address = null;
        $this->selectedId = null;
        $this->creating = false;
        $this->editing = false;
    }

    public function render()
    {
        $branches = Branch::query()
            ->when(filled($this->search), fn ($q) => $q->where('name', 'ilike', '%'.trim($this->search).'%'))
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.master-data.branch', ['branches' => $branches]);
    }
}
