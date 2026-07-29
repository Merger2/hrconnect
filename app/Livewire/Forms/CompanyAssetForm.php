<?php

namespace App\Livewire\Forms;

use App\Models\CompanyAsset;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;

class CompanyAssetForm extends Form
{
    public ?CompanyAsset $companyAsset = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:255|unique:company_assets,serial_number')]
    public ?string $serial_number = null;

    #[Validate('nullable|string|max:255')]
    public ?string $type = null;

    #[Validate('nullable|date')]
    public ?string $purchase_date = null;

    #[Validate('nullable|numeric')]
    public ?float $purchase_cost = null;

    #[Validate('nullable|date')]
    public ?string $expiration_date = null;

    #[Validate('nullable|integer|exists:users,id')]
    public ?int $user_id = null;

    #[Validate('nullable|date')]
    public ?string $date_assigned = null;

    #[Validate('nullable|date|after_or_equal:date_assigned')]
    public ?string $return_date = null;

    #[Validate('required|string')]
    public string $status = CompanyAsset::STATUS_AVAILABLE;

    #[Validate('nullable|string|max:1000')]
    public ?string $notes = null;

    public function setCompanyAsset(CompanyAsset $companyAsset)
    {
        $this->companyAsset = $companyAsset;
        $this->name = $companyAsset->name;
        $this->serial_number = $companyAsset->serial_number;
        $this->type = $companyAsset->type;
        $this->purchase_date = $companyAsset->purchase_date?->format('Y-m-d');
        $this->purchase_cost = $companyAsset->purchase_cost;
        $this->expiration_date = $companyAsset->expiration_date?->format('Y-m-d');
        $this->user_id = $companyAsset->user_id;
        $this->date_assigned = $companyAsset->date_assigned?->format('Y-m-d');
        $this->return_date = $companyAsset->return_date?->format('Y-m-d');
        $this->status = $companyAsset->status;
        $this->notes = $companyAsset->notes;
    }

    public function store(): CompanyAsset
    {
        $this->validate();

        $companyAsset = CompanyAsset::create($this->all());

        $this->reset();

        return $companyAsset;
    }

    public function update(): CompanyAsset
    {
        $this->validate([
            'serial_number' => ['nullable', 'string', 'max:255', Rule::unique('company_assets', 'serial_number')->ignore($this->companyAsset)],
        ]);

        $this->companyAsset->update($this->all());

        return $this->companyAsset;
    }
}
