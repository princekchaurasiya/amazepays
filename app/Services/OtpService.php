<?php

namespace App\Services;

use App\Models\Otp;
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

        // Hash and store in DB
        Otp::where('mobile_number', $phone)->where('is_used', false)->delete();

        Otp::create([
            'mobile_number' => $phone,
            'otp' => Hash::make($otp),
            'type' => $type,
            'expires_at' => now()->addMinutes(config('sms.otp.validity_minutes', 5)),
            'is_used' => false,
            'ip_address' => request()?->ip(),
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
        $otpRecord = Otp::where('mobile_number', $phone)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $otpRecord) {
            return false;
        }

        // Check max attempts
        if ($otpRecord->attempts >= config('sms.otp.max_attempts', 5)) {
            app(SecurityEventService::class)->log(
                SecurityEventLog::EVENT_OTP_FAILED,
                SecurityEventLog::SEVERITY_MEDIUM,
                null,
                ['reason' => 'max_attempts_exceeded', 'phone_last4' => substr($phone, -4)]
            );
            $otpRecord->update(['is_used' => true]);

            return false;
        }

        if (! Hash::check($code, $otpRecord->otp)) {
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
            'is_used' => true,
            'verified_at' => now(),
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
}
