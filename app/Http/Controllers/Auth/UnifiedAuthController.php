<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\BlockedMobile;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class UnifiedAuthController extends Controller
{
    public const SESSION_PHONE = 'unified_auth_verified_phone';

    public const SESSION_AT = 'unified_auth_verified_at';

    private function normalizePhone(string $raw): string
    {
        $normalized = preg_replace('/\D+/', '', $raw);
        if (strlen($normalized) > 10) {
            if (str_starts_with($normalized, '91') && strlen($normalized) >= 12) {
                $normalized = substr($normalized, -10);
            } elseif (str_starts_with($normalized, '0') && strlen($normalized) >= 11) {
                $normalized = substr($normalized, -10);
            } else {
                $normalized = substr($normalized, -10);
            }
        }

        return $normalized;
    }

    /**
     * Send OTP to any mobile (login or signup).
     */
    public function sendOtp(Request $request, OtpService $otp)
    {
        $phone = $this->normalizePhone((string) $request->input('phone', $request->input('destination', '')));

        $validator = Validator::make(
            ['phone' => $phone],
            [
                'phone' => ['required', 'regex:/^[6-9]\d{9}$/'],
            ],
            ['phone.regex' => 'Please enter a valid 10-digit Indian mobile number.']
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $result = $otp->sendOtp($phone, (string) $request->input('type', 'login'));

        if (! ($result['success'] ?? false)) {
            return response()->json([
                'status' => 'error',
                'message' => $result['message'] ?? 'Failed to send OTP.',
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => $result['message'] ?? 'OTP sent.',
            'expires_in' => $result['expires_in'] ?? null,
            'resend_available' => $result['resend_available'] ?? null,
        ]);
    }

    /**
     * Verify OTP. Existing user: log in. New user: set session flag for profile step.
     */
    public function verifyOtp(Request $request, OtpService $otp)
    {
        $phone = $this->normalizePhone((string) $request->input('phone', $request->input('destination', '')));
        $otp = (string) $request->input('otp', '');

        $validator = Validator::make(
            ['phone' => $phone, 'otp' => $otp],
            [
                'phone' => ['required', 'regex:/^[6-9]\d{9}$/'],
                'otp' => ['required', 'string', 'min:4'],
            ],
            ['phone.regex' => 'Please enter a valid 10-digit Indian mobile number.']
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 422);
        }

        if (! $otp->verifyOtp($phone, $otp, (string) $request->input('type', 'login'))) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired OTP',
            ], 400);
        }

        if (BlockedMobile::isBlocked($phone)) {
            return response()->json([
                'status' => 'error',
                'message' => 'This mobile number cannot be used to sign in. Please contact support.',
                'contact_info' => [
                    'email' => config('companyDefaultValues.company_email', 'support@amazepays.in'),
                    'phone' => '+91 '.config('companyDefaultValues.company_contact_no', ''),
                ],
            ], 403);
        }

        $user = User::query()->whereMobile($phone)->first();

        if ($user) {
            if ($user->is_blocked) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Your account has been restricted. Please contact support.',
                    'contact_info' => [
                        'email' => config('companyDefaultValues.company_email', 'support@amazepays.in'),
                        'phone' => '+91 '.config('companyDefaultValues.company_contact_no', ''),
                    ],
                ], 403);
            }

            Auth::login($user);

            // Ensure the mobile identity is marked verified for OTP-only auth.
            $user->authIdentities()
                ->where('type', 'mobile')
                ->where('identifier', $phone)
                ->whereNull('verified_at')
                ->update(['verified_at' => now()]);

            $request->session()->forget([self::SESSION_PHONE, self::SESSION_AT]);

            return response()->json([
                'status' => 'success',
                'action' => 'logged_in',
                'redirect_url' => $user->homeUrl(),
            ]);
        }

        $request->session()->put(self::SESSION_PHONE, $phone);
        $request->session()->put(self::SESSION_AT, now()->timestamp);

        return response()->json([
            'status' => 'success',
            'action' => 'needs_profile',
            'phone' => $phone,
        ]);
    }

    /**
     * Complete registration after OTP verified for a new mobile (session-bound).
     */
    public function completeRegistration(Request $request)
    {
        $sessionPhone = $request->session()->get(self::SESSION_PHONE);
        $sessionAt = $request->session()->get(self::SESSION_AT);

        if (! $sessionPhone || ! $sessionAt || (now()->timestamp - $sessionAt) > 900) {
            return response()->json([
                'status' => 'error',
                'message' => 'Session expired. Please verify your mobile again.',
            ], 400);
        }

        $phone = $this->normalizePhone((string) $request->input('phone', $sessionPhone));
        if ($phone !== $sessionPhone) {
            return response()->json([
                'status' => 'error',
                'message' => 'Phone number does not match verified session.',
            ], 400);
        }

        if (User::query()->whereMobile($phone)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'An account already exists for this number.',
            ], 409);
        }

        if (BlockedMobile::isBlocked($phone)) {
            return response()->json([
                'status' => 'error',
                'message' => 'This mobile number cannot be used to register. Please contact support.',
                'contact_info' => [
                    'email' => config('companyDefaultValues.company_email', 'support@amazepays.in'),
                    'phone' => '+91 '.config('companyDefaultValues.company_contact_no', ''),
                ],
            ], 403);
        }

        $validator = Validator::make($request->only(['name', 'referral_code']), [
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/'],
            'referral_code' => ['nullable', 'string', 'max:64'],
        ], [
            'name.regex' => 'Name should only contain letters and spaces.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $validated = $validator->validated();

        // NOTE: Registration flow needs full Phase-3 identity creation.
        // For now, create a minimal user principal and identities so mobile login works.
        $user = User::create([
            'tenant_id' => null,
            'display_name' => $validated['name'],
            'account_type' => 'customer',
            'status' => 'active',
            'is_super_admin' => false,
        ]);

        $user->authIdentities()->firstOrCreate(
            ['type' => 'mobile', 'identifier' => $phone],
            [
                'display_identifier' => $phone,
                'is_primary' => true,
                'verified_at' => now(),
            ]
        );

        $request->session()->forget([self::SESSION_PHONE, self::SESSION_AT]);
        Auth::login($user);

        return response()->json([
            'status' => 'success',
            'action' => 'registered',
            'redirect_url' => $user->homeUrl(),
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('login', status: Response::HTTP_FOUND);
    }
}
