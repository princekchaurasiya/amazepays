<?php

namespace App\Http\Middleware;

use App\Models\IpWhitelist;
use App\Services\SecurityEventService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforce IP whitelist for B2B API access.
 * If the tenant has defined IP whitelist entries, only those IPs are allowed.
 */
class VerifyIpWhitelist
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = app('current_tenant');

        if (! $tenant) {
            return $next($request);
        }

        $whitelist = IpWhitelist::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->pluck('ip_address')
            ->toArray();

        // If no whitelist defined, allow all
        if (empty($whitelist)) {
            return $next($request);
        }

        $ip = $request->ip();

        if (! in_array($ip, $whitelist)) {
            app(SecurityEventService::class)->log(
                'ip_whitelist_violation',
                'high',
                $ip,
                [
                    'tenant_id' => $tenant->id,
                    'tenant_slug' => $tenant->slug,
                    'allowed_ips' => $whitelist,
                ]
            );

            return response()->json([
                'error' => 'IP_NOT_WHITELISTED',
                'message' => 'Your IP address is not authorized for API access on this account.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
