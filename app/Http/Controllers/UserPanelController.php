<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App\Models\User;
use App\Models\QsProduct;
use App\Models\QsCategory;

use Session;
use Illuminate\Support\Facades\Http;
use App\Models\QsOrder;
use App\Models\GiftCard;
use Illuminate\Support\Facades\Validator;
use Auth;
use App\Helpers\CommonHelper;
use Illuminate\Support\Facades\Redirect;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\OtpVerificationController;
// use App\Http\Controllers\APIs\AuthenticationController;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Http\Client\ConnectionException;

class UserPanelController extends Controller
{
    protected $commonController;
    protected $smsController;
    // protected $authenticationController;
    protected $otpVerificationController;


    public function __construct()
    {
        $this->commonController = new CommonController();
        $this->smsController = new SmsController();
        // $this->authenticationController = new AuthenticationController();
        $this->otpVerificationController = new OtpVerificationController();

        // Add middleware to check for blocked users on login only
        $this->middleware(function ($request, $next) {
            if (Auth::check()) {
                $user = Auth::user();
                
                // If user is login-blocked, force logout
                if ($user->is_blocked) {
                    Auth::logout();
                    Session::flush();
                    
                    if ($request->ajax()) {
                        return response()->json([
                            'status' => 403,
                            'msg' => 'Your account access has been restricted. Please contact:',
                            'contact_info' => [
                                'email' => config('companyDefaultValues.company_email'),
                                'phone' => '+91 ' . config('companyDefaultValues.company_contact_no')
                            ],
                            'redirect' => route('home')
                        ]);
                    }
                    
                    return redirect()->route('home')
                        ->with('error', 'Your account access has been restricted. Please contact support.')
                        ->with('contact_info', [
                            'email' => config('companyDefaultValues.company_email'),
                            'phone' => '+91 ' . config('companyDefaultValues.company_contact_no')
                        ]);
                }
            }
            return $next($request);
        })->only(['userLogin', 'homePage']);
    }

    /**
     * Check if the current route is a transaction-related route
     */
    private function isTransactionRoute($routeName, $transactionRoutes)
    {
        foreach ($transactionRoutes as $route) {
            if (str_contains($route, '*')) {
                $pattern = str_replace('*', '.*', $route);
                if (preg_match('/' . $pattern . '/', $routeName)) {
                    return true;
                }
            } elseif ($route === $routeName) {
                return true;
            }
        }
        return false;
    }

    public function homePage()
    {
        Log::info('homePage method called');
        try {
            // $getCategory = QsCategory::first();
            // Log::info('Fetched category', ['getCategory' => $getCategory]);

            // Fetch all products with their associated category using Eloquent
            // $allProducts = QsProduct::with('category')
            // ->orderByRaw('IFNULL(priority, 999999) ASC') // Sort by priority in ascending order if null treat as 99999
            // ->get();

            $allProducts = QsProduct::where('show_product', true)
                ->orderByRaw('IFNULL(priority, 999999) ASC') // Sort by priority in ascending order, treating nulls as 99999
                ->get();


            // Log::info('Fetched all products', ['allProducts' => $allProducts]);


            $allProducts->map(function ($item) {
                $item->currency = json_decode($item->currency);
                $item->price = json_decode($item->price);
                $item->images = json_decode($item->images);
                // Log::info('Processed product', ['item' => $item]);


            });



            return view('userpanel/index', compact('allProducts'));
        } catch (Exception $e) {
            Log::error('Error in homePage method', ['error' => $e->getMessage()]);
            return $e->getMessage();
        }
    }

    public function userRegistration(Request $request)
    {
        Log::info('userRegistration method called', ['request' => $request->all()]);

        try {
            // Validate request data
            $validator = Validator::make($request->all(), [
                'name' => ['required', 'regex:/^[a-zA-Z\s]+$/'],
                'mobile' => ['required', 'regex:/^(?:(?:\+|0{0,2})91)?[789]\d{9}$/', 'unique:users,mobile'],
                'email' => 'required|email|unique:users,email',
                'password' => 'required|confirmed|min:8',
            ], [
                'name.regex' => 'Name should only contain letters and spaces',
                'mobile.regex' => 'Invalid mobile number',
                'mobile.unique' => 'Mobile number already exists',
                'email.email' => 'Invalid email address',
                'email.unique' => 'Email already exists',
                'password.confirmed' => 'Password confirmation does not match',
                'password.min' => 'Password must be at least 8 characters long',
            ]);

            if ($validator->fails()) {
                $data = [
                    'status' => 400,
                    'errors' => $validator->errors()->toArray(),
                ];
                Log::warning('Validation errors in userRegistration', ['errors' => $validator->errors()->toArray()]);
                return response()->json($data);
            }

            // Verify OTP
            $otpVerificationResponse = $this->otpVerificationController->VerifyOtp($request->mobile, $request->otp);

            if ($otpVerificationResponse['status'] === 'error') {
                return response()->json(['status' => 400, 'errors' => ['registerOTP' => [$otpVerificationResponse['message']]]]);
            }

            // If OTP is verified, proceed to create the user
            User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'role_id' => 2,
                'mobile' => $request->mobile,
            ]);
            Log::info('User created', ['user' => $request->only(['name', 'email', 'mobile'])]);

