<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponse
{
    /**
     * Return a success response with the standard envelope.
     */
    protected function ok(string $message, array $data = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * Return a 201 Created response.
     */
    protected function created(string $message, array $data = []): JsonResponse
    {
        return $this->ok($message, $data, 201);
    }

    /**
     * Return a paginated response with meta information.
     */
    protected function paginated(LengthAwarePaginator $paginator, string $key = 'items'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Return a standard error response.
     */
    protected function error(string $code, string $message, int $status = 400, array $details = []): JsonResponse
    {
        $error = ['code' => $code, 'message' => $message];

        if ($details) {
            $error['details'] = $details;
        }

        return response()->json([
            'success' => false,
            'error' => $error,
        ], $status);
    }

    protected function notFound(string $message = 'Resource not found.'): JsonResponse
    {
        return $this->error('NOT_FOUND', $message, 404);
    }

    protected function forbidden(string $message = 'Access denied.'): JsonResponse
    {
        return $this->error('FORBIDDEN', $message, 403);
    }

    protected function unauthorized(string $message = 'Authentication required.'): JsonResponse
    {
        return $this->error('UNAUTHENTICATED', $message, 401);
    }
}
