<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckIfAuthenticatedAndShowModal
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            // Set session flag indicating login is required
            session(['login_required' => true]);

        }

        return $next($request);
    }

}
