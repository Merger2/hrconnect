<?php

namespace App\Models;

use App\Enums\FamilyRelationship;
use App\Enums\Gender;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ParagonIE\CipherSweet\BlindIndex;
use ParagonIE\CipherSweet\EncryptedRow;
use Spatie\LaravelCipherSweet\Concerns\UsesCipherSweet;
use Spatie\LaravelCipherSweet\Contracts\CipherSweetEncrypted;

#[Fillable(['employee_id', 'name', 'gender', 'relationship', 'nik', 'birth_date', 'job', 'phone', 'address', 'is_emergency'])]
#[Hidden(['nik', 'phone', 'address'])]
class FamilyDetail extends Model implements CipherSweetEncrypted
{
    use HasFactory, UsesCipherSweet;

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'is_emergency' => 'boolean',
            'gender' => Gender::class,
            'relationship' => FamilyRelationship::class,
        ];
    }

    public static function configureCipherSweet(EncryptedRow $encryptedRow): void
    {
        $encryptedRow
            ->addOptionalTextField('nik')
            ->addBlindIndex('nik', new BlindIndex('nik_hash'))

            ->addOptionalTextField('phone')
            ->addBlindIndex('phone', new BlindIndex('phone_hash'))

            ->addOptionalTextField('address');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
