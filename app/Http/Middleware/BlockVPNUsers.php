<?php

namespace App\Http\Middleware;

use Closure;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BlockVPNUsers
{
    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();
        // Testing VPN proxy address
        // $ip = '104.244.72.115';

        // Skip local IPs
        if ($ip === '127.0.0.1' || $ip === '::1' || $ip === '3.108.172.198') {
            return $next($request);
        }

        $client = new Client;

        try {
            $response = $client->get("http://v2.api.iphub.info/ip/{$ip}", [
                'headers' => [
                    'X-Key' => env('IPHub_API_KEY'),
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            if (isset($data['block']) && $data['block'] > 0) {
                // block = 1 or 2 means it's a VPN or hosting provider
                if ($request->header('X-Inertia')) {
                    return Inertia::render('Error', [
                        'status' => 403,
                        'message' => 'Access denied. VPN/proxy usage is not allowed.',
                    ])->toResponse($request)->setStatusCode(403);
                }

                return response('Access denied. VPN/proxy usage is not allowed.', 403, [
                    'Content-Type' => 'text/plain; charset=UTF-8',
                ]);
            }
        } catch (\Exception $e) {
            // Log error, but don’t block access on failure
            \Log::error('VPN Check failed: '.$e->getMessage());
        }

        return $next($request);
    }
}
