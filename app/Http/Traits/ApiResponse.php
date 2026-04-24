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
    protected function ok(string $message, array $data = [], int $status = 200): ResponsePayload
    {
        return new ResponsePayload(
            success: true,
            code: $status === 201 ? ResponseCode::CREATED : ResponseCode::OK,
            messageKey: $message,
            data: $data,
            httpStatus: $status,
        );
    }

    /**
     * Return a 201 Created response.
     */
    protected function created(string $message, array $data = []): ResponsePayload
    {
        return $this->ok($message, $data, 201);
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
    protected function error(string $code, string $message, int $status = 400, array $details = []): ResponsePayload
    {
        // For legacy call sites we accept a string $code and map to ResponseCode when possible.
        $enum = ResponseCode::tryFrom($code) ?? ResponseCode::INTERNAL_ERROR;

        return ResponsePayload::fail($enum, $message, $details, $status);
    }

    protected function notFound(string $message = ''): ResponsePayload
    {
        return ResponsePayload::fail(
            ResponseCode::NOT_FOUND,
            $message !== '' ? $message : 'api.resource_not_found',
            httpStatus: 404
        );
    }

    protected function forbidden(string $message = ''): ResponsePayload
    {
        return ResponsePayload::fail(
            ResponseCode::FORBIDDEN,
            $message !== '' ? $message : 'api.access_denied',
            httpStatus: 403
        );
    }

    protected function unauthorized(string $message = ''): ResponsePayload
    {
        return ResponsePayload::fail(
            ResponseCode::UNAUTHENTICATED,
            $message !== '' ? $message : 'api.authentication_required',
            httpStatus: 401
        );
    }
}
