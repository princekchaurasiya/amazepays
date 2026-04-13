<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function sendOtp(string $phone, string $otp, string $templateType = 'otp_login'): bool
    {
        $driver = config('sms.default', 'msg91');

        return match ($driver) {
            'msg91' => $this->sendMsg91($phone, $otp, $templateType),
            'digimiles' => $this->sendDigimiles($phone, $otp, $templateType),
            'null' => $this->logOnly($phone, $otp),
            default => false,
        };
    }

    public function sendMessage(string $phone, string $message, string $templateType = 'otp_login'): bool
    {
        $driver = config('sms.default', 'msg91');

        return match ($driver) {
            'msg91' => $this->sendMsg91Raw($phone, $message, $templateType),
            'digimiles' => $this->sendDigimilesRaw($phone, $message),
            'null' => $this->logOnly($phone, $message),
            default => false,
        };
    }

    private function sendMsg91(string $phone, string $otp, string $templateType): bool
    {
        $authKey = config('sms.msg91.auth_key');
        $templateId = config("sms.msg91.templates.{$templateType}");
        $senderId = config('sms.msg91.sender_id', 'AMZPAY');

        if (! $authKey || ! $templateId) {
            Log::error('MSG91 config missing', ['template' => $templateType]);

            return false;
        }

        try {
            $response = Http::withHeaders([
                'authkey' => $authKey,
                'content-type' => 'application/json',
            ])->post('https://api.msg91.com/api/v5/otp', [
                'template_id' => $templateId,
                'mobile' => $this->normalizePhone($phone),
                'authkey' => $authKey,
                'otp' => $otp,
                'sender' => $senderId,
            ]);

            if ($response->successful()) {
                Log::info('SMS OTP sent via MSG91', ['phone_last4' => substr($phone, -4)]);

                return true;
            }

            Log::warning('MSG91 SMS failed', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('MSG91 exception', ['error' => $e->getMessage()]);
        }

        return false;
    }

    private function sendMsg91Raw(string $phone, string $message, string $templateType): bool
    {
        $authKey = config('sms.msg91.auth_key');
        $templateId = config("sms.msg91.templates.{$templateType}");
        $senderId = config('sms.msg91.sender_id', 'AMZPAY');

        if (! $authKey) {
            return false;
        }

        try {
            $response = Http::withHeaders([
                'authkey' => $authKey,
            ])->post('https://api.msg91.com/api/v5/sms', [
                'sender' => $senderId,
                'route' => config('sms.msg91.route', '4'),
                'country' => config('sms.msg91.country', '91'),
                'sms' => [[
                    'message' => $message,
                    'to' => [$this->normalizePhone($phone)],
                ]],
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('MSG91 raw SMS exception', ['error' => $e->getMessage()]);
        }

        return false;
    }

    private function sendDigimiles(string $phone, string $otp, string $templateType): bool
    {
        $templateConfig = config('sms.digimiles');
        $message = "Your AmazePays OTP is {$otp}. Valid for 5 minutes. Do not share.";

        return $this->sendDigimilesRaw($phone, $message);
    }

    private function sendDigimilesRaw(string $phone, string $message): bool
    {
        $config = config('sms.digimiles');

        if (! $config['username']) {
            return false;
        }

        try {
            $response = Http::get($config['api_url'], [
                'username' => $config['username'],
                'password' => $config['password'],
                'type' => 0,
                'dlr' => 1,
                'destination' => $this->normalizePhone($phone),
                'source' => $config['sender_id'],
                'message' => $message,
                'entityid' => $config['entity_id'],
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Digimiles SMS exception', ['error' => $e->getMessage()]);
        }

        return false;
    }

    private function normalizePhone(string $phone): string
    {
        // Remove spaces, dashes, parentheses
        $phone = preg_replace('/[\s\-\(\)\+]/', '', $phone);

        // Add India country code if not present
        if (strlen($phone) === 10) {
            $phone = '91'.$phone;
        }

        return $phone;
    }

    private function logOnly(string $phone, string $message): bool
    {
        Log::info('[SMS NULL DRIVER] Would send SMS', [
            'phone_last4' => substr($phone, -4),
            'message' => $message,
        ]);

        return true;
    }
}
