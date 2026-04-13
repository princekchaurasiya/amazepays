<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Block high-risk actions for a period after sensitive account changes.
 *
 * Usage: Route::middleware('cooling.off:password_change')
 */
class CoolingOffPeriod
{
    public function handle(Request $request, Closure $next, string $triggerEvent = 'password_change'): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $coolingOffHours = config("security.cooling_off.{$triggerEvent}", 0);

        if ($coolingOffHours <= 0) {
            return $next($request);
        }

        $triggerTime = $this->getTriggerTime($user, $triggerEvent);

        if (! $triggerTime) {
            return $next($request);
        }

        $coolingEndsAt = $triggerTime->addHours($coolingOffHours);

        if (now()->isBefore($coolingEndsAt)) {
            $remaining = now()->diffInMinutes($coolingEndsAt);

            return response()->json([
                'error' => 'COOLING_OFF_PERIOD',
                'message' => "This action is temporarily restricted after a recent account change. Please try again in {$remaining} minutes.",
                'available_at' => $coolingEndsAt->toISOString(),
            ], Response::HTTP_LOCKED);
        }

        return $next($request);
    }

    private function getTriggerTime($user, string $event): ?Carbon
    {
        return match ($event) {
            'password_change' => $user->password_changed_at,
            'email_change' => $user->email_changed_at,
            'phone_change' => $user->phone_changed_at,
            default => null,
        };
    }
}
