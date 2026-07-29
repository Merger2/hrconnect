<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type', // allowance, deduction
        'description',
        'percentage',
        'is_taxable',
        'is_bpjs_applicable',
        'is_active',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'is_taxable' => 'boolean',
        'is_bpjs_applicable' => 'boolean',
        'is_active' => 'boolean',
    ];
}
