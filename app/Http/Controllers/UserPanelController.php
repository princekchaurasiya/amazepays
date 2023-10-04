<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App\Models\User;
use App\QsProduct;
use Session;
use Carbon;
use Illuminate\Support\Facades\Http;
use App\Models\QsOrder;
use App\Models\GiftCard;
use Auth;
use App\Helpers\CommonHelper;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Redirect;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\Log;
class UserPanelController extends Controller
{
    protected $commonController;

    public function __construct()
    {
        $this->commonController = new CommonController();
    }

    public function homePage()
    {
        try {
            $getCategory = DB::table('qs_categories')->first();

            $allProducts = DB::table('qs_products')
                ->select('qs_products.*', 'qs_categories.name as category_name')
                ->leftjoin('qs_categories', 'qs_products.qs_category_id', '=', 'qs_categories.id')
                ->get();

            $allProducts->map(function ($item, $key) {
                $item->currency = json_decode($item->currency);
                $item->price = json_decode($item->price);
                $item->images = json_decode($item->images);
            });

            return view('userpanel/index', compact('allProducts', 'getCategory'));
        } catch (Exception $e) {
            return $e->getMessage();
        }
        //return View::make("userpanel/index", compact('allProducts'));
    }

    public function userRegistration(Request $request)
    {
        try {
            $mobileExists = User::where('mobile', $request->mobile)->exists();
            $emailExists = User::where('email', $request->email)->exists();
            $errors = [];

            if ($mobileExists) {
                $errors['mobile'] = 'Mobile number already exists';
            }

            if ($emailExists) {
                $errors['email'] = 'Email already exists';
            }

            if (!empty($errors)) {
                $data = [
                    'status' => 400,
                    'errors' => $errors,
                ];
            } else {
                User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => bcrypt($request->password),
                    'role_id' => 2,
                    'mobile' => $request->mobile,
                ]);

                if (\Auth::attempt($request->only('email', 'password'))) {
                    $data = [
                        'status' => 200,
                        'msg' => 'User registered successfully',
                    ];
                } else {
                    $data = [
                        'status' => 400,
                        'msg' => 'Something went wrong',
                    ];
                }
            }

