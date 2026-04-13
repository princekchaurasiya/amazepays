<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBusinessHours
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = app('current_tenant');

        if (! $tenant) {
            return $next($request);
        }

        if (! $tenant->isWithinBusinessHours()) {
            return response()->json([
                'error' => 'OUTSIDE_BUSINESS_HOURS',
                'message' => 'Orders can only be placed during business hours for your account.',
                'hours' => $tenant->business_hours,
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
