<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
// use Illuminate\Support\Facades\Log;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;

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
        $this->reportable(function (Throwable $e) {

        });
    }

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $exception
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

        // Check if request is from admin panel - keep errors in admin
        $isAdminRequest = $request->is('admin/*') || $request->is('voyager/*');
        
        // Check if it's a database exception - show user-friendly error
        if ($exception instanceof \Illuminate\Database\QueryException || 
            $exception instanceof \PDOException ||
            str_contains($message, 'Base table or view not found') ||
            str_contains($message, 'Table') && str_contains($message, "doesn't exist")) {
            
            // Log the actual error for debugging
            \Illuminate\Support\Facades\Log::error('Database Error', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine()
            ]);
            
            // Show user-friendly error page
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'We are experiencing some technical difficulties. Please try again later.'
                ], 500);
            }
            
            // If admin request, show admin error page, otherwise show public error page
            if ($isAdminRequest) {
                return response()->view('errors.admin-500', [
                    'exception' => $exception
                ], 500);
            }
            
            return response()->view('errors.500', [], 500);
        }

        // Check if it's a general exception (not specifically handled)
        if (!($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpException)) {
            // Log the error
            \Illuminate\Support\Facades\Log::error('Application Error', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ]);
            
            // Show user-friendly error page instead of exposing error details
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'We are experiencing some technical difficulties. Please try again later.'
                ], 500);
            }
            
            // If admin request, show admin error page, otherwise show public error page
            if ($isAdminRequest) {
                return response()->view('errors.admin-500', [
                    'exception' => $exception
                ], 500);
            }
            
            return response()->view('errors.500', [], 500);
        }

        if (str_contains($message, 'Address in mailbox given [] does not comply with RFC 2822, 3.6.2.') || 
        str_contains($message, 'Error code : 5313') || 
        str_contains($message, 'Order failed: Duplicate reference number provided') || 
        str_contains($message, 'Error code: 400')) {

        return response()->json([
            'message' => 'Please place a fresh new order'
        ], 400);
        }

        return parent::render($request, $exception);
    }
}
