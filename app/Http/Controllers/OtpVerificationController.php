<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Otp;
use Auth;
use App\Models\User;
use Carbon\Carbon;
class OtpVerificationController extends Controller
{
    public function verifyOtp(Request $request)
    {
        $otp = $request->input('otp');
        $mobileNumber = $request->input('destination');
        $latestOtpEntry = Otp::where('mobile_number', $mobileNumber)->latest()->first();
        if (!$latestOtpEntry) {
            return response()->json(['status' => 'error', 'message' => 'Invalid OTP.']);
        }
        $expirationTime = Carbon::parse($latestOtpEntry->created_at)->addMinutes(5);
        if (Carbon::now()->greaterThan($expirationTime)) {
            return response()->json(['status' => 'error', 'message' => 'OTP has expired.']);
        }
        if ($otp == $latestOtpEntry->otp) {
            $user = User::where('mobile', $mobileNumber)->first();
            if ($user) {
                Auth::login($user);
                return response()->json(['status' => 'success', 'message' => 'You are logged in.', 'user' => auth()->user(),]);
            } else {
                return response()->json(['status' => 'error', 'message' => 'Invalid Mobile Number']);
            }
        } else {
            return response()->json(['status' => 'error', 'message' => 'Invalid OTP.']);
        }
    }
}
