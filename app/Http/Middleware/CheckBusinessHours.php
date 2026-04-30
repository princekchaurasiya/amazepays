<?php

namespace App\Http\Middleware;

use App\Enums\ResponseCode;
use App\Support\Http\ResponsePayload;
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
            return ResponsePayload::fail(
                ResponseCode::FORBIDDEN,
                'error.forbidden',
                details: [
                    'reason' => 'outside_business_hours',
                    'hours' => $tenant->business_hours,
                ],
                httpStatus: Response::HTTP_FORBIDDEN
            )->toResponse($request);
        }

        return $next($request);
    }
}
