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

/**
 * @mixin IdeHelperCompany
 */
#[Fillable(['name', 'phone', 'email', 'website', 'npwp', 'code', 'logo', 'is_active', 'slug', 'status'])]
#[Hidden(['npwp'])]

class Company extends Model implements CipherSweetEncrypted
{
    use HasFactory, UsesCipherSweet;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

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
            ->addBlindIndex('npwp', new BlindIndex('npwp_hash'))

            ->addOptionalTextField('phone')
            ->addBlindIndex('phone', new BlindIndex('phone_hash'));
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
