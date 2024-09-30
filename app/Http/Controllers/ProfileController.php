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

    public function updateProfile(Request $request)
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
                'otp' => 'nullable|string|size:6'
            ]);

            Log::info('Validation passed for user: ' . Auth::id(), ['validated_data' => $validatedData]);

            $user = Auth::user();

            // Check if the mobile number has changed
            if ($user->mobile !== $validatedData['mobile']) {
                // Log before OTP verification
                Log::info('Mobile number has changed, verifying OTP for user: ' . Auth::id(), [
                    'mobile' => $validatedData['mobile'],
                    'otp' => $validatedData['otp']
                ]);

                // Verify the OTP for the new phone number
                $otpVerificationResponse = $this->otpVerificationController->profileUpdateVerifyOtp($validatedData['mobile'], $validatedData['otp']);

                Log::info('OTP verification response for user: ' . Auth::id(), ['otp_verification_response' => $otpVerificationResponse]);

                if ($otpVerificationResponse['status'] === 'error') {
                    Log::warning('OTP verification failed for user: ' . Auth::id(), ['otp_verification_response' => $otpVerificationResponse]);

                    // Store the error message in session
                    return redirect()->back()->withErrors(['otp' => $otpVerificationResponse['message']]);
                }
            } else {
                Log::info('Mobile number has not changed, skipping OTP verification for user: ' . Auth::id());
            }

            // Log before updating the profile
            Log::info('Updating profile for user: ' . Auth::id(), ['user_data' => $validatedData]);

            // Update the user profile
            $user->name = $validatedData['name'];
            $user->email = $validatedData['email'];
            $user->mobile = $validatedData['mobile'];

            if ($user->save()) {
                Log::info('Profile updated successfully for user: ' . Auth::id());

                // Store success message in session
                return redirect()->back()->with('success', 'Profile updated successfully!');
            } else {
                Log::error('Failed to save profile for user: ' . Auth::id());

                // Store error message in session
                return redirect()->back()->withErrors(['general' => 'Failed to update the profile. Please try again later.']);
            }
        } catch (ValidationException $e) {
            Log::error('Validation errors for user: ' . Auth::id(), ['errors' => $e->errors()]);

            // Store validation errors in session
            return redirect()->back()->withErrors($e->errors());
        } catch (\Exception $e) {
            Log::error('Unexpected error during profile update for user: ' . Auth::id(), ['exception' => $e->getMessage()]);

            // Store unexpected error message in session
            return redirect()->back()->withErrors(['general' => 'An unexpected error occurred. Please try again.']);
        }
    }



    // The method to check if the mobile number is already in use
    public function isMobileNumberInUse(Request $request)
    {
        Log::info('Checking if mobile number is in use: ' . $request->destination);

        // Find if the mobile number exists for another user
        $isInUse = User::where('mobile', $request->destination)
            ->where('id', '!=', auth()->id()) // Ensure it's not the same user
            ->exists(); // Use exists to check if a record exists

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
