<?php

namespace App\Http\Controllers;

use App\Enums\ResponseCode;
use App\Support\Http\ResponsePayload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ErrorController extends Controller
{
    public function handleError(Request $request)
    {

        $errorMessage = $request->get('errorMessage', 'An error occurred');

        // Log the error message
        Log::info('Error occurred: '.$errorMessage);

        return Inertia::render('Error', [
            'status' => 500,
            'message' => $errorMessage,
        ])->toResponse($request)->setStatusCode(500);
    }

    public function badRequest(string $message = 'The request was invalid or malformed.'): ResponsePayload
    {
        return ResponsePayload::fail(
            ResponseCode::VALIDATION_FAILED,
            'error.validation_failed',
            details: ['reason' => $message],
            httpStatus: 400
        );
    }

    public function internalServerError(string $message = 'An unexpected error occurred on the server.'): ResponsePayload
    {
        return ResponsePayload::fail(
            ResponseCode::INTERNAL_ERROR,
            'error.unknown',
            details: ['reason' => $message],
            httpStatus: 500
        );
    }

    public function unauthorized(string $message = 'Authentication failed or invalid credentials.'): ResponsePayload
    {
        return ResponsePayload::fail(
            ResponseCode::UNAUTHENTICATED,
            'error.unauthenticated',
            details: ['reason' => $message],
            httpStatus: 401
        );
    }

    public function accessDenied(string $message = 'You do not have permission to perform this action.'): ResponsePayload
    {
        return ResponsePayload::fail(
            ResponseCode::FORBIDDEN,
            'error.forbidden',
            details: ['reason' => $message],
            httpStatus: 403
        );
    }

    public function conflict(string $message = 'The request could not be completed due to a conflict with the current state of the resource.'): ResponsePayload
    {
        return ResponsePayload::fail(
            ResponseCode::VALIDATION_FAILED,
            'error.validation_failed',
            details: ['reason' => $message],
            httpStatus: 409
        );
    }

    public function recordNotFound(string $message = 'The requested resource or record was not found.'): ResponsePayload
    {
        return ResponsePayload::fail(
            ResponseCode::NOT_FOUND,
            'error.not_found',
            details: ['reason' => $message],
            httpStatus: 404
        );
    }

    public function unprocessableEntity(string $message = 'The request was valid but could not be processed due to semantic errors.'): ResponsePayload
    {
        return ResponsePayload::fail(
            ResponseCode::VALIDATION_FAILED,
            'error.validation_failed',
            details: ['reason' => $message],
            httpStatus: 422
        );
    }
}
