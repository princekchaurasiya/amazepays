<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\OtpVerificationController;
use App\Mail\SendVerificationCode;
use App\Models\EmailVerificationCode;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
                'mobile' => ['required', 'regex:/^(?:(?:\+|0{0,2})91)?[789]\d{9}$/', 'unique:users,mobile'],
                'email' => 'required|email|unique:users,email',
                'password' => 'required|confirmed|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).+$/',
                'otp' => ['required', 'string'],
            ], [
                'name.regex' => 'Name should only contain letters and spaces',
                'mobile.regex' => 'Invalid mobile number',
                'mobile.unique' => 'Mobile number already exists',
                'email.email' => 'Invalid email address',
                'email.unique' => 'Email already exists',
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

            User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => bcrypt($validated['password']),
                'role_id' => 2,
                'mobile' => $validated['mobile'],
            ]);

            $code = rand(100000, 999999);

            EmailVerificationCode::create([
                'email' => $validated['email'],
                'code' => $code,
                'expires_at' => Carbon::now()->addMinutes(10),
            ]);

            Mail::to($validated['email'])->send(new SendVerificationCode($code));

            if (Auth::attempt(['email' => $validated['email'], 'password' => $validated['password']])) {
                $data = [
                    'status' => 200,
                    'msg' => 'Login successful. Welcome back!',
                ];
            } else {
                $data = [
                    'status' => 400,
                    'msg' => 'Login failed. Please check your email and password and try again.',
                ];
            }

            return response()->json($data);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'msg' => 'Internal Server Error',
            ], 500);
        }
    }
}
