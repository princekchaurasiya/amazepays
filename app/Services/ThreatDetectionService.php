<?php

namespace App\Services;

use App\Models\BlockedIp;
use App\Models\SecurityEventLog;
use App\Models\User;
use App\Notifications\IpAutoBlockedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class ThreatDetectionService
{
    public function analyzeRequest(Request $request): ThreatLevel
    {
        $ip = $request->ip();

        // Check 1: Known blocked IP (fast cache check)
        if (BlockedIp::isBlocked($ip)) {
            return ThreatLevel::blocked('IP is in blocklist');
        }

        $threatScore = 0;
        $reasons = [];

        // Check 2: VPN (uses cached result from DetectVpnProxy middleware)
        if ($request->attributes->get('vpn_flagged')) {
            $threatScore += 50;
            $reasons[] = 'VPN/proxy detected';
        }

        // Check 3: Failed logins from this IP in last hour
        $failedLogins = SecurityEventLog::forIp($ip)
            ->ofType(SecurityEventLog::EVENT_LOGIN_FAILED)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        $loginThreshold = config('security.threat_detection.failed_login_threshold', 5);
        if ($failedLogins > $loginThreshold) {
            $threatScore += 30;
            $reasons[] = "Failed logins: {$failedLogins}/hr";
        }

        // Check 4: Request rate (Redis counter)
        $requestCount = $this->getRequestRate($ip);
        $rateThreshold = config('security.threat_detection.request_rate_threshold', 200);
        if ($requestCount > $rateThreshold) {
            $threatScore += 40;
            $reasons[] = "High request rate: {$requestCount}/min";
        }

        // Check 5: Suspicious user agent
        if ($this->isSuspiciousUserAgent($request->userAgent())) {
            $threatScore += 20;
            $reasons[] = 'Suspicious user-agent';
        }

        // Check 6: Attack payload in request
        if ($this->hasAttackPayload($request)) {
            $threatScore += 80;
            $reasons[] = 'Attack payload detected';

            app(SecurityEventService::class)->log(
                SecurityEventLog::EVENT_ATTACK_PAYLOAD_DETECTED,
                SecurityEventLog::SEVERITY_CRITICAL,
                $ip,
                ['url' => $request->fullUrl(), 'method' => $request->method()]
            );
        }

        // Auto-block if score too high
        $autoBlockScore = config('security.threat_detection.auto_block_score', 80);
        if ($threatScore >= $autoBlockScore) {
            $this->autoBlockIp($ip, $reasons);

            return ThreatLevel::blocked(implode('; ', $reasons));
        }

        return new ThreatLevel(
            score: $threatScore,
            action: $threatScore >= 50 ? 'challenge' : 'allow',
            reasons: $reasons,
        );
    }

    public function autoBlockIp(string $ip, array $reasons): BlockedIp
    {
        $duration = config('security.threat_detection.auto_block_duration', 86400);
        $record = BlockedIp::block($ip, implode('; ', $reasons), $duration, true);

        app(SecurityEventService::class)->log(
            SecurityEventLog::EVENT_IP_AUTO_BLOCKED,
            SecurityEventLog::SEVERITY_CRITICAL,
            $ip,
            ['reasons' => $reasons, 'duration_seconds' => $duration]
        );

        if (config('security.threat_detection.admin_alert_on_block', true)) {
            try {
                $admins = User::role('super-admin')->get();
                if ($admins->isNotEmpty()) {
                    Notification::send($admins, new IpAutoBlockedNotification($ip, $reasons));
                }
            } catch (\Exception) {
                // Non-critical — don't fail the block operation
            }
        }

        return $record;
    }

    private function getRequestRate(string $ip): int
    {
        $key = "req_rate:{$ip}";
        $count = Cache::get($key, 0);
        Cache::put($key, $count + 1, 60);

        return $count + 1;
    }

    private function isSuspiciousUserAgent(?string $ua): bool
    {
        if (! $ua || strlen($ua) < 10) {
            return true;
        }

        $suspicious = [
            'python-requests', 'curl/', 'wget/', 'scrapy',
            'phantomjs', 'selenium', 'headless', 'sqlmap',
            'nikto', 'masscan', 'nmap', 'scanner',
        ];

        foreach ($suspicious as $pattern) {
            if (stripos($ua, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    private function hasAttackPayload(Request $request): bool
    {
        $input = json_encode(array_merge(
            $request->all(),
            $request->headers->all()
        ));

        $patterns = [
            '/(\bunion\b.*\bselect\b)/i',
            '/<script[^>]*>/i',
            '/\.\.\//i',
            '/\b(eval|exec|system|passthru|shell_exec)\b/i',
            '/\$\{.*\}/i',
            '/\b(sleep|benchmark)\s*\(/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }
}

/**
 * Value object representing the result of a threat analysis.
 */
class ThreatLevel
{
    public function __construct(
        public readonly int $score,
        public readonly string $action,
        public readonly array $reasons,
    ) {}

    public static function blocked(string $reason): self
    {
        return new self(score: 100, action: 'block', reasons: [$reason]);
    }

    public function isBlocked(): bool
    {
        return $this->action === 'block';
    }

    public function requiresChallenge(): bool
    {
        return $this->action === 'challenge';
    }
}
