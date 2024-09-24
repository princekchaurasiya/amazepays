<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Http\Controllers\OtpVerificationController;

class ProfileController extends Controller
{
    protected $otpVerificationController;

    public function __construct()
    {
        $this->otpVerificationController = new OtpVerificationController();
    }

    public function update(Request $request)
    {
        // Log when the profile update request is received
        Log::info('Profile Update Request received for user: ' . Auth::id(), ['request_data' => $request->all()]);

        try {
            // Validate form fields with detailed logging
            Log::info('Starting validation for user: ' . Auth::id());

            $validatedData = $request->validate([
                'name' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z\s]+$/'],
                'email' => 'required|email|unique:users,email,' . Auth::id(),
                'mobile' => ['required', 'regex:/^[0-9]{10}$/'],
                'otp' => 'required|string|size:6',
            ]);

            Log::info('Validation passed for user: ' . Auth::id(), ['validated_data' => $validatedData]);

            $user = Auth::user();

            // Log before OTP verification
            Log::info('Verifying OTP for user: ' . Auth::id(), [
                'mobile' => $validatedData['mobile'],
                'otp' => $validatedData['otp']
            ]);

            // Verify the OTP for the new phone number
            $otpVerificationResponse = $this->otpVerificationController->profileUpdateVerifyOtp($validatedData['mobile'], $validatedData['otp']);

            Log::info('OTP verification response for user: ' . Auth::id(), ['otp_verification_response' => $otpVerificationResponse]);

            if ($otpVerificationResponse['status'] === 'error') {
                Log::warning('OTP verification failed for user: ' . Auth::id(), ['otp_verification_response' => $otpVerificationResponse]);

                return response()->json([
                    'status' => 'error',
                    'status_code' => 400,
                    'msg' => $otpVerificationResponse['message']
                ]);
            }

            // Log before updating the profile
            Log::info('Updating profile for user: ' . Auth::id(), ['user_data' => $validatedData]);

            // Update the user profile
            $user->name = $validatedData['name'];
            $user->email = $validatedData['email'];
            $user->mobile = $validatedData['mobile'];

            if ($user->save()) {
                Log::info('Profile updated successfully for user: ' . Auth::id());

                return response()->json([
                    'status' => 'success',
                    'status_code' => 200,
                    'msg' => 'Profile updated successfully!',
                ]);
            } else {
                Log::error('Failed to save profile for user: ' . Auth::id());

                return response()->json([
                    'status' => 'error',
                    'status_code' => 500,
                    'msg' => 'Failed to update the profile. Please try again later.'
                ]);
            }
        } catch (ValidationException $e) {
            Log::error('Validation errors for user: ' . Auth::id(), ['errors' => $e->errors()]);

            return response()->json([
                'status' => 'error',
                'status_code' => 422,
                'msg' => 'Validation errors occurred.',
                'errors' => $e->errors()
            ]);
        } catch (\Exception $e) {
            Log::error('Unexpected error during profile update for user: ' . Auth::id(), ['exception' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'status_code' => 500,
                'msg' => 'An unexpected error occurred. Please try again.'
            ]);
        }
    }

    // The method to check if the mobile number is already in use
    public function isMobileNumberInUse(Request $request)
    {
        Log::info('Checking if mobile number is in use: ' . $request->destination);

        // Find user with this mobile number
        $isInUse = User::where('mobile', $request->destination)->exists();

        if ($isInUse) {
            Log::info('Mobile number is already in use: ' . $request->destination);

            return response()->json([
                'status' => 'unavailable',
                'message' => 'This mobile number is already in use.',
            ]);
        }

        Log::info('Mobile number is available: ' . $request->destination);

        return response()->json([
            'status' => 'available',
            'message' => 'This mobile number is available.',
        ]);
    }
}
