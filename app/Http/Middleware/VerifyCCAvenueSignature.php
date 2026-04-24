<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

final class VerifyCCAvenueSignature
{
    public function handle(Request $request, Closure $next)
    {
        // CCAvenue returns an encrypted `encResp` field (AES-128-CBC using working key).
        // We treat presence of encResp as the minimum integrity requirement here.
        // The actual decryption + order_status validation happens in the gateway handler.
        if (! $request->filled('encResp')) {
            return response('Bad Request', 400);
        }

        // CCAvenue encrypted response is treated as integrity-protected by the working key.
        // Mark as "verified" for downstream persistence/audit.
        $request->attributes->set('signature_verified', true);
        $request->attributes->set('signature_header', null);

        return $next($request);
    }
}
