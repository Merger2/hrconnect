<?php

namespace App\Livewire\Admin;

use App\Livewire\Forms\EmployeeForm;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\Village;
use Livewire\Component;

class EmployeeCreate extends Component
{
    public EmployeeForm $form;

    public function mount(): void
    {
        Gate::authorize('create', Employee::class);

        $latest = Employee::withTrashed()
            ->where('employee_number', 'like', 'EMP-%')
            ->orderBy('employee_number', 'desc')
            ->value('employee_number');

        $next = $latest ? (int) substr($latest, 4) + 1 : 1;
        $this->form->employee_number = 'EMP-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
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

            $this->dispatch('toast', variant: 'success', text: __('Karyawan berhasil ditambahkan.'));

            return $this->redirect(route('admin.employees.show', $employee), navigate: true);
        } catch (\Throwable $e) {
            $this->clearSensitive();

            Log::error('Gagal tambah karyawan: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            $this->dispatch('toast', variant: 'error', text: __('Gagal: ').$e->getMessage());
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
        $provinces = Province::orderBy('name')->get();

        $cities = collect();
        if ($this->form->province_id) {
            $province = Province::find($this->form->province_id);
            $cities = $province
                ? City::where('province_code', $province->code)->orderBy('name')->get()
                : collect();
        }

        $districts = collect();
        if ($this->form->city_id) {
            $city = City::find($this->form->city_id);
            $districts = $city
                ? District::where('city_code', $city->code)->orderBy('name')->get()
                : collect();
        }

        $villages = collect();
        if ($this->form->district_id) {
            $district = District::find($this->form->district_id);
            $villages = $district
                ? Village::where('district_code', $district->code)->orderBy('name')->get()
                : collect();
        }

        $companies = Company::orderBy('name')->get(['id', 'name']);
        $branches = Branch::orderBy('name')->get(['id', 'name']);
        $departments = Department::orderBy('name')->get(['id', 'name']);
        $positions = Position::orderBy('name')->get(['id', 'name']);
        $managers = Employee::whereHas('user', fn ($q) => $q->whereNotNull('id'))
            ->orderBy('full_name')
            ->get(['id', 'full_name']);

        return view('livewire.admin.employee-create', compact(
            'provinces', 'cities', 'districts', 'villages',
            'companies', 'branches', 'departments', 'positions', 'managers',
        ));
    }
}
