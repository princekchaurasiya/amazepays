<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Support\Facades\Log;
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
            //
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
        Log::info($exception->getMessage(), ['exception' => $exception]);

        parent::report($exception);
    }

    public function render($request, Throwable $exception)
    {
        // Check if it's an authentication or validation exception
        if ($exception instanceof AuthenticationException || $exception instanceof ValidationException) {
            return parent::render($request, $exception);
        }

        // Check if it's a general exception (not specifically handled)
        if (!($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpException)) {
            $errorMessage = $exception->getMessage();
            return response()->view('userpanel.wentWrong', ['errorMessage' => $errorMessage], 500);
        }

        return parent::render($request, $exception);
    }
}
