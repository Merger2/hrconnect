<?php

namespace App\Livewire\User;

use App\Models\Payroll;
use App\Services\Payroll\PayslipPdfService;
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

    public function download(int $payrollId): void
    {
        $payroll = Payroll::findOrFail($payrollId);

        $this->authorize('downloadPayslip', $payroll);

        $password = $payroll->employee?->payslip_password;

        $service = app(PayslipPdfService::class);
        $path = $service->generateAndStore($payroll, $password);

        $filename = sprintf(
            'payslip-%s-%s.pdf',
            $payroll->period,
            $payroll->employee?->employee_number ?? 'unknown'
        );

        $this->dispatch('download-file', url: $path, filename: $filename);
    }

    public function render()
    {
        return view('livewire.user.my-payslips', [
            'payrolls' => $this->payrolls,
        ]);
    }
}
