<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App\Models\User;
use App\Models\QsProduct;
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
use App\Http\Controllers\APIs\AuthenticationController;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Http\Client\ConnectionException;

class UserPanelController extends Controller
{
    protected $commonController;
    protected $smsController;
    protected $authenticationController;
    protected $otpVerificationController;


    public function __construct()
    {
        $this->commonController = new CommonController();
        $this->smsController = new SmsController();
        $this->authenticationController = new AuthenticationController();
        $this->otpVerificationController = new OtpVerificationController();
    }

    public function homePage()
    {
        Log::info('homePage method called');
        try {
            $getCategory = DB::table('qs_categories')->first();
            // Log::info('Fetched category', ['getCategory' => $getCategory]);

            $allProducts = DB::table('qs_products')
                ->select('qs_products.*', 'qs_categories.name as category_name')
                ->leftJoin('qs_categories', 'qs_products.qs_category_id', '=', 'qs_categories.id')
                ->orderBy('qs_products.name')
                ->get();
            // Log::info('Fetched all products', ['allProducts' => $allProducts]);

            $allProducts->map(function ($item) {
                $item->currency = json_decode($item->currency);
                $item->price = json_decode($item->price);
                $item->images = json_decode($item->images);
                // Log::info('Processed product', ['item' => $item]);
            });

            return view('userpanel/index', compact('allProducts', 'getCategory'));
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
                'name' => ['required','regex:/^[a-zA-Z\s]+$/'],
                'mobile' => ['required','regex:/^(?:(?:\+|0{0,2})91)?[789]\d{9}$/','unique:users,mobile'],
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
                return response()->json(['status' => 400, 'errors' => ['registerOTP'=> [$otpVerificationResponse['message']]]]);
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

    public function userLogin(Request $request)
    {
        Log::info('userLogin method called', ['request' => $request->all()]);
        try {
            if (Auth::attempt($request->only('mobile', 'password'))) {
                Log::info('User login successful', ['user' => $request->mobile]);
                return response()->json([
                    'status' => 200,
                ]);
            } else {
                Log::warning('User login failed', ['user' => $request->mobile]);
                return response()->json([
                    'status' => 401,
                ]);
            }
        } catch (Exception $e) {
            Log::error('Error in userLogin method', ['error' => $e->getMessage()]);
            return $e->getMessage();
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
        $viewProds = QsProduct::all();
        Log::info('Fetched all products', ['viewProds' => $viewProds]);
        return $viewProds;
    }

    public function checkOut(Request $request, $sku)
    {
        Log::info('checkOut method called', ['request' => $request->all(), 'sku' => $sku]);
        try {
            session()->put('denomination', $request->denomination);
            session()->put('quantity', $request->quantity);
            session()->put('gift_send_option', $request->gift_send_option);
            session()->put('receiver_name', $request->receiver_name);
            session()->put('receiver_email', $request->receiver_email);
            session()->put('receiver_mobile', $request->receiver_mobile);
            session()->put('receiver_msg', $request->receiver_msg);
            session()->put('delivery_mode', $request->delivery_mode);
            Session::put('user_id', Auth::id());
            Log::info('Session variables set', ['session' => session()->all()]);

            $qsProd = QsProduct::where('sku', $sku)->first();
            Log::info('Fetched product by SKU', ['qsProd' => $qsProd]);

            $qsProd->currency = json_decode($qsProd->currency);
            $qsProd->images = json_decode($qsProd->images);
            $qsProd->prodData = $request->all();
            // Log::info('Processed product data', ['qsProd' => $qsProd]);

            if (Auth::check()) {
                Log::info('User authenticated, proceeding to checkout');
                return view('userpanel.checkout', compact('qsProd'));
            } else {
                Log::warning('User not authenticated, redirecting to home');
                return redirect('/');
            }
        } catch (Exception $e) {
            Log::error('Error in checkOut method', ['error' => $e->getMessage()]);
            return $e->getMessage();
        }
    }
}
