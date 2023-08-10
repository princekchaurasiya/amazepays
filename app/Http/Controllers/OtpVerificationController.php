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
        // Get the OTP entered by the user
        $otp = $request->input('otp');
        $mobileNumber = $request->input('destination');

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
                // Log in the user
                Auth::login($user);

                // Return a JSON response indicating success
                return response()->json([
                    'status' => 'success',
                    'message' => 'You are logged in.',
                    'user' => auth()->user(),
                ]);
            } else {
                // User not found with the given mobile number
                return response()->json(['status' => 'error', 'message' => 'Invalid Mobile Number']);
            }
        } else {
            // Invalid OTP, show an error message
            return response()->json(['status' => 'error', 'message' => 'Invalid OTP.']);
        }
    }
}