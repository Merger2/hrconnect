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

class EmployeeEdit extends Component
{
    public EmployeeForm $form;

    public Employee $employee;

    public function mount(Employee $employee): void
    {
        Gate::authorize('update', $employee);

        $this->employee = $employee;
        $this->form->employee = $employee;

        $user = $employee->user;

        $this->form->name = $user->name;
        $this->form->email = $user->email;
        $this->form->password = '';

        $this->form->employee_number = $employee->employee_number;
        $this->form->full_name = $employee->full_name;
        $this->form->nik = '';
        $this->form->phone = '';
        $this->form->gender = $employee->gender?->value ?? '';
        $this->form->marital_status = $employee->marital_status?->value ?? '';
        $this->form->blood_type = $employee->blood_type?->value ?? '';
        $this->form->birth_date = $employee->birth_date?->format('Y-m-d') ?? '';
        $this->form->join_date = $employee->join_date?->format('Y-m-d') ?? '';

        $this->form->province_id = $employee->province_id ? (string) $employee->province_id : null;
        $this->form->city_id = $employee->city_id ? (string) $employee->city_id : null;
        $this->form->district_id = $employee->district_id ? (string) $employee->district_id : null;
        $this->form->village_id = $employee->village_id ? (string) $employee->village_id : null;
        $this->form->address_detail = $employee->address_detail ?? '';

        $this->form->company_id = (string) $employee->company_id;
        $this->form->branch_id = (string) $employee->branch_id;
        $this->form->department_id = (string) $employee->department_id;
        $this->form->position_id = (string) $employee->position_id;
        $this->form->parent_id = $employee->parent_id ? (string) $employee->parent_id : null;
        $this->form->employment_type = $employee->employment_type?->value ?? '';
        $this->form->salary_type = $employee->salary_type?->value ?? '';

        $this->form->education_level = $employee->education_level?->value ?? '';
        $this->form->institution_name = $employee->institution_name ?? '';
        $this->form->graduation_year = (string) $employee->graduation_year ?? '';
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
            $employee = $this->form->update();

            $this->clearSensitive();

            session()->flash('success', 'Karyawan berhasil diperbarui.');

            return $this->redirect(route('admin.employees.show', $employee), navigate: true);
        } catch (\Throwable $e) {
            $this->clearSensitive();

            Log::error('Gagal update karyawan: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'employee_id' => $this->employee->id,
            ]);
            session()->flash('error', 'Gagal memperbarui karyawan: '.$e->getMessage());
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
        $managers = Employee::where('id', '!=', $this->employee->id)
            ->whereHas('user', fn ($q) => $q->whereNotNull('id'))
            ->orderBy('full_name')
            ->get(['id', 'full_name']);

        return view('livewire.admin.employee-edit', compact(
            'provinces', 'cities', 'districts', 'villages',
            'companies', 'branches', 'departments', 'positions', 'managers',
        ));
    }
}
