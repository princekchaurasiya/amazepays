<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::check() || ! Auth::user()->hasRole('admin')) {
            return redirect('/')->with('error', 'Unauthorized access');
        }

        if (! $request->user() || ! $request->user()->is_admin) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
