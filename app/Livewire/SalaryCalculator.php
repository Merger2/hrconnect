<?php

namespace App\Livewire;

use App\Models\Employee;
use App\Services\Payroll\BpjsService;
use App\Services\Payroll\PotonganService;
use App\Services\Payroll\Pph21Service;
use Livewire\Component;

class SalaryCalculator extends Component
{
    public ?int $employeeId = null;

    public float $gajiPokok = 0;

    public float $tunjanganJabatan = 0;

    public float $tunjanganMakan = 0;

    public float $tunjanganTransport = 0;

    public float $lemburPay = 0;

    public int $hariAlfa = 0;

    public array $result = [];

    public function mount(?int $employeeId = null): void
    {
        $this->employeeId = $employeeId;

        if ($employeeId) {
            $employee = Employee::find($employeeId);
            if ($employee) {
                $position = $employee->position;
                $this->gajiPokok = (float) ($position?->basic_salary ?? 0);
                $this->tunjanganJabatan = (float) ($position?->allowance_jabatan ?? 0);
            }
        }
    }

    public function calculate(): void
    {
        $bruto = $this->gajiPokok + $this->tunjanganJabatan + $this->tunjanganMakan + $this->tunjanganTransport + $this->lemburPay;

        $deductionAlfa = app(PotonganService::class)->calculateAlfa(
            $this->gajiPokok, $this->tunjanganJabatan,
            $this->tunjanganMakan, $this->tunjanganTransport,
            $this->hariAlfa,
        );

        $deductionPph21 = app(Pph21Service::class)->calculateMonthly($bruto * 12);
        $deductionBpjs = app(BpjsService::class)->calculate($this->gajiPokok, $this->tunjanganJabatan)['total'];

        $totalDeductions = $deductionAlfa + $deductionPph21 + $deductionBpjs;

        $this->result = [
            'gaji_pokok' => $this->gajiPokok,
            'tunjangan_jabatan' => $this->tunjanganJabatan,
            'tunjangan_makan' => $this->tunjanganMakan,
            'tunjangan_transport' => $this->tunjanganTransport,
            'lembur_pay' => $this->lemburPay,
            'penghasilan_bruto' => $bruto,
            'potongan_alfa' => $deductionAlfa,
            'potongan_pph21' => $deductionPph21,
            'potongan_bpjs' => $deductionBpjs,
            'total_potongan' => $totalDeductions,
            'total_bersih' => max(0, $bruto - $totalDeductions),
        ];
    }

    public function render()
    {
        return view('livewire.salary-calculator');
    }
}
