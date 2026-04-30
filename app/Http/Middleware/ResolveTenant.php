<?php

namespace App\Http\Middleware;

use App\Enums\ResponseCode;
use App\Models\ApiKey;
use App\Models\Tenant;
use App\Support\Http\ResponsePayload;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = null;

        // 1. Resolve from authenticated user's tenant (primary pivot, else first link)
        if ($user = $request->user()) {
            $tenant = $user->tenants()->wherePivot('is_primary', true)->first()
                ?? $user->tenants()->first();
        }

        // 2. Resolve from X-Tenant-Slug header (API requests)
        if (! $tenant && $slug = $request->header('X-Tenant-Slug')) {
            $tenant = Tenant::where('slug', $slug)->where('status', 'active')->first();
        }

        // 3. Resolve from API key's tenant (for reseller/loyalty API requests)
        if (! $tenant && $apiKey = $request->bearerToken()) {
            $keyRecord = ApiKey::where('key', hash('sha256', $apiKey))
                ->where('is_active', true)
                ->first();

            if ($keyRecord) {
                $tenant = $keyRecord->tenant;
            }
        }

        if ($tenant) {
            app()->instance('current_tenant_id', $tenant->id);
            app()->instance('current_tenant', $tenant);
            $request->attributes->set('tenant', $tenant);

            // Check if tenant is suspended
            if ($tenant->isSuspended() && ! $request->routeIs('admin.*')) {
                return ResponsePayload::fail(
                    ResponseCode::FORBIDDEN,
                    'error.forbidden',
                    details: ['reason' => 'tenant_suspended'],
                    httpStatus: Response::HTTP_FORBIDDEN
                )->toResponse($request);
            }
        } else {
            app()->instance('current_tenant_id', null);
            app()->instance('current_tenant', null);
        }

        return $next($request);
    }
}
