<?php

declare(strict_types=1);

namespace App\Livewire\MasterData;

use App\Models\Branch;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class BranchComponent extends Component
{
    use WithPagination;

    public bool $confirmingDeletion = false;

    public ?int $selectedId = null;

    public ?string $deleteName = null;

    public string $search = '';

    public int $perPage = 10;

    public bool $canManage = false;

    protected $queryString = ['search' => ['except' => '']];

    public function confirmDeletion(int $id): void
    {
        $this->deleteName = Branch::findOrFail($id)->name;
        $this->confirmingDeletion = true;
        $this->selectedId = $id;
    }

    public function delete(): void
    {
        $branch = Branch::findOrFail($this->selectedId);
        Gate::authorize('delete', $branch);

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
        $this->canManage = Gate::allows('create', Branch::class);

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
