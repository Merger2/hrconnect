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

    public bool $isActive = true;

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
            'isActive' => ['boolean'],
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
        $this->isActive = true;
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
            'radius' => $this->radius !== null && $this->radius !== '' ? (int) $this->radius : 100,
            'is_active' => (bool) $this->isActive,
        ]);
        $this->creating = false;
        $this->dispatch('toast', variant: 'success', text: __('Cabang berhasil ditambahkan.'));
    }

    public function edit(int $id): void
    {
        $branch = Branch::findOrFail($id);
        $this->name = $branch->name;
        $this->address = $branch->address;
        $this->latitude = $branch->latitude;
        $this->longitude = $branch->longitude;
        $this->radius = (string) $branch->radius;
        $this->isActive = (bool) $branch->is_active;
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
            'radius' => $this->radius !== null && $this->radius !== '' ? (int) $this->radius : 100,
            'is_active' => (bool) $this->isActive,
        ]);
        $this->editing = false;
        $this->dispatch('toast', variant: 'success', text: __('Cabang berhasil diperbarui.'));
    }

    public function confirmDeletion(int $id): void
    {
        $this->deleteName = Branch::findOrFail($id)->name;
        $this->confirmingDeletion = true;
        $this->selectedId = $id;
    }

    public function delete(): void
    {
        Gate::authorize('manage_branches');

        $branch = Branch::findOrFail($this->selectedId);

        if ($branch->departments()->count() > 0) {
            $this->dispatch('toast', variant: 'error', text: __('Cabang tidak bisa dihapus karena masih memiliki departemen.'));
            $this->confirmingDeletion = false;
            return;
        }

        if ($branch->employees()->count() > 0) {
            $this->dispatch('toast', variant: 'error', text: __('Cabang tidak bisa dihapus karena masih memiliki pegawai.'));
            $this->confirmingDeletion = false;
            return;
        }

        $branch->delete();
        $this->dispatch('toast', variant: 'success', text: __('Cabang berhasil dihapus.'));
        $this->confirmingDeletion = false;
        $this->selectedId = null;
        $this->deleteName = null;
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.master-data.branch', [
            'branches' => Branch::query()
                ->when(filled($this->search), function ($q) {
                    $search = '%'.trim($this->search).'%';
                    $q->where('name', \Illuminate\Support\Facades\DB::getDriverName() === 'pgsql' ? 'ilike' : 'like', $search);
                })
                ->orderBy('name')
                ->paginate($this->perPage),
        ]);
    }
}
