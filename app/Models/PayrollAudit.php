<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperPayrollAudit
 */
class PayrollAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_id',
        'employee_id',
        'basic_salary',
        'total_allowance',
        'overtime_pay',
        'gross_salary',
        'ptkp',
        'pph21_ter',
        'pph21_netto',
        'pph21_rate',
        'bpjs_health',
        'bpjs_employment',
        'loan_deduction',
        'attendance_penalty',
        'total_deduction',
        'net_salary',
        'meta',
        'calculated_by',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'total_allowance' => 'decimal:2',
        'overtime_pay' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'ptkp' => 'decimal:2',
        'pph21_ter' => 'decimal:2',
        'pph21_netto' => 'decimal:2',
        'pph21_rate' => 'decimal:2',
        'bpjs_health' => 'decimal:2',
        'bpjs_employment' => 'decimal:2',
        'loan_deduction' => 'decimal:2',
        'attendance_penalty' => 'decimal:2',
        'total_deduction' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'meta' => 'array',
        'calculated_at' => 'datetime',
    ];

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
