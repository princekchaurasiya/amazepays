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

class SmsController extends Controller
{
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

        // Generate the OTP (a 6-digit random number)
        $otp = mt_rand(100000, 999999);

        // Build the API URL with the encoded credentials and Template ID
        $apiUrl = "http://route.digimiles.in/bulksms/bulksms?username=DG35-frenetic&password=digimile&type=0&dlr=1&destination={$destination}&source=FRNTIC&message=Dear%20User,%20Your%20one%20time%20password%20{$otp}%20and%20its%20valid%20for%205%20minutes%20only.%20Do%20not%20share%20to%20anyone.%20Thanks%20-%20FRENETIC%20INDIA&entityid=1101633530000071318&tempid=1107169019646710710";

        // Store the OTP in the database along with the user ID and expiration time

        // OTP valid for 5 minutes
        
        $otpExpiration = Carbon::now()->addMinutes(5);
        
        $otpData = [
            'user_id' => null, // Assuming the user is not logged in, so user_id is null
            'mobile_number' => $destination, // Store the mobile number
            'otp' => $otp,
            'expiry_time' => $otpExpiration,
        ];
        
        Otp::create($otpData);
        // dd($otpExpiration, $otpData); 

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
                    return response()->json(['status' => 'error', 'error_code' => 'unknown', 'message' => 'Failed to send OTP']);
            }
        } else {
            // Error occurred while sending OTP
            \Log::error('API Request Failed:', ['response' => $response->body()]);
            return response()->json(['status' => 'error', 'error_code' => 'unknown', 'message' => 'Failed to send OTP']);
        }
    }
}
