<?php

namespace App\Livewire\Admin;

use App\Livewire\Forms\EmployeeForm;
use App\Models\Employee;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class EmployeeCreate extends Component
{
    public EmployeeForm $form;

    public function mount(): void
    {
        Gate::authorize('create', Employee::class);
    }

    public function updatedFormProvinceId(): void
    {
        $this->form->city_id = null;
        $this->form->district_id = null;
        $this->form->village_id = null;
    }

    public function updatedFormCityId(): void
    {
        $this->form->district_id = null;
        $this->form->village_id = null;
    }

    public function updatedFormDistrictId(): void
    {
        $this->form->village_id = null;
    }

    public function save()
    {
        try {
            $this->validate();
        } catch (\Throwable $e) {
            $this->clearSensitive();
            throw $e;
        }

        try {
            $employee = $this->form->store();

            $this->clearSensitive();

            session()->flash('success', 'Karyawan berhasil ditambahkan.');

            return $this->redirect(route('admin.employees.show', $employee), navigate: true);
        } catch (\Throwable $e) {
            $this->clearSensitive();

            Log::error('Gagal tambah karyawan: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', 'Gagal menambahkan karyawan: '.$e->getMessage());
        }
    }

    private function clearSensitive(): void
    {
        $this->form->password = '';
        $this->form->password_confirmation = '';
        $this->form->nik = '';
        $this->form->phone = '';
    }

    public function render()
    {
        $provinces = \Laravolt\Indonesia\Models\Province::orderBy('name')->get();

        $cities = collect();
        if ($this->form->province_id) {
            $cities = \Laravolt\Indonesia\Models\City::where('province_id', $this->form->province_id)->orderBy('name')->get();
        }

        $districts = collect();
        if ($this->form->city_id) {
            $districts = \Laravolt\Indonesia\Models\District::where('city_id', $this->form->city_id)->orderBy('name')->get();
        }

        $villages = collect();
        if ($this->form->district_id) {
            $villages = \Laravolt\Indonesia\Models\Village::where('district_id', $this->form->district_id)->orderBy('name')->get();
        }

        $companies = \App\Models\Company::orderBy('name')->get(['id', 'name']);
        $branches = \App\Models\Branch::orderBy('name')->get(['id', 'name']);
        $departments = \App\Models\Department::orderBy('name')->get(['id', 'name']);
        $positions = \App\Models\Position::orderBy('name')->get(['id', 'name']);
        $managers = Employee::whereHas('user', fn ($q) => $q->whereNotNull('id'))
            ->orderBy('full_name')
            ->get(['id', 'full_name']);

        return view('livewire.admin.employee-create', compact(
            'provinces', 'cities', 'districts', 'villages',
            'companies', 'branches', 'departments', 'positions', 'managers',
        ));
    }
}
