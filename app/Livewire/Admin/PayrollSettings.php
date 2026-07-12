<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\BpjsConfig;
use App\Models\TaxConfig;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class PayrollSettings extends Component
{
    public string $activeTab = 'pph21';

    // PPh21 (TER)
    public ?int $taxSelectedId = null;

    public bool $taxEditing = false;

    public ?string $terCategory = null;

    public ?string $minIncome = null;

    public ?string $maxIncome = null;

    public ?string $rate = null;

    public ?string $effectiveRate = null;

    // BPJS
    public ?int $bpjsSelectedId = null;

    public bool $bpjsEditing = false;

    public ?string $bpjsName = null;

    public ?string $employerRate = null;

    public ?string $employeeRate = null;

    public ?string $ceiling = null;

    protected function rules(): array
    {
        return [
            'terCategory' => ['required', 'string', 'size:1', 'in:A,B,C'],
            'minIncome' => ['required', 'numeric', 'min:0'],
            'maxIncome' => ['required', 'numeric', 'gt:minIncome'],
            'rate' => ['required', 'numeric', 'between:0,1'],
            'effectiveRate' => ['nullable', 'numeric', 'between:0,1'],
            'bpjsName' => ['required', 'string', 'max:50'],
            'employerRate' => ['required', 'numeric', 'between:0,1'],
            'employeeRate' => ['required', 'numeric', 'between:0,1'],
            'ceiling' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function canManage(): bool
    {
        return Gate::allows('manage_tax_configs') || Gate::allows('manage_bpjs_configs');
    }

    // ── PPh21 ──────────────────────────────────────────
    public function editTax(int $id): void
    {
        $this->resetErrorBag();
        $tax = TaxConfig::findOrFail($id);
        $this->taxSelectedId = $id;
        $this->terCategory = $tax->ter_category;
        $this->minIncome = (string) $tax->min_income;
        $this->maxIncome = (string) $tax->max_income;
        $this->rate = (string) $tax->rate;
        $this->effectiveRate = $tax->effective_rate ? (string) $tax->effective_rate : null;
        $this->taxEditing = true;
    }

    public function updateTax(): void
    {
        Gate::authorize('manage_tax_configs');
        $this->validate([
            'terCategory' => ['required', 'string', 'size:1', 'in:A,B,C'],
            'minIncome' => ['required', 'numeric', 'min:0'],
            'maxIncome' => ['required', 'numeric', 'gt:minIncome'],
            'rate' => ['required', 'numeric', 'between:0,1'],
            'effectiveRate' => ['nullable', 'numeric', 'between:0,1'],
        ]);
        $tax = TaxConfig::findOrFail($this->taxSelectedId);
        $tax->update([
            'ter_category' => $this->terCategory,
            'min_income' => $this->minIncome,
            'max_income' => $this->maxIncome,
            'rate' => $this->rate,
            'effective_rate' => $this->effectiveRate,
        ]);
        Cache::forget('tax_configs:all');
        $this->taxEditing = false;
        $this->resetTaxForm();
        $this->dispatch('toast', variant: 'success', text: __('Konfigurasi PPh21 berhasil diperbarui.'));
    }

    private function resetTaxForm(): void
    {
        $this->taxSelectedId = null;
        $this->terCategory = null;
        $this->minIncome = null;
        $this->maxIncome = null;
        $this->rate = null;
        $this->effectiveRate = null;
    }

    // ── BPJS ──────────────────────────────────────────
    public function editBpjs(int $id): void
    {
        $this->resetErrorBag();
        $bpjs = BpjsConfig::findOrFail($id);
        $this->bpjsSelectedId = $id;
        $this->bpjsName = $bpjs->name;
        $this->employerRate = (string) $bpjs->employer_rate;
        $this->employeeRate = (string) $bpjs->employee_rate;
        $this->ceiling = $bpjs->ceiling ? (string) $bpjs->ceiling : null;
        $this->bpjsEditing = true;
    }

    public function updateBpjs(): void
    {
        Gate::authorize('manage_bpjs_configs');
        $this->validate([
            'bpjsName' => ['required', 'string', 'max:50'],
            'employerRate' => ['required', 'numeric', 'between:0,1'],
            'employeeRate' => ['required', 'numeric', 'between:0,1'],
            'ceiling' => ['nullable', 'numeric', 'min:0'],
        ]);
        $bpjs = BpjsConfig::findOrFail($this->bpjsSelectedId);
        $bpjs->update([
            'name' => $this->bpjsName,
            'employer_rate' => $this->employerRate,
            'employee_rate' => $this->employeeRate,
            'ceiling' => $this->ceiling,
        ]);
        Cache::forget('bpjs_configs:all');
        $this->bpjsEditing = false;
        $this->resetBpjsForm();
        $this->dispatch('toast', variant: 'success', text: __('Konfigurasi BPJS berhasil diperbarui.'));
    }

    private function resetBpjsForm(): void
    {
        $this->bpjsSelectedId = null;
        $this->bpjsName = null;
        $this->employerRate = null;
        $this->employeeRate = null;
        $this->ceiling = null;
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        $taxConfigs = TaxConfig::orderBy('ter_category')->orderBy('min_income')->get();
        $bpjsConfigs = BpjsConfig::orderBy('name')->get();

        return view('livewire.admin.payroll-settings', [
            'taxConfigs' => $taxConfigs,
            'bpjsConfigs' => $bpjsConfigs,
        ]);
    }
}
