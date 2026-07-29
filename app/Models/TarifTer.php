<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TarifTer extends Model
{
    use HasFactory;

    protected $table = 'tarif_ter';

    protected $fillable = [
        'kategori_ter_id',
        'batas_bawah',
        'batas_atas',
        'tarif',
    ];

    protected function casts(): array
    {
        return [
            'batas_bawah' => 'decimal:2',
            'batas_atas' => 'decimal:2',
            'tarif' => 'decimal:4',
        ];
    }

    public function kategoriTer(): BelongsTo
    {
        return $this->belongsTo(KategoriTer::class, 'kategori_ter_id');
    }
}
