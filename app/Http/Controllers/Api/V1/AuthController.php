<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ResponseCode;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\SecurityEventLog;
use App\Models\User;
use App\Services\DeviceTrustService;
use App\Services\MultiAccountDetector;
use App\Services\OtpService;
use App\Services\SecurityEventService;
use App\Services\TwoFactorService;
use App\Support\Http\ResponsePayload;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * API v1 — Mobile-first OTP auth (aligned with UnifiedAuthController).
 */
class AuthController extends Controller
{
    use ApiResponse;

    private const PROFILE_CACHE_PREFIX = 'api_auth_profile:';

    public function __construct(
        private OtpService $otp,
        private TwoFactorService $twoFactor,
        private DeviceTrustService $deviceTrust,
        private MultiAccountDetector $multiAccount,
        private SecurityEventService $securityEvents,
    ) {}

    /**
     * Normalize to 10-digit Indian mobile (same rules as UnifiedAuthController).
     */
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

    public function sendOtp(Request $request): ResponsePayload
    {
        $phone = $this->normalizePhone((string) $request->input('mobile', ''));

        $validator = Validator::make(
            ['mobile' => $phone],
            [
                'mobile' => ['required', 'regex:/^[6-9]\d{9}$/'],
            ],
            ['mobile.regex' => 'Please enter a valid 10-digit Indian mobile number.']
        );

        if ($validator->fails()) {
            return ResponsePayload::fail(
                ResponseCode::VALIDATION_FAILED,
                'auth.validation_failed',
                ['mobile' => $validator->errors()->get('mobile')],
                422
            );
        }

        $result = $this->otp->sendOtp($phone, $request->input('type', 'login'));

        if (! ($result['success'] ?? false)) {
            return ResponsePayload::fail(
                ResponseCode::VALIDATION_FAILED,
                'auth.otp_send_failed',
                ['reason' => $result['error'] ?? ($result['message'] ?? null)],
                422
            );
        }

        return ResponsePayload::ok('auth.otp_sent', [
            'expires_in' => $result['expires_in'] ?? null,
            'resend_available' => $result['resend_available'] ?? null,
        ]);
    }

    /**
     * Verify OTP: existing user → Sanctum token; new number → temp token for profile step.
     */
    public function verifyOtp(Request $request): ResponsePayload
    {
        $phone = $this->normalizePhone((string) $request->input('mobile', ''));
        $otp = (string) $request->input('otp', '');

        $validator = Validator::make(
            ['mobile' => $phone, 'otp' => $otp],
            [
                'mobile' => ['required', 'regex:/^[6-9]\d{9}$/'],
                'otp' => ['required', 'string', 'min:4'],
            ],
            ['mobile.regex' => 'Please enter a valid 10-digit Indian mobile number.']
        );

        if ($validator->fails()) {
            return ResponsePayload::fail(
                ResponseCode::VALIDATION_FAILED,
                'auth.validation_failed',
                $validator->errors()->toArray(),
                422
            );
        }

        if (! $this->otp->verifyOtp($phone, $otp)) {
            throw ValidationException::withMessages(['otp' => 'Invalid or expired OTP.']);
        }

        $user = User::query()->whereMobile($phone)->first();

        if ($user) {
            if ($user->is_blocked) {
                return ResponsePayload::fail(
                    ResponseCode::FORBIDDEN,
                    'auth.account_blocked',
                    [
                        'contact_info' => [
                            'email' => config('companyDefaultValues.company_email', 'support@amazepays.in'),
                            'phone' => '+91 '.config('companyDefaultValues.company_contact_no', ''),
                        ],
                    ],
                    403
                );
            }

            if ($user->account_locked) {
                return ResponsePayload::fail(
                    ResponseCode::FORBIDDEN,
                    'auth.account_locked',
                    [
                        'locked_until' => $user->account_locked_until,
                        'reason' => $user->account_locked_reason,
                    ],
                    423
                );
            }

            $deviceCheck = $this->deviceTrust->checkDevice($user, $request);

            $user->update([
                'last_login_ip' => $request->ip(),
                'last_login_at' => now(),
                'last_login_country' => $request->attributes->get('vpn_details')['country_code'] ?? null,
            ]);

            if ($user->two_factor_enabled) {
                $pendingToken = $user->createToken('2fa-pending', ['2fa-pending'])->plainTextToken;

                return new ResponsePayload(
                    success: true,
                    code: ResponseCode::OK,
                    messageKey: 'auth.two_factor_required',
                    data: [
                        'action' => '2fa_required',
                        'message' => __('auth.two_factor_required'),
                        'temp_token' => $pendingToken,
                        'method' => 'totp',
                        'new_device' => $deviceCheck['is_new'],
                    ],
                    httpStatus: 202
                );
            }

            $token = $user->createToken('mobile-app')->plainTextToken;

            $this->securityEvents->log(
                SecurityEventLog::EVENT_LOGIN_SUCCESS,
                SecurityEventLog::SEVERITY_INFO
            );

            return ResponsePayload::ok('auth.logged_in', [
                'action' => 'logged_in',
                'token' => $token,
                'user' => $this->formatUser($user),
                'new_device' => $deviceCheck['is_new'],
            ]);
        }

        $tempToken = bin2hex(random_bytes(32));

        Cache::put(
            self::PROFILE_CACHE_PREFIX.$tempToken,
            ['phone' => $phone, 'at' => now()->timestamp],
            now()->addSeconds(900)
        );

        return ResponsePayload::ok('auth.needs_profile', [
            'action' => 'needs_profile',
            'temp_token' => $tempToken,
            'phone' => $phone,
        ]);
    }

