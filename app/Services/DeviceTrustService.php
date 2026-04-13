<?php

namespace App\Services;

use App\Models\SecurityEventLog;
use App\Models\TrustedDevice;
use App\Models\User;
use App\Notifications\NewDeviceLoginNotification;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;

class DeviceTrustService
{
    public function checkDevice(User $user, Request $request): array
    {
        $deviceId = $request->header('X-Device-Id') ?? $this->generateDeviceId($request);
        $info = $this->parseDeviceInfo($request);

        $device = TrustedDevice::recordDevice($user->id, $deviceId, [
            'ip_address' => $request->ip(),
            'device_type' => $info['type'],
            'browser' => $info['browser'],
            'os' => $info['os'],
        ]);

        $isNew = $device->wasRecentlyCreated;
        $isTrusted = $device->is_trusted;

        if ($isNew) {
            $this->handleNewDevice($user, $device, $request);
        }

        return [
            'device_id' => $deviceId,
            'is_new' => $isNew,
            'is_trusted' => $isTrusted,
        ];
    }

    public function generateDeviceId(Request $request): string
    {
        // Generate a stable fingerprint from available headers
        $components = [
            $request->ip(),
            $request->userAgent(),
            $request->header('Accept-Language', ''),
            $request->header('Accept-Encoding', ''),
        ];

        return hash('sha256', implode('|', $components));
    }

    private function handleNewDevice(User $user, TrustedDevice $device, Request $request): void
    {
        app(SecurityEventService::class)->log(
            SecurityEventLog::EVENT_NEW_DEVICE_LOGIN,
            SecurityEventLog::SEVERITY_MEDIUM,
            $request->ip(),
            [
                'device_id' => $device->device_id,
                'browser' => $device->browser,
                'os' => $device->os,
                'ip' => $request->ip(),
            ]
        );

        // Notify user about new device login
        try {
            $user->notify(new NewDeviceLoginNotification($device));
        } catch (\Throwable) {
            // Notification failure should not block login (includes missing notification class in some envs)
        }
    }

    private function parseDeviceInfo(Request $request): array
    {
        $ua = $request->userAgent() ?? '';

        // Simple detection without Agent library
        $browser = 'Unknown';
        $os = 'Unknown';
        $type = 'desktop';

        if (stripos($ua, 'Mobile') !== false || stripos($ua, 'Android') !== false) {
            $type = 'mobile';
        } elseif (stripos($ua, 'Tablet') !== false || stripos($ua, 'iPad') !== false) {
            $type = 'tablet';
        }

        if (stripos($ua, 'Chrome') !== false) {
            $browser = 'Chrome';
        } elseif (stripos($ua, 'Firefox') !== false) {
            $browser = 'Firefox';
        } elseif (stripos($ua, 'Safari') !== false) {
            $browser = 'Safari';
        } elseif (stripos($ua, 'MSIE') !== false || stripos($ua, 'Trident') !== false) {
            $browser = 'Internet Explorer';
        }

        if (stripos($ua, 'Windows') !== false) {
            $os = 'Windows';
        } elseif (stripos($ua, 'Macintosh') !== false || stripos($ua, 'Mac OS') !== false) {
            $os = 'macOS';
        } elseif (stripos($ua, 'Linux') !== false) {
            $os = 'Linux';
        } elseif (stripos($ua, 'Android') !== false) {
            $os = 'Android';
        } elseif (stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false) {
            $os = 'iOS';
        }

        return ['browser' => $browser, 'os' => $os, 'type' => $type];
    }
}
