<?php

namespace App\Http\Middleware;

use App\Models\SecurityEventLog;
use App\Services\SecurityEventService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyTransactionPin
{
    public function __construct(private SecurityEventService $securityEvents) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.transaction_pin.enabled')) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'UNAUTHENTICATED'], Response::HTTP_UNAUTHORIZED);
        }

        // Check if transaction PIN is required for this user
        if (! $this->isPinRequired($request, $user)) {
            return $next($request);
        }

        $pin = $request->header('X-Transaction-Pin') ?? $request->input('transaction_pin');
        $pinCode = $request->input('pin');

        if (! $pin && ! $pinCode) {
            return response()->json([
                'error' => 'TRANSACTION_PIN_REQUIRED',
                'message' => 'Transaction PIN is required for this action.',
            ], Response::HTTP_FORBIDDEN);
        }

        $transactionPin = $user->transactionPin;

        if (! $transactionPin) {
            return response()->json([
                'error' => 'PIN_NOT_SET',
                'message' => 'Please set a transaction PIN before performing financial actions.',
            ], Response::HTTP_FORBIDDEN);
        }

        if ($transactionPin->isLocked()) {
            $this->securityEvents->log(
                SecurityEventLog::EVENT_TRANSACTION_PIN_FAILED,
                SecurityEventLog::SEVERITY_HIGH,
                null,
                ['reason' => 'pin_locked', 'user_id' => $user->id]
            );

            return response()->json([
                'error' => 'PIN_LOCKED',
                'message' => 'Transaction PIN is locked. Try again in 30 minutes.',
                'locked_until' => $transactionPin->locked_until,
            ], Response::HTTP_LOCKED);
        }

        if (! $transactionPin->verify($pin ?? $pinCode)) {
            $this->securityEvents->log(
                SecurityEventLog::EVENT_TRANSACTION_PIN_FAILED,
                SecurityEventLog::SEVERITY_MEDIUM,
                null,
                [
                    'user_id' => $user->id,
                    'failed_attempts' => $transactionPin->fresh()->failed_attempts,
                ]
            );

            return response()->json([
                'error' => 'INVALID_PIN',
                'message' => 'Invalid transaction PIN.',
                'attempts_remaining' => max(0, config('security.transaction_pin.max_failed_attempts', 5) - $transactionPin->fresh()->failed_attempts),
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }

    private function isPinRequired(Request $request, $user): bool
    {
        if (! $user->transaction_pin_enabled) {
            return false;
        }

        // Always required for B2B
        if ($user->hasRole(['b2b-client', 'b2b-operator']) && config('security.transaction_pin.required_for_b2b')) {
            return true;
        }

        // Optional for B2C but required above threshold
        $threshold = config('security.transaction_pin.required_above_amount', 500);
        $amount = $request->input('amount') ?? $request->input('grand_payable_amount');

        if ($amount && $amount >= $threshold) {
            return true;
        }

        return config('security.transaction_pin.required_for_b2c', false);
    }
}
