<?php

namespace App\Traits;

use Illuminate\Support\Facades\Crypt;

trait Encryptable
{
    /**
     * Get the encrypted value of a field.
     *
     * @param  string  $key
     * @return string|null
     */
    public function getEncryptedAttribute($key)
    {
        if (is_null($value = $this->getAttributeValue($key))) {
            return null;
        }

        return Crypt::decryptString($value);
    }

    /**
     * Set the encrypted value of a field.
     *
     * @param  string  $key
     * @param  string  $value
     * @return void
     */
    public function setEncryptedAttribute($key, $value)
    {
        if (! is_null($value)) {
            $value = Crypt::encryptString($value);
        }

        $this->attributes[$key] = $value;
    }

    /**
     * Specify the attributes that should be encrypted.
     *
     * @return array
     */
    public function getEncryptableAttributes()
    {
        return property_exists($this, 'encryptable') ? $this->encryptable : [];
    }

    /**
     * Boot the encryptable trait.
     *
     * @return void
     */
    public static function bootEncryptable()
    {
        static::creating(function ($model) {
            $model->encryptAttributes();
        });

        static::created(function ($model) {
            //
        });
    }

    /**
     * Encrypt all encryptable attributes.
     *
     * @return void
     */
    protected function encryptAttributes()
    {
        foreach ($this->getEncryptableAttributes() as $attribute) {
            if (isset($this->attributes[$attribute]) && ! is_null($this->attributes[$attribute])) {
                $this->attributes[$attribute] = Crypt::encryptString($this->attributes[$attribute]);
            }
        }
    }

    /**
     * Decrypt all encryptable attributes when loading from database.
     *
     * @return array
     */
    protected function castAttributesFromDatabase(array $attributes)
    {
        foreach ($this->getEncryptableAttributes() as $attribute) {
            if (isset($attributes[$attribute]) && ! is_null($attributes[$attribute])) {
                try {
                    $attributes[$attribute] = Crypt::decryptString($attributes[$attribute]);
                } catch (\Exception $e) {
                    // If decryption fails, the field might not be encrypted
                    // Keep the original value
                }
            }
        }

        return $attributes;
    }
}
