<?php

namespace App\Services;

use App\Models\UserOtpCode;
use App\Models\SecurityEventLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class OtpService
{
    public function __construct(private SmsService $sms) {}

    public function sendOtp(string $phone, string $type = 'login'): array
    {
        // Check rate limit
        if ($this->isRateLimited($phone)) {
            return [
                'success' => false,
                'error' => 'RATE_LIMIT',
                'message' => 'Too many OTP requests. Please wait before requesting again.',
            ];
        }

        // Generate OTP
        $otp = $this->generateOtp();

        $purpose = $this->normalizePurpose($type);

        // Hash and store in Phase-3 OTP table
        UserOtpCode::query()
            ->where('channel', 'sms')
            ->where('purpose', $purpose)
            ->where('identifier', $phone)
            ->whereNull('consumed_at')
            ->delete();

        UserOtpCode::query()->create([
            'user_id' => null,
            'identity_id' => null,
            'channel' => 'sms',
            'purpose' => $purpose,
            'identifier' => $phone,
            'code_hash' => Hash::make($otp),
            'attempts' => 0,
            'max_attempts' => (int) config('sms.otp.max_attempts', 5),
            'expires_at' => now()->addMinutes(config('sms.otp.validity_minutes', 5)),
            'consumed_at' => null,
            'request_ip' => request()?->ip(),
        ]);

        // Track rate limit
        $this->trackRateLimit($phone);

        if (app()->environment('local') && config('app.debug')) {
            Log::info('[TEST MODE] OTP stored (hash of 123456 used)', ['phone_last4' => substr($phone, -4)]);

            return [
                'success' => true,
                'message' => 'OTP sent (test mode). Use 123456.',
                'expires_in' => config('sms.otp.validity_minutes', 5) * 60,
                'resend_available' => now()->addSeconds(config('sms.otp.resend_cooldown', 60))->timestamp,
            ];
        }

        // Send SMS
        $sent = $this->sms->sendOtp($phone, $otp, "otp_{$type}");

        if (! $sent) {
            return [
                'success' => false,
                'error' => 'SMS_FAILED',
                'message' => 'Failed to send OTP. Please try again.',
            ];
        }

        return [
            'success' => true,
            'message' => 'OTP sent successfully.',
            'expires_in' => config('sms.otp.validity_minutes', 5) * 60,
            'resend_available' => now()->addSeconds(config('sms.otp.resend_cooldown', 60))->timestamp,
        ];
    }

    public function verifyOtp(string $phone, string $code): bool
    {
        $purpose = $this->normalizePurpose('login');

        $otpRecord = UserOtpCode::query()
            ->where('channel', 'sms')
            ->where('purpose', $purpose)
            ->where('identifier', $phone)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $otpRecord) {
            return false;
        }

        // Check max attempts
        $maxAttempts = (int) ($otpRecord->max_attempts ?: config('sms.otp.max_attempts', 5));
        if ($otpRecord->attempts >= $maxAttempts) {
            app(SecurityEventService::class)->log(
                SecurityEventLog::EVENT_OTP_FAILED,
                SecurityEventLog::SEVERITY_MEDIUM,
                null,
                ['reason' => 'max_attempts_exceeded', 'phone_last4' => substr($phone, -4)]
            );
            $otpRecord->update(['consumed_at' => now()]);

            return false;
        }

        if (! Hash::check($code, $otpRecord->code_hash)) {
            $otpRecord->increment('attempts');

            app(SecurityEventService::class)->log(
                SecurityEventLog::EVENT_OTP_FAILED,
                SecurityEventLog::SEVERITY_LOW,
                null,
                [
                    'phone_last4' => substr($phone, -4),
                    'attempt' => $otpRecord->attempts,
                ]
            );

            return false;
        }

        $otpRecord->update([
            'consumed_at' => now(),
        ]);

        return true;
    }

    private function generateOtp(): string
    {
        if (app()->environment('local') && config('app.debug')) {
            return '123456';
        }

        $length = config('sms.otp.length', 6);

        return str_pad(random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    private function isRateLimited(string $phone): bool
    {
        $key = "otp_rate:{$phone}";
        $limit = config('sms.otp.rate_limit.per_phone', 3);
        $window = config('sms.otp.rate_limit.window', 600);
        $count = Cache::get($key, 0);

        return $count >= $limit;
    }

    private function trackRateLimit(string $phone): void
    {
        $key = "otp_rate:{$phone}";
        $window = config('sms.otp.rate_limit.window', 600);
        $count = Cache::get($key, 0);

        Cache::put($key, $count + 1, $window);
    }

    private function normalizePurpose(string $type): string
    {
        $t = strtolower(trim($type));
        return match ($t) {
            'login' => 'login',
            'signup', 'register' => 'signup',
            'password_reset', 'forgot', 'forget', 'reset' => 'password_reset',
            'transaction' => 'transaction',
            default => 'login',
        };
    }
}
