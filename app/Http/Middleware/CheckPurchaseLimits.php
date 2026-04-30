<?php

namespace App\Http\Middleware;

use App\Models\Order;
use App\Support\Http\ResponsePayload;
use App\Enums\ResponseCode;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPurchaseLimits
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $amount = (float) ($request->input('amount') ?? $request->input('grand_payable_amount') ?? 0);

        if ($amount <= 0) {
            return $next($request);
        }

        $limits = $this->getLimitsForUser($user);

        // Check single-order max
        if ($amount > $limits['single_order_max']) {
            return ResponsePayload::fail(
                ResponseCode::PURCHASE_LIMIT_EXCEEDED,
                'error.purchase_limit_exceeded',
                details: [
                    'scope' => 'single_order',
                    'limit' => $limits['single_order_max'],
                    'amount' => $amount,
                ],
                httpStatus: Response::HTTP_FORBIDDEN
            )->toResponse($request);
        }

        // Check daily limit
        $dailySpent = $this->getDailySpent($user->id);
        if ($dailySpent + $amount > $limits['daily_max']) {
            $remaining = max(0, $limits['daily_max'] - $dailySpent);

            return ResponsePayload::fail(
                ResponseCode::PURCHASE_LIMIT_EXCEEDED,
                'error.purchase_limit_exceeded',
                details: [
                    'scope' => 'daily',
                    'limit' => $limits['daily_max'],
                    'remaining' => $remaining,
                ],
                httpStatus: Response::HTTP_FORBIDDEN
            )->toResponse($request);
        }

        // Check monthly limit
        $monthlySpent = $this->getMonthlySpent($user->id);
        if ($monthlySpent + $amount > $limits['monthly_max']) {
            $remaining = max(0, $limits['monthly_max'] - $monthlySpent);

            return ResponsePayload::fail(
                ResponseCode::PURCHASE_LIMIT_EXCEEDED,
                'error.purchase_limit_exceeded',
                details: [
                    'scope' => 'monthly',
                    'limit' => $limits['monthly_max'],
                    'remaining' => $remaining,
                ],
                httpStatus: Response::HTTP_FORBIDDEN
            )->toResponse($request);
        }

        return $next($request);
    }

    private function getLimitsForUser($user): array
    {
        $role = $user->hasAnyRole(['b2b-client', 'b2b-operator']) ? 'b2b' : 'b2c';
        $defaults = config("security.purchase_limits.{$role}");

        return [
            'single_order_max' => $user->daily_purchase_limit
                ? min($defaults['single_order_max'], $user->daily_purchase_limit)
                : $defaults['single_order_max'],
            'daily_max' => $user->daily_purchase_limit ?? $defaults['daily_max'],
            'monthly_max' => $user->monthly_purchase_limit ?? $defaults['monthly_max'],
        ];
    }

    private function getDailySpent(int $userId): float
    {
        return Order::where('user_id', $userId)
            ->whereDate('created_at', today())
            ->where('order_status', '!=', 'failed')
            ->sum('grand_payable_amount');
    }

    private function getMonthlySpent(int $userId): float
    {
        return Order::where('user_id', $userId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('order_status', '!=', 'failed')
            ->sum('grand_payable_amount');
    }
}
