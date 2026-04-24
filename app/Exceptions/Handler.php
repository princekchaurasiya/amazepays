<?php

namespace App\Exceptions;

use App\Enums\ResponseCode;
use App\Exceptions\Checkout\KycRequiredException;
use App\Support\Http\ResponsePayload;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
// use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {});

        $this->renderable(function (KycRequiredException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ResponsePayload::fail(
                    ResponseCode::KYC_REQUIRED,
                    'responses.KYC_REQUIRED',
                    [
                        'required_threshold_amount_minor' => (int) $e->threshold->threshold_amount_minor,
                        'currency' => (string) $e->threshold->currency,
                        'required_document_types' => $e->threshold->required_document_types,
                        'missing_document_types' => $e->missingDocumentTypes,
                        'enforcement' => (string) $e->threshold->enforcement,
                    ],
                    422
                );
            }
        });

        $this->renderable(function (WalletFrozenException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ResponsePayload::fail(ResponseCode::WALLET_FROZEN, 'wallet.frozen', [
                    'reason' => $e->getMessage(),
                ]);
            }
        });
    }

    /**
     * Report or log an exception.
     *
     * @return void
     */
    public function report(Throwable $exception)
    {
        // Log the exception
        // Log::info($exception->getMessage(), ['exception' => $exception]);

        parent::report($exception);
    }

    public function render($request, Throwable $exception)
    {
        $message = $exception->getMessage();

        // Check if it's an authentication or validation exception
        if ($exception instanceof AuthenticationException || $exception instanceof ValidationException) {
            return parent::render($request, $exception);
        }

        if ($exception instanceof WalletFrozenException) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ResponsePayload::fail(ResponseCode::WALLET_FROZEN, 'wallet.frozen', [
                    'reason' => $exception->getMessage(),
                ]);
            }

            return parent::render($request, $exception);
        }

        // Check if request is from admin panel - keep errors in admin
        $isAdminRequest = $request->is('admin/*') || $request->is('panel/*');

        // Check if it's a database exception - show user-friendly error
        if ($exception instanceof QueryException ||
            $exception instanceof \PDOException ||
            str_contains($message, 'Base table or view not found') ||
            str_contains($message, 'Table') && str_contains($message, "doesn't exist")) {

            // Log the actual error for debugging
            Log::error('Database Error', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            // Show user-friendly error page
            if ($request->expectsJson()) {
                return ResponsePayload::fail(ResponseCode::INTERNAL_ERROR, 'errors.technical_difficulties');
            }

            // If admin request, show admin error page, otherwise show public error page
            if ($isAdminRequest) {
                if ($request->header('X-Inertia')) {
                    return Inertia::render('Error', [
                        'status' => 500,
                        'message' => 'Something went wrong.',
                    ])->toResponse($request)->setStatusCode(500);
                }

                return response('Something went wrong.', 500, [
                    'Content-Type' => 'text/plain; charset=UTF-8',
                ]);
            }

            if ($request->header('X-Inertia')) {
                return Inertia::render('Error', [
                    'status' => 500,
                    'message' => 'Something went wrong.',
                ])->toResponse($request)->setStatusCode(500);
            }

            return response('Something went wrong.', 500, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        }

        // Check if it's a general exception (not specifically handled)
        if (! ($exception instanceof HttpException)) {
            // Log the error
            Log::error('Application Error', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]);

            // Show user-friendly error page instead of exposing error details
            if ($request->expectsJson()) {
                return ResponsePayload::fail(ResponseCode::INTERNAL_ERROR, 'errors.technical_difficulties');
            }

            // If admin request, show admin error page, otherwise show public error page
            if ($isAdminRequest) {
                if ($request->header('X-Inertia')) {
                    return Inertia::render('Error', [
                        'status' => 500,
                        'message' => 'Something went wrong.',
                    ])->toResponse($request)->setStatusCode(500);
                }

                return response('Something went wrong.', 500, [
                    'Content-Type' => 'text/plain; charset=UTF-8',
                ]);
            }

            if ($request->header('X-Inertia')) {
                return Inertia::render('Error', [
                    'status' => 500,
                    'message' => 'Something went wrong.',
                ])->toResponse($request)->setStatusCode(500);
            }

            return response('Something went wrong.', 500, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        }

        if (str_contains($message, 'Address in mailbox given [] does not comply with RFC 2822, 3.6.2.') ||
        str_contains($message, 'Error code : 5313') ||
        str_contains($message, 'Order failed: Duplicate reference number provided') ||
        str_contains($message, 'Error code: 400')) {

            return ResponsePayload::fail(ResponseCode::VALIDATION_FAILED, 'orders.place_fresh_order', httpStatus: 400);
        }

        return parent::render($request, $exception);
    }
}
