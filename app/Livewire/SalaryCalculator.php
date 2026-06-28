<?php

namespace App\Livewire;

use App\Models\Employee;
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

        $deductionAlfa = $this->calculateAlfa();
        $deductionPph21 = $this->calculatePph21($bruto);
        $deductionBpjs = $this->calculateBpjs();

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

    private function calculateAlfa(): float
    {
        if ($this->hariAlfa <= 0) {
            return 0;
        }

        $upahTetap = $this->gajiPokok + $this->tunjanganJabatan;
        $perHari = $upahTetap / 22;

        return round($perHari * $this->hariAlfa);
    }

    private function calculatePph21(float $bruto): float
    {
        if ($bruto <= 0) {
            return 0;
        }

        $pkp = $bruto * 12 - 54_000_000;

        if ($pkp <= 0) {
            return 0;
        }

        $pph21Setahun = 0;
        if ($pkp <= 60_000_000) {
            $pph21Setahun = $pkp * 0.05;
        } elseif ($pkp <= 250_000_000) {
            $pph21Setahun = 60_000_000 * 0.05 + ($pkp - 60_000_000) * 0.15;
        } elseif ($pkp <= 500_000_000) {
            $pph21Setahun = 60_000_000 * 0.05 + 190_000_000 * 0.15 + ($pkp - 250_000_000) * 0.25;
        } else {
            $pph21Setahun = 60_000_000 * 0.05 + 190_000_000 * 0.15 + 250_000_000 * 0.25 + ($pkp - 500_000_000) * 0.3;
        }

        return round($pph21Setahun / 12);
    }

    private function calculateBpjs(): float
    {
        $dasar = $this->gajiPokok + $this->tunjanganJabatan;

        // BPJS Kesehatan: 1% dari gaji (kap 12jt)
        $kesehatan = round(min($dasar, 12_000_000) * 0.01);

        // BPJS JHT: 2% dari gaji pokok
        $jht = round($this->gajiPokok * 0.02);

        // BPJS JP: 1% dari gaji (kap ~10.5jt)
        $jp = round(min($dasar, 10_547_400) * 0.01);

        return $kesehatan + $jht + $jp;
    }
}
