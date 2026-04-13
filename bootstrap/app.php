<?php

use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\OrderCreationException;
use App\Exceptions\PaymentFailedException;
use App\Exceptions\VoucherFulfillmentException;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\AuthenticateApiKey;
use App\Http\Middleware\CheckBusinessHours;
use App\Http\Middleware\CheckPurchaseLimits;
use App\Http\Middleware\CheckUserTransactionStatus;
use App\Http\Middleware\CoolingOffPeriod;
use App\Http\Middleware\DetectVpnProxy;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RequireTwoFactor;
use App\Http\Middleware\ResolveTenant;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\StepUpAuth;
use App\Http\Middleware\ThreatDetection;
use App\Http\Middleware\VerifyIpWhitelist;
use App\Http\Middleware\VerifyTransactionPin;
use App\Http\Middleware\VerifyUnlimitSignature;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/auth.php'));

            Route::middleware('web')
                ->group(base_path('routes/checkout.php'));

            Route::middleware('web')
                ->group(base_path('routes/payments.php'));

            Route::middleware('web')
                ->group(base_path('routes/webhooks.php'));

            Route::middleware('web')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Global middleware (every request)
        $middleware->use([
            TrustProxies::class,
            HandleCors::class,
            PreventRequestsDuringMaintenance::class,
            SecurityHeaders::class,
        ]);

        // Web group
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // API group
        $middleware->api(prepend: [
            EnsureFrontendRequestsAreStateful::class,
            ThreatDetection::class,
            DetectVpnProxy::class,
        ]);

        // Middleware aliases
        $middleware->alias([
            'auth' => Authenticate::class,
            'admin' => AdminMiddleware::class,
            'admin.user' => AdminMiddleware::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'vpn.check' => DetectVpnProxy::class,
            'step.up' => StepUpAuth::class,
            'transaction.pin' => VerifyTransactionPin::class,
            'cooling.off' => CoolingOffPeriod::class,
            'two.factor' => RequireTwoFactor::class,
            'tenant' => ResolveTenant::class,
            'ip.whitelist' => VerifyIpWhitelist::class,
            'purchase.limits' => CheckPurchaseLimits::class,
            'business.hours' => CheckBusinessHours::class,
            'api.key' => AuthenticateApiKey::class,
            'check.transaction' => CheckUserTransactionStatus::class,
            'verify.unlimit.signature' => VerifyUnlimitSignature::class,
        ]);

        // Redis rate limiting requires ext-redis (phpredis) or predis + a Redis server.
        // Default off so local XAMPP / setups without Redis work; enable in production when ready.
        if (filter_var(env('RATE_LIMITER_USE_REDIS', false), FILTER_VALIDATE_BOOLEAN)) {
            $middleware->throttleWithRedis();
        }
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Standard JSON envelope for all error responses
        $jsonError = fn (string $code, string $message, int $status, array $details = []) => response()->json([
            'success' => false,
            'error' => array_filter([
                'code' => $code,
                'message' => $message,
                'details' => $details ?: null,
            ]),
        ], $status);

        $exceptions->render(function (AuthenticationException $e, $request) use ($jsonError) {
            if ($request->expectsJson()) {
                return $jsonError('UNAUTHENTICATED', 'Authentication required.', 401);
            }
        });

        $exceptions->render(function (UnauthorizedException $e, $request) use ($jsonError) {
            if ($request->expectsJson()) {
                return $jsonError('FORBIDDEN', 'You do not have permission to perform this action.', 403);
            }
            if ($request->header('X-Inertia')) {
                return Inertia::render('Error', [
                    'status' => 403,
                    'message' => 'You do not have permission to perform this action.',
                ])
                    ->toResponse($request)
                    ->setStatusCode(403);
            }

            return response()->view('errors.403', [], 403);
        });

        $exceptions->render(function (AuthorizationException $e, $request) use ($jsonError) {
            $msg = $e->getMessage() !== '' ? $e->getMessage() : 'You do not have permission to perform this action.';

            if ($request->expectsJson()) {
                return $jsonError('FORBIDDEN', $msg, 403);
            }
            if ($request->header('X-Inertia')) {
                return Inertia::render('Error', [
                    'status' => 403,
                    'message' => $msg,
                ])
                    ->toResponse($request)
                    ->setStatusCode(403);
            }

            return response()->view('errors.403', ['message' => $msg], 403);
        });

        $exceptions->render(function (ModelNotFoundException $e, $request) use ($jsonError) {
            if ($request->expectsJson()) {
                return $jsonError('NOT_FOUND', 'The requested resource was not found.', 404);
            }
            if ($request->header('X-Inertia')) {
                return Inertia::render('Error', [
                    'status' => 404,
                    'message' => 'The page you requested could not be found.',
                ])
                    ->toResponse($request)
                    ->setStatusCode(404);
            }
        });

        $exceptions->render(function (InsufficientBalanceException $e, $request) use ($jsonError) {
            return $jsonError('INSUFFICIENT_BALANCE', $e->getMessage(), 422, [
                'required' => $e->required,
                'available' => $e->available,
            ]);
        });

        $exceptions->render(function (VoucherFulfillmentException $e, $request) use ($jsonError) {
            return $jsonError('VOUCHER_FULFILLMENT_FAILED', $e->getMessage(), 502, [
                'provider' => $e->provider,
            ]);
        });

        $exceptions->render(function (PaymentFailedException $e, $request) use ($jsonError) {
            return $jsonError('PAYMENT_FAILED', $e->getMessage(), 502, [
                'gateway' => $e->gateway,
            ]);
        });

        $exceptions->render(function (OrderCreationException $e, $request) use ($jsonError) {
            return $jsonError('ORDER_CREATION_FAILED', $e->getMessage(), 422);
        });
    })->create();
