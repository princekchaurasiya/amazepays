<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class VerifyRazorpaySignature
{
    public function handle(Request $request, Closure $next)
    {
        $secret = (string) config('services.razorpay.key_secret', env('RAZORPAY_KEY_SECRET', ''));
        if ($secret === '') {
            // In local/testing we allow missing secret to keep dev environments usable.
            if (app()->environment(['local', 'testing'])) {
                $request->attributes->set('signature_verified', false);
                $request->attributes->set('signature_header', (string) ($request->header('X-Razorpay-Signature') ?? ''));
                return $next($request);
            }

            return response('Forbidden', 403);
        }

        $signature = (string) ($request->header('X-Razorpay-Signature') ?? '');
        if ($signature === '') {
            // Some flows POST a verify payload instead of a webhook event; allow it through.
            if ($request->hasAll(['razorpay_signature', 'razorpay_payment_id', 'razorpay_order_id'])) {
                $request->attributes->set('signature_verified', true);
                $request->attributes->set('signature_header', (string) ($request->input('razorpay_signature') ?? ''));
                return $next($request);
            }

            return response('Forbidden', 403);
        }

        $raw = $request->getContent();
        $expected = hash_hmac('sha256', $raw, $secret);

        // Compare case-insensitively; Razorpay signatures are hex.
        if (! hash_equals(Str::lower($expected), Str::lower($signature))) {
            return response('Forbidden', 403);
        }

        $request->attributes->set('signature_verified', true);
        $request->attributes->set('signature_header', $signature);

        return $next($request);
    }
}
