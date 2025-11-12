<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use GuzzleHttp\Client;

class BlockVPNUsers
{
    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();
        //Testing VPN proxy address
        //$ip = '104.244.72.115';

        // Skip local IPs
        if ($ip === '127.0.0.1' || $ip === '::1' || $ip === '3.108.172.198') {
            return $next($request);
        }

        $client = new Client();

        try {
            $response = $client->get("http://v2.api.iphub.info/ip/{$ip}", [
                'headers' => [
                    'X-Key' => env('IPHub_API_KEY'),
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            if (isset($data['block']) && $data['block'] > 0) {
                // block = 1 or 2 means it's a VPN or hosting provider
                //abort(403, 'Access denied. VPN usage is not allowed.');
                return response()->view('errors.vpn_blocked', [], 403);
            }
        } catch (\Exception $e) {
            // Log error, but don’t block access on failure
            \Log::error('VPN Check failed: ' . $e->getMessage());
        }

        return $next($request);
    }
}
