<?php

namespace App\Http\Middleware;

use App\Enums\ResponseCode;
use App\Support\Http\ResponsePayload;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CheckUserTransactionStatus
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Local/testing: allow mock gateway flows even for restricted accounts.
        // This keeps production enforcement intact while letting developers understand the flow end-to-end.
        if (app()->environment(['local', 'testing'])) {
            $name = (string) optional($request->route())->getName();
            if (str_starts_with($name, 'payment.mock_razorpay') || str_starts_with($name, 'mock.razorpay.')) {
                return $next($request);
            }
        }

        if (Auth::check()) {
            $user = Auth::user();

            $isBlocked = Schema::hasColumn('users', 'is_blocked') ? (bool) ($user->is_blocked ?? false) : false;
            $canTransact = Schema::hasColumn('users', 'can_transact') ? (bool) ($user->can_transact ?? true) : true;

            if ($isBlocked || ! $canTransact) {
                Log::warning('Blocked user attempted transaction:', [
                    'user_id' => $user->id,
                    'mobile' => $user->mobile,
                    'route' => $request->route()->getName(),
                ]);

                if ($request->ajax()) {
                    return ResponsePayload::fail(
                        ResponseCode::FORBIDDEN,
                        'auth.account.blocked',
                        details: [
                            'contact_info' => [
                                'email' => config('companyDefaultValues.company_email'),
                                'phone' => '+91 '.config('companyDefaultValues.company_contact_no'),
                            ],
                        ],
                        httpStatus: 403
                    )->toResponse($request);
                }

                return redirect()->route('home')
                    ->with('error', 'Your account is currently restricted from making transactions. Please contact support.')
                    ->with('contact_info', [
                        'email' => config('companyDefaultValues.company_email'),
                        'phone' => '+91 '.config('companyDefaultValues.company_contact_no'),
                    ]);
            }
        }

        return $next($request);
    }
}
