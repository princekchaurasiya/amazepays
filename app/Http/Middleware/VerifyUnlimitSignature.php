<?php

namespace App\Http\Middleware;

use App\Enums\ResponseCode;
use App\Support\Http\ResponsePayload;
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

            return ResponsePayload::fail(ResponseCode::VALIDATION_FAILED, 'payments.missing_signature', httpStatus: 400);
        }

        $callbackSecret = config('services.unlimit.callback_secret');
        if (! is_string($callbackSecret) || $callbackSecret === '') {
            Log::error('Unlimit callback secret missing in configuration');

            return ResponsePayload::fail(ResponseCode::INTERNAL_ERROR, 'payments.signature_unavailable', httpStatus: 503);
        }

        // Get raw request body (important: don't re-encode JSON)
        $rawBody = $request->getContent();

        // Concatenate body + secret
        $stringToSign = $rawBody.$callbackSecret;

        // Hash with SHA-512
        $expectedSignature = hash('sha512', $stringToSign);

        if (! hash_equals($expectedSignature, $signatureHeader)) {
            Log::warning('Invalid Unlimit callback signature', [
                'path' => $request->path(),
                'ip' => $request->ip(),
            ]);

            return ResponsePayload::fail(ResponseCode::FORBIDDEN, 'payments.invalid_signature', httpStatus: 403);
        }

        $request->attributes->set('signature_verified', true);
        $request->attributes->set('signature_header', (string) $signatureHeader);

        return $next($request);
    }
}
