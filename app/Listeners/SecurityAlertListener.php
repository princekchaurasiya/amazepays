<?php

namespace App\Listeners;

use App\Events\SecurityEventCreated;
use App\Models\SecurityEventLog;
use App\Models\User;
use App\Notifications\CriticalSecurityAlert;
use App\Services\ThreatDetectionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SecurityAlertListener implements ShouldQueue
{
    public string $queue = 'security';

    public function handle(SecurityEventCreated $event): void
    {
        $log = $event->securityEventLog;

        if ($log->severity === SecurityEventLog::SEVERITY_CRITICAL) {
            $this->notifyAdmins($log);
            $this->sendSlackAlert($log);
            $this->checkForAutoBlock($log);
        }
    }

    private function notifyAdmins(SecurityEventLog $log): void
    {
        try {
            $admins = User::role('super-admin')->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new CriticalSecurityAlert($log));
            }
        } catch (\Exception $e) {
            Log::warning('Failed to send admin security notification', ['error' => $e->getMessage()]);
        }
    }

    private function sendSlackAlert(SecurityEventLog $log): void
    {
        $webhookUrl = config('security.alerts.slack_webhook_url');
        if (! $webhookUrl) {
            return;
        }

        try {
            Http::post($webhookUrl, [
                'text' => sprintf(
                    ":rotating_light: *CRITICAL SECURITY EVENT*\n*Type:* %s\n*IP:* %s\n*User:* %s\n*Time:* %s\n*Details:* %s",
                    $log->event_type,
                    $log->ip_address ?? 'unknown',
                    $log->user_id ? "User #{$log->user_id}" : 'Anonymous',
                    $log->created_at->toDateTimeString(),
                    json_encode($log->metadata)
                ),
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to send Slack security alert', ['error' => $e->getMessage()]);
        }
    }

    private function checkForAutoBlock(SecurityEventLog $log): void
    {
        if (! $log->ip_address || ! config('security.alerts.alert_on_auto_block', true)) {
            return;
        }

        $threshold = config('security.alerts.repeated_critical_threshold', 3);

        $recentCritical = SecurityEventLog::forIp($log->ip_address)
            ->where('severity', SecurityEventLog::SEVERITY_CRITICAL)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recentCritical >= $threshold) {
            app(ThreatDetectionService::class)->autoBlockIp(
                $log->ip_address,
                ['Repeated critical security events: '.$recentCritical.' in last hour']
            );
        }
    }
}
