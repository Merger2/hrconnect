<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\PayrollComponent;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
final class PayrollSettings extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public string $typeFilter = 'all';

    public string $activeFilter = 'all';

    public int $perPage = 10;

    public bool $showModal = false;

    public bool $confirmingDeletion = false;

    public ?int $selectedId = null;

    public string $name = '';

    public string $type = 'allowance';

    public string $calculation_type = 'fixed';

    public ?float $amount = null;

    public ?float $percentage = null;

    public bool $is_taxable = false;

    protected $queryString = ['search', 'typeFilter', 'activeFilter', 'perPage'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingActiveFilter(): void
    {
        $this->resetPage();
    }

    public function boot(): void
    {
        Gate::authorize('managePayrollSettings');
    }

    public function render(): View
    {
        $query = PayrollComponent::query();

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('code', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->typeFilter !== 'all') {
            $query->where('type', $this->typeFilter);
        }

        if ($this->activeFilter !== 'all') {
            $query->where('is_active', $this->activeFilter === 'active');
        }

        return view('livewire.admin.payroll-settings', [
            'components' => $query->orderBy('name')->paginate($this->perPage),
        ]);
    }

    public function create(): void
    {
        $this->resetValidation();
        $this->reset(['selectedId', 'name', 'type', 'calculation_type', 'amount', 'percentage', 'is_taxable']);
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $component = PayrollComponent::findOrFail($id);

        $this->selectedId = $component->id;
        $this->name = $component->name;
        $this->type = $component->type;
        $this->calculation_type = $component->calculation_type ?? 'fixed';
        // Cast decimal:2 mengembalikan string — harus float untuk typed ?float
        // (regresi 2026-08-16: TypeError 500 saat edit komponen ber-amount).
        $this->amount = $component->amount === null ? null : (float) $component->amount;
        $this->percentage = $component->percentage === null ? null : (float) $component->percentage;
        $this->is_taxable = $component->is_taxable;

        $this->showModal = true;
    }

    public function save(): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['allowance', 'deduction'])],
            'calculation_type' => ['required', Rule::in(['fixed', 'daily_presence', 'percentage_basic'])],
            'is_taxable' => ['boolean'],
        ];

        if ($this->calculation_type === 'percentage_basic') {
            $rules['percentage'] = ['required', 'numeric', 'min:0', 'max:100'];
        } else {
            $rules['amount'] = ['required', 'numeric', 'min:0'];
        }

        $validated = $this->validate($rules);

        $data = [
            'name' => $validated['name'],
            'type' => $validated['type'],
            'calculation_type' => $validated['calculation_type'],
            'amount' => $validated['amount'] ?? null,
            'percentage' => $validated['percentage'] ?? null,
            'is_taxable' => $validated['is_taxable'] ?? false,
        ];

        if ($this->selectedId) {
            $component = PayrollComponent::findOrFail($this->selectedId);
            $component->update($data);

            $this->dispatch('notify', type: 'success', message: __('Payroll component updated.'));
        } else {
            $data['code'] = strtoupper(str_replace(' ', '_', $validated['name']));
            PayrollComponent::create($data);

            $this->dispatch('notify', type: 'success', message: __('Payroll component created.'));
        }

        $this->showModal = false;
        $this->reset(['selectedId', 'name', 'type', 'calculation_type', 'amount', 'percentage', 'is_taxable']);
    }

    public function confirmDelete(int $id): void
    {
        $this->selectedId = $id;
        $this->confirmingDeletion = true;
    }

    public function delete(): void
    {
        $component = PayrollComponent::findOrFail($this->selectedId);
        $component->delete();

        $this->confirmingDeletion = false;
        $this->selectedId = null;

        $this->dispatch('notify', type: 'success', message: __('Payroll component deleted.'));
    }

    public function toggleActive(int $id): void
    {
        $component = PayrollComponent::findOrFail($id);
        $component->update(['is_active' => ! $component->is_active]);

        $this->dispatch('notify', type: 'success', message: $component->is_active
            ? __('Payroll component activated.')
            : __('Payroll component deactivated.')
        );
    }
}
