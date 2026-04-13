<?php

namespace App\Http\Middleware;

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
                return response()->json(['error' => 'UNAUTHENTICATED'], 401);
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
                return response()->json([
                    'error' => '2FA_REQUIRED',
                    'message' => 'Two-factor authentication must be enabled for your account.',
                    'setup_url' => route('admin.2fa.setup'),
                ], Response::HTTP_FORBIDDEN);
            }

            return redirect()->route('admin.2fa.setup')
                ->with('warning', 'Please enable Two-Factor Authentication to continue.');
        }

        // Check if current request has 2FA verified in session
        if ($user->two_factor_enabled && ! session('2fa_verified')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => '2FA_CHALLENGE',
                    'message' => 'Please complete two-factor authentication.',
                ], Response::HTTP_FORBIDDEN);
            }

            return redirect()->route('admin.2fa.challenge');
        }

        return $next($request);
    }
}
