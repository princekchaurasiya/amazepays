<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\OtpVerificationController;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PasswordResetController extends Controller
{
    public function __construct(
        protected OtpVerificationController $otpVerificationController
    ) {}

    public function reset(Request $request)
    {
        try {
            $input = $request->only(['mobile', 'otp', 'newPassword', 'confirm_new_password']);

            if (empty($input['mobile']) && empty($input['otp']) && empty($input['newPassword']) && empty($input['confirm_new_password'])) {
                return response()->json([
                    'status' => 400,
                    'message' => 'All fields are required.',
                ], 400);
            }

            $validator = Validator::make($input, [
                'mobile' => ['required', 'regex:/^(?:(?:\+|0{0,2})91)?[789]\d{9}$/'],
                'otp' => ['required'],
                'newPassword' => ['required', 'min:8', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).+$/'],
                'confirm_new_password' => ['required', 'same:newPassword'],
            ], [
                'mobile.required' => 'backend Mobile number is required.',
                'mobile.regex' => 'Invalid mobile number format.',
                'otp.required' => 'OTP is required.',
                'newPassword.required' => 'New password is required.',
                'newPassword.min' => 'Password must be at least 8 characters,at least 1 lowercase, 1 uppercase,1 number and 1 special character.',
                'confirm_new_password.required' => 'Confirm password is required.',
                'confirm_new_password.same' => 'Confirm password must match new password.',
            ]);

            if ($validator->fails()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'status' => 422,
                        'message' => 'Validation failed.',
                        'errors' => $validator->errors(),
                    ], 422);
                }

                return redirect()->back()->withErrors($validator)->withInput();
            }

            $validated = $validator->validated();

            $otpVerificationResponse = $this->otpVerificationController->VerifyOtp($validated['mobile'], $validated['otp']);

            if ($otpVerificationResponse['status'] === 'error') {
                return response()->json(['status' => 400, 'errors' => ['registerOTP' => [$otpVerificationResponse['message']]]]);
            }

            $user = User::where('mobile', $validated['mobile'])->first();

            if (! $user) {
                return response()->json([
                    'status' => 404,
                    'message' => 'User not found.',
                ], 404);
            }

            $user->password = bcrypt($validated['newPassword']);
            $user->save();

            return response()->json([
                'status' => 200,
                'message' => 'Password changed successfully! You can log in with your new password.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }
}
