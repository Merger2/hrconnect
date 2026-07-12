<?php

declare(strict_types=1);

namespace App\Livewire\MasterData;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class BranchForm extends Component
{
    public string $name = '';

    public string $address = '';

    public ?string $latitude = null;

    public ?string $longitude = null;

    public ?string $radius = null;

    public bool $isMain = false;

    public bool $isActive = true;

    public ?Branch $branch = null;

    public string $companyId = '';

    public function mount(?Branch $branch = null): void
    {
        if ($branch?->exists) {
            Gate::authorize('update', $branch);

            $this->branch = $branch;
            $this->companyId = (string) $branch->company_id;
            $this->name = $branch->name;
            $this->address = $branch->address ?? '';
            $this->latitude = $branch->latitude;
            $this->longitude = $branch->longitude;
            $this->radius = (string) $branch->radius;
            $this->isMain = (bool) $branch->is_main;
            $this->isActive = (bool) $branch->is_active;
        } else {
            Gate::authorize('create', Branch::class);
        }
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('branches')->ignore($this->branch?->id)],
            'companyId' => ['required', 'exists:companies,id'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'radius' => ['nullable', 'integer', 'min:10', 'max:5000'],
            'isMain' => ['boolean'],
            'isActive' => ['boolean'],
        ];
    }

    public function save(): void
    {
        if ($this->branch?->exists) {
            Gate::authorize('update', $this->branch);
        } else {
            Gate::authorize('create', Branch::class);
        }

        $this->validate();

        if ($this->isMain) {
            Branch::where('company_id', $this->companyId)
                ->when($this->branch?->exists, fn ($q) => $q->where('id', '!=', $this->branch->id))
                ->update(['is_main' => false]);
        }

        $data = [
            'company_id' => (int) $this->companyId,
            'name' => trim($this->name),
            'address' => $this->address ? trim($this->address) : null,
            'is_main' => (bool) $this->isMain,
            'latitude' => $this->latitude !== null && $this->latitude !== '' ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null && $this->longitude !== '' ? (float) $this->longitude : null,
            'radius' => $this->radius !== null && $this->radius !== '' ? (int) $this->radius : 100,
            'is_active' => (bool) $this->isActive,
        ];

        if ($this->branch?->exists) {
            $this->branch->update($data);
            $message = __('Cabang berhasil diperbarui.');
        } else {
            Branch::create($data);
            $message = __('Cabang berhasil ditambahkan.');
        }

        $this->dispatch('toast', variant: 'success', text: $message);

        $this->redirect(route('master-data.branches'), navigate: true);
    }

    public function render()
    {
        return view('livewire.master-data.branch-form', [
            'companies' => Company::orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
