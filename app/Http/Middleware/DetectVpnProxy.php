<?php

namespace App\Http\Middleware;

use App\Services\SecurityEventService;
use App\Services\VpnDetectionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DetectVpnProxy
{
    public function __construct(
        private VpnDetectionService $vpnDetector,
        private SecurityEventService $securityEvents,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.vpn_detection.enabled', true)) {
            return $next($request);
        }

        // Skip exempt routes
        $exemptRoutes = config('security.vpn_detection.exempt_routes', []);
        foreach ($exemptRoutes as $exempt) {
            if ($request->is($exempt)) {
                return $next($request);
            }
        }

        $ip = $request->ip();
        $result = $this->vpnDetector->check($ip);

        if ($result['is_vpn'] || $result['is_proxy'] || $result['is_tor']) {
            // Log the event
            $this->securityEvents->log('vpn_detected', 'high', $ip, [
                'vpn_provider' => $result['provider'] ?? 'unknown',
                'country_code' => $result['country_code'] ?? null,
                'detection_method' => $result['detection_method'] ?? 'unknown',
                'url' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
            ], $request->attributes->get('is_vpn', false));

            $action = $this->resolveAction($request);

            if ($action === 'block') {
                return response()->json([
                    'error' => 'ACCESS_DENIED',
                    'message' => 'Access denied. VPN/proxy connections are not permitted for this action.',
                ], Response::HTTP_FORBIDDEN);
            }

            // Flag mode — attach VPN info to request for downstream use
            $request->attributes->set('vpn_flagged', true);
            $request->attributes->set('vpn_details', $result);
        }

        return $next($request);
    }

    private function resolveAction(Request $request): string
    {
        $routeGroups = config('security.vpn_detection.route_groups', []);

        foreach ($routeGroups as $routePattern => $groupAction) {
            if ($request->routeIs($routePattern) || $request->is(str_replace('.', '/', $routePattern).'*')) {
                return $groupAction;
            }
        }

        return config('security.vpn_detection.action', 'block');
    }
}
