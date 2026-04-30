<?php

namespace App\Http\Middleware;

use App\Enums\ResponseCode;
use App\Support\Http\ResponsePayload;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->expectsJson()) {
                return ResponsePayload::fail(ResponseCode::UNAUTHENTICATED, 'error.unauthenticated', httpStatus: 401)
                    ->toResponse($request);
            }

            return redirect()->guest(route('login'));
        }

        if (app()->environment('local') && config('app.debug')) {
            return $next($request);
        }

        // Check if 2FA is required for this role
        $requireFor = ['super-admin', 'admin', 'finance', 'b2b-client'];

        if ($user->hasAnyRole($requireFor) && ! $user->two_factor_enabled) {
            if ($request->expectsJson()) {
                return ResponsePayload::fail(
                    ResponseCode::FORBIDDEN,
                    'auth.two_factor.required',
                    details: ['setup_url' => route('panel.2fa.setup')],
                    httpStatus: Response::HTTP_FORBIDDEN
                )->toResponse($request);
            }

            return redirect()->route('panel.2fa.setup')
                ->with('warning', 'Please enable Two-Factor Authentication to continue.');
        }

        // Check if current request has 2FA verified in session
        if ($user->two_factor_enabled && ! session('2fa_verified')) {
            if ($request->expectsJson()) {
                return ResponsePayload::fail(
                    ResponseCode::FORBIDDEN,
                    'auth.two_factor.required',
                    details: ['reason' => 'two_factor_challenge_required'],
                    httpStatus: Response::HTTP_FORBIDDEN
                )->toResponse($request);
            }

            return redirect()->route('panel.2fa.challenge');
        }

        return $next($request);
    }
}
