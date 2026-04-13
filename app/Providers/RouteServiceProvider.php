<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        // Routes are registered in bootstrap/app.php (Laravel 11+).
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });

        // SECURITY: Rate limiting for payment endpoints - prevent abuse
        RateLimiter::for('payments', function (Request $request) {
            // Limit to 10 payment attempts per minute per user/IP
            return Limit::perMinute(10)->by(optional($request->user())->id ?: $request->ip());
        });

        // SECURITY: Stricter rate limiting for payment callbacks/webhooks
        RateLimiter::for('payment-callbacks', function (Request $request) {
            // Limit to 30 callbacks per minute per IP (webhooks from payment gateway)
            return Limit::perMinute(30)->by($request->ip());
        });
    }
}
