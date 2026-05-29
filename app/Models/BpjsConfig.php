<?php

namespace App\Models;

use App\Enums\BpjsType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

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

    /**
     * Ambil semua BpjsConfig dari cache (atau database kalau cache kosong).
     * Hasilnya plain array, BUKAN Eloquent Collection.
     *
     * Cache disimpan 1 hari. Akan dihapus otomatis kalau ada perubahan
     * data BpjsConfig (lewat BpjsConfigObserver — kita buat di Sesi 6).
     *
     * @return array<int, array{name:string, employer_rate:float, employee_rate:float, ceiling:float|null}>
     */
    public static function cachedAll(): array
    {
        return Cache::remember(
            'bpjs_configs',
            now()->addDay(),
            fn () => static::all()->map(fn (BpjsConfig $c) => [
                'name' => $c->name instanceof \BackedEnum
                    ? $c->name->value
                    : $c->name,
                'employer_rate' => (float) $c->employer_rate,
                'employee_rate' => (float) $c->employee_rate,
                'ceiling' => $c->ceiling !== null
                    ? (float) $c->ceiling
                    : null,
            ])->toArray()
        );
    }
}
