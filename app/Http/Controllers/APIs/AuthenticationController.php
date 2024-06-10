<?php

namespace App\Http\Controllers\APIs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Otp;
use App\Models\QsProduct;
use App\Http\Controllers\SmsController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;
use Config;
use App\Models\User;
use Carbon\Carbon;
use DB;

class AuthenticationController extends Controller
{
    function sendUserLoginOtp(Request $request){
        // $result = (new SmsController)->sendSms($request);

         // Validate the destination (mobile number) input
        $validator = Validator::make($request->all(), [
            'mobileNumber' => 'required|regex:/^[6-9]\d{9}$/',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please enter a valid 10-digit Indian mobile number.',
            ]);
        }

        // Extract the destination number from the request
        $destination = $request->mobileNumber;
        $user = User::where('mobile', $destination)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'The provided mobile number does not match any registered user. Please register first and then login.',
            ]);
        }

        // Get the user's stored mobile number
        $userStoredMobileNumber = $user->mobile;

        if ($destination == $userStoredMobileNumber) {
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
            //$response = Http::get($apiUrl);

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
                        // $request->session()->put('otp', $otp);
                        return response()->json([
                            'status'  => 'success',
                            'status_code' => 200,
                            'message' => 'OTP sent successfully.',
                        ]);
                     // Redirect to the OTP login form
                     break;
                    case 400:
                        // Invalid URL Error
                        return response()->json(['status' => 'error', 'status_code' => 1702, 'message' => 'Invalid URL Error']);
                        break;

                    case 401:
                        // Invalid value in username or password field
                        return response()->json(['status' => 'error', 'status_code' => 1703, 'message' => 'Invalid value in username or password field']);
                        break;

                    default:
                        // Handle other cases or unexpected status codes
                        return response()->json(['status' => 'error', 'status_code' => 500, 'message' => 'Internal Server Error']);
                }
            } else {
                // Error occurred while sending OTP
                \Log::error('API Request Failed:', ['response' => $response->body()]);
                return response()->json(['status' => 'error', 'status_code' => 503, 'message' => 'Service Unavailable']);
            }
        }
    }

    function verifyUserLoginOtp(Request $request){

         // Get the OTP entered by the user
         $mobileNumber = $request->mobileNumber;
         $otp = $request->otp;

         // Retrieve the latest OTP entry from the database based on the mobile number
         $latestOtpEntry = Otp::where('mobile_number', $mobileNumber)
             ->latest()
             ->first();



         if (!$latestOtpEntry) {
             // No OTP entry found for the mobile number
             return response()->json(['status' => 'error', 'message' => 'Invalid OTP.']);
         }

         // Check if the OTP has expired
         $expirationTime = Carbon::parse($latestOtpEntry->created_at)->addMinutes(5);


         if (Carbon::now()->greaterThan($expirationTime)) {
             // OTP has expired
             return response()->json(['status' => 'error', 'message' => 'OTP has expired.']);
         }

         // Check if the entered OTP matches the one stored in the database
         if ($otp == $latestOtpEntry->otp) {
             // Get the user based on the mobile number
             $user = User::where('mobile', $mobileNumber)->first();

             if ($user) {
                 // Return a JSON response indicating success
                 return response()->json([
                     'status' => 'success',
                     'status_code'=>200,
                     'message' => 'You are logged in.',
                 ]);
             } else {
                 // User not found with the given mobile number
                 return response()->json(['status' => 'error', 'status_code'=>500, 'message' => 'Invalid Mobile Number']);
             }
         } else {
             // Invalid OTP, show an error message
             return response()->json(['status' => 'error',  'status_code'=>400, 'message' => 'Invalid OTP.']);
         }
    }

    function userRegistration(Request $request){
        try {
            $mobileExists = User::where('mobile', $request->mobileNumber)->exists();
            $emailExists = User::where('email', $request->email)->exists();
            $password = 'Z@123456z';
            $errors = [];

            if ($mobileExists) {
                $errors['mobile'] = 'Mobile number already exists';
            }

            if ($emailExists) {
                $errors['email'] = 'Email already exists';
            }

            if (!empty($errors)) {
                $data = [
                    'status' => 400,
                    'errors' => $errors,
                ];
            } else {
                User::create([
                    'name' => $request->first_name ." ". $request->last_name,
                    'email' => $request->email,
                    'password' => bcrypt($password),
                    'role_id' => 2,
                    'mobile' => $request->mobileNumber,
                ]);
            }
            return response()->json(['status' => 'success', 'status_code'=>200, 'message' => 'Redirect to Login Page']);

        } catch (Exception $e) {
            $data = [
                'status' => 500,
                'msg' => 'Internal Server Error',
            ];
            return response()->json($data);
        }
    }

    function homeSlider(Request $request){
        dd(123123);
    }

    function allProduct(Request $request){
        try {
            $getCategory = DB::table('qs_categories')->first();

            $allProducts = DB::table('qs_products')
                ->select('qs_products.*', 'qs_categories.name as category_name')
                ->leftjoin('qs_categories', 'qs_products.qs_category_id', '=', 'qs_categories.id')
                ->get();

            $allProducts->map(function ($item, $key) {
                $item->currency = json_decode($item->currency);
                $item->price = json_decode($item->price);
                $item->images = json_decode($item->images);
            });

            return response()->json(['status' => 'success', 'status_code'=>200, 'data' => $allProducts]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'status_code'=>500, 'message' => $e->getMessage()]);
        }
    }

    function singleProductDetails(Request $request){
        $getprdtDetails = QsProduct::where('sku', '=', $request->sku)->first()->toArray();
        // Convert certain JSON fields back to objects
        $getprdtDetails['price'] = json_decode($getprdtDetails['price']);
        $getprdtDetails['images'] = json_decode($getprdtDetails['images']);
        $getprdtDetails['tnc'] = json_decode($getprdtDetails['tnc']);
        return response()->json(['status' => 'success', 'status_code'=>200, 'data' => $getprdtDetails]);
    }

    function checkOut(Request $request){
        dd(123123);
    }
}
