<?php

namespace App\Livewire\User;

use App\Models\Payroll;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class MyPayslips extends Component
{
    use AuthorizesRequests;

    public bool $needsSetup = false;

    public string $new_password = '';

    public string $new_password_confirmation = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Payroll::class);

        $employee = auth()->user()->employee;

        $this->needsSetup = ! $employee || ! $employee->hasValidPayslipPassword();
    }

    #[Computed]
    public function payrolls()
    {
        $user = auth()->user();

        if (! $user->employee) {
            return collect();
        }

        $query = Payroll::query()
            ->where('employee_id', $user->employee->id)
            ->orderBy('period', 'desc');

        return $query->paginate(12);
    }

    public function setupPassword(): void
    {
        $this->validate([
            'new_password' => 'required|string|min:4|max:100',
            'new_password_confirmation' => 'required|same:new_password',
        ]);

        $employee = auth()->user()->employee;

        if (! $employee) {
            $this->addError('new_password', 'Data karyawan tidak ditemukan.');

            return;
        }

        $employee->update([
            'payslip_password' => $this->new_password,
            'payslip_password_set_at' => now(),
        ]);

        $this->needsSetup = false;
        $this->reset(['new_password', 'new_password_confirmation']);

        $this->dispatch('notify', type: 'success', message: 'Password payslip berhasil disimpan.');
    }

    public function triggerReset(): void
    {
        $this->needsSetup = true;
        $this->reset(['new_password', 'new_password_confirmation']);
    }

    public function cancelReset(): void
    {
        $this->needsSetup = false;
        $this->reset(['new_password', 'new_password_confirmation']);
    }

    // Download payslip dipindah ke route web `payslip.download` (P1 fix
    // 2026-08-11): dispatch 'download-file' tidak punya listener di JS/blade,
    // dan authorize('downloadPayslip') hanya cek permission (IDOR). Route
    // memakai PayrollPolicy::download (kepemilikan + status approved/paid).

    public function render()
    {
        return view('livewire.user.my-payslips', [
            'payrolls' => $this->payrolls,
        ]);
    }
}
