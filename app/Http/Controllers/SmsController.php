<?php

namespace App\Http\Controllers;

use App\Models\BlockedMobile;
use App\Models\User;
use App\Models\UserOtpCode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SmsController extends Controller
{
    public function loginWithOtp(Request $request)
    {
        $destination = $request->input('destination');
        $user = User::query()->whereMobile((string) $destination)->first();

        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'The provided mobile number does not match any registered user. Please register first and then login.']);
        }

        if ($user->is_blocked) {
            return response()->json([
                'status' => 'error',
                'message' => 'Your account has been restricted. Please contact support.',
                'contact_info' => [
                    'email' => config('companyDefaultValues.company_email'),
                    'phone' => '+91 '.config('companyDefaultValues.company_contact_no'),
                ],
            ]);
        }

        return $this->sendSms($request);
    }

    public function registerWithOtp(Request $request)
    {
        $destination = $request->input('destination');
        if (! $destination) {
            return response()->json(['status' => 'error', 'message' => 'Mobile number is required.']);
        }

        $user = User::query()->whereMobile((string) $destination)->first();

        if ($user) {
            return response()->json(['status' => 'error', 'message' => 'User Already Exists. Please try to log in.']);
        }

        try {
            return $this->sendSms($request);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to send OTP. Please try again.']);
        }
    }

    public function forgetPasswordWithMobileOtp(Request $request)
    {
        $destination = $request->input('destination');
        if (! $destination) {
            return response()->json(['status' => 'error', 'message' => 'Mobile number is required to send OTP.']);
        }

        $user = User::query()->whereMobile((string) $destination)->first();

        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Mobile number not registered. Please sign up.']);
        }

        try {
            return $this->sendSms($request);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to send OTP. Please try again.']);
        }
    }

    public function profileUpdateSendOtp(Request $request)
    {
        return $this->sendSms($request);
    }

    public function sendSms(Request $request)
    {
        $rawDestination = $request->input('destination', '');
        $normalized = preg_replace('/\D+/', '', $rawDestination);
        if (strlen($normalized) > 10) {
            if (substr($normalized, 0, 2) === '91' && strlen($normalized) >= 12) {
                $normalized = substr($normalized, -10);
            } elseif (substr($normalized, 0, 1) === '0' && strlen($normalized) >= 11) {
                $normalized = substr($normalized, -10);
            } else {
                $normalized = substr($normalized, -10);
            }
        }
        $request->merge(['destination' => $normalized]);

        $validator = Validator::make($request->only(['destination']), [
            'destination' => 'required|regex:/^[6-9]\d{9}$/',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Please enter a valid 10-digit Indian mobile number.']);
        }

        $destination = $validator->validated()['destination'];

        if (BlockedMobile::isBlocked($destination)) {
            return response()->json([
                'status' => 'error',
                'message' => 'This mobile number cannot receive OTP. Please contact support.',
                'contact_info' => [
                    'email' => config('companyDefaultValues.company_email', 'support@amazepays.in'),
                    'phone' => '+91 '.config('companyDefaultValues.company_contact_no', ''),
                ],
            ], 403);
        }

        $purpose = strtolower((string) $request->input('otp_type', 'login'));
        $purpose = match ($purpose) {
            'signup', 'register' => 'signup',
            'password_reset', 'forgot', 'forget', 'reset' => 'password_reset',
            'transaction' => 'transaction',
            default => 'login',
        };

        $latestOtpEntry = UserOtpCode::query()
            ->where('channel', 'sms')
            ->where('purpose', $purpose)
            ->where('identifier', $destination)
            ->latest('id')
            ->first();

        if ($latestOtpEntry) {
            $expirationTime = Carbon::parse($latestOtpEntry->created_at)->addMinutes(1);

            if (! Carbon::now()->greaterThan($expirationTime)) {
                return response()->json(['status' => 'error', 'message' => 'Wait for a minute before you resend the OTP']);
            }
        }

        $otp = config('companyDefaultValues.generated_otp');

        $sms_api_url = config('companyDefaultValues.sms_api_url');
        $sms_user_name = config('companyDefaultValues.sms_user_name');
        $sms_user_password = config('companyDefaultValues.sms_user_password');
        $sms_source = config('companyDefaultValues.sms_source');
        $sms_message = config('companyDefaultValues.sms_message');
        $sms_entity_id = config('companyDefaultValues.sms_entity_id');
        $sms_otp_temp_id = config('companyDefaultValues.sms_otp_temp_id');
        $sms_tmid = config('companyDefaultValues.sms_tmid');

        $apiUrl = "$sms_api_url?username=$sms_user_name&password=$sms_user_password&type=0&dlr=1&destination={$destination}&source=$sms_source&message=$sms_message&entityid=$sms_entity_id&tempid=$sms_otp_temp_id&tmid=$sms_tmid";

        $expiresAt = config('companyDefaultValues.otpExpiration');
        if (! $expiresAt instanceof Carbon) {
            $expiresAt = now()->addMinutes(5);
        }
        $userId = User::query()->whereMobile((string) $destination)->value('id');

        UserOtpCode::query()->create([
            'user_id' => $userId,
            'identity_id' => null,
            'channel' => 'sms',
            'purpose' => $purpose,
            'identifier' => $destination,
            'code_hash' => Hash::make($otp),
            'attempts' => 0,
            'max_attempts' => (int) config('sms.otp.max_attempts', 5),
            'expires_at' => $expiresAt,
            'consumed_at' => null,
            'request_ip' => $request->ip(),
        ]);

        if (app()->environment('local') && config('app.debug')) {
            Log::info('[TEST MODE] OTP generated (no SMS sent), use 123456 to verify', [
                'mobile' => $destination,
            ]);
            $request->session()->put('otp', '123456');

            return response()->json(['status' => 'success', 'message' => 'OTP sent (test mode). Use 123456.']);
        }

        $response = Http::get($apiUrl);

        if ($response->successful()) {
            $statusCode = $response->status();
            switch ($statusCode) {
                case 200:
                    $request->session()->put('otp', $otp);

                    return response()->json(['status' => 'success', 'message' => 'OTP sent successfully.']);
                case 400:
                    return response()->json(['status' => 'error', 'error_code' => 1702, 'message' => 'Invalid URL Error']);
                case 401:
                    return response()->json(['status' => 'error', 'error_code' => 1703, 'message' => 'Invalid value in username or password field']);
                default:
                    return response()->json(['status' => 'error', 'error_code' => '500', 'message' => 'Internal Server Error']);
            }
        }

        return response()->json(['status' => 'error', 'error_code' => '503', 'message' => 'Service Unavailable']);
    }
}
