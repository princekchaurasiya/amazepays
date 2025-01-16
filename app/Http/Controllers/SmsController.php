<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\Sender;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Otp;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;
use Config;
use App\Models\User;
use Mail;

class SmsController extends Controller
{
    public function loginWithOtp(Request $request)
    {
        \Log::info('Login with OTP initiated', ['request' => $request->all()]);

        $destination = $request->input('destination');
        $user = User::where('mobile', $destination)->first();

        if (!$user) {
            \Log::warning('User not found for mobile number', ['mobile' => $destination]);
            return response()->json(['status' => 'error', 'message' => 'The provided mobile number does not match any registered user. Please register first and then login.']);
        }

        \Log::info('User found, proceeding to send SMS', ['user_id' => $user->id]);
        return $this->sendSms($request);
    }

    public function registerWithOtp(Request $request)
    {
        \Log::info('Register with OTP initiated', ['request' => $request->all()]);

        $destination = $request->input('destination');
        $user = User::where('mobile', $destination)->first();

        if ($user) {
            \Log::warning('User already exists for mobile number', ['mobile' => $destination]);
            return response()->json(['status' => 'error', 'message' => 'User Already Exist Please try to log in']);
        }

        \Log::info('No user found, proceeding to send SMS');
        return $this->sendSms($request);
    }

    public function profileUpdateSendOtp(Request $request)
    {
        \Log::info('Profile update OTP send initiated', ['request' => $request->all()]);

        $destination = $request->input('destination');
        return $this->sendSms($request);
    }

    public function sendSms(Request $request)
    {
        \Log::info('Sending SMS initiated', ['request' => $request->all()]);

        $validator = Validator::make($request->all(), [
            'destination' => 'required|regex:/^[6-9]\d{9}$/',
        ]);

        if ($validator->fails()) {
            \Log::error('Validation failed', ['errors' => $validator->errors()]);
            return response()->json(['status' => 'error', 'message' => 'Please enter a valid 10-digit Indian mobile number.']);
        }

        $destination = $request->input('destination');
        \Log::info('Valid mobile number received', ['destination' => $destination]);


        //code block for resend otp concept
        $latestOtpEntry = Otp::where('mobile_number', $destination)->latest()->first();

        // Check if $latestOtpEntry is null
        if ($latestOtpEntry) {
            $expirationTime = Carbon::parse($latestOtpEntry->created_at)->addMinutes(1);
            // \Log::info('Latest OTP entry found', ['latestOtpEntry' => $latestOtpEntry, 'expirationTime' => $expirationTime]);

            if (!Carbon::now()->greaterThan($expirationTime)) {
                // \Log::info('OTP resend attempt blocked', ['destination' => $destination]);
                return response()->json(['status' => 'error', 'message' => 'Wait for a minute before you resend the OTP']);
            }
        } else {
            // \Log::info('No previous OTP entry found for destination', ['destination' => $destination]);
        }

        $otp = config('companyDefaultValues.generated_otp');
        \Log::info('Generated OTP', ['otp' => $otp]);

        $sms_api_url = config('companyDefaultValues.sms_api_url');
        $sms_user_name = config('companyDefaultValues.sms_user_name');
        $sms_user_password = config('companyDefaultValues.sms_user_password');
        $sms_source = config('companyDefaultValues.sms_source');
        $sms_message = config('companyDefaultValues.sms_message');
        $sms_entity_id = config('companyDefaultValues.sms_entity_id');
        $sms_temp_id = config('companyDefaultValues.sms_temp_id');
        $sms_tmid = config("companyDefaultValues.sms_tmid");

        $apiUrl = "$sms_api_url?username=$sms_user_name&password=$sms_user_password&type=0&dlr=1&destination={$destination}&source=$sms_source&message=$sms_message&entityid=$sms_entity_id&tempid=$sms_temp_id&tmid=$sms_tmid";

        // Log the API URL in a beautified format
        \Log::info('Constructed SMS API URL', [

            'api_url' => $apiUrl,
            'base_url' => $sms_api_url,
            'username' => $sms_user_name,
            'password' => $sms_user_password,
            'type' => 0,
            'dlr' => 1,
            'destination' => $destination,
            'source' => $sms_source,
            'message' => $sms_message,
            'entityid' => $sms_entity_id,
            'tempid' => $sms_temp_id,
            'tmid' => $sms_tmid,
        ]);





        $otpExpiration = config('companyDefaultValues.otpExpiration');
        $otpData = [
            'user_id' => null,
            'mobile_number' => $destination,
            'otp' => $otp,
            'expiry_time' => $otpExpiration,
        ];

        \Log::info('Creating OTP entry in database', ['otpData' => $otpData]);
        Otp::create($otpData);
        \Log::info('OTP entry created successfully');

        $response = Http::get($apiUrl);
        \Log::info('API Response received', ['response' => $response->body()]);

        if ($response->successful()) {
            $statusCode = $response->status();
            switch ($statusCode) {
                case 200:
                    $request->session()->put('otp', $otp);
                    \Log::info('OTP sent successfully', ['destination' => $destination]);
                    return response()->json(['status' => 'success', 'message' => 'OTP sent successfully.']);
                case 400:
                    \Log::error('Invalid URL Error', ['destination' => $destination]);
                    return response()->json(['status' => 'error', 'error_code' => 1702, 'message' => 'Invalid URL Error']);
                case 401:
                    \Log::error('Invalid username or password', ['destination' => $destination]);
                    return response()->json(['status' => 'error', 'error_code' => 1703, 'message' => 'Invalid value in username or password field']);
                default:
                    \Log::error('Internal Server Error', ['destination' => $destination]);
                    return response()->json(['status' => 'error', 'error_code' => '500', 'message' => 'Internal Server Error']);
            }
        } else {
            \Log::error('API Request Failed', ['response' => $response->body()]);
            return response()->json(['status' => 'error', 'error_code' => '503', 'message' => 'Service Unavailable']);
        }
    }
}
