<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmailVerificationCode;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    //functions for email verification
    public function showVerifyForm()
    {
        return view('auth.verify-email');
    }

    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6'
        ], [
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'code.required' => 'Verification code is required.',
            'code.size' => 'Verification code must be exactly 6 digits.'
        ]);

        try {
            // Find the verification code
            $record = EmailVerificationCode::where('email', $request->email)
                ->where('code', $request->code)
                ->where('expires_at', '>', now())
                ->first();

            if (!$record) {
                // Check if code exists but is expired
                $expiredRecord = EmailVerificationCode::where('email', $request->email)
                    ->where('code', $request->code)
                    ->first();

                if ($expiredRecord) {
                    $expiredRecord->delete(); // Clean up expired code
                    return back()->withErrors(['code' => 'Verification code has expired. Please request a new one.'])->withInput();
                }

                return back()->withErrors(['code' => 'Invalid verification code. Please check and try again.'])->withInput();
            }

            // Find the user
            $user = User::where('email', $request->email)->first();
            
            if (!$user) {
                return back()->withErrors(['email' => 'No user found with this email address.'])->withInput();
            }

            // Check if user is already verified
            if ($user->email_verified_at) {
                return back()->with('info', 'Your email is already verified. You can now log in.');
            }

            // Mark user as verified
            $user->email_verified_at = now();
            $user->save();

            // Delete used code
            $record->delete();

            Log::info('Email verified successfully', ['user_id' => $user->id, 'email' => $user->email]);

            return redirect()->route('login')->with('success', 'Email verified successfully! You can now log in to your account.');

        } catch (\Exception $e) {
            Log::error('Error during email verification', [
                'email' => $request->email,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['code' => 'An error occurred during verification. Please try again.'])->withInput();
        }
    }

    public function resendVerification(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        try {
            // Check if user exists
            $user = User::where('email', $request->email)->first();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No user found with this email address.'
                ], 404);
            }

            // Check if user is already verified
            if ($user->email_verified_at) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This email is already verified.'
                ], 400);
            }

            // Delete any existing verification codes for this email
            EmailVerificationCode::where('email', $request->email)->delete();

            // Generate new verification code
            $code = rand(100000, 999999);

            // Create new verification code
            EmailVerificationCode::create([
                'email' => $request->email,
                'code' => $code,
                'expires_at' => \Carbon\Carbon::now()->addMinutes(10),
            ]);

            // Send new verification email
            \Mail::to($request->email)->send(new \App\Mail\SendVerificationCode($code));

            Log::info('Verification code resent', ['email' => $request->email]);

            return response()->json([
                'status' => 'success',
                'message' => 'A new verification code has been sent to your email.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error resending verification code', [
                'email' => $request->email,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send verification code. Please try again.'
            ], 500);
        }
    }
}
