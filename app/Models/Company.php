<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use ParagonIE\CipherSweet\BlindIndex;
use ParagonIE\CipherSweet\EncryptedRow;
use Spatie\LaravelCipherSweet\Concerns\UsesCipherSweet;
use Spatie\LaravelCipherSweet\Contracts\CipherSweetEncrypted;

#[Fillable(['name', 'phone', 'email', 'website', 'npwp', 'code', 'logo', 'is_active'])]
#[Hidden(['npwp'])]

class Company extends Model implements CipherSweetEncrypted
{
    use HasFactory, UsesCipherSweet;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public static function configureCipherSweet(EncryptedRow $encryptedRow): void
    {
        $encryptedRow
            ->addOptionalTextField('npwp')
            ->addBlindIndex('npwp', new BlindIndex('npwp_hash'));
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(CompanySetting::class);
    }
}
