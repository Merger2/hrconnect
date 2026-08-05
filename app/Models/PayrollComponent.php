<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperPayrollComponent
 */
class PayrollComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type', // allowance, deduction
        'description',
        'amount',
        'calculation_type', // fixed, daily_presence, percentage_basic
        'percentage',
        'is_taxable',
        'is_bpjs_applicable',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'calculation_type' => 'string',
        'percentage' => 'decimal:2',
        'is_taxable' => 'boolean',
        'is_bpjs_applicable' => 'boolean',
        'is_active' => 'boolean',
    ];
}
