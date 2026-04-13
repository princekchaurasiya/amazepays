<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class TwoFactorSecret extends Model
{
    protected $fillable = [
        'user_id', 'secret', 'recovery_codes', 'confirmed', 'confirmed_at',
    ];

    protected $hidden = ['secret', 'recovery_codes'];

    protected $casts = [
        'confirmed' => 'boolean',
        'confirmed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDecryptedSecret(): string
    {
        return Crypt::decryptString($this->secret);
    }

    public function getRecoveryCodesArray(): array
    {
        if (! $this->recovery_codes) {
            return [];
        }

        return json_decode(Crypt::decryptString($this->recovery_codes), true) ?? [];
    }

    public function setRecoveryCodes(array $codes): void
    {
        $this->update([
            'recovery_codes' => Crypt::encryptString(json_encode($codes)),
        ]);
    }

    public function useRecoveryCode(string $code): bool
    {
        $codes = $this->getRecoveryCodesArray();
        $index = array_search($code, $codes);

        if ($index === false) {
            return false;
        }

        array_splice($codes, $index, 1);
        $this->setRecoveryCodes($codes);

        return true;
    }
}
