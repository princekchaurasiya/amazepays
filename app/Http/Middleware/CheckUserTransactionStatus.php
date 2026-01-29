<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckUserTransactionStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            

            if ($user->is_blocked || !$user->can_transact) {
                Log::warning('Blocked user attempted transaction:', [
                    'user_id' => $user->id,
                    'mobile' => $user->mobile,
                    'route' => $request->route()->getName()
                ]);

                if ($request->ajax()) {
                    return response()->json([
                        'status' => 403,
                        'msg' => 'Your account is currently restricted from making transactions. Please contact:',
                        'contact_info' => [
                            'email' => config('companyDefaultValues.company_email'),
                            'phone' => '+91 ' . config('companyDefaultValues.company_contact_no')
                        ]
                    ]);
                }
                
                return redirect()->route('home')
                    ->with('error', 'Your account is currently restricted from making transactions. Please contact support.')
                    ->with('contact_info', [
                        'email' => config('companyDefaultValues.company_email'),
                        'phone' => '+91 ' . config('companyDefaultValues.company_contact_no')
                    ]);
            }
        }

        return $next($request);
    }
} 