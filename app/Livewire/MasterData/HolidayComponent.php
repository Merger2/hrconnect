<?php

declare(strict_types=1);

namespace App\Livewire\MasterData;

use App\Models\Holiday;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class HolidayComponent extends Component
{
    use WithPagination;

    public ?string $name = null;

    public ?string $date = null;

    public bool $is_active = true;

    public ?string $deleteName = null;

    public bool $creating = false;

    public bool $editing = false;

    public bool $confirmingDeletion = false;

    public ?int $selectedId = null;

    public string $search = '';

    public int $perPage = 15;

    public ?int $yearFilter = null;

    protected $queryString = ['search' => ['except' => ''], 'yearFilter' => ['except' => null]];

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'date' => ['required', 'date', Rule::unique('holidays')->ignore($this->selectedId)],
            'is_active' => ['boolean'],
        ];
    }

    public function canManage(): bool
    {
        return Gate::allows('manage_holidays');
    }

    public function mount(): void
    {
        $this->yearFilter = $this->yearFilter ?? (int) now()->year;
    }

    public function showCreating(): void
    {
        $this->resetErrorBag();
        $this->resetForm();
        $this->creating = true;
    }

    public function create(): void
    {
        Gate::authorize('manage_holidays');
        $this->validate();
        Holiday::create([
            'name' => trim($this->name),
            'date' => $this->date,
            'is_active' => $this->is_active,
        ]);
        $this->creating = false;
        $this->resetForm();
        $this->dispatch('toast', variant: 'success', text: __('Hari libur berhasil ditambahkan.'));
    }

    public function edit(int $id): void
    {
        $this->resetErrorBag();
        $holiday = Holiday::findOrFail($id);
        $this->name = $holiday->name;
        $this->date = $holiday->date?->format('Y-m-d');
        $this->is_active = (bool) $holiday->is_active;
        $this->selectedId = $id;
        $this->editing = true;
    }

    public function update(): void
    {
        Gate::authorize('manage_holidays');
        $this->validate();
        $holiday = Holiday::findOrFail($this->selectedId);
        $holiday->update([
            'name' => trim($this->name),
            'date' => $this->date,
            'is_active' => $this->is_active,
        ]);
        $this->editing = false;
        $this->resetForm();
        $this->dispatch('toast', variant: 'success', text: __('Hari libur berhasil diperbarui.'));
    }

    public function confirmDeletion(int $id): void
    {
        $holiday = Holiday::findOrFail($id);
        $this->deleteName = $holiday->name;
        $this->confirmingDeletion = true;
        $this->selectedId = $id;
    }

    public function delete(): void
    {
        Gate::authorize('manage_holidays');
        $holiday = Holiday::findOrFail($this->selectedId);
        $holiday->delete();
        $this->dispatch('toast', variant: 'success', text: __('Hari libur berhasil dihapus.'));
        $this->confirmingDeletion = false;
        $this->selectedId = null;
        $this->deleteName = null;
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedYearFilter(): void
    {
        $this->resetPage();
    }

    private function resetForm(): void
    {
        $this->name = null;
        $this->date = null;
        $this->is_active = true;
        $this->selectedId = null;
        $this->creating = false;
        $this->editing = false;
    }

    public function render()
    {
        $holidays = Holiday::query()
            ->when(filled($this->yearFilter), fn ($q) => $q->whereYear('date', $this->yearFilter))
            ->when(filled($this->search), function ($q) {
                $search = '%'.trim($this->search).'%';
                $q->where('name', DB::getDriverName() === 'pgsql' ? 'ilike' : 'like', $search);
            })
            ->orderBy('date')
            ->paginate($this->perPage);

        $years = Holiday::selectRaw('DISTINCT EXTRACT(YEAR FROM date) as y')->orderBy('y', 'desc')->pluck('y')->map(fn ($y) => (int) $y)->toArray();

        return view('livewire.master-data.holiday', [
            'holidays' => $holidays,
            'years' => $years,
        ]);
    }
}
