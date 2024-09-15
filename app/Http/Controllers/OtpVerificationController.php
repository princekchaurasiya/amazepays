<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Otp;
use Auth;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class OtpVerificationController extends Controller
{
    // Verify OTP for login
    public function loginVerifyOtp(Request $request)
    {
        try {
            $otp = $request->input('otp');
            $mobileNumber = $request->input('destination');

            Log::info('Attempting OTP verification for login', ['mobile' => $mobileNumber, 'otp' => $otp]);
            $verificationResult = $this->VerifyOtp($mobileNumber, $otp);

            if ($verificationResult['status'] === 'success') {
                $user = User::where('mobile', $mobileNumber)->first();

                if ($user) {
                    Auth::login($user);
                    Log::info('Login successful for user', ['user_id' => $user->id]);
                } else {
                    Log::error('User not found for login', ['mobile' => $mobileNumber]);
                    return response()->json(['status' => 'error', 'message' => 'User not found.']);
                }
            }

            return response()->json($verificationResult);

        } catch (\Exception $e) {
            Log::error('Error during login OTP verification', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => 'An error occurred during verification.']);
        }
    }

    // Verify OTP for registration
    public function registerVerifyOtp(Request $request)
    {
        try {
            $otp = $request->input('otp');
            $mobileNumber = $request->input('destination');

            Log::info('Attempting OTP verification for registration', ['mobile' => $mobileNumber, 'otp' => $otp]);
            $verificationResult = $this->VerifyOtp($mobileNumber, $otp);

            if ($verificationResult['status'] === 'success') {
                // Create a new user record after successful OTP verification
                $user = User::create([
                    'mobile' => $mobileNumber,
                    // Add other fields as necessary
                ]);

                Auth::login($user); // Automatically log in the user after registration
                Log::info('Registration and login successful for user', ['user_id' => $user->id]);
            }

            return response()->json($verificationResult);

        } catch (\Exception $e) {
            Log::error('Error during registration OTP verification', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => 'An error occurred during registration.']);
        }
    }

    // OTP verification logic
    public function VerifyOtp($mobileNumber, $otp)
    {
        try {
            Log::info('Starting OTP verification', ['mobile' => $mobileNumber]);

            $latestOtpEntry = Otp::where('mobile_number', $mobileNumber)->latest()->first();

            if (!$latestOtpEntry) {
                Log::error('No OTP entry found for mobile number', ['mobile' => $mobileNumber]);
                return ['status' => 'error', 'message' => 'Invalid OTP'];
            }

            $expirationTime = Carbon::parse($latestOtpEntry->created_at)->addMinutes(5);

            if (Carbon::now()->greaterThan($expirationTime)) {
                Log::warning('OTP has expired', ['mobile' => $mobileNumber]);
                return ['status' => 'error', 'message' => 'OTP has expired'];
            }

            if ($otp == $latestOtpEntry->otp) {
                Log::info('OTP successfully verified', ['mobile' => $mobileNumber]);
                return ['status' => 'success', 'message' => 'OTP verified'];
            } else {
                Log::error('Invalid OTP entered', ['mobile' => $mobileNumber]);
                return ['status' => 'error', 'message' => 'Invalid OTP'];
            }

        } catch (\Exception $e) {
            Log::error('Error during OTP verification process', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'An error occurred during OTP verification.'];
        }
    }
}
