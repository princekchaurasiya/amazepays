<?php

namespace App\Services;

use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Step-Up Authentication Service
 *
 * Determines what level of authentication is required for an action
 * based on the amount, user role, and risk signals.
 *
 * Levels:
 *   0 = none (public browsing)
 *   1 = session only (normal logged-in actions)
 *   2 = session + transaction PIN
 *   3 = session + PIN + OTP
 *   4 = session + PIN + OTP + 2FA
 */
class StepUpAuthService
{
    public function requiredLevelForAmount(float $amount, string $context = 'order'): int
    {
        $thresholds = config('security.step_up.thresholds');

        if ($context === 'wallet_load') {
            return 3; // All wallet loads require PIN + OTP
        }

        if ($amount <= 0) {
            return 1;
        }

        if ($amount < $thresholds['order_low']) {
            return 1;
        }

        if ($amount < $thresholds['order_medium']) {
            return 2;
        }

        if ($amount < $thresholds['order_high']) {
            return 3;
        }

        return 4;
    }

    public function requiredLevelForUser(User $user, Request $request, float $amount = 0, string $context = 'order'): int
    {
        $base = $this->requiredLevelForAmount($amount, $context);

        // B2B bulk orders above threshold require level 4
        if ($user->hasRole(['b2b-client', 'b2b-operator'])) {
            $b2bThreshold = config('security.step_up.thresholds.b2b_bulk_order', 50000);
            if ($amount > $b2bThreshold) {
                $base = max($base, 4);
            }
        }

        // New device adds one level
        $deviceId = $request->header('X-Device-Id');
        if ($deviceId && ! TrustedDevice::isDeviceTrusted($user->id, $deviceId)) {
            $base = min($base + 1, 4);
        }

        // VPN flagged adds one level
        if ($request->attributes->get('vpn_flagged')) {
            $base = min($base + 1, 4);
        }

        return $base;
    }

    public function levelName(int $level): string
    {
        return config('security.step_up.levels.'.$level, 'none');
    }

    public function getCurrentLevel(Request $request): int
    {
        $user = $request->user();

        if (! $user) {
            return 0;
        }

        $level = 1; // Has session

        if ($request->attributes->get('pin_verified')) {
            $level = 2;
        }

        if ($request->attributes->get('otp_verified')) {
            $level = 3;
        }

        if ($request->attributes->get('2fa_verified')) {
            $level = 4;
        }

        return $level;
    }

    public function isSatisfied(int $required, Request $request): bool
    {
        return $this->getCurrentLevel($request) >= $required;
    }
}
