<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyUnlimitSignature
{
    public function handle(Request $request, Closure $next)
    {
        Log::info('Raw callback body:', ['body' => $request->getContent()]);
        $signatureHeader = $request->header('Signature');

        if (!$signatureHeader) {
            return response()->json(['error' => 'Missing signature'], 400);
        }

        $callbackSecret = config('services.unlimit.callback_secret');

        // Get raw request body (important: don't re-encode JSON)
        $rawBody = $request->getContent();

        // Concatenate body + secret
        $stringToSign = $rawBody . $callbackSecret;

        // Hash with SHA-512
        $expectedSignature = hash('sha512', $stringToSign);

        if (!hash_equals($expectedSignature, $signatureHeader)) {
            Log::warning('Invalid Unlimit callback signature', [
                'expected' => $expectedSignature,
                'received' => $signatureHeader,
            ]);

            return response()->json(['error' => 'Invalid signature'], 403);
        }

        return $next($request);
    }
}
