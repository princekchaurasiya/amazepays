<?php

namespace App\Http\Middleware;

use App\Enums\ResponseCode;
use App\Models\SecurityEventLog;
use App\Services\SecurityEventService;
use App\Support\Http\ResponsePayload;
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
            return ResponsePayload::fail(ResponseCode::UNAUTHENTICATED, 'error.unauthenticated', httpStatus: Response::HTTP_UNAUTHORIZED)
                ->toResponse($request);
        }

        // Check if transaction PIN is required for this user
        if (! $this->isPinRequired($request, $user)) {
            return $next($request);
        }

        $pin = $request->header('X-Transaction-Pin') ?? $request->input('transaction_pin');
        $pinCode = $request->input('pin');

        if (! $pin && ! $pinCode) {
            return ResponsePayload::fail(
                ResponseCode::FORBIDDEN,
                'error.forbidden',
                details: ['reason' => 'transaction_pin_required'],
                httpStatus: Response::HTTP_FORBIDDEN
            )->toResponse($request);
        }

        $transactionPin = $user->transactionPin;

        if (! $transactionPin) {
            return ResponsePayload::fail(
                ResponseCode::FORBIDDEN,
                'error.forbidden',
                details: ['reason' => 'transaction_pin_not_set'],
                httpStatus: Response::HTTP_FORBIDDEN
            )->toResponse($request);
        }

        if ($transactionPin->isLocked()) {
            $this->securityEvents->log(
                SecurityEventLog::EVENT_TRANSACTION_PIN_FAILED,
                SecurityEventLog::SEVERITY_HIGH,
                null,
                ['reason' => 'pin_locked', 'user_id' => $user->id]
            );

            return ResponsePayload::fail(
                ResponseCode::FORBIDDEN,
                'error.forbidden',
                details: [
                    'reason' => 'transaction_pin_locked',
                    'locked_until' => $transactionPin->locked_until,
                ],
                httpStatus: Response::HTTP_LOCKED
            )->toResponse($request);
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

            return ResponsePayload::fail(
                ResponseCode::FORBIDDEN,
                'error.forbidden',
                details: [
                    'reason' => 'invalid_transaction_pin',
                    'attempts_remaining' => max(0, config('security.transaction_pin.max_failed_attempts', 5) - $transactionPin->fresh()->failed_attempts),
                ],
                httpStatus: Response::HTTP_FORBIDDEN
            )->toResponse($request);
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