            // Attempt login
            if (Auth::attempt($request->only('email', 'password'))) {
                $data = [
                    'status' => 200,
                    'msg' => 'Login successful. Welcome back!',
                ];
                Log::info('User login successful', ['user' => $request->email]);
            } else {
                $data = [
                    'status' => 400,
                    'msg' => 'Login failed. Please check your email and password and try again.',
                ];
                Log::warning('User login failed', ['user' => $request->email]);
            }

            return response()->json($data);
        } catch (Exception $e) {
            Log::error('Error in userRegistration method', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 500,
                'msg' => 'Internal Server Error',
            ], 500);
        }
    }




    public function userForgotPassword(Request $request)
    {
        Log::info('userForgotPassword method called prince', ['request' => $request->all()]);

        try {
            $input = $request->all();
            Log::info('Input data received', ['input' => $input]);

            // Check if all fields are empty
            if (empty($input['mobile']) && empty($input['otp']) && empty($input['newPassword']) && empty($input['confirm_new_password'])) {
                Log::warning('All fields are empty');
                return response()->json([
                    'status' => 400,
                    'message' => 'All fields are required.'
                ], 400);
            }

            // Validate request data
            $validator = Validator::make($input, [
                'mobile' => ['required', 'regex:/^(?:(?:\+|0{0,2})91)?[789]\d{9}$/'],
                'otp' => ['required'],
                'newPassword' => ['required', 'min:6'],
                'confirm_new_password' => ['required', 'same:newPassword'],
            ], [
                'mobile.required' => 'backend Mobile number is required.',
                'mobile.regex' => 'Invalid mobile number format.',
                'otp.required' => 'OTP is required.',
                'newPassword.required' => 'New password is required.',
                'newPassword.min' => 'Password must be at least 6 characters.',
                'confirm_new_password.required' => 'Confirm password is required.',
                'confirm_new_password.same' => 'Confirm password must match new password.',
            ]);

            // If validation fails, return errors
            if ($validator->fails()) {
                Log::error('Validation failed', ['errors' => $validator->errors()]);
                return response()->json([
                    'status' => 400,
                    'errors' => $validator->errors()
                ], 400);
            }

            Log::info('Validation passed', ['mobile' => $request->mobile, 'otp' => $request->otp]);

            // Verify OTP
            $otpVerificationResponse = $this->otpVerificationController->VerifyOtp($request->mobile, $request->otp);
            Log::info('OTP verification response', ['response' => $otpVerificationResponse]);

            if ($otpVerificationResponse['status'] === 'error') {
                Log::warning('OTP verification failed', ['mobile' => $request->mobile, 'message' => $otpVerificationResponse['message']]);
                return response()->json(['status' => 400, 'errors' => ['registerOTP' => [$otpVerificationResponse['message']]]]);
            }

            // Log successful OTP verification
            Log::info('OTP verification successful', ['mobile' => $request->mobile]);

            // Find the user by mobile number
            $user = User::where('mobile', $request->mobile)->first();

            if (!$user) {
                Log::error('User not found', ['mobile' => $request->mobile]);
                return response()->json([
                    'status' => 404,
                    'message' => 'User not found.'
                ], 404);
            }

            // Update the user's password using bcrypt()
            $user->password = bcrypt($request->newPassword);
            $user->save();

            Log::info('Password updated successfully', ['mobile' => $request->mobile]);

            return response()->json([
                'status' => 200,
                'message' => 'Password changed successfully! You can log in with your new password.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error in userForgotPassword: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong, please try again.'
            ], 500);
        }
    }







    public function userLogin(Request $request)
    {
        Log::info('userLogin attempt', ['mobile' => $request->mobile]);
        
        try {
            // Find user first to check if they exist
            $user = User::where('mobile', $request->mobile)->first();
            
            if (!$user) {
                Log::warning('Login failed - user not found', ['mobile' => $request->mobile]);
                return response()->json([
                    'status' => 400,
                    'msg' => 'Invalid credentials. Please check your mobile number and password.'
                ]);
            }

            // Attempt authentication with mobile instead of email
            if (Auth::attempt(['mobile' => $request->mobile, 'password' => $request->password])) {
                $user = Auth::user();
                
                // Check if user is blocked from logging in
                if ($user->is_blocked) {
                    Auth::logout();
                    Log::warning('Blocked user attempted to login', [
                        'user_id' => $user->id,
                        'mobile' => $user->mobile
                    ]);
                    return response()->json([
                        'status' => 403,
                        'msg' => 'Your account has been blocked. For assistance, please contact:',
                        'contact_info' => [
                            'email' => config('companyDefaultValues.company_email'),
                            'phone' => '+91 ' . config('companyDefaultValues.company_contact_no')
                        ]
                    ]);
                }

                Log::info('Login successful', [
                    'user_id' => $user->id,
                    'mobile' => $user->mobile
                ]);

                return response()->json([
                    'status' => 200,
                    'msg' => 'Login successful. Welcome back!',
                    'redirect' => route('home')
                ]);
            }

            Log::warning('Login failed - wrong password', ['mobile' => $request->mobile]);
            return response()->json([
                'status' => 400,
                'msg' => 'Invalid credentials. Please check your mobile number and password.'
            ]);

        } catch (Exception $e) {
            Log::error('Error in userLogin method', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 500,
                'msg' => 'Internal Server Error'
            ], 500);
        }
    }

    public function userLogOut()
    {
        Log::info('userLogOut method called');
        Session::flush();
        Auth::logout();
        Log::info('User logged out');
        return redirect('/');
    }

    public function viewAllProduct()
    {
        Log::info('viewAllProduct method called');
        // Fetch all products that are visible (show_product = true)
        $viewProds = QsProduct::where('show_product', true)->get();
        Log::info('Fetched all visible products', ['viewProds' => $viewProds]);
        return $viewProds;
    }


    public function checkOut(Request $request, $sku)
    {
        Log::info('checkOut method called', ['request' => $request->all(), 'sku' => $sku]);
        try {
            // Check if user is authenticated
            if (!Auth::check()) {
                Log::warning('Unauthenticated user attempted checkout');
                return response()->json([
                    'status' => 401,
                    'message' => 'Please login to continue.'
                ]);
            }

            $user = Auth::user();
            
            // Check if user is blocked from logging in
            if ($user->is_blocked) {
                Log::warning('Login-blocked user attempted checkout', [
                    'user_id' => $user->id,
                    'mobile' => $user->mobile
                ]);
                Auth::logout();
                Session::flush();
                return response()->json([
                    'status' => 403,
                    'message' => 'Your account access has been restricted. Please contact support.'
                ]);
            }

            // Check if user is blocked from transactions
            if (!$user->can_transact) {
                Log::warning('Transaction-blocked user attempted checkout', [
                    'user_id' => $user->id,
                    'mobile' => $user->mobile
                ]);
                return response()->json([
                    'status' => 403,
                    'message' => 'Your account is currently restricted from making transactions. Please contact support.'
                ]);
            }

            // Check for restricted features
            if ($user->restricted_features) {
                $restrictedFeatures = json_decode($user->restricted_features, true);
                if (in_array('checkout', $restrictedFeatures)) {
                    Log::warning('Feature-restricted user attempted checkout', [
                        'user_id' => $user->id,
                        'mobile' => $user->mobile,
                        'restricted_features' => $restrictedFeatures
                    ]);
                    return response()->json([
                        'status' => 403,
                        'message' => 'You are not allowed to make purchases at this time. Please contact support.'
                    ]);
                }
            }

            // If all checks pass, proceed with checkout
            session()->put('denomination', $request->denomination);
            session()->put('quantity', $request->quantity);
            session()->put('gift_send_option', $request->gift_send_option);
            session()->put('receiver_name', $request->receiver_name);
            session()->put('receiver_email', $request->receiver_email);
            session()->put('receiver_mobile', $request->receiver_mobile);
            session()->put('receiver_msg', $request->receiver_msg);
            session()->put('delivery_mode', $request->delivery_mode);
            Session::put('user_id', Auth::id());
            
            $qsProd = QsProduct::where('sku', $sku)->first();
            if (!$qsProd) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Product not found.'
                ]);
            }

            $qsProd->currency = json_decode($qsProd->currency);
            $qsProd->images = json_decode($qsProd->images);
            $qsProd->prodData = $request->all();

            return view('userpanel.checkout', compact('qsProd'));

        } catch (Exception $e) {
            Log::error('Error in checkOut method', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred. Please try again.'
            ]);
        }
    }

    public function saveGiftCardFormValues(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'gift_send_option' => 'required|string',
                'receiver_name' => 'nullable|string|max:255',
                'receiver_email' => 'nullable|email|max:255',
                'receiver_mobile' => 'nullable|string|max:20',
                'receiver_msg' => 'nullable|string',
                'delivery_mode' => 'required|string|in:both,email,sms'
            ]);

            // Store the validated form data in the session
            session([
                'gift_card_form' => $validatedData
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Gift card form data saved successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error saving gift card form: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save gift card form data'
            ], 500);
        }
    }
}
