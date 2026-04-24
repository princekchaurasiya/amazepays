<?php

use Illuminate\Http\JsonResponse;

if (! function_exists('kgenError')) {
    function kgenError(string $message, string $errCode): JsonResponse
    {
        $statusCode = match ($errCode) {
            'BAD_REQUEST' => 400,
            'UNAUTHORIZED' => 401,
            'FORBIDDEN' => 403,
            'RECORD_NOT_FOUND' => 404,
            'CONFLICT' => 409,
            'UNPROCESSABLE_ENTITY' => 422,
            'INSUFFICIENT_BALANCE' => 402,
            'OUT_OF_STOCK' => 409,
            'INTERNAL_SERVER_ERROR' => 500,
            default => 500,
        };

        return response()->json([
            'error' => $message,
            'errCode' => $errCode,
        ], $statusCode);
    }
}
