<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GolonganPtkp extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode',
        'nama',
        'ptkp_tahun',
        'ptkp_bulan',
        'kategori_ter_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'ptkp_tahun' => 'decimal:2',
            'ptkp_bulan' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function kategoriTer(): BelongsTo
    {
        return $this->belongsTo(KategoriTer::class, 'kategori_ter_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'golongan_ptkp_id');
    }
}
