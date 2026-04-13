<?php

namespace App\Services;

use App\Models\TwoFactorSecret;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA;
    }

    /**
     * Generate a new TOTP secret for the user and return setup data.
     */
    public function generateSecret(User $user): array
    {
        $secret = $this->google2fa->generateSecretKey();

        // Store encrypted (not yet confirmed)
        TwoFactorSecret::updateOrCreate(
            ['user_id' => $user->id],
            [
                'secret' => Crypt::encryptString($secret),
                'confirmed' => false,
            ]
        );

        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name', 'AmazePays'),
            $user->email,
            $secret
        );

        return [
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
        ];
    }

    /**
     * Confirm 2FA setup by verifying the first code from the authenticator app.
     */
    public function confirm(User $user, string $code): bool
    {
        $twoFactor = $user->twoFactorSecret;

        if (! $twoFactor) {
            return false;
        }

        $secret = $twoFactor->getDecryptedSecret();

        if (! $this->google2fa->verifyKey($secret, $code)) {
            return false;
        }

        // Generate recovery codes
        $recoveryCodes = $this->generateRecoveryCodes();

        $twoFactor->update([
            'confirmed' => true,
            'confirmed_at' => now(),
        ]);
        $twoFactor->setRecoveryCodes($recoveryCodes);

        $user->update(['two_factor_enabled' => true]);

        return true;
    }

    /**
     * Verify a TOTP code during login.
     */
    public function verify(User $user, string $code): bool
    {
        $twoFactor = $user->twoFactorSecret;

        if (! $twoFactor || ! $twoFactor->confirmed) {
            return false;
        }

        $secret = $twoFactor->getDecryptedSecret();

        return (bool) $this->google2fa->verifyKey($secret, $code, 1);
    }

    /**
     * Verify a recovery code and invalidate it after use.
     */
    public function verifyRecoveryCode(User $user, string $code): bool
    {
        $twoFactor = $user->twoFactorSecret;

        if (! $twoFactor) {
            return false;
        }

        return $twoFactor->useRecoveryCode($code);
    }

    /**
     * Disable 2FA for a user. Sets cooling-off period.
     */
    public function disable(User $user): void
    {
        $user->twoFactorSecret?->delete();
        $user->update([
            'two_factor_enabled' => false,
            'password_changed_at' => now(), // Trigger cooling-off
        ]);
    }

    /**
     * Generate 8 single-use recovery codes.
     */
    private function generateRecoveryCodes(): array
    {
        return Collection::times(8, fn () => Str::random(10))->toArray();
    }
}
