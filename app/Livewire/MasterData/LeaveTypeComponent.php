<?php

declare(strict_types=1);

namespace App\Livewire\MasterData;

use App\Models\LeaveType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class LeaveTypeComponent extends Component
{
    use WithPagination;

    public ?string $name = null;

    public ?string $code = null;

    public ?int $quota = 12;

    public bool $is_paid = true;

    public bool $is_active = true;

    public bool $deducts_from_quota = true;

    public bool $eligible_for_carry_forward = true;

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
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:20', Rule::unique('leave_types')->ignore($this->selectedId)],
            'quota' => ['required', 'integer', 'min:0', 'max:365'],
            'is_paid' => ['boolean'],
            'is_active' => ['boolean'],
            'deducts_from_quota' => ['boolean'],
            'eligible_for_carry_forward' => ['boolean'],
        ];
    }

    public function canManage(): bool
    {
        return auth()->user()?->hasRole('super-admin', 'hr-manager') ?? false;
    }

    public function showCreating(): void
    {
        $this->resetErrorBag();
        $this->resetForm();
        $this->creating = true;
    }

    public function create(): void
    {
        if (! $this->canManage()) {
            return;
        }
        $this->validate();
        LeaveType::create([
            'name' => trim($this->name),
            'code' => strtoupper(trim($this->code)),
            'quota' => $this->quota,
            'is_paid' => $this->is_paid,
            'is_active' => $this->is_active,
            'deducts_from_quota' => $this->deducts_from_quota,
            'eligible_for_carry_forward' => $this->eligible_for_carry_forward,
        ]);
        $this->creating = false;
        $this->resetForm();
        $this->dispatch('toast', variant: 'success', text: __('Tipe cuti berhasil ditambahkan.'));
    }

    public function edit(int $id): void
    {
        $this->resetErrorBag();
        $lt = LeaveType::findOrFail($id);
        $this->name = $lt->name;
        $this->code = $lt->code;
        $this->quota = $lt->quota;
        $this->is_paid = (bool) $lt->is_paid;
        $this->is_active = (bool) $lt->is_active;
        $this->deducts_from_quota = (bool) $lt->deducts_from_quota;
        $this->eligible_for_carry_forward = (bool) $lt->eligible_for_carry_forward;
        $this->selectedId = $id;
        $this->editing = true;
    }

    public function update(): void
    {
        if (! $this->canManage()) {
            return;
        }
        $this->validate();
        $lt = LeaveType::findOrFail($this->selectedId);
        $lt->update([
            'name' => trim($this->name),
            'code' => strtoupper(trim($this->code)),
            'quota' => $this->quota,
            'is_paid' => $this->is_paid,
            'is_active' => $this->is_active,
            'deducts_from_quota' => $this->deducts_from_quota,
            'eligible_for_carry_forward' => $this->eligible_for_carry_forward,
        ]);
        $this->editing = false;
        $this->resetForm();
        $this->dispatch('toast', variant: 'success', text: __('Tipe cuti berhasil diperbarui.'));
    }

    public function confirmDeletion(int $id): void
    {
        $lt = LeaveType::findOrFail($id);
        $this->deleteName = $lt->name;
        $this->confirmingDeletion = true;
        $this->selectedId = $id;
    }

    public function delete(): void
    {
        if (! $this->canManage()) {
            return;
        }
        $lt = LeaveType::findOrFail($this->selectedId);

        if ($lt->leaves()->count() > 0) {
            $this->dispatch('toast', variant: 'error', text: __('Tipe cuti tidak bisa dihapus karena masih punya data cuti.'));
            $this->confirmingDeletion = false;

            return;
        }

        $lt->delete();
        $this->dispatch('toast', variant: 'success', text: __('Tipe cuti berhasil dihapus.'));
        $this->confirmingDeletion = false;
        $this->selectedId = null;
        $this->deleteName = null;
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function toggleActive(int $id): void
    {
        if (! $this->canManage()) {
            return;
        }
        $lt = LeaveType::findOrFail($id);
        $lt->update(['is_active' => ! $lt->is_active]);
        $this->dispatch('toast', variant: 'success', text: $lt->is_active ? __('Tipe cuti diaktifkan.') : __('Tipe cuti dinonaktifkan.'));
    }

    private function resetForm(): void
    {
        $this->name = null;
        $this->code = null;
        $this->quota = 12;
        $this->is_paid = true;
        $this->is_active = true;
        $this->deducts_from_quota = true;
        $this->eligible_for_carry_forward = true;
        $this->selectedId = null;
        $this->creating = false;
        $this->editing = false;
    }

    public function render()
    {
        $leaveTypes = LeaveType::query()
            ->when(filled($this->search), function ($q) {
                $search = '%'.trim($this->search).'%';
                $q->where('name', DB::getDriverName() === 'pgsql' ? 'ilike' : 'like', $search)
                    ->orWhere('code', 'ilike', $search);
            })
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.master-data.leave-type', ['leaveTypes' => $leaveTypes]);
    }
}
