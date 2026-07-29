<?php

namespace App\Livewire\Admin;

use App\Livewire\Forms\UserForm;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class EmployeeEdit extends Component
{
    use WithFileUploads;

    public UserForm $form;

    public Employee $employee;

    public function mount(Employee $employee): void
    {
        $this->employee = $employee;

        $user = $employee->user;

        if (! $user) {
            abort(404, 'User not found for this employee.');
        }

        Gate::authorize('manageUserRecord', [$user, 'user']);

        $this->form->setUser($user);
    }

    public function update()
    {
        Gate::authorize('manageUserRecord', [$this->form->user, 'user']);

        $this->form->update();

        return redirect()->route('admin.employees')
            ->with('flash.banner', __('Updated successfully.'));
    }

    public function updated($property, $value)
    {
        if ($property === 'form.job_title_id' && $value) {
            $position = Position::find($value);
            if ($position && $position->division_id) {
                $this->form->division_id = $position->division_id;
            }
            $this->form->manager_id = null;
        }

        if ($property === 'form.division_id') {
            $this->form->job_title_id = null;
            $this->form->manager_id = null;
        }

        if ($property === 'form.provinsi_kode') {
            $this->form->kabupaten_kode = null;
            $this->form->kecamatan_kode = null;
            $this->form->kelurahan_kode = null;
        }

        if ($property === 'form.kabupaten_kode') {
            $this->form->kecamatan_kode = null;
            $this->form->kelurahan_kode = null;
        }

        if ($property === 'form.kecamatan_kode') {
            $this->form->kelurahan_kode = null;
        }
    }

    public function render()
    {
        $provinces = Wilayah::whereRaw('LENGTH(kode) = 2')->orderBy('nama')->get();
        $regencies = $this->form->provinsi_kode
            ? Wilayah::where('kode', 'like', $this->form->provinsi_kode.'.%')->whereRaw('LENGTH(kode) = 5')->orderBy('nama')->get()
            : collect();
        $districts = $this->form->kabupaten_kode
            ? Wilayah::where('kode', 'like', $this->form->kabupaten_kode.'.%')->whereRaw('LENGTH(kode) = 8')->orderBy('nama')->get()
            : collect();
        $villages = $this->form->kecamatan_kode
            ? Wilayah::where('kode', 'like', $this->form->kecamatan_kode.'.%')->whereRaw('LENGTH(kode) = 13')->orderBy('nama')->get()
            : collect();

        $availableJobTitles = Position::query()
            ->where('is_active', true)
            ->when($this->form->division_id, function ($q) {
                $q->where('division_id', $this->form->division_id)
                    ->orWhereNull('division_id');
            })
            ->get();

        $managerOptions = [];
        $currentUserId = $this->form->user?->id;

        if ($currentUserId) {
            $managerOptions = User::query()
                ->where('group', 'user')
                ->managedBy(auth()->user())
                ->where('id', '!=', $currentUserId)
                ->with('employee.position', 'employee.division')
                ->orderBy('name')
                ->get()
                ->map(function (User $manager) {
                    $details = collect([
                        $manager->jobTitle?->name,
                        $manager->division?->name,
                    ])->filter()->implode(' / ');

                    return [
                        'id' => $manager->id,
                        'name' => $details ? "{$manager->name} - {$details}" : $manager->name,
                    ];
                })
                ->values();
        }

        return view('livewire.admin.employee-edit', [
            'provinces' => $provinces,
            'regencies' => $regencies,
            'districts' => $districts,
            'villages' => $villages,
            'availableJobTitles' => $availableJobTitles,
            'managerOptions' => $managerOptions,
            'employmentStatuses' => Employee::employmentStatuses(),
            'manualEmploymentStatuses' => Employee::manuallyManagedEmploymentStatuses(),
            'canManageEmployeeStatuses' => Gate::allows('manageEmployeeStatuses'),
        ]);
    }
}
