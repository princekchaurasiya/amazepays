<?php

namespace App\Services;

use App\Models\SecurityEventLog;
use App\Models\User;

class MultiAccountDetector
{
    public function checkNewRegistration(User $user, array $context = []): array
    {
        $flags = [];
        $suspect = false;

        // Check 1: Same device fingerprint
        $deviceId = $context['device_id'] ?? $user->device_fingerprint;
        if ($deviceId) {
            $sameDeviceCount = User::where('device_fingerprint', $deviceId)
                ->where('id', '!=', $user->id)
                ->count();

            $maxPerDevice = config('security.multi_account.same_device_threshold', 3);
            if ($sameDeviceCount >= $maxPerDevice) {
                $flags[] = 'multiple_accounts_same_device';
                $suspect = true;
            }
        }

        // Check 2: Same IP in last 24h registrations
        $ip = $context['ip'] ?? $user->registration_ip;
        if ($ip) {
            $sameIpCount = User::where('registration_ip', $ip)
                ->where('id', '!=', $user->id)
                ->where('created_at', '>=', now()->subDay())
                ->count();

            $maxPerIp = config('security.multi_account.same_ip_threshold', 5);
            if ($sameIpCount >= $maxPerIp) {
                $flags[] = 'multiple_registrations_same_ip';
                $suspect = true;
            }
        }

        // Check 3: Same mobile number used to request OTP for multiple accounts
        if ($user->mobile) {
            $sameMobile = User::query()->whereMobile((string) $user->mobile)
                ->where('id', '!=', $user->id)
                ->exists();

            if ($sameMobile) {
                $flags[] = 'duplicate_mobile';
                $suspect = true;
            }
        }

        if ($suspect) {
            app(SecurityEventService::class)->log(
                SecurityEventLog::EVENT_MULTI_ACCOUNT_DETECTED,
                SecurityEventLog::SEVERITY_HIGH,
                $ip,
                [
                    'user_id' => $user->id,
                    'flags' => $flags,
                    'device' => $deviceId,
                ]
            );
        }

        return ['flags' => $flags, 'is_suspect' => $suspect];
    }

    public function detectImpossibleTravel(User $user, string $newIp, string $newCountry): bool
    {
        if (! config('security.impossible_travel.enabled', true)) {
            return false;
        }

        $lastLogin = $user->last_login_ip;
        $lastCountry = $user->last_login_country;

        if (! $lastLogin || ! $lastCountry || ! $newCountry) {
            return false;
        }

        if ($lastCountry === $newCountry) {
            return false;
        }

        $lastLoginTime = $user->last_login_at;

        if (! $lastLoginTime) {
            return false;
        }

        // Simple heuristic: different continent within 1 hour
        $hoursSince = $lastLoginTime->diffInHours(now());
        $sameRegion = $this->isSameRegion($lastCountry, $newCountry);

        if (! $sameRegion && $hoursSince < 2) {
            app(SecurityEventService::class)->log(
                SecurityEventLog::EVENT_IMPOSSIBLE_TRAVEL,
                SecurityEventLog::SEVERITY_HIGH,
                $newIp,
                [
                    'user_id' => $user->id,
                    'last_country' => $lastCountry,
                    'new_country' => $newCountry,
                    'hours_since' => $hoursSince,
                    'last_ip' => $lastLogin,
                ]
            );

            return true;
        }

        return false;
    }

    private function isSameRegion(string $country1, string $country2): bool
    {
        $regions = [
            'south_asia' => ['IN', 'PK', 'BD', 'LK', 'NP', 'BT'],
            'southeast_asia' => ['SG', 'MY', 'TH', 'ID', 'PH', 'VN'],
            'middle_east' => ['AE', 'SA', 'KW', 'QA', 'BH', 'OM'],
            'europe' => ['GB', 'DE', 'FR', 'NL', 'IT', 'ES'],
            'north_america' => ['US', 'CA', 'MX'],
        ];

        foreach ($regions as $region => $countries) {
            if (in_array($country1, $countries) && in_array($country2, $countries)) {
                return true;
            }
        }

        return false;
    }
}
