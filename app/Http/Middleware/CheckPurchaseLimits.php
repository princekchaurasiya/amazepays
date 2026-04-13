<?php

namespace App\Http\Middleware;

use App\Models\Order;
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
            return response()->json([
                'error' => 'PURCHASE_LIMIT_EXCEEDED',
                'message' => "Maximum order amount is ₹{$limits['single_order_max']}.",
                'limit' => $limits['single_order_max'],
            ], Response::HTTP_FORBIDDEN);
        }

        // Check daily limit
        $dailySpent = $this->getDailySpent($user->id);
        if ($dailySpent + $amount > $limits['daily_max']) {
            $remaining = max(0, $limits['daily_max'] - $dailySpent);

            return response()->json([
                'error' => 'DAILY_LIMIT_EXCEEDED',
                'message' => "Daily purchase limit reached. Remaining today: ₹{$remaining}.",
                'limit' => $limits['daily_max'],
                'remaining' => $remaining,
            ], Response::HTTP_FORBIDDEN);
        }

        // Check monthly limit
        $monthlySpent = $this->getMonthlySpent($user->id);
        if ($monthlySpent + $amount > $limits['monthly_max']) {
            $remaining = max(0, $limits['monthly_max'] - $monthlySpent);

            return response()->json([
                'error' => 'MONTHLY_LIMIT_EXCEEDED',
                'message' => "Monthly purchase limit reached. Remaining this month: ₹{$remaining}.",
                'limit' => $limits['monthly_max'],
                'remaining' => $remaining,
            ], Response::HTTP_FORBIDDEN);
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
