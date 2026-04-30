<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stamp every API request with a correlation ID.
 *
 * Accepts X-Request-Id from the client (e.g. React Native) or generates a
 * new UUID if none is supplied. The ID is:
 *   - injected into every log line via Log::withContext() for the request lifecycle
 *   - echoed back in the X-Request-Id response header so mobile clients can
 *     match their logs to server logs when reporting issues
 *
 * Register in bootstrap/app.php under the API middleware group (prepend).
 */
class StampRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header('X-Request-Id') ?: (string) Str::uuid();

        // Make the ID available to controllers via request attributes.
        $request->attributes->set('request_id', $requestId);

        // Inject into every log line emitted during this request — no manual
        // passing required; all Log::info/warning/error calls pick it up.
        Log::withContext(['request_id' => $requestId]);

        /** @var Response $response */
        $response = $next($request);

        // Echo back so the client can correlate their own logs.
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
