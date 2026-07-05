<?php

declare(strict_types=1);

namespace App\Livewire\MasterData;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class BranchComponent extends Component
{
    use WithPagination;

    public ?string $name = null;

    public ?string $address = null;

    public ?string $latitude = null;

    public ?string $longitude = null;

    public ?string $radius = null;

    public bool $creating = false;

    public bool $editing = false;

    public bool $confirmingDeletion = false;

    public ?int $selectedId = null;

    public ?string $deleteName = null;

    public string $search = '';

    public int $perPage = 10;

    protected $queryString = ['search' => ['except' => '']];

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('branches')->ignore($this->selectedId)],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'radius' => ['nullable', 'integer', 'min:10', 'max:5000'],
        ];
    }

    public function canManage(): bool
    {
        return Gate::allows('manage_branches');
    }

    public function showCreating(): void
    {
        $this->resetErrorBag();
        $this->name = null;
        $this->address = null;
        $this->latitude = null;
        $this->longitude = null;
        $this->radius = null;
        $this->selectedId = null;
        $this->creating = true;
    }

    public function create(): void
    {
        Gate::authorize('manage_branches');
        $this->validate();
        Branch::create([
            'company_id' => Company::first()?->id,
            'name' => trim($this->name),
            'address' => $this->address ? trim($this->address) : null,
            'latitude' => $this->latitude !== null && $this->latitude !== '' ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null && $this->longitude !== '' ? (float) $this->longitude : null,
            'radius' => $this->radius !== null && $this->radius !== '' ? (int) $this->radius : null,
        ]);
        $this->creating = false;
        $this->dispatch('toast', variant: 'success', text: __('Cabang berhasil ditambahkan.'));
    }

    public function edit(int $id): void
    {
        $this->resetErrorBag();
        $branch = Branch::findOrFail($id);
        $this->name = $branch->name;
        $this->address = $branch->address;
        $this->latitude = $branch->latitude;
        $this->longitude = $branch->longitude;
        $this->radius = $branch->radius;
        $this->selectedId = $id;
        $this->editing = true;
    }

    public function update(): void
    {
        Gate::authorize('manage_branches');
        $this->validate();
        $branch = Branch::findOrFail($this->selectedId);
        $branch->update([
            'name' => trim($this->name),
            'address' => $this->address ? trim($this->address) : null,
            'latitude' => $this->latitude !== null && $this->latitude !== '' ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null && $this->longitude !== '' ? (float) $this->longitude : null,
            'radius' => $this->radius !== null && $this->radius !== '' ? (int) $this->radius : null,
        ]);
        $this->editing = false;
        $this->dispatch('toast', variant: 'success', text: __('Cabang berhasil diperbarui.'));
    }

    public function confirmDeletion(int $id): void
    {
        $this->deleteName = Branch::findOrFail($id)->name;
        $this->confirmingDeletion = true;
        $this->selectedId = $id;
        $this->dispatch('open-modal', 'delete-branch');
    }

    public function delete(): void
    {
        Gate::authorize('manage_branches');
        Branch::findOrFail($this->selectedId)->delete();
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

    public function render()
    {
        return view('livewire.master-data.branch', [
            'branches' => Branch::query()
                ->when(filled($this->search), fn ($q) => $q->where('name', 'ilike', '%'.trim($this->search).'%'))
                ->orderBy('name')
                ->paginate($this->perPage),
        ]);
    }
}
