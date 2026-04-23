<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyUnlimitSignature
{
    public function handle(Request $request, Closure $next)
    {
        $signatureHeader = $request->header('Signature');

        if (! $signatureHeader) {
            Log::warning('Unlimit callback missing signature header', [
                'path' => $request->path(),
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Missing signature'], 400);
        }

        $callbackSecret = config('services.unlimit.callback_secret');
        if (! is_string($callbackSecret) || $callbackSecret === '') {
            Log::error('Unlimit callback secret missing in configuration');

            return response()->json(['error' => 'Signature verification unavailable'], 503);
        }

        // Get raw request body (important: don't re-encode JSON)
        $rawBody = $request->getContent();

        // Concatenate body + secret
        $stringToSign = $rawBody . $callbackSecret;

        // Hash with SHA-512
        $expectedSignature = hash('sha512', $stringToSign);

        if (! hash_equals($expectedSignature, $signatureHeader)) {
            Log::warning('Invalid Unlimit callback signature', [
                'path' => $request->path(),
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 403);
        }

        return $next($request);
    }
}
