<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\OtpVerificationController;
use App\Mail\SendVerificationCode;
use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Models\UserAuthIdentity;
use App\Models\UserAuthSecret;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    public function __construct(
        protected OtpVerificationController $otpVerificationController
    ) {}

    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->only(['name', 'mobile', 'email', 'password', 'password_confirmation', 'otp']), [
                'name' => ['required', 'regex:/^[a-zA-Z\s]+$/'],
                // Phase-3: mobile/email are stored in user_auth_identities (not users table)
                'mobile' => ['required', 'regex:/^(?:(?:\+|0{0,2})91)?[789]\d{9}$/'],
                'email' => 'required|email|max:255',
                'password' => 'required|confirmed|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).+$/',
                'otp' => ['required', 'string'],
            ], [
                'name.regex' => 'Name should only contain letters and spaces',
                'mobile.regex' => 'Invalid mobile number',
                'email.email' => 'Invalid email address',
                'password.confirmed' => 'Password confirmation does not match',
                'password.min' => 'Password must be at least 8 characters long,at least 1 lowercase,1 uppercase,1 number and 1 special character',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 400,
                    'errors' => $validator->errors()->toArray(),
                ]);
            }

            $validated = $validator->validated();

            $otpVerificationResponse = $this->otpVerificationController->VerifyOtp($validated['mobile'], $validated['otp']);

            if ($otpVerificationResponse['status'] === 'error') {
                return response()->json(['status' => 400, 'errors' => ['registerOTP' => [$otpVerificationResponse['message']]]]);
            }

            $user = User::create([
                'tenant_id' => null,
                'display_name' => (string) $validated['name'],
                'account_type' => 'customer',
                'status' => 'active',
                'is_super_admin' => false,
            ]);

            $emailIdentity = UserAuthIdentity::firstOrCreate(
                ['provider' => 'email', 'identifier' => (string) $validated['email']],
                [
                    'user_id' => $user->id,
                    'display_identifier' => (string) $validated['email'],
                    'is_primary' => true,
                    'is_verified' => true,
                    'verified_at' => now(),
                ]
            );

            UserAuthIdentity::firstOrCreate(
                ['provider' => 'mobile', 'identifier' => (string) $validated['mobile']],
                [
                    'user_id' => $user->id,
                    'display_identifier' => (string) $validated['mobile'],
                    'is_primary' => false,
                    'is_verified' => true,
                    'verified_at' => now(),
                ]
            );

            UserAuthSecret::firstOrCreate(
                ['identity_id' => $emailIdentity->id],
                [
                    'user_id' => $user->id,
                    'password_hash' => Hash::make((string) $validated['password']),
                    'failed_attempts' => 0,
                ]
            );

            $code = rand(100000, 999999);

            EmailVerificationCode::create([
                'email' => $validated['email'],
                'code' => $code,
                'expires_at' => Carbon::now()->addMinutes(10),
            ]);

            Mail::to($validated['email'])->send(new SendVerificationCode($code));

            Auth::login($user);
            $data = [
                'status' => 200,
                'msg' => 'Registration successful.',
            ];

            return response()->json($data);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'msg' => 'Internal Server Error',
            ], 500);
        }
    }
}
