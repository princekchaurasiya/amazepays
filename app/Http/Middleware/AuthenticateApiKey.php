<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
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
            return response()->json([
                'error' => 'MISSING_AUTH_HEADERS',
                'message' => 'API key authentication headers are required: X-API-Key, X-Timestamp, X-Signature',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Replay attack: reject requests older than 5 minutes
        if (abs(time() - (int) $timestamp) > 300) {
            return response()->json([
                'error' => 'REQUEST_EXPIRED',
                'message' => 'Request timestamp is expired. Ensure your clock is synchronized.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Look up the API key
        $hashedKey = hash('sha256', $rawKey);
        $apiKey = ApiKey::where('key', $hashedKey)->where('is_active', true)->first();

        if (! $apiKey || ! $apiKey->isValid()) {
            return response()->json([
                'error' => 'INVALID_API_KEY',
                'message' => 'The provided API key is invalid or inactive.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Verify HMAC signature
        $bodyHash = hash('sha256', $request->getContent());
        $expected = hash_hmac('sha256', $rawKey.$timestamp.$bodyHash, $apiKey->secret);

        if (! hash_equals($expected, $signature)) {
            return response()->json([
                'error' => 'INVALID_SIGNATURE',
                'message' => 'Request signature verification failed.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Check IP whitelist
        if ($apiKey->allowed_ips && ! in_array($request->ip(), $apiKey->allowed_ips)) {
            return response()->json([
                'error' => 'IP_NOT_ALLOWED',
                'message' => 'Request IP is not in the allowed list for this API key.',
            ], Response::HTTP_FORBIDDEN);
        }

        $apiKey->markUsed();

        $request->attributes->set('api_key', $apiKey);
        $request->attributes->set('api_key_tenant', $apiKey->tenant);

        return $next($request);
    }
}
