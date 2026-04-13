<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VpnDetectionService
{
    public function check(string $ip): array
    {
        if (! config('security.vpn_detection.enabled')) {
            return $this->clean();
        }

        // Skip private/loopback IPs
        if ($this->isPrivateIp($ip)) {
            return $this->clean();
        }

        $bypassIps = config('security.vpn_detection.bypass_ips', []);
        if (in_array($ip, $bypassIps)) {
            return $this->clean();
        }

        $cacheKey = "vpn_check:{$ip}";
        $ttl = config('security.vpn_detection.cache_ttl', 86400);

        return Cache::remember($cacheKey, $ttl, function () use ($ip) {
            return $this->detectVpn($ip);
        });
    }

    private function detectVpn(string $ip): array
    {
        $results = [];

        // Layer 1: IPHub API (primary)
        $results['iphub'] = $this->checkIpHub($ip);

        // Layer 2: ip-api.com (fallback if IPHub failed)
        if (! $results['iphub']['success']) {
            $results['ipapi'] = $this->checkIpApi($ip);
        }

        // Layer 3: Reverse DNS — detect datacenter hosting
        $results['rdns'] = $this->checkReverseDns($ip);

        return $this->aggregate($ip, $results);
    }

    private function checkIpHub(string $ip): array
    {
        $apiKey = config('security.iphub.api_key');

        if (! $apiKey) {
            return ['success' => false, 'reason' => 'no_api_key'];
        }

        try {
            $response = Http::timeout(config('security.iphub.timeout', 3))
                ->withHeaders(['X-Key' => $apiKey])
                ->get(config('security.iphub.base_url').$ip);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'is_vpn' => ($data['block'] ?? 0) === 1,
                    'is_hosting' => ($data['block'] ?? 0) === 2,
                    'provider' => $data['isp'] ?? null,
                    'country_code' => $data['countryCode'] ?? null,
                    'city' => $data['city'] ?? null,
                    'detection_method' => 'iphub',
                ];
            }
        } catch (\Exception $e) {
            Log::warning('IPHub API failed', ['ip' => $ip, 'error' => $e->getMessage()]);
        }

        // Fail closed: if API is down and fail_behavior is 'closed', treat as VPN
        if (config('security.vpn_detection.fail_behavior') === 'closed') {
            return [
                'success' => false,
                'is_vpn' => true,
                'detection_method' => 'iphub_failed_closed',
            ];
        }

        return ['success' => false];
    }

    private function checkIpApi(string $ip): array
    {
        try {
            $response = Http::timeout(3)
                ->get("http://ip-api.com/json/{$ip}", [
                    'fields' => 'status,proxy,hosting,isp,country,countryCode,city',
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'is_vpn' => (bool) ($data['proxy'] ?? false),
                    'is_hosting' => (bool) ($data['hosting'] ?? false),
                    'provider' => $data['isp'] ?? null,
                    'country_code' => $data['countryCode'] ?? null,
                    'city' => $data['city'] ?? null,
                    'detection_method' => 'ipapi',
                ];
            }
        } catch (\Exception $e) {
            Log::warning('ip-api.com check failed', ['ip' => $ip, 'error' => $e->getMessage()]);
        }

        return ['success' => false];
    }

    private function checkReverseDns(string $ip): array
    {
        try {
            $hostname = gethostbyaddr($ip);
            $keywords = [
                'digitalocean', 'amazonaws', 'linode', 'vultr', 'hetzner',
                'ovh', 'cloudflare', 'azure', 'google', 'oracle',
                'nordvpn', 'expressvpn', 'surfshark', 'cyberghost',
                'vpn', 'proxy', 'tor', 'exit', 'relay',
            ];

            foreach ($keywords as $keyword) {
                if (stripos($hostname, $keyword) !== false) {
                    return [
                        'is_datacenter' => true,
                        'hostname' => $hostname,
                        'matched_keyword' => $keyword,
                        'detection_method' => 'rdns',
                    ];
                }
            }

            return ['is_datacenter' => false, 'hostname' => $hostname];
        } catch (\Exception) {
            return ['is_datacenter' => false];
        }
    }

    private function aggregate(string $ip, array $checks): array
    {
        $isVpn = false;
        $detectionMethod = 'none';
        $provider = null;
        $countryCode = null;
        $city = null;

        foreach ($checks as $source => $check) {
            if (! empty($check['is_vpn']) || ! empty($check['is_hosting']) || ! empty($check['is_datacenter'])) {
                $isVpn = true;
                $detectionMethod = $check['detection_method'] ?? $source;
                break;
            }
        }

        $primary = $checks['iphub'] ?? $checks['ipapi'] ?? [];
        $provider = $primary['provider'] ?? null;
        $countryCode = $primary['country_code'] ?? null;
        $city = $primary['city'] ?? null;

        return [
            'ip' => $ip,
            'is_vpn' => $isVpn,
            'is_proxy' => $isVpn,
            'is_tor' => false,
            'provider' => $provider,
            'country_code' => $countryCode,
            'city' => $city,
            'detection_method' => $detectionMethod,
        ];
    }

    private function clean(): array
    {
        return [
            'is_vpn' => false,
            'is_proxy' => false,
            'is_tor' => false,
        ];
    }

    private function isPrivateIp(string $ip): bool
    {
        return ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}
