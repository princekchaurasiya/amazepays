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

            Log::info('OTP verification result', $verificationResult);

            if ($verificationResult['status'] === 'success') {
                $user = User::where('mobile', $mobileNumber)->first();

                if ($user) {
                    Auth::login($user);
                    Log::info('Login successful for user', ['user_id' => $user->id]);

                    // Return the intended URL or homepage URL as the redirect URL
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Login successful',
                        'redirect_url' => url()->previous() // This will provide the URL the user intended to visit
                    ]);
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

            Log::info('OTP verification result', $verificationResult);

            if ($verificationResult['status'] === 'success') {
                // Create a new user record after successful OTP verification
                $user = User::create([
                    'mobile' => $mobileNumber,
                    // Add other fields as necessary
                ]);

                Auth::login($user); // Automatically log in the user after registration
                Log::info('Registration and login successful for user', ['user_id' => $user->id]);
                return redirect()->intended(url()->previous());
            }

            return response()->json($verificationResult);
        } catch (\Exception $e) {
            Log::error('Error during registration OTP verification', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => 'An error occurred during registration.']);
        }
    }

    // Profile update verification of OTP
    public function profileUpdateVerifyOtp($mobileNumber, $otp)
    {
        Log::info("in profileUpdateVerifyOtp function with mobile number = " . $mobileNumber . " & otp = " . $otp);
        try {
            Log::info('profileUpdateVerifyOtp function Attempting OTP verification for profile update', ['mobile' => $mobileNumber, 'otp' => $otp]);
            return $this->VerifyOtp($mobileNumber, $otp);
        } catch (\Exception $e) {
            Log::error('Error during profile update OTP verification', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'An error occurred during profile update verification.'];
        }
    }

    // OTP verification logic
    public function VerifyOtp($mobileNumber, $otp)
    {

        Log::info("in VerifyOtp function with mobile number = " . $mobileNumber . " & otp = " . $otp);

        try {
            Log::info('Starting OTP verification', ['mobile' => $mobileNumber]);

            $latestOtpEntry = Otp::where('mobile_number', $mobileNumber)->latest()->first();
            Log::info('Latest OTP entry found', ['otp_entry' => $latestOtpEntry]);

            if (!$latestOtpEntry) {
                Log::error('No OTP entry found for mobile number', ['mobile' => $mobileNumber]);
                return ['status' => 'error', 'message' => 'Invalid OTP'];
            }

            $expirationTime = Carbon::parse($latestOtpEntry->created_at)->addMinutes(5);
            Log::info('OTP expiration time', ['expiration_time' => $expirationTime]);

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
