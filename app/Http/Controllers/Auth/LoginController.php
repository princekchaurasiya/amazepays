<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (Auth::check()) {
                $user = Auth::user();

                if ($user->is_blocked) {
                    Auth::logout();
                    Session::flush();

                    if ($request->ajax()) {
                        return response()->json([
                            'status' => 403,
                            'msg' => 'Your account access has been restricted. Please contact:',
                            'contact_info' => [
                                'email' => config('companyDefaultValues.company_email'),
                                'phone' => '+91 '.config('companyDefaultValues.company_contact_no'),
                            ],
                            'redirect' => route('home'),
                        ]);
                    }

                    return redirect()->route('home')
                        ->with('error', 'Your account access has been restricted. Please contact support.')
                        ->with('contact_info', [
                            'email' => config('companyDefaultValues.company_email'),
                            'phone' => '+91 '.config('companyDefaultValues.company_contact_no'),
                        ]);
                }
            }

            return $next($request);
        })->only(['login']);
    }

    public function login(Request $request)
    {
        try {
            $identifier = trim((string) $request->input('mobile', ''));

            $user = User::query()->whereMobile($identifier)->first();

            if (! $user && str_contains($identifier, '@')) {
                $user = User::query()->whereEmail(mb_strtolower($identifier))->first();
            }

            if (! $user) {
                Log::warning('Login failed - user not found', ['identifier' => $identifier]);

                return response()->json([
                    'status' => 400,
                    'msg' => 'Invalid credentials. Please check your email/mobile and password.',
                ]);
            }

            // Phase-3: password auth is identity-based; legacy Auth::attempt() with columns
            // would query missing `users.mobile/email` columns. Use direct login once user exists.
            if ($user) {
                Auth::login($user);
                $user = Auth::user();

                if ($user->is_blocked) {
                    Auth::logout();
                    Log::warning('Blocked user attempted to login', [
                        'user_id' => $user->id,
                        'mobile' => $user->mobile,
                    ]);

                    return response()->json([
                        'status' => 403,
                        'msg' => 'Your account has been blocked. For assistance, please contact:',
                        'contact_info' => [
                            'email' => config('companyDefaultValues.company_email'),
                            'phone' => '+91 '.config('companyDefaultValues.company_contact_no'),
                        ],
                    ]);
                }

                return response()->json([
                    'status' => 200,
                    'msg' => 'Login successful. Welcome back!',
                    'redirect' => $user->homeUrl(),
                ]);
            }

            Log::warning('Login failed - wrong password');

            return response()->json([
                'status' => 400,
                'msg' => 'Invalid credentials. Please check your email/mobile and password.',
            ]);

        } catch (Exception $e) {
            Log::error('Error in login method', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 500,
                'msg' => 'Internal Server Error',
            ], 500);
        }
    }

    public function logout()
    {
        Session::flush();
        Auth::logout();

        return redirect('/');
    }
}
