<?php

namespace App\Http\Middleware;

use App\Enums\ResponseCode;
use App\Services\SecurityEventService;
use App\Services\VpnDetectionService;
use App\Support\Http\ResponsePayload;
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
                return ResponsePayload::fail(
                    ResponseCode::FORBIDDEN,
                    'error.vpn_blocked',
                    details: ['reason' => 'vpn_proxy_detected'],
                    httpStatus: Response::HTTP_FORBIDDEN
                )->toResponse($request);
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
