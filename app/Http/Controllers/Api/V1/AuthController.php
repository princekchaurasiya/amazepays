<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\SecurityEventLog;
use App\Models\User;
use App\Services\DeviceTrustService;
use App\Services\MultiAccountDetector;
use App\Services\OtpService;
use App\Services\SecurityEventService;
use App\Services\TwoFactorService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
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

    public function sendOtp(Request $request): JsonResponse
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
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $result = $this->otp->sendOtp($phone, $request->input('type', 'login'));

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Verify OTP: existing user → Sanctum token; new number → temp token for profile step.
     */
    public function verifyOtp(Request $request): JsonResponse
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
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        if (! $this->otp->verifyOtp($phone, $otp)) {
            throw ValidationException::withMessages(['otp' => 'Invalid or expired OTP.']);
        }

        $user = User::where('mobile', $phone)->first();

        if ($user) {
            if ($user->is_blocked) {
                return response()->json([
                    'error' => 'ACCOUNT_BLOCKED',
                    'message' => 'Your account has been restricted. Please contact support.',
                    'contact_info' => [
                        'email' => config('companyDefaultValues.company_email', 'support@amazepays.in'),
                        'phone' => '+91 '.config('companyDefaultValues.company_contact_no', ''),
                    ],
                ], 403);
            }

            if ($user->account_locked) {
                return response()->json([
                    'error' => 'ACCOUNT_LOCKED',
                    'message' => $user->account_locked_reason ?? 'Your account has been locked.',
                    'locked_until' => $user->account_locked_until,
                ], 423);
            }

            $deviceCheck = $this->deviceTrust->checkDevice($user, $request);

            $user->update([
                'last_login_ip' => $request->ip(),
                'last_login_at' => now(),
                'last_login_country' => $request->attributes->get('vpn_details')['country_code'] ?? null,
            ]);

            if ($user->two_factor_enabled) {
                $pendingToken = $user->createToken('2fa-pending', ['2fa-pending'])->plainTextToken;

                return response()->json([
                    'action' => '2fa_required',
                    'message' => 'Please complete 2FA verification.',
                    'temp_token' => $pendingToken,
                    'method' => 'totp',
                    'new_device' => $deviceCheck['is_new'],
                ], 202);
            }

            $token = $user->createToken('mobile-app')->plainTextToken;

            $this->securityEvents->log(
                SecurityEventLog::EVENT_LOGIN_SUCCESS,
                SecurityEventLog::SEVERITY_INFO
            );

            return response()->json([
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

        return response()->json([
            'action' => 'needs_profile',
            'temp_token' => $tempToken,
            'phone' => $phone,
        ]);
    }

    /**
     * Complete registration after OTP verified for a new mobile (cache-bound temp token).
     */
    public function completeProfile(Request $request): JsonResponse
    {
        $request->validate([
            'temp_token' => 'required|string',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'referral_code' => 'nullable|string|max:64',
        ]);

        if (! $request->filled('email') || trim((string) $request->input('email')) === '') {
            $request->merge(['email' => null]);
        }

        $payload = Cache::get(self::PROFILE_CACHE_PREFIX.$request->input('temp_token'));

        if (! $payload || ! isset($payload['phone'], $payload['at'])) {
            return response()->json([
                'message' => 'Session expired. Please verify your mobile again.',
            ], 400);
        }

        if ((now()->timestamp - (int) $payload['at']) > 900) {
            Cache::forget(self::PROFILE_CACHE_PREFIX.$request->input('temp_token'));

            return response()->json([
                'message' => 'Session expired. Please verify your mobile again.',
            ], 400);
        }

        $phone = $payload['phone'];

        if (User::where('mobile', $phone)->exists()) {
            return response()->json([
                'message' => 'An account already exists for this number.',
            ], 409);
        }

        $validator = Validator::make($request->only(['name', 'email', 'referral_code']), [
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'referral_code' => ['nullable', 'string', 'max:64'],
        ], [
            'name.regex' => 'Name should only contain letters and spaces.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already registered.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $validated = $validator->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'mobile' => $phone,
            'password' => null,
            'referral_code' => $validated['referral_code'] ?? null,
        ]);

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

        return response()->json([
            'message' => 'Registration successful.',
            'action' => 'registered',
            'token' => $token,
            'user' => $this->formatUser($user),
        ], 201);
    }

    public function verifyTwoFactor(Request $request): JsonResponse
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

        return response()->json([
            'message' => '2FA verified.',
            'token' => $token,
            'user' => $this->formatUser($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
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

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->formatUser($request->user())]);
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
