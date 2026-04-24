<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class VerifyWoohooSignature
{
    public function handle(Request $request, Closure $next)
    {
        $secret = (string) env('WOOHOO_WEBHOOK_SECRET', '');
        if ($secret === '') {
            $request->attributes->set('signature_verified', false);
            $request->attributes->set('signature_header', (string) $request->header('X-Woohoo-Signature', ''));
            return app()->environment(['local', 'testing'])
                ? $next($request)
                : response('Forbidden', 403);
        }

        $signature = (string) $request->header('X-Woohoo-Signature', '');
        if ($signature === '') {
            return response('Forbidden', 403);
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        if (! hash_equals(Str::lower($expected), Str::lower($signature))) {
            return response('Forbidden', 403);
        }

        $request->attributes->set('signature_verified', true);
        $request->attributes->set('signature_header', $signature);

        return $next($request);
    }
}
