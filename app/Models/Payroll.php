<?php

namespace App\Models;

use App\Enums\PayrollStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['employee_id', 'period', 'basic_salary', 'total_allowance', 'gross_salary', 'overtime_pay', 'pph21', 'bpjs_health', 'bpjs_employment', 'loan_deduction', 'attendance_penalty', 'total_deduction', 'net_salary', 'status'])]
class Payroll extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'total_allowance' => 'decimal:2',
            'gross_salary' => 'decimal:2',
            'overtime_pay' => 'decimal:2',
            'pph21' => 'decimal:2',
            'bpjs_health' => 'decimal:2',
            'bpjs_employment' => 'decimal:2',
            'loan_deduction' => 'decimal:2',
            'attendance_penalty' => 'decimal:2',
            'total_deduction' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'status' => PayrollStatus::class,
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function reimbursements(): HasMany
    {
        return $this->hasMany(Reimbursement::class);
    }

    public function loanInstallments(): HasMany
    {
        return $this->hasMany(LoanInstallment::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(PayrollAdjustment::class);
    }

    public function isLocked(): bool
    {
        return in_array($this->status, [PayrollStatus::PUBLISHED, PayrollStatus::PAID]);
    }

    public function generatePdf(): string
    {
        return "payslips/{$this->period}/{$this->employee_id}.pdf";
    }
}
