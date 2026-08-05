<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperKategoriTer
 */
class KategoriTer extends Model
{
    use HasFactory;

    protected $table = 'kategori_ter';

    protected $fillable = [
        'kode',
        'nama',
        'deskripsi',
    ];

    public function golonganPtkp(): HasMany
    {
        return $this->hasMany(GolonganPtkp::class, 'kategori_ter_id');
    }

    public function tarifTer(): HasMany
    {
        return $this->hasMany(TarifTer::class, 'kategori_ter_id');
    }
}
