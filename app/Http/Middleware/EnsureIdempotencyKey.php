<?php

namespace App\Http\Middleware;

use App\Enums\ResponseCode;
use App\Models\IdempotencyKey;
use App\Support\Http\ResponsePayload;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class EnsureIdempotencyKey
{
    public function handle(Request $request, Closure $next, string $scope = 'default'): Response
    {
        $method = strtoupper($request->getMethod());
        if (! in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            return $next($request);
        }

        $key = (string) $request->header('Idempotency-Key', '');
        if ($key === '') {
            return $next($request);
        }

        // Normalize key: keep it small and safe for DB index
        $key = Str::limit(trim($key), 120, '');
        $userId = Auth::check() ? (int) Auth::id() : null;
        $hash = hash('sha256', $method.'|'.$request->path().'|'.$request->getContent());

        // Strict scopes should only be used for machine/API flows that expect 409 conflicts.
        // Web flows (checkout button double-click / browser retry / 302 chains) should not hard-fail UX.
        $isStrictScope = str_starts_with($scope, 'api.v1.payments.')
            || str_starts_with($scope, 'api.v1.orders.')
            || str_starts_with($scope, 'api.v1.wallet.');

        try {
            $row = IdempotencyKey::query()->where('scope', $scope)->where('idempotency_key', $key)->first();
            if ($row) {
                // Phase 2/3 behavior: reject duplicates.
                // If the same key is reused with a different payload, treat it as a conflict.
                if ($row->request_hash !== null && $row->request_hash !== $hash) {
                    return ResponsePayload::fail(
                        ResponseCode::DUPLICATE_ORDER,
                        'responses.DUPLICATE_ORDER',
                        [
                            'idempotency_key' => $key,
                            'reason' => 'idempotency_key_reused_with_different_request',
                        ],
                        409
                    )->toResponse($request);
                }

                // Best-practice for non-critical scopes: ignore replay of same request key
                // instead of hard-failing the UI with 409 (users double-click / browsers retry).
                if (! $isStrictScope) {
                    return $next($request);
                }

                return ResponsePayload::fail(
                    ResponseCode::DUPLICATE_ORDER,
                    'responses.DUPLICATE_ORDER',
                    [
                        'idempotency_key' => $key,
                        'completed_at' => $row->completed_at?->toISOString(),
                        'response_status' => $row->response_status,
                    ],
                    409
                )->toResponse($request);
            }

            IdempotencyKey::create([
                'scope' => $scope,
                'idempotency_key' => $key,
                'user_id' => $userId,
                'request_hash' => $hash,
            ]);
        } catch (\Throwable) {
            // Never block business flows if idempotency storage fails.
            return $next($request);
        }

        /** @var Response $response */
        $response = $next($request);

        try {
            IdempotencyKey::query()
                ->where('scope', $scope)
                ->where('idempotency_key', $key)
                ->update([
                    'response_status' => $response->getStatusCode(),
                    'completed_at' => now(),
                ]);
        } catch (\Throwable) {
            // ignore
        }

        return $response;
    }
}
