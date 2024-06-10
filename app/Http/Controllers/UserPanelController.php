<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App\Models\User;
use App\Models\QsProduct;
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
use Illuminate\Http\Client\Response;
use App\Jobs\StatusCheckJob;
use Exception;
use Illuminate\Http\Client\ConnectionException;
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

            $allProducts = DB::table('qs_products')->select('qs_products.*', 'qs_categories.name as category_name')->leftjoin('qs_categories', 'qs_products.qs_category_id', '=', 'qs_categories.id') ->orderBy('qs_products.name')->get();

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

    // user registration function

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
                        'msg' => 'Login successful. Welcome back!',
                    ];
                } else {
                    $data = [
                        'status' => 400,
                        'msg' => 'Login failed. Please check your email and password and try again.',
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

    // user login function
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
            Session::put('user_id', Auth::id());
            // session()->put('user_id', Auth::id());
            $qsProd = QsProduct::where('sku', $sku)->first();

            Log::alert("$qsProd");
            $qsProd['prodData'] = $request->all();

            Log::alert("$qsProd");
            $currency = json_decode($qsProd['currency']);
            $qsProd['currency'] = $currency;
            $qsProd['images'] = json_decode($qsProd->images);

            // dd(json_decode($qsProd->images));

            if (\Auth::check()) {
                return view('userpanel.checkout', compact('qsProd'));
            } else {
                return redirect('/');
            }
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    private function prepareBillingData($request)
    {
        $modify_user_data = [
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
            'refno' => 'order id',

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
        return $modify_user_data;
    }

    public function orderProceed(Request $request)
    {
        $modify_user_data = $this->prepareBillingData($request);
        // Log::info($modify_user_data);
        $order_id = $modify_user_data['amazepay_order_id'];
        Log::info('amazepay order id for CCavenue transaction is ' . $order_id);
        $qsOrder = new QsOrder();
        $qsOrder->user_id = Auth::user()->id;
        $qsOrder->reference_id = $modify_user_data['refno'];
        $qsOrder->order_id = $modify_user_data['amazepay_order_id'];
        $qsOrder->price = session::get('denomination');
        $qsOrder->qty = session::get('quantity') ?? 0;
        $qsOrder->sku = $modify_user_data['products'][0]['sku'];
        $qsOrder->is_gifted = session::get('gift_send_option') == 'send_as_gift' ? 1 : 0;
        $qsOrder->save();

        $merchant_id = config('paymentconfig.merchant_id');
        $requestAllData = $request->all();
        $data = $requestAllData + compact('merchant_id', 'order_id');

        return view('paymentFolder.ccavRequestHandler', compact('data', 'modify_user_data'));

        // if($orderPaymentResponse == "Success"){
        //    dd("payment success");
        // }
        // elseif ($orderPaymentResponse  == "Failure") {
        //    dd("payment failure");
        // }
        // else{
        //     dd("bhalta error");
        // }
    }

    // $orderPaymentResponse = public function paymentResponse($paymentResponse)
    // {
    //     return $paymentResponse;

    // }

    // public function orderPaymentResponse($orderPaymentResponse);

    // public function createOrderRequest($modify_user_data)
    // {
    //     $requestBody = json_encode($modify_user_data);
    //     $requestHttpMethod = 'post';
    //     $absApiUrl = 'https://' . setting('api.woohoo_url') . '/rest/v3/orders';
    //     $clientSecret = setting('api.qs_clientSecret');
    //     $bearerToken = setting('api.bearer_token');
    //     $signature = CommonHelper::generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);
    //     $dateAtClient = Carbon\Carbon::now()->toIso8601String();
    //     $refno = $modify_user_data['refno'];

    //     Log::info('**************** Order Creation Api *************************' . "\n");
    //     Log::info('Before hitting API time is ' . now() . "\n");
    //     try {
    //         // Make the HTTP request
    //         $createOrderResponse = Http::acceptJson()
    //             ->timeout(10)
    //             ->withHeaders([
    //                 'Content-Type' => 'application/json',
    //                 'Authorization' => 'Bearer ' . $bearerToken,
    //                 'Accept' => '*/*',
    //                 'dateAtClient' => $dateAtClient,
    //                 'signature' => $signature,
    //             ])
    //             ->send('POST', $absApiUrl, [
    //                 'body' => $requestBody,
    //             ]);

    //         Log::info('API Request:', [
    //             'url' => $absApiUrl,
    //             'method' => $requestHttpMethod,
    //             'headers' => [
    //                 'Content-Type' => 'application/json',
    //                 'Authorization' => 'Bearer ' . $bearerToken,
    //                 'Accept' => '*/*',
    //                 'dateAtClient' => $dateAtClient,
    //                 'signature' => $signature,
    //             ],
    //             'data' => $requestBody,
    //         ]);

    //         Log::info("\n");
    //         Log::info('********************** check order api hit response whether received or not ***************************' . "\n");

    //         Log::info('Get Response from woohoo server ' . $createOrderResponse . "\n");
    //         if ($createOrderResponse->successful()) {
    //             Log::info('Order creation was successful within 10 seconds');
    //             $responseData = $createOrderResponse->json();
    //             return $responseData;
    //         } elseif ($createOrderResponse->clientError()) {
    //             Log::error('Client error: ' . $createOrderResponse);
    //             return response()->json(['error' => 'Client error'], 500);
    //             // $this->getStatusByReferenceNumber($refno);
    //         } elseif ($createOrderResponse->serverError()) {
    //             Log::error('Server error: ' . $createOrderResponse);
    //             return response()->json(['error' => 'Server error'], 500);
    //         } elseif ($createOrderResponse->failed()) {
    //             Log::error('Order failed check status' . $createOrderResponse);
    //             return response()->json(['error' => 'Order Creation Failed'], 500);
    //             // $this->getStatusByReferenceNumber($refno);
    //         } else {
    //             Log::error('Unexpected status code: ' . $createOrderResponse);
    //             return response()->json(['error' => 'Unexpected Error '], 500);
    //         }
    //     } catch (ConnectionException $e) {
    //         // Handle the cURL error here
    //         Log::error('cURL Error happened request broken in between ' . $e->getMessage());
    //         Log::alert('Curl errors happened Logging before 30 second delay ' . now());
    //         sleep(30);
    //         Log::alert('Curl Errors happened Hitting  order status API after a 30 - second delay that is total 40 second delay after order creation API Hit ' . now());
    //         $statusFunctionResponse = $this->getStatusByReferenceNumber($refno);
    //         return $statusFunctionResponse;
    //         // $this->getStatusByReferenceNumber($refno);
    //     }
    // }

    // private function createQsOrder($createOrderResponse, $modify_user_data, $refno)
    // {
    //     $qsOrder = new QsOrder();

    //     $qsOrder->order_created_status = $createOrderResponse['status'] ?? null;
    //     $qsOrder->cards = encrypt(json_encode($createOrderResponse['cards']), env('ENCRYPTION_KEY'));

    //     $cancelData = isset($createOrderResponse['cancel']) ? $createOrderResponse['cancel'] : null;
    //     $qsOrder->order_cancel = json_encode($cancelData);

    //     // Handle 'payments'
    //     $paymentsData = isset($createOrderResponse['payments']) ? $createOrderResponse['payments'] : null;
    //     $qsOrder->order_payment = json_encode($paymentsData);

    //     // Handle 'currency'
    //     $currencyData = isset($createOrderResponse['currency']) ? $createOrderResponse['currency'] : null;
    //     $qsOrder->currency = json_encode($currencyData);

    //     // Handle 'additionalTxnFields'
    //     $additionalTxnFieldsData = isset($createOrderResponse['additionalTxnFields']) ? $createOrderResponse['additionalTxnFields'] : null;
    //     $qsOrder->additionalTxnFields = json_encode($additionalTxnFieldsData);

    //     $qsOrder->is_gifted = session::get('gift_send_option') == 'send_as_gift' ? 1 : 0;

    //     if (session::get('gift_send_option') == 'send_as_gift') {
    //         $gift_card = new GiftCard();
    //         $gift_card->sender_id = Auth::user()->id;
    //         $gift_card->order_id = isset($createOrderResponse['orderId']) ? $createOrderResponse['orderId'] : null;
    //         $gift_card->receiver_name = session::get('receiver_name');
    //         $gift_card->receiver_email = session::get('receiver_email');
    //         $gift_card->receiver_mobile = session::get('receiver_mobile');
    //         $gift_card->receiver_msg = session::get('receiver_msg');

    //         $gift_card->card = encrypt(json_encode($createOrderResponse['cards']), env('ENCRYPTION_KEY'));
    //         $gift_card->gift_send_option = session::get('gift_send_option');
    //         $gift_card->amount = session::get('denomination') * session::get('quantity');
    //         $gift_card->delivery_mode = session::get('delivery_mode');
    //         $gift_card->save();
    //     }

    //     if (session::get('gift_send_option') == 'buy_for_self') {
    //         $gift_card = new GiftCard();
    //         $gift_card->sender_id = Auth::user()->id;
    //         $gift_card->order_id = $createOrderResponse['orderId'];
    //         $gift_card->receiver_name = $modify_user_data['billing']['firstname'];
    //         $gift_card->receiver_email = $modify_user_data['billing']['email'];
    //         $gift_card->receiver_mobile = $modify_user_data['billing']['telephone'];
    //         $gift_card->receiver_msg = null;
    //         $gift_card->card = encrypt(json_encode($createOrderResponse['cards']), env('ENCRYPTION_KEY'));
    //         $gift_card->amount = session::get('denomination') * session::get('quantity');
    //         $gift_card->gift_send_option = session::get('gift_send_option');
    //         $gift_card->delivery_mode = session::get('delivery_mode');
    //         $gift_card->save();
    //     }
    //     $qsOrder->save();
    // }

    // private function getStatusByReferenceNumber($refno)
    // {
    //     Log::info('You are in get Status Function');
    //     Log::info('attempt 1 has happened, go for step 2');
    //     $attempt = 1;
    //     $max_retries = 2; // Change this to the desired number of retries
    //     $retry_interval = 40; // Retry interval in seconds
    //     $requestHttpMethod = 'GET';
    //     $absApiUrl = 'https://' . setting('api.woohoo_url') . '/rest/v3/order/' . $refno . '/status';
    //     $clientSecret = setting('api.qs_clientSecret');
    //     $bearerToken = setting('api.bearer_token');
    //     $requestBody = '';
    //     $dateAtClient = Carbon\Carbon::now()->toIso8601String();
    //     $signature = CommonHelper::generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);

    //     while ($attempt <= $max_retries) {
    //         $cardStatusApiResponse = Http::acceptJson()
    //             ->withToken($bearerToken)
    //             ->withHeaders([
    //                 'signature' => $signature,
    //                 'dateAtClient' => $dateAtClient,
    //             ])
    //             ->get($absApiUrl);

    //         if ($cardStatusApiResponse->status() == 200) {
    //             $cardStatusApiResponseData = json_decode($cardStatusApiResponse->getBody(), true);

    //             if ($cardStatusApiResponseData['status'] === 'COMPLETE') {
    //                 $orderId = $cardStatusApiResponseData['orderId'];
    //                 $activatedCardReponseFromFunction = $this->callCardActivation($cardStatusApiResponseData);
    //                 return $activatedCardReponseFromFunction;
    //             } elseif ($cardStatusApiResponseData['status'] === 'PROCESSING') {
    //                 // If the order is still processing, wait for the retry interval
    //                 sleep($retry_interval);
    //             } else {
    //                 Log::info('Anything other than processing or complete status');
    //                 return false;
    //             }
    //         } else {
    //             Log::info('Order failed, response 200 not received');
    //             return false;
    //         }

    //         $attempt++;
    //     }

    //     Log::info('Max retries reached without reaching a complete status.');
    //     return false;

    //     // You can handle the case where the maximum number of retries is reached without a complete status here.
    // }

    // public function callCardActivation($cardStatusApiResponseData)
    // {
    //     Log::info('You are in Card Activation Function');
    //     $orderId = $cardStatusApiResponseData['orderId'];
    //     $clientSecret = setting('api.qs_clientSecret'); // Your client secret
    //     $bearerToken = setting('api.bearer_token'); // Your bearer token
    //     $apiUrl = 'https://' . setting('api.woohoo_url');
    //     $absApiUrl = "$apiUrl/rest/v3/order/{$orderId}/cards";
    //     $requestBody = '';
    //     $requestHttpMethod = 'GET';
    //     $dateAtClient = Carbon\Carbon::now()->toIso8601String();
    //     $signature = CommonHelper::generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);
    //     $activatedCardApiResponse = Http::acceptJson()
    //         ->withToken($bearerToken)
    //         ->withHeaders([
    //             'signature' => $signature,
    //             'dateAtClient' => $dateAtClient,
    //         ])
    //         ->get($absApiUrl);

    //     if ($activatedCardApiResponse->status() == 200) {
    //         Log::alert('card activation successfull');
    //         $activatedCardApiResponseData = $activatedCardApiResponse->json();

    //         // Create a new array by merging the two response data arrays
    //         $combinedData = array_merge($cardStatusApiResponseData, $activatedCardApiResponseData);
    //         return $combinedData;
    //     } else {
    //         Log::info('Order failed, response 200 not received');
    //         return false;
    //     }
    // }

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

    // private function createGiftCard($status, $data, $request, $receiverData = null)
    // {
    //     $gift_card = new GiftCard();
    //     $gift_card->sender_id = Auth::user()->id;
    //     $gift_card->order_id = $status['orderId'];

    //     if ($receiverData) {
    //         $gift_card->receiver_name = $receiverData['firstname'];
    //         $gift_card->receiver_email = $receiverData['email'];
    //         $gift_card->receiver_mobile = $receiverData['telephone'];
    //         $gift_card->receiver_msg = null;
    //     } else {
    //         $gift_card->receiver_name = session::get('receiver_name');
    //         $gift_card->receiver_email = session::get('receiver_email');
    //         $gift_card->receiver_mobile = session::get('receiver_mobile');
    //         $gift_card->receiver_msg = session::get('receiver_msg');
    //     }

    //     $gift_card->card = json_encode($status['cards']);
    //     $gift_card->gift_send_option = session::get('gift_send_option');
    //     $gift_card->amount = session::get('denomination') * session::get('quantity');
    //     $gift_card->delivery_mode = session::get('delivery_mode');
    //     $gift_card->save();
    // }

    // dd('bjwsbw');
    // if ($status_code == 201 || $status_code == 202) {
    //     $refno = $response['refno'];
    //     $orderId = $response['orderId'];
    //     if ($status['status'] == 'COMPLETE') {
    //         $qsOrder = new QsOrder();
    //         $qsOrder->user_id = Auth::user()->id;
    //         // $qsOrder->product = json_encode($response['payments']);
    //         $qsOrder->reference_id = $response['refno'];
    //         $qsOrder->order_id = $response['orderId'];
    //         $qsOrder->order_created_status = 'COMPLETE';
    //         $qsOrder->cards = encrypt(json_encode($response['cards']), env('ENCRYPTION_KEY'));
    //         $qsOrder->order_cancel = json_encode($response['cancel']);
    //         $qsOrder->order_payment = json_encode($response['payments']);
    //         $qsOrder->currency = json_encode($response['currency']);
    //         $qsOrder->additionalTxnFields = json_encode($response['additionalTxnFields']);
    //         $qsOrder->sku = $request->sku;
    //         $qsOrder->price = session::get('denomination');
    //         $qsOrder->qty = session::get('quantity');
    //         $qsOrder->is_gifted = session::get('gift_send_option') == 'send_as_gift' ? 1 : 0;
    //         $qsOrder->save();
    //         $request['order_id'] = $status['orderId'];
    //         $request['merchant_id'] = config('paymentconfig.merchant_id');
    //         $data = $request->all();
    //         // dd($data);
    //         $allSessionData = session()->all();
    //         if (session::get('gift_send_option') == 'send_as_gift') {
    //             $gift_card = new GiftCard();
    //             $gift_card->sender_id = Auth::user()->id;
    //             $gift_card->order_id = $status['orderId'];
    //             $gift_card->receiver_name = session::get('receiver_name');
    //             $gift_card->receiver_email = session::get('receiver_email');
    //             $gift_card->receiver_mobile = session::get('receiver_mobile');
    //             $gift_card->receiver_msg = session::get('receiver_msg');
    //             $gift_card->card = json_encode($response['cards']);
    //             $gift_card->gift_send_option = session::get('gift_send_option');
    //             $gift_card->amount = session::get('denomination') * session::get('quantity');
    //             $gift_card->delivery_mode = session::get('delivery_mode');
    //             $gift_card->save();
    //         }
    //         if (session::get('gift_send_option') == 'buy_for_self') {
    //             $gift_card = new GiftCard();
    //             $gift_card->sender_id = Auth::user()->id;
    //             $gift_card->order_id = $status['orderId'];
    //             $gift_card->receiver_name = $modify_user_data['billing']['firstname'];
    //             $gift_card->receiver_email = $modify_user_data['billing']['email'];
    //             $gift_card->receiver_mobile = $modify_user_data['billing']['telephone'];
    //             $gift_card->receiver_msg = null;
    //             $qsOrder->cards = encrypt(json_encode($response['cards']), env('ENCRYPTION_KEY'));
    //             $gift_card->amount = session::get('denomination') * session::get('quantity');
    //             $gift_card->gift_send_option = session::get('gift_send_option');
    //             $gift_card->delivery_mode = session::get('delivery_mode');
    //             $gift_card->save();
    //         }
    //         return view('paymentFolder.ccavRequestHandler', compact('data'));
    //     } elseif ($status['status'] == 'PROCESSING') {
    //         $qsOrder = new QsOrder();
    //         $qsOrder->user_id = Auth::user()->id;
    //         $qsOrder->reference_id = $response['refno'];
    //         // dd($response['refno']);
    //         $qsOrder->order_id = $response['orderId'];
    //         $qsOrder->order_created_status = 'PROCESSING';
    //         // $qsOrder->cards = encrypt(json_encode($response['cards']), env('ENCRYPTION_KEY'));
    //         $qsOrder->order_cancel = json_encode($response['cancel']);
    //         $qsOrder->order_payment = json_encode($response['payments']);
    //         $qsOrder->currency = json_encode($response['currency']);
    //         // $qsOrder->additionalTxnFields = json_encode($response['additionalTxnFields']);
    //         // $qsOrder->sku = $request->sku;
    //         $qsOrder->price = session::get('denomination');
    //         $qsOrder->qty = session::get('quantity');
    //         $qsOrder->is_gifted = session::get('gift_send_option') == 'send_as_gift' ? 1 : 0;
    //         $qsOrder->save();
    //         $request['order_id'] = $status['orderId'];
    //         $request['merchant_id'] = config('paymentconfig.merchant_id');
    //         $data = $request->all();
    //         $allSessionData = session()->all();

    //         if (session::get('gift_send_option') == 'send_as_gift') {
    //             $gift_card = new GiftCard();
    //             $gift_card->sender_id = Auth::user()->id;
    //             $gift_card->order_id = $status['orderId'];
    //             $gift_card->receiver_name = session::get('receiver_name');
    //             $gift_card->receiver_email = session::get('receiver_email');
    //             $gift_card->receiver_mobile = session::get('receiver_mobile');
    //             $gift_card->receiver_msg = session::get('receiver_msg');
    //             $gift_card->gift_send_option = session::get('gift_send_option');
    //             $gift_card->amount = session::get('denomination') * session::get('quantity');
    //             $gift_card->delivery_mode = session::get('delivery_mode');
    //             $gift_card->save();
    //         }
    //         if (session::get('gift_send_option') == 'buy_for_self') {
    //             $gift_card = new GiftCard();
    //             $gift_card->sender_id = Auth::user()->id;
    //             $gift_card->order_id = $status['orderId'];
    //             $gift_card->receiver_name = $modify_user_data['billing']['firstname'];
    //             $gift_card->receiver_email = $modify_user_data['billing']['email'];
    //             $gift_card->receiver_mobile = $modify_user_data['billing']['telephone'];
    //             $gift_card->receiver_msg = null;
    //             // $gift_card->card = encrypt(json_encode($response['cards']), env('ENCRYPTION_KEY'));
    //             $gift_card->amount = session::get('denomination') * session::get('quantity');
    //             $gift_card->gift_send_option = session::get('gift_send_option');
    //             $gift_card->delivery_mode = session::get('delivery_mode');
    //             $gift_card->save();
    //         }

    //         // dd($refno);
    //         $this->commonController->handleRetryAttempt($refno, $data, $orderId);
    //         return view('paymentFolder.ccavRequestHandler', compact('data'));
    //     } else {
    //         $msg = 'Something went wrong';
    //         return redirect('checkout', ['sku', $request->sku])->with('msg', $msg);
    //     }
    // } else {
    //     // Handle a server error 500 response
    //     return redirect()->route('order-failed');
    // }
    // $status = json_decode($response->getBody(), true);
}
