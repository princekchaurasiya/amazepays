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
        $destination = $request->input('destination');

        $user = User::where('mobile', $destination)->first();


        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'The provided mobile number does not match any registered user. Please register first and then login.',
            ]);
        }
        return $this->sendSms($request);

    }

    public function registerWithOtp(Request $request)
    {
        $destination = $request->input('destination');

        $user = User::where('mobile', $destination)->first();


        if ($user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User Already Exist Please try to log in',
            ]);
        }
        
        return $this->sendSms($request);
    }



    public function sendSms(Request $request)
    {


        // Validate the destination (mobile number) input
        $validator = Validator::make($request->all(), [
            'destination' => 'required|regex:/^[6-9]\d{9}$/',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please enter a valid 10-digit Indian mobile number.',
            ]);
        }

        // Extract the destination number from the request
        $destination = $request->input('destination');



        // Get the user's stored mobile number
        // $userStoredMobileNumber = $user->mobile;



        // if ($destination == $userStoredMobileNumber) {
        // Generate the OTP (a 6-digit random number)
        $otp = config('companyDefaultValues.generated_otp');



        // get the value from config file
        $sms_api_url = config('companyDefaultValues.sms_api_url');
        $sms_user_name = config('companyDefaultValues.sms_user_name');
        $sms_user_password = config('companyDefaultValues.sms_user_password');
        $sms_source = config('companyDefaultValues.sms_source');
        $sms_message = config('companyDefaultValues.sms_message');
        $sms_entity_id = config('companyDefaultValues.sms_entity_id');
        $sms_temp_id = config('companyDefaultValues.sms_temp_id');

        // Build the API URL with the encoded credentials and Template ID
        $apiUrl = "$sms_api_url?username=$sms_user_name&password=$sms_user_password&type=0&dlr=1&destination={$destination}&source=$sms_source&message=$sms_message&entityid=$sms_entity_id&tempid=$sms_temp_id";


        // Store the OTP in the database along with the user ID and expiration time
        $otpExpiration = config('companyDefaultValues.otpExpiration');

        $otpData = [
            'user_id' => null, // Assuming the user is not logged in, so user_id is null
            'mobile_number' => $destination, // Store the mobile number
            'otp' => $otp,
            'expiry_time' => $otpExpiration,
        ];

        Otp::create($otpData);

        // Send the HTTP GET request to the API
        $response = Http::get($apiUrl);

        // Log the response for debugging
        \Log::info('API Response:', ['response' => $response]);

        // Check if the API call was successful
        if ($response->successful()) {
            // Parse the response to extract the status code
            $statusCode = $response->status();

            // Handle the response based on the status code
            switch ($statusCode) {
                case 200:
                    // Success, Message Submitted successfully

                    // Store the OTP in the session along with the user ID and expiration time
                    $request->session()->put('otp', $otp);

                    return response()->json([
                        'status' => 'success',
                        'message' => 'OTP sent successfully.',
                    ]);
                    // Redirect to the OTP login form
                    break;
                case 400:
                    // Invalid URL Error
                    return response()->json(['status' => 'error', 'error_code' => 1702, 'message' => 'Invalid URL Error']);
                    break;

                case 401:
                    // Invalid value in username or password field
                    return response()->json(['status' => 'error', 'error_code' => 1703, 'message' => 'Invalid value in username or password field']);
                    break;

                default:
                    // Handle other cases or unexpected status codes
                    return response()->json(['status' => 'error', 'error_code' => '500', 'message' => 'Internal Server Error']);
            }
        } else {
            // Error occurred while sending OTP
            \Log::error('API Request Failed:', ['response' => $response->body()]);
            return response()->json(['status' => 'error', 'error_code' => '503', 'message' => 'Service Unavailable']);
        }
        // }
    }
}
