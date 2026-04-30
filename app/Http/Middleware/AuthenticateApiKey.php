<?php

namespace App\Http\Middleware;

use App\Enums\ResponseCode;
use App\Models\ApiKey;
use App\Support\Http\ResponsePayload;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticate Reseller API requests using HMAC-SHA256 signature.
 *
 * Request headers required:
 *   X-API-Key: {raw_key}
 *   X-Timestamp: {unix_timestamp}
 *   X-Signature: HMAC-SHA256(raw_key + timestamp + request_body_sha256, api_secret)
 */
class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $rawKey = $request->header('X-API-Key');
        $timestamp = $request->header('X-Timestamp');
        $signature = $request->header('X-Signature');

        if (! $rawKey || ! $timestamp || ! $signature) {
            return ResponsePayload::fail(
                ResponseCode::UNAUTHENTICATED,
                'error.unauthenticated',
                details: ['reason' => 'missing_auth_headers'],
                httpStatus: Response::HTTP_UNAUTHORIZED
            )->toResponse($request);
        }

        // Replay attack: reject requests older than 5 minutes
        if (abs(time() - (int) $timestamp) > 300) {
            return ResponsePayload::fail(
                ResponseCode::UNAUTHENTICATED,
                'error.unauthenticated',
                details: ['reason' => 'request_expired'],
                httpStatus: Response::HTTP_UNAUTHORIZED
            )->toResponse($request);
        }

        // Look up the API key
        $hashedKey = hash('sha256', $rawKey);
        $apiKey = ApiKey::where('key', $hashedKey)->where('is_active', true)->first();

        if (! $apiKey || ! $apiKey->isValid()) {
            return ResponsePayload::fail(
                ResponseCode::UNAUTHENTICATED,
                'error.unauthenticated',
                details: ['reason' => 'invalid_api_key'],
                httpStatus: Response::HTTP_UNAUTHORIZED
            )->toResponse($request);
        }

        // Verify HMAC signature
        $bodyHash = hash('sha256', $request->getContent());
        $expected = hash_hmac('sha256', $rawKey.$timestamp.$bodyHash, $apiKey->secret);

        if (! hash_equals($expected, $signature)) {
            return ResponsePayload::fail(
                ResponseCode::UNAUTHENTICATED,
                'error.unauthenticated',
                details: ['reason' => 'invalid_signature'],
                httpStatus: Response::HTTP_UNAUTHORIZED
            )->toResponse($request);
        }

        // Check IP whitelist
        if ($apiKey->allowed_ips && ! in_array($request->ip(), $apiKey->allowed_ips)) {
            return ResponsePayload::fail(
                ResponseCode::FORBIDDEN,
                'error.forbidden',
                details: ['reason' => 'ip_not_allowed'],
                httpStatus: Response::HTTP_FORBIDDEN
            )->toResponse($request);
        }

        $apiKey->markUsed();

        $request->attributes->set('api_key', $apiKey);
        $request->attributes->set('api_key_tenant', $apiKey->tenant);

        return $next($request);
    }
}
