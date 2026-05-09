<?php

namespace App\Models;

use App\Enums\BpjsType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'employer_rate', 'employee_rate', 'ceiling'])]
class BpjsConfig extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'name' => BpjsType::class,
            'employer_rate' => 'decimal:4',
            'employee_rate' => 'decimal:4',
            'ceiling' => 'decimal:2',
        ];
    }
}
