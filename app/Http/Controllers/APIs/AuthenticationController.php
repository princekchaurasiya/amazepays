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
    // function sendUserLoginOtp(Request $request)
    // {

    //     $validator = Validator::make($request->all(), ['mobileNumber' => 'required|regex:/^[6-9]\d{9}$/',]);
    //     if ($validator->fails()) {
    //         return response()->json(['status' => 'error', 'message' => 'Please enter a valid 10-digit Indian mobile number.',]);
    //     }
    //     $destination = $request->mobileNumber;


    //     $user = User::where('mobile', $destination)->first();
    //     if (!$user) {
    //         return response()->json(['status' => 'error', 'message' => 'The provided mobile number does not match any registered user. Please register first and then login.',]);
    //     }
    //     $userStoredMobileNumber = $user->mobile;
    //     if ($destination == $userStoredMobileNumber) {
    //         $otp = config('companyDefaultValues.generated_otp');
    //         $sms_api_url = config('companyDefaultValues.sms_api_url');
    //         $sms_user_name = config('companyDefaultValues.sms_user_name');
    //         $sms_user_password = config('companyDefaultValues.sms_user_password');
    //         $sms_source = config('companyDefaultValues.sms_source');
    //         $sms_message = config('companyDefaultValues.sms_message');
    //         $sms_entity_id = config('companyDefaultValues.sms_entity_id');
    //         $sms_temp_id = config('companyDefaultValues.sms_temp_id');
    //         $apiUrl = "$sms_api_url?username=$sms_user_name&password=$sms_user_password&type=0&dlr=1&destination={$destination}&source=$sms_source&message=$sms_message&entityid=$sms_entity_id&tempid=$sms_temp_id";
    //         $otpExpiration = config('companyDefaultValues.otpExpiration');
    //         $otpData = ['user_id' => null, 'mobile_number' => $destination, 'otp' => $otp, 'expiry_time' => $otpExpiration,];
    //         Otp::create($otpData);
    //         \Log::info('API Response:', ['response' => $response]);
    //         if ($response->successful()) {
    //             $statusCode = $response->status();
    //             switch ($statusCode) {
    //                 case 200:
    //                     return response()->json(['status' => 'success', 'status_code' => 200, 'message' => 'OTP sent successfully.',]);
    //                     break;
    //                 case 400:
    //                     return response()->json(['status' => 'error', 'status_code' => 1702, 'message' => 'Invalid URL Error']);
    //                     break;
    //                 case 401:
    //                     return response()->json(['status' => 'error', 'status_code' => 1703, 'message' => 'Invalid value in username or password field']);
    //                     break;
    //                 default:
    //                     return response()->json(['status' => 'error', 'status_code' => 500, 'message' => 'Internal Server Error']);
    //             }
    //         } else {
    //             \Log::error('API Request Failed:', ['response' => $response->body()]);
    //             return response()->json(['status' => 'error', 'status_code' => 503, 'message' => 'Service Unavailable']);
    //         }
    //     }
    // }
    // function verifyUserLoginOtp(Request $request)
    // {
    //     $mobileNumber = $request->mobileNumber;
    //     $otp = $request->otp;
    //     $latestOtpEntry = Otp::where('mobile_number', $mobileNumber)->latest()->first();
    //     if (!$latestOtpEntry) {
    //         return response()->json(['status' => 'error', 'message' => 'Invalid OTP.']);
    //     }
    //     $expirationTime = Carbon::parse($latestOtpEntry->created_at)->addMinutes(5);
    //     if (Carbon::now()->greaterThan($expirationTime)) {
    //         return response()->json(['status' => 'error', 'message' => 'OTP has expired.']);
    //     }
    //     if ($otp == $latestOtpEntry->otp) {
    //         $user = User::where('mobile', $mobileNumber)->first();
    //         if ($user) {
    //             return response()->json(['status' => 'success', 'status_code' => 200, 'message' => 'You are logged in.',]);
    //         } else {
    //             return response()->json(['status' => 'error', 'status_code' => 500, 'message' => 'Invalid Mobile Number']);
    //         }
    //     } else {
    //         return response()->json(['status' => 'error', 'status_code' => 400, 'message' => 'Invalid OTP.']);
    //     }
    // }

    function allProduct(Request $request)
    {
        try {
            $getCategory = DB::table('qs_categories')->first();
            $allProducts = DB::table('qs_products')->select('qs_products.*', 'qs_categories.name as category_name')->leftjoin('qs_categories', 'qs_products.qs_category_id', '=', 'qs_categories.id')->get();
            $allProducts->map(function ($item, $key) {
                $item->currency = json_decode($item->currency);
                $item->price = json_decode($item->price);
                $item->images = json_decode($item->images); });
            return response()->json(['status' => 'success', 'status_code' => 200, 'data' => $allProducts]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'status_code' => 500, 'message' => $e->getMessage()]);
        }
    }
    function singleProductDetails(Request $request)
    {
        $getprdtDetails = QsProduct::where('sku', '=', $request->sku)->first()->toArray();
        $getprdtDetails['price'] = json_decode($getprdtDetails['price']);
        $getprdtDetails['images'] = json_decode($getprdtDetails['images']);
        $getprdtDetails['tnc'] = json_decode($getprdtDetails['tnc']);
        return response()->json(['status' => 'success', 'status_code' => 200, 'data' => $getprdtDetails]);
    }

}
