<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use ParagonIE\CipherSweet\BlindIndex;
use ParagonIE\CipherSweet\EncryptedRow;
use Spatie\LaravelCipherSweet\Concerns\UsesCipherSweet;
use Spatie\LaravelCipherSweet\Contracts\CipherSweetEncrypted;

/**
 * @mixin IdeHelperSite
 */
class Site extends Model implements CipherSweetEncrypted
{
    use HasFactory, UsesCipherSweet;

    protected $table = 'sites';

    protected $fillable = [
        'name',
        'code',
        'address',
        'city',
        'province',
        'postal_code',
        'phone',
        'email',
        'latitude',
        'longitude',
        'radius_meters',
        'is_active',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'radius_meters' => 'integer',
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    public static function configureCipherSweet(EncryptedRow $encryptedRow): void
    {
        $encryptedRow
            ->addOptionalTextField('phone')
            ->addBlindIndex('phone', new BlindIndex('phone_hash'))
            ->addOptionalTextField('address')
            ->addBlindIndex('address', new BlindIndex('address_hash'));
    }
}
