<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'group',
        'is_encrypted',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'value'        => 'array',
            'is_encrypted' => 'boolean',
            'is_public'    => 'boolean',
        ];
    }

    /**
     * Decrypt the stored value when flagged as encrypted.
     */
    public function getDecryptedValue(): mixed
    {
        $value = $this->value;

        if ($this->is_encrypted && is_array($value) && array_key_exists('_enc', $value)) {
            try {
                return Crypt::decryptString($value['_enc']);
            } catch (\Throwable) {
                return null;
            }
        }

        return $value;
    }

    /**
     * Encrypt a raw value for storage when the setting is sensitive.
     */
    public static function wrapEncrypted(mixed $raw): array
    {
        return ['_enc' => Crypt::encryptString((string) $raw)];
    }
}
