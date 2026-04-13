<?php

namespace App\Http\Controllers;

use App\Models\Otp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class OtpVerificationController extends Controller
{
    public function loginVerifyOtp(Request $request)
    {
        try {
            $otp = $request->input('otp');
            $mobileNumber = $this->normalizeIndianMobile($request->input('destination', ''));

            Log::info('Attempting OTP verification for login', ['mobile' => $mobileNumber]);
            $verificationResult = $this->VerifyOtp($mobileNumber, (string) $otp);

            Log::info('OTP verification result', $verificationResult);

            if ($verificationResult['status'] === 'success') {
                $user = User::where('mobile', $mobileNumber)->first();

                if ($user) {
                    if ($user->is_blocked) {
                        Log::warning('Blocked user attempted to verify OTP', ['user_id' => $user->id]);

                        return response()->json([
                            'status' => 'error',
                            'message' => 'Your account has been blocked. For assistance, please contact:',
                            'contact_info' => [
                                'email' => 'support@amazepays.in',
                                'phone' => '+91 9324449485',
                            ],
                        ]);
                    }

                    Auth::login($user);

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Login successful',
                        'redirect_url' => $user->homeUrl(),
                    ]);
                }

                Log::error('User not found for login', ['mobile' => $mobileNumber]);

                return response()->json(['status' => 'error', 'message' => 'User not found.']);
            }

            return response()->json($verificationResult);
        } catch (\Exception $e) {
            Log::error('Error during login OTP verification', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'error', 'message' => 'An error occurred during verification.']);
        }
    }

    public function registerVerifyOtp(Request $request)
    {
        try {
            $otp = $request->input('otp');
            $mobileNumber = $this->normalizeIndianMobile($request->input('destination', ''));

            Log::info('Attempting OTP verification for registration', ['mobile' => $mobileNumber]);
            $verificationResult = $this->VerifyOtp($mobileNumber, (string) $otp);

            Log::info('OTP verification result', $verificationResult);

            if ($verificationResult['status'] === 'success') {
                $user = User::create([
                    'mobile' => $mobileNumber,
                ]);

                Auth::login($user);

                return redirect()->intended(url()->previous());
            }

            return response()->json($verificationResult);
        } catch (\Exception $e) {
            Log::error('Error during registration OTP verification', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'error', 'message' => 'An error occurred during registration.']);
        }
    }

    public function profileUpdateVerifyOtp($mobileNumber, $otp)
    {
        Log::info('profileUpdateVerifyOtp', ['mobile' => $mobileNumber]);
        try {
            $normalized = $this->normalizeIndianMobile((string) $mobileNumber);

            return $this->VerifyOtp($normalized, (string) $otp);
        } catch (\Exception $e) {
            Log::error('Error during profile update OTP verification', ['error' => $e->getMessage()]);

            return ['status' => 'error', 'message' => 'An error occurred during profile update verification.'];
        }
    }

    public function VerifyOtp(string $mobileNumber, string $otp)
    {
        Log::info('VerifyOtp starting', ['mobile' => $mobileNumber]);

        try {
            if (app()->environment('local') && config('app.debug') && $otp === '123456') {
                Log::info('[TEST MODE] OTP bypass with fixed 123456', ['mobile' => $mobileNumber]);
                Otp::where('mobile_number', $mobileNumber)->where('is_used', false)->update([
                    'is_used' => true,
                    'verified_at' => now(),
                ]);

                return ['status' => 'success', 'message' => 'OTP verified (test mode)'];
            }

            $latestOtpEntry = Otp::where('mobile_number', $mobileNumber)
                ->where('is_used', false)
                ->where('expires_at', '>', now())
                ->latest('id')
                ->first();

            if (! $latestOtpEntry) {
                Log::error('No valid OTP entry found for mobile number', ['mobile' => $mobileNumber]);

                return ['status' => 'error', 'message' => 'Invalid OTP'];
            }

            $maxAttempts = config('sms.otp.max_attempts', 5);
            if ($latestOtpEntry->attempts >= $maxAttempts) {
                Log::warning('OTP max attempts exceeded', ['mobile' => $mobileNumber]);
                $latestOtpEntry->update(['is_used' => true]);

                return ['status' => 'error', 'message' => 'Invalid OTP'];
            }

            if (! Hash::check($otp, $latestOtpEntry->otp)) {
                $latestOtpEntry->increment('attempts');
                Log::error('Invalid OTP entered', ['mobile' => $mobileNumber]);

                return ['status' => 'error', 'message' => 'Invalid OTP'];
            }

            $latestOtpEntry->update([
                'is_used' => true,
                'verified_at' => now(),
            ]);

            Log::info('OTP successfully verified', ['mobile' => $mobileNumber]);

            return ['status' => 'success', 'message' => 'OTP verified'];
        } catch (\Exception $e) {
            Log::error('Error during OTP verification process', ['error' => $e->getMessage()]);

            return ['status' => 'error', 'message' => 'An error occurred during OTP verification.'];
        }
    }

    private function normalizeIndianMobile(string $raw): string
    {
        $normalized = preg_replace('/\D+/', '', $raw);
        if (strlen($normalized) > 10) {
            if (str_starts_with($normalized, '91') && strlen($normalized) >= 12) {
                $normalized = substr($normalized, -10);
            } elseif (str_starts_with($normalized, '0') && strlen($normalized) >= 11) {
                $normalized = substr($normalized, -10);
            } else {
                $normalized = substr($normalized, -10);
            }
        }

        return $normalized;
    }
}
