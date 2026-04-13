<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    protected $otpVerificationController;

    public function __construct()
    {
        $this->otpVerificationController = new OtpVerificationController;
    }

    public function updateProfile(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z\s]+$/'],
                'email' => 'required|email|unique:users,email,'.Auth::id(),
                'mobile' => ['required', 'regex:/^[0-9]{10}$/'],
                'otp' => 'nullable|string|size:6',
            ]);

            $user = Auth::user();

            if ($user->mobile !== $validated['mobile']) {
                $otpVerificationResponse = $this->otpVerificationController->profileUpdateVerifyOtp($validated['mobile'], $validated['otp'] ?? '');

                if ($otpVerificationResponse['status'] === 'error') {
                    return redirect()->back()->withErrors(['otp' => $otpVerificationResponse['message']]);
                }
            }

            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->mobile = $validated['mobile'];

            if ($user->save()) {
                return redirect()->back()->with('success', 'Profile updated successfully!');
            }

            return redirect()->back()->withErrors(['general' => 'Failed to update the profile. Please try again later.']);
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['general' => 'An unexpected error occurred. Please try again.']);
        }
    }

    public function isMobileNumberInUse(Request $request)
    {
        $isInUse = User::where('mobile', $request->destination)
            ->where('id', '!=', auth()->id())
            ->exists();

        if ($isInUse) {
            return response()->json([
                'status' => 'unavailable',
                'message' => 'This mobile number is already in use.',
            ]);
        }

        return response()->json([
            'status' => 'available',
            'message' => 'This mobile number is available.',
        ]);
    }
}
