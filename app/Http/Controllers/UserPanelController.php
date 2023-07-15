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

class UserPanelController extends Controller
{
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
                $data = [
                    'status' => 400,
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
        // dd(session::get('denomination'), $request->all());
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
            'syncOnly' => $request['quantity'] > 10 ? false : true,
            'delivery_mode' => 'API',
        ];
        // dd($modfy_user_data);
        $requestBody = json_encode($modfy_user_data);
        $requestHttpMethod = 'post';
        $absApiUrl = 'https://' . setting('api.woohoo_url') . '/rest/v3/orders';
        $clientSecret = setting('api.qs_clientSecret');
        $bearerToken = setting('api.bearer_token');
        $signature = CommonController::generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);
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
            ->send('POST', 'https://sandbox.woohoo.in/rest/v3/orders', [
                'body' => $requestBody,
            ]);
        $status = $response->json();
        dd($status);
        if ($status['status'] == 'COMPLETE') {
            $userOrder = new QsOrder();
            $userOrder->user_id = Auth::user()->id;
            $userOrder->product = json_encode($response['payments']);
            $userOrder->reference_id = $response['refno'];
            $userOrder->order_id = $response['orderId'];
            $userOrder->order_status = 'PENDING';
            $userOrder->cards = json_encode($response['cards']);
            $userOrder->order_cancel = json_encode($response['cancel']);
            $userOrder->order_payment = json_encode($response['payments']);
            $userOrder->currency = json_encode($response['currency']);
            $userOrder->additionalTxnFields = json_encode($response['additionalTxnFields']);
            $userOrder->sku = $request->sku;
            $userOrder->price = session::get('denomination');
            $userOrder->qty = session::get('quantity');
            $userOrder->is_gifted = session::get('gift_send_option') == 'send_as_gift' ? 1 : 0;
            // dd($userOrder);
            $userOrder->save();
            $request['order_id'] = $status['orderId'];
            $request['merchant_id'] = config('auth.merchant_id');
            $data = $request->all();
            if (session::get('gift_send_option') == 'send_as_gift') {
                $gift_card = new GiftCard();
                $gift_card->sender_id = Auth::user()->id;
                $gift_card->order_id = $status['orderId'];
                $gift_card->receiver_name = session::get('receiver_name');
                $gift_card->receiver_email = session::get('receiver_email');
                $gift_card->receiver_mobile = session::get('receiver_mobile');
                $gift_card->receiver_msg = session::get('receiver_msg');
                $gift_card->card = json_encode($response['cards']);
                $gift_card->amount = session::get('denomination') * session::get('quantity');
                $gift_card->save();
            }
            return view('paymentFolder.ccavRequestHandler', compact('data'));
        } else {
            $msg = 'Something went wrong';
            return redirect('checkout', ['sku', $request->sku])->with('msg', $msg);
        }
    }

    public function applyCoupan(Request $request)
    {
        $grandTotal = $request->grand_total;
        $coupanVal = 10;
        $aftApplyCoupan = $request->grand_total - $request->grand_total * ($coupanVal / 100);
        $data = [
            'coupan' => $coupanVal,
            'aftApplyCoupan' => $aftApplyCoupan,
        ];
        return response()->json($data);
    }

    public function removeApplyCoupan(Request $request)
    {
        $grandTotal = $request->grand_total;
        $data = [
            'coupan' => '',
            'grandTotal' => $grandTotal,
        ];
        return response()->json($data);
    }
}
