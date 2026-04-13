<?php

namespace App\Http\Middleware;

use App\Services\ThreatDetectionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ThreatDetection
{
    public function __construct(private ThreatDetectionService $detector) {}

    public function handle(Request $request, Closure $next): Response
    {
        $threat = $this->detector->analyzeRequest($request);

        if ($threat->isBlocked()) {
            abort(Response::HTTP_FORBIDDEN, 'Access denied. Your IP has been blocked due to suspicious activity.');
        }

        if ($threat->requiresChallenge()) {
            $request->attributes->set('threat_challenge', true);
            $request->attributes->set('threat_score', $threat->score);
        }

        return $next($request);
    }
}
