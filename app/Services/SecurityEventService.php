<?php

namespace App\Services;

use App\Events\SecurityEventCreated;
use App\Models\BlockedIp;
use App\Models\BlockedMobile;
use App\Models\SecurityEventLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class SecurityEventService
{
    public function log(
        string $eventType,
        string $severity,
        ?string $ipAddress = null,
        array $metadata = [],
        bool $isVpn = false
    ): SecurityEventLog {
        $request = request();

        try {
            $event = SecurityEventLog::create([
                'event_type' => $eventType,
                'severity' => $severity,
                'ip_address' => $ipAddress ?? $request->ip(),
                'user_id' => auth()->id(),
                'tenant_id' => auth()->check() ? (auth()->user()->currentTenantId() ?? null) : null,
                'user_agent' => $request->userAgent(),
                'request_url' => $request->fullUrl(),
                'request_method' => $request->method(),
                'country_code' => $metadata['country_code'] ?? null,
                'city' => $metadata['city'] ?? null,
                'is_vpn' => $isVpn,
                'device_id' => $request->header('X-Device-Id'),
                'metadata' => $metadata,
            ]);

            // Fire event for listeners (Slack alerts, auto-blocking, etc.)
            event(new SecurityEventCreated($event));

            return $event;
        } catch (\Exception $e) {
            Log::error('Failed to log security event', [
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);

            // Return a dummy event to prevent cascading failures
            return new SecurityEventLog([
                'event_type' => $eventType,
                'severity' => $severity,
            ]);
        }
    }

    public function getOverview(): array
    {
        return [
            'active_threats' => SecurityEventLog::unresolved()
                ->highSeverity()
                ->where('created_at', '>=', now()->subDay())
                ->count(),

            'vpn_attempts_today' => SecurityEventLog::today()
                ->ofType(SecurityEventLog::EVENT_VPN_DETECTED)
                ->count(),

            'blocked_ips' => BlockedIp::active()->count(),

            'blocked_mobiles' => BlockedMobile::active()->count(),

            'failed_logins_today' => SecurityEventLog::today()
                ->ofType(SecurityEventLog::EVENT_LOGIN_FAILED)
                ->count(),

            'wallet_fraud_flags' => SecurityEventLog::unresolved()
                ->ofType(SecurityEventLog::EVENT_WALLET_FRAUD_CHECK)
                ->highSeverity()
                ->count(),

            'top_threat_ips' => SecurityEventLog::highSeverity()
                ->where('created_at', '>=', now()->subDay())
                ->whereNotNull('ip_address')
                ->selectRaw('ip_address, COUNT(*) as event_count')
                ->groupBy('ip_address')
                ->orderByDesc('event_count')
                ->limit(10)
                ->get(),
        ];
    }

    public function getTimelineForIp(string $ip): Collection
    {
        return SecurityEventLog::forIp($ip)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();
    }

    public function getUserSecurityProfile(int $userId): array
    {
        return [
            'recent_logins' => SecurityEventLog::where('user_id', $userId)
                ->ofType(SecurityEventLog::EVENT_LOGIN_SUCCESS)
                ->orderByDesc('created_at')
                ->limit(10)
                ->get(),

            'unique_ips' => SecurityEventLog::where('user_id', $userId)
                ->distinct('ip_address')
                ->count('ip_address'),

            'vpn_detections' => SecurityEventLog::where('user_id', $userId)
                ->ofType(SecurityEventLog::EVENT_VPN_DETECTED)
                ->count(),

            'fraud_flags' => SecurityEventLog::where('user_id', $userId)
                ->ofType(SecurityEventLog::EVENT_WALLET_FRAUD_CHECK)
                ->highSeverity()
                ->count(),
        ];
    }
}
