<?php

namespace App\Http\Middleware;

use App\Services\StepUpAuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforce step-up authentication for financial actions.
 *
 * Usage: Route::middleware('step.up:3')  (requires level 3)
 *        Route::middleware('step.up')    (auto-determine from request amount)
 */
class StepUpAuth
{
    public function __construct(private StepUpAuthService $stepUp) {}

    public function handle(Request $request, Closure $next, int $requiredLevel = 0): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'UNAUTHENTICATED'], 401);
        }

        // Auto-determine required level from amount if not specified
        if ($requiredLevel === 0) {
            $amount = (float) ($request->input('amount') ?? $request->input('grand_payable_amount') ?? 0);
            $context = $request->routeIs('*.wallet.*') ? 'wallet_load' : 'order';
            $requiredLevel = $this->stepUp->requiredLevelForUser($user, $request, $amount, $context);
        }

        $current = $this->stepUp->getCurrentLevel($request);

        if ($current < $requiredLevel) {
            return response()->json([
                'error' => 'STEP_UP_REQUIRED',
                'required_level' => $requiredLevel,
                'required_action' => $this->stepUp->levelName($requiredLevel),
                'current_level' => $current,
                'message' => $this->getStepUpMessage($requiredLevel, $current),
            ], Response::HTTP_PAYMENT_REQUIRED);
        }

        return $next($request);
    }

    private function getStepUpMessage(int $required, int $current): string
    {
        return match ($required) {
            2 => 'Transaction PIN is required.',
            3 => 'Transaction PIN and OTP verification are required.',
            4 => 'Transaction PIN, OTP, and 2FA verification are required.',
            default => 'Additional verification is required.',
        };
    }
}
