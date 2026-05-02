<?php

namespace App\Http\Controllers\Auth;

use App\Enums\ResponseCode;
use App\Http\Controllers\Controller;
use App\Models\BlockedMobile;
use App\Models\User;
use App\Models\UserIdentity;
use App\Services\OtpService;
use App\Support\Http\ResponsePayload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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

    private function normalizeOtpType(?string $type): string
    {
        $t = strtolower(trim((string) $type));

        return match ($t) {
            'login', 'signup', 'register', 'transaction' => $t,
            default => 'login',
        };
    }

    private function isInertiaRequest(Request $request): bool
    {
        return $request->headers->has('X-Inertia');
    }

    /**
     * JSON clients (e.g. axios from AuthModal) cannot follow `redirect()->intended()`; mirror that behavior with `redirect_url`.
     */
    private function otpJsonRedirectTarget(Request $request, User $user): string
    {
        $default = (string) $user->homeUrl();
        if ($default === '' || ! str_starts_with($default, '/')) {
            $default = '/';
        }

        $intended = $request->session()->pull('url.intended');
        if (! is_string($intended) || $intended === '') {
            return $default;
        }

        if (str_starts_with($intended, '/') && ! str_starts_with($intended, '//')) {
            return $intended;
        }

        $appUrl = rtrim((string) config('app.url'), '/');
        if ($appUrl !== '' && str_starts_with($intended, $appUrl)) {
            return $intended;
        }

        return $default;
    }

    /**
     * Send OTP to any mobile (login or signup).
     */
    public function sendOtp(Request $request, OtpService $otp)
    {
        $phone = $this->normalizePhone((string) $request->input('phone', $request->input('destination', $request->input('mobile', ''))));

        $validator = Validator::make(
            ['phone' => $phone],
            [
                'phone' => ['required', 'regex:/^[6-9]\d{9}$/'],
            ],
            ['phone.regex' => 'Please enter a valid 10-digit Indian mobile number.']
        );

        if ($validator->fails()) {
            return ResponsePayload::fail(
                ResponseCode::VALIDATION_FAILED,
                messageKey: 'error.validation_failed',
                details: ['phone' => $validator->errors()->get('phone')],
                httpStatus: 422
            );
        }

        $type = $this->normalizeOtpType($request->input('type'));

        // Always create identity first (even before user exists).
        $identity = UserIdentity::query()->firstOrCreate(
            ['type' => 'mobile', 'identifier' => $phone],
            [
                'user_id' => null,
                'display_identifier' => $phone,
                'is_primary' => false,
                'verified_at' => null,
            ]
        );

        $result = $otp->sendOtp($phone, $type, $identity);

        if (! ($result['success'] ?? false)) {
            if (($result['error'] ?? null) === 'RATE_LIMIT' || ($result['error'] ?? null) === 'COOLDOWN') {
                return ResponsePayload::fail(
                    ResponseCode::TOO_MANY_REQUESTS,
                    messageKey: 'error.rate_limited',
                    details: ['retry_after' => $result['retry_after'] ?? null],
                    httpStatus: 429
                );
            }

            return ResponsePayload::fail(
                ResponseCode::UNKNOWN_ERROR,
                messageKey: 'error.unknown',
                details: ['reason' => $result['error'] ?? ($result['message'] ?? null)],
                httpStatus: 422
            );
        }

        return ResponsePayload::ok('auth.otp.sent', [
            'expires_in' => $result['expires_in'] ?? null,
            'resend_available' => $result['resend_available'] ?? null,
            'identity_id' => $identity->id,
        ]);
    }

    /**
     * Verify OTP. Existing user: log in. New user: set session flag for profile step.
     */
    public function verifyOtp(Request $request, OtpService $otp)
    {
        $phone = $this->normalizePhone((string) $request->input('phone', $request->input('destination', $request->input('mobile', ''))));
        $otpCode = (string) $request->input('otp', '');

        $validator = Validator::make(
            ['phone' => $phone, 'otp' => $otpCode],
            [
                'phone' => ['required', 'regex:/^[6-9]\d{9}$/'],
                'otp' => ['required', 'string', 'min:4'],
            ],
            ['phone.regex' => 'Please enter a valid 10-digit Indian mobile number.']
        );

        if ($validator->fails()) {
            return ResponsePayload::fail(
                ResponseCode::VALIDATION_FAILED,
                messageKey: 'error.validation_failed',
                details: $validator->errors()->toArray(),
                httpStatus: 422
            );
        }

        $type = $this->normalizeOtpType($request->input('type'));
        $otpRecord = $otp->verifyOtpRecord($phone, $otpCode, $type);

        if (! $otpRecord) {
            return ResponsePayload::fail(
                ResponseCode::INVALID_OTP,
                messageKey: 'error.invalid_otp',
                details: ['otp' => ['Invalid OTP.']],
                httpStatus: 422
            );
        }

        if (BlockedMobile::isBlocked($phone)) {
            return ResponsePayload::fail(
                ResponseCode::FORBIDDEN,
                messageKey: 'auth.account.blocked',
                details: [
                    'contact_info' => [
                        'email' => config('companyDefaultValues.company_email', 'support@amazepays.in'),
                        'phone' => '+91 '.config('companyDefaultValues.company_contact_no', ''),
                    ],
                ],
                httpStatus: 403
            );
        }

        $identity = UserIdentity::query()->firstOrCreate(
            ['type' => 'mobile', 'identifier' => $phone],
            [
                'user_id' => null,
                'display_identifier' => $phone,
                'is_primary' => false,
                'verified_at' => null,
            ]
        );

        $user = User::query()->whereMobile($phone)->first();

        if ($user) {
            if ($user->is_blocked) {
                return ResponsePayload::fail(
                    ResponseCode::FORBIDDEN,
                    messageKey: 'auth.account.blocked',
                    details: [
                        'contact_info' => [
                            'email' => config('companyDefaultValues.company_email', 'support@amazepays.in'),
                            'phone' => '+91 '.config('companyDefaultValues.company_contact_no', ''),
                        ],
                    ],
                    httpStatus: 403
                );
            }

            if (! $identity->user_id) {
                $identity->forceFill([
                    'user_id' => $user->id,
                    'is_primary' => true,
                ])->save();
            }

            if (! $identity->verified_at) {
                $identity->forceFill(['verified_at' => now()])->save();
            }

            if (! $otpRecord->user_id || ! $otpRecord->identity_id) {
                $otpRecord->forceFill([
                    'user_id' => $user->id,
                    'identity_id' => $identity->id,
                ])->save();
            }

            Auth::login($user);
            $request->session()->regenerate();

            $isInertia = $this->isInertiaRequest($request);
            $expectsJson = $request->expectsJson();

            Log::info('OTP verified', [
                'user_id' => $user->id,
                'authenticated' => auth()->check(),
                'session_id' => $request->session()->getId(),
                'is_inertia' => $isInertia,
                'expects_json' => $expectsJson,
            ]);

            $request->session()->forget([self::SESSION_PHONE, self::SESSION_AT]);

            if ($isInertia || ! $expectsJson) {
                $url = (string) $user->homeUrl();
                if (! str_starts_with($url, '/')) {
                    $url = '/';
                }

                return redirect()->intended($url);
            }

            return ResponsePayload::ok('auth.login.success', [
                'action' => 'logged_in',
                'redirect_url' => $this->otpJsonRedirectTarget($request, $user),
                'identity_id' => $identity->id,
            ]);
        }

        $request->session()->put(self::SESSION_PHONE, $phone);
        $request->session()->put(self::SESSION_AT, now()->timestamp);
        $request->session()->put('unified_auth_identity_id', $identity->id);

        return ResponsePayload::ok('auth.profile.required', [
            'action' => 'needs_profile',
            'phone' => $phone,
            'identity_id' => $identity->id,
        ]);
    }

    /**
     * Complete registration after OTP verified for a new mobile (session-bound).
     */
    public function completeRegistration(Request $request)
    {
        $sessionPhone = $request->session()->get(self::SESSION_PHONE);
        $sessionAt = $request->session()->get(self::SESSION_AT);
        $identityId = $request->session()->get('unified_auth_identity_id');

        if (! $sessionPhone || ! $sessionAt || (now()->timestamp - $sessionAt) > 900) {
            return ResponsePayload::fail(ResponseCode::VALIDATION_FAILED, 'auth.session.expired', details: ['reason' => 'registration_session_expired'], httpStatus: 400);
        }

        $phone = $this->normalizePhone((string) $request->input('phone', $request->input('destination', $request->input('mobile', $sessionPhone))));
        if ($phone !== $sessionPhone) {
            return ResponsePayload::fail(ResponseCode::VALIDATION_FAILED, 'error.validation_failed', details: ['reason' => 'phone_mismatch'], httpStatus: 400);
        }

        if (User::query()->whereMobile($phone)->exists()) {
            return ResponsePayload::fail(ResponseCode::VALIDATION_FAILED, 'auth.account.exists', httpStatus: 409);
        }

        if (BlockedMobile::isBlocked($phone)) {
            return ResponsePayload::fail(
                ResponseCode::FORBIDDEN,
                'auth.account.blocked',
                [
                    'contact_info' => [
                        'email' => config('companyDefaultValues.company_email', 'support@amazepays.in'),
                        'phone' => '+91 '.config('companyDefaultValues.company_contact_no', ''),
                    ],
                ],
                403
            );
        }

        $validator = Validator::make($request->only(['name', 'referral_code']), [
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/'],
            'referral_code' => ['nullable', 'string', 'max:64'],
        ], [
            'name.regex' => 'Name should only contain letters and spaces.',
        ]);

        if ($validator->fails()) {
            return ResponsePayload::fail(ResponseCode::VALIDATION_FAILED, 'error.validation_failed', $validator->errors()->toArray(), 422);
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

        $identity = $identityId ? UserIdentity::query()->find((int) $identityId) : null;
        $identity = $identity ?: UserIdentity::query()->firstOrCreate(
            ['type' => 'mobile', 'identifier' => $phone],
            [
                'user_id' => null,
                'display_identifier' => $phone,
                'is_primary' => false,
                'verified_at' => null,
            ]
        );

        $identity->forceFill([
            'user_id' => $user->id,
            'display_identifier' => $phone,
            'is_primary' => true,
            'verified_at' => $identity->verified_at ?: now(),
        ])->save();

        $request->session()->forget([self::SESSION_PHONE, self::SESSION_AT]);
        Auth::login($user);
        $request->session()->regenerate();

        $isInertia = $this->isInertiaRequest($request);
        $expectsJson = $request->expectsJson();

        Log::info('OTP verified (registration complete)', [
            'user_id' => $user->id,
            'authenticated' => auth()->check(),
            'session_id' => $request->session()->getId(),
            'is_inertia' => $isInertia,
            'expects_json' => $expectsJson,
        ]);

        if ($isInertia || ! $expectsJson) {
            $url = (string) $user->homeUrl();
            if (! str_starts_with($url, '/')) {
                $url = '/';
            }

            return redirect()->intended($url);
        }

        return ResponsePayload::created('auth.profile.completed', [
            'action' => 'registered',
            'redirect_url' => $this->otpJsonRedirectTarget($request, $user),
            'identity_id' => $identity->id,
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