            return response()->json($data);
        } catch (Exception $e) {
            $data = [
                'status' => 500,
                'msg' => 'Internal Server Error',
            ];
            return response()->json($data, 500);
        }
    }

    public function userLogin(Request $request)
    {
        try {
            if (\Auth::attempt($request->only('mobile', 'password'))) {
                $data = [
                    'status' => 200,
                ];
            } else {
                // If authentication using password fails, try OTP validation
                return $data = [
                    'status' => 401,
                ];
            }

            return response()->json($data);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function userLogOut()
    {
        \Session::flush();
        \Auth::logout();
        return redirect('/');
    }
    public function viewAllProduct()
    {
        $viewProds = QsProduct::all();
        return $viewProds;
    }
    public function checkOut(Request $request, $sku)
    {
        try {
            session()->put('denomination', $request->denomination);
            session()->put('quantity', $request->quantity);
            session()->put('gift_send_option', $request->gift_send_option);
            session()->put('receiver_name', $request->receiver_name);
            session()->put('receiver_email', $request->receiver_email);
            session()->put('receiver_mobile', $request->receiver_mobile);
            session()->put('receiver_msg', $request->receiver_msg);
            session()->put('delivery_mode', $request->delivery_mode);
            $qsProd = QsProduct::where('sku', $sku)->first();
            $qsProd['prodData'] = $request->all();
            $currency = json_decode($qsProd['currency']);
            $qsProd['currency'] = $currency;
            $qsProd['images'] = json_decode($qsProd->images);

            // dd(json_decode($qsProd->images));
            if (\Auth::user()) {
                return view('userpanel/checkout', compact('qsProd'));
            } else {
                return redirect('/');
            }
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function orderProceed(Request $request)
    {
        $modfy_user_data = [
            'address' => [
                'firstname' => $request->billing_name,
                'lastname' => 'test',
                'email' => $request->billing_email,
                'telephone' => '+91' . $request->billing_tel,
                'line1' => $request->billing_address,
                'line2' => $request->billing_address_two,
                'city' => $request->billing_city,
                'region' => $request->billing_state,
                'country' => 'IN',
                'postcode' => $request->billing_zip,
                'languages' => 'Hindi',
                'billToThis' => true,
            ],
            'billing' => [
                'firstname' => $request->billing_name,
                'lastname' => 'test',
                'email' => $request->billing_email,
                'telephone' => '+91' . $request->billing_tel,
                'line1' => $request->billing_address,
                'line2' => $request->billing_address_two,
                'city' => $request->billing_city,
                'region' => $request->billing_state,
                'country' => 'IN',
                'postcode' => $request->billing_zip,
                'languages' => 'Hindi',
                'billToThis' => true,
            ],
            'payments' => [
                [
                    'code' => 'svc',
                    'amount' => $request->amount, //take from selected front end
                ],
            ],
            'refno' => 'Amaz' . mt_rand(1111, 9999),
            'products' => [
                [
                    'sku' => $request->sku,
                    'price' => session::get('denomination'),
                    'qty' => session::get('quantity'),
                    'currency' => $request['numericCode'],
                ],
            ],
            'syncOnly' => $request['quantity'] > 10 ? false : true, // If 'quantity' in $request is greater than 10,
            // then set 'syncOnly' to false, otherwise set it to true.
            'delivery_mode' => 'API',
        ];

        // dd($modfy_user_data);

        $commonController = new CommonController();
        $requestBody = json_encode($modfy_user_data);
        // dd($requestBody);
        $requestHttpMethod = 'post';
        $absApiUrl = 'https://' . setting('api.woohoo_url') . '/rest/v3/orders';
        $clientSecret = setting('api.qs_clientSecret');
        $bearerToken = setting('api.bearer_token');
        $signature = CommonHelper::generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);

        $dateAtClient = Carbon\Carbon::now()->toIso8601String();
        $response = Http::acceptJson()
            // ->withToken($bearerToken)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $bearerToken,
                'Accept' => '*/*',
                'dateAtClient' => $dateAtClient,
                'signature' => $signature,
            ])
            // ->timeout(10) // Set a timeout of 10 seconds
            ->send('POST', 'https://sandbox.woohoo.in/rest/v3/orders', [
                'body' => $requestBody,
            ]);
        $status_code = $response->getStatusCode();
        // dd($status_code);
        $status = json_decode($response->getBody(), true);
        if ($status_code == 201 || $status_code == 202) {
            $refno = $response['refno'];
            $orderId = $response['orderId'];
            if ($status['status'] == 'COMPLETE') {
                $qsOrder = new QsOrder();
                $qsOrder->user_id = Auth::user()->id;
                // $qsOrder->product = json_encode($response['payments']);
                $qsOrder->reference_id = $response['refno'];
                $qsOrder->order_id = $response['orderId'];
                $qsOrder->order_created_status = 'COMPLETE';
                $qsOrder->cards = encrypt(json_encode($response['cards']), env('ENCRYPTION_KEY'));
                $qsOrder->order_cancel = json_encode($response['cancel']);
                $qsOrder->order_payment = json_encode($response['payments']);
                $qsOrder->currency = json_encode($response['currency']);
                $qsOrder->additionalTxnFields = json_encode($response['additionalTxnFields']);
                $qsOrder->sku = $request->sku;
                $qsOrder->price = session::get('denomination');
                $qsOrder->qty = session::get('quantity');
                $qsOrder->is_gifted = session::get('gift_send_option') == 'send_as_gift' ? 1 : 0;
                $qsOrder->save();
                $request['order_id'] = $status['orderId'];
                $request['merchant_id'] = config('paymentconfig.merchant_id');
                $data = $request->all();
                $allSessionData = session()->all();
                if (session::get('gift_send_option') == 'send_as_gift') {
                    $gift_card = new GiftCard();
                    $gift_card->sender_id = Auth::user()->id;
                    $gift_card->order_id = $status['orderId'];
                    $gift_card->receiver_name = session::get('receiver_name');
                    $gift_card->receiver_email = session::get('receiver_email');
                    $gift_card->receiver_mobile = session::get('receiver_mobile');
                    $gift_card->receiver_msg = session::get('receiver_msg');
                    $gift_card->card = json_encode($response['cards']);
                    $gift_card->gift_send_option = session::get('gift_send_option');
                    $gift_card->amount = session::get('denomination') * session::get('quantity');
                    $gift_card->delivery_mode = session::get('delivery_mode');
                    $gift_card->save();
                }
                if (session::get('gift_send_option') == 'buy_for_self') {
                    $gift_card = new GiftCard();
                    $gift_card->sender_id = Auth::user()->id;
                    $gift_card->order_id = $status['orderId'];
                    $gift_card->receiver_name = $modfy_user_data['billing']['firstname'];
                    $gift_card->receiver_email = $modfy_user_data['billing']['email'];
                    $gift_card->receiver_mobile = $modfy_user_data['billing']['telephone'];
                    $gift_card->receiver_msg = null;
                    $qsOrder->cards = encrypt(json_encode($response['cards']), env('ENCRYPTION_KEY'));
                    $gift_card->amount = session::get('denomination') * session::get('quantity');
                    $gift_card->gift_send_option = session::get('gift_send_option');
                    $gift_card->delivery_mode = session::get('delivery_mode');
                    $gift_card->save();
                }
                return view('paymentFolder.ccavRequestHandler', compact('data'));
            } elseif ($status['status'] == 'PROCESSING') {
                $qsOrder = new QsOrder();
                $qsOrder->user_id = Auth::user()->id;
                $qsOrder->reference_id = $response['refno'];
                // dd($response['refno']);
                $qsOrder->order_id = $response['orderId'];
                $qsOrder->order_created_status = 'PROCESSING';
                // $qsOrder->cards = encrypt(json_encode($response['cards']), env('ENCRYPTION_KEY'));
                $qsOrder->order_cancel = json_encode($response['cancel']);
                $qsOrder->order_payment = json_encode($response['payments']);
                $qsOrder->currency = json_encode($response['currency']);
                // $qsOrder->additionalTxnFields = json_encode($response['additionalTxnFields']);
                // $qsOrder->sku = $request->sku;
                $qsOrder->price = session::get('denomination');
                $qsOrder->qty = session::get('quantity');
                $qsOrder->is_gifted = session::get('gift_send_option') == 'send_as_gift' ? 1 : 0;
                $qsOrder->save();
                $request['order_id'] = $status['orderId'];
                $request['merchant_id'] = config('paymentconfig.merchant_id');
                $data = $request->all();
                $allSessionData = session()->all();

                if (session::get('gift_send_option') == 'send_as_gift') {
                    $gift_card = new GiftCard();
                    $gift_card->sender_id = Auth::user()->id;
                    $gift_card->order_id = $status['orderId'];
                    $gift_card->receiver_name = session::get('receiver_name');
                    $gift_card->receiver_email = session::get('receiver_email');
                    $gift_card->receiver_mobile = session::get('receiver_mobile');
                    $gift_card->receiver_msg = session::get('receiver_msg');
                    $gift_card->gift_send_option = session::get('gift_send_option');
                    $gift_card->amount = session::get('denomination') * session::get('quantity');
                    $gift_card->delivery_mode = session::get('delivery_mode');
                    $gift_card->save();
                }
                if (session::get('gift_send_option') == 'buy_for_self') {
                    $gift_card = new GiftCard();
                    $gift_card->sender_id = Auth::user()->id;
                    $gift_card->order_id = $status['orderId'];
                    $gift_card->receiver_name = $modfy_user_data['billing']['firstname'];
                    $gift_card->receiver_email = $modfy_user_data['billing']['email'];
                    $gift_card->receiver_mobile = $modfy_user_data['billing']['telephone'];
                    $gift_card->receiver_msg = null;
                    // $gift_card->card = encrypt(json_encode($response['cards']), env('ENCRYPTION_KEY'));
                    $gift_card->amount = session::get('denomination') * session::get('quantity');
                    $gift_card->gift_send_option = session::get('gift_send_option');
                    $gift_card->delivery_mode = session::get('delivery_mode');
                    $gift_card->save();
                }
                // dd($refno);
                $this->commonController->handleRetryAttempt($refno, $data, $orderId);
            } else {
                $msg = 'Something went wrong';
                return redirect('checkout', ['sku', $request->sku])->with('msg', $msg);
            }
        } else {
            // Handle a server error 500 response
            return redirect()->route('order-failed');
        }
        $status = json_decode($response->getBody(), true);

        // dd($status);
        // dd($status['status']);
    }

    // public function applyCoupan(Request $request)
    // {
    //     $grandTotal = $request->grand_total;
    //     $coupanVal = 10;
    //     $aftApplyCoupan = $request->grand_total - $request->grand_total * ($coupanVal / 100);
    //     $data = [
    //         'coupan' => $coupanVal,
    //         'aftApplyCoupan' => $aftApplyCoupan,
    //     ];
    //     return response()->json($data);
    // }

    // public function removeApplyCoupan(Request $request)
    // {
    //     $grandTotal = $request->grand_total;
    //     $data = [
    //         'coupan' => '',
    //         'grandTotal' => $grandTotal,
    //     ];
    //     return response()->json($data);
    // }
}
