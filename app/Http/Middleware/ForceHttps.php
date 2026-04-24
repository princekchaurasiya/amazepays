<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * SECURITY: Force HTTPS for payment-related routes in production
 * This ensures all payment flows use encrypted connections
 */
class ForceHttps
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Only enforce HTTPS in production
        if (app()->environment('production') && ! $request->secure()) {
            $url = $request->getRequestUri();

            // Check if this is a payment-related route
            $paymentRoutes = [
                '/payment/',
                '/unlimit/',
                '/upi/',
                '/checkout',
                '/woohoo/',
            ];

            $isPaymentRoute = false;
            foreach ($paymentRoutes as $route) {
                if (str_contains($url, $route)) {
                    $isPaymentRoute = true;
                    break;
                }
            }

            if ($isPaymentRoute) {
                Log::warning('⚠️ HTTP request to payment route in production', [
                    'url' => $url,
                    'ip' => $request->ip(),
                ]);

                // Redirect to HTTPS version
                return redirect()->secure($request->getRequestUri());
            }
        }

        return $next($request);
    }
}