    /**
     * Complete registration after OTP verified for a new mobile (cache-bound temp token).
     */
    public function completeProfile(Request $request): ResponsePayload
    {
        $request->validate([
            'temp_token' => 'required|string',
            'name' => 'required|string|max:255',
            'referral_code' => 'nullable|string|max:64',
        ]);

        $payload = Cache::get(self::PROFILE_CACHE_PREFIX.$request->input('temp_token'));

        if (! $payload || ! isset($payload['phone'], $payload['at'])) {
            return ResponsePayload::fail(ResponseCode::VALIDATION_FAILED, 'auth.session_expired', httpStatus: 400);
        }

        if ((now()->timestamp - (int) $payload['at']) > 900) {
            Cache::forget(self::PROFILE_CACHE_PREFIX.$request->input('temp_token'));

            return ResponsePayload::fail(ResponseCode::VALIDATION_FAILED, 'auth.session_expired', httpStatus: 400);
        }

        $phone = $payload['phone'];

        if (User::query()->whereMobile((string) $phone)->exists()) {
            return ResponsePayload::fail(ResponseCode::VALIDATION_FAILED, 'auth.account_exists', httpStatus: 409);
        }

        $validator = Validator::make($request->only(['name', 'referral_code']), [
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/'],
            'referral_code' => ['nullable', 'string', 'max:64'],
        ], [
            'name.regex' => 'Name should only contain letters and spaces.',
        ]);

        if ($validator->fails()) {
            return ResponsePayload::fail(
                ResponseCode::VALIDATION_FAILED,
                'auth.validation_failed',
                $validator->errors()->toArray(),
                422
            );
        }

        $validated = $validator->validated();

        $user = User::create([
            'tenant_id' => null,
            'display_name' => $validated['name'],
            'account_type' => 'customer',
            'status' => 'active',
            'is_super_admin' => false,
        ]);

        $user->authIdentities()->firstOrCreate(
            ['type' => 'mobile', 'identifier' => (string) $phone],
            [
                'display_identifier' => (string) $phone,
                'is_primary' => true,
                'verified_at' => now(),
            ]
        );

        $user->assignRole('b2c-user');

        $this->multiAccount->checkNewRegistration($user, [
            'ip' => $request->ip(),
            'device_id' => $this->deviceTrust->generateDeviceId($request),
        ]);

        Cache::forget(self::PROFILE_CACHE_PREFIX.$request->input('temp_token'));

        $token = $user->createToken('mobile-app')->plainTextToken;

        $this->securityEvents->log(
            SecurityEventLog::EVENT_LOGIN_SUCCESS,
            SecurityEventLog::SEVERITY_INFO,
            null,
            ['action' => 'registered_otp']
        );

        return ResponsePayload::created('auth.registration_successful', [
            'action' => 'registered',
            'token' => $token,
            'user' => $this->formatUser($user),
        ]);
    }

    public function verifyTwoFactor(Request $request): ResponsePayload
    {
        $request->validate(['code' => 'required|string']);

        $user = $request->user();

        if (! $this->twoFactor->verify($user, $request->code)
            && ! $this->twoFactor->verifyRecoveryCode($user, $request->code)) {
            $this->securityEvents->log(SecurityEventLog::EVENT_2FA_FAILED, SecurityEventLog::SEVERITY_HIGH);

            throw ValidationException::withMessages(['code' => 'Invalid 2FA code.']);
        }

        $pending = $request->user()->currentAccessToken();
        if ($pending instanceof Model) {
            $pending->delete();
        }
        $token = $user->createToken('mobile-app')->plainTextToken;

        return ResponsePayload::ok('auth.two_factor_verified', [
            'token' => $token,
            'user' => $this->formatUser($user),
        ]);
    }

    public function logout(Request $request): ResponsePayload
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof Model) {
            $token->delete();
        }

        // Sanctum checks the `web` session before Bearer tokens; clear it so mobile/API logout is complete.
        Auth::guard('web')->logout();
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return ResponsePayload::ok('auth.logged_out');
    }

    public function me(Request $request): ResponsePayload
    {
        return ResponsePayload::ok('auth.me', ['user' => $this->formatUser($request->user())]);
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'two_factor_enabled' => $user->two_factor_enabled,
            'transaction_pin_set' => $user->transaction_pin_enabled,
            'roles' => $user->getRoleNames(),
        ];
    }
}
