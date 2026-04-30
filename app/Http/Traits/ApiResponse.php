<?php

namespace App\Http\Traits;

use App\Enums\ResponseCode;
use App\Support\Http\ResponsePayload;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponse
{
    /**
     * Return a success response with the standard envelope.
     */
    protected function ok(string $messageKey = 'response.ok', array $data = []): ResponsePayload
    {
        return ResponsePayload::ok($messageKey, $data);
    }

    /**
     * Return a 201 Created response.
     */
    protected function created(string $messageKey = 'response.created', array $data = []): ResponsePayload
    {
        return ResponsePayload::created($messageKey, $data);
    }

    /**
     * Return a paginated response with meta information.
     */
    protected function paginated(LengthAwarePaginator $paginator, string $key = 'items'): ResponsePayload
    {
        return ResponsePayload::paginated($paginator);
    }

    /**
     * Return a standard error response.
     */
    protected function error(ResponseCode $code, string $messageKey = 'error.unknown', int $status = 400, array $details = []): ResponsePayload
    {
        return ResponsePayload::fail($code, $messageKey, $details, $status);
    }

    protected function notFound(string $messageKey = 'error.not_found'): ResponsePayload
    {
        return ResponsePayload::fail(
            ResponseCode::NOT_FOUND,
            $messageKey,
            httpStatus: 404
        );
    }

    protected function forbidden(string $messageKey = 'error.forbidden'): ResponsePayload
    {
        return ResponsePayload::fail(
            ResponseCode::FORBIDDEN,
            $messageKey,
            httpStatus: 403
        );
    }

    protected function unauthorized(string $messageKey = 'error.unauthenticated'): ResponsePayload
    {
        return ResponsePayload::fail(
            ResponseCode::UNAUTHENTICATED,
            $messageKey,
            httpStatus: 401
        );
    }
}
