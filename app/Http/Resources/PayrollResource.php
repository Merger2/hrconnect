<?php

namespace App\Http\Resources;

use App\Models\Payroll;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Payroll $resource
 *
 * @mixin Payroll
 */
class PayrollResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'period' => $this->period,
            'status' => $this->status->value,
            'gross_salary' => (int) $this->gross_salary,
            'total_deduction' => (int) $this->total_deduction,
            'net_salary' => (int) $this->net_salary,
            'basic_salary' => (int) $this->basic_salary,
            'total_allowance' => (int) $this->total_allowance,
            'overtime_pay' => (int) $this->overtime_pay,
            'pph21' => (int) $this->pph21,
            'bpjs_health' => (int) $this->bpjs_health,
            'bpjs_employment' => (int) $this->bpjs_employment,
            'attendance_penalty' => (int) $this->attendance_penalty,
            'loan_deduction' => (int) $this->loan_deduction,
            // Subset minimal — query payroll memuat employee partial (id,employee_number,full_name).
            'employee' => $this->whenLoaded('employee', fn () => $this->employee ? [
                'id' => $this->employee->id,
                'employee_number' => $this->employee->employee_number,
                'full_name' => $this->employee->full_name,
            ] : null),
            'items' => PayrollItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
