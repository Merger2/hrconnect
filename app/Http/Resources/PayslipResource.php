<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayslipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'period' => $this->period,
            'status' => $this->status?->value,
            'basic_salary' => (int) $this->basic_salary,
            'total_allowance' => (int) $this->total_allowance,
            'gross_salary' => (int) $this->gross_salary,
            'overtime_pay' => (int) $this->overtime_pay,
            'pph21' => (int) $this->pph21,
            'bpjs_health' => (int) $this->bpjs_health,
            'bpjs_employment' => (int) $this->bpjs_employment,
            'attendance_penalty' => (int) $this->attendance_penalty,
            'total_deduction' => (int) $this->total_deduction,
            'net_salary' => (int) $this->net_salary,
            'employee' => EmployeeResource::make($this->whenLoaded('employee')),
            'pdf_path' => $this->pdf_path,
        ];
    }
}
